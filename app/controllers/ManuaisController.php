<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\PublicRateLimiter;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\ManualModel;
use App\Models\ManualPortalTokenModel;

class ManuaisController extends BaseController
{
    private ManualModel $manuais;
    private ManualPortalTokenModel $portalTokens;
    private ClienteModel $clientes;
    private DepartamentoModel $departamentos;

    public function __construct()
    {
        $this->manuais = new ManualModel();
        $this->portalTokens = new ManualPortalTokenModel();
        $this->clientes = new ClienteModel();
        $this->departamentos = new DepartamentoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $empresaId = (int)($_GET['empresa_id'] ?? 0);
        $departamentoId = (int)($_GET['departamento_id'] ?? 0);
        $nome = trim((string)($_GET['nome'] ?? ''));
        $empresaId = (int)($this->resolveScopedClienteId($empresaId > 0 ? $empresaId : null) ?? 0);

        $clientes = $this->clientes->all();
        $departamentos = $empresaId > 0 ? $this->departamentos->allByCliente($empresaId) : $this->departamentos->all();
        $items = $this->manuais->list([
            'empresa_id' => $empresaId > 0 ? $empresaId : null,
            'departamento_id' => $departamentoId > 0 ? $departamentoId : null,
            'nome' => $nome,
        ]);

        $this->render('manuais/index', [
            'items' => $items,
            'clientes' => $clientes,
            'departamentos' => $departamentos,
            'selectedEmpresa' => $empresaId,
            'selectedDepartamento' => $departamentoId,
            'searchNome' => $nome,
            'canManageManuais' => ManualModel::canManage(),
            'portalLink' => $empresaId > 0 && ManualModel::canManage() && !empty($_GET['portal_token'])
                ? $this->buildPortalLink((string)$_GET['portal_token'])
                : '',
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        if (!ManualModel::canManage()) {
            http_response_code(403);
            echo 'Sem permissão para enviar manuais.';
            return;
        }
        $empresa = (int)($_GET['empresa_id'] ?? $_GET['cliente'] ?? 0);
        $empresa = (int)($this->resolveScopedClienteId($empresa > 0 ? $empresa : null) ?? 0);
        $clientes = $this->clientes->all();
        $allDepartamentos = [];
        foreach ($clientes as $cliente) {
            $clienteId = (int)($cliente['id'] ?? 0);
            foreach ($this->departamentos->allByCliente($clienteId) as $dep) {
                $allDepartamentos[] = $dep;
            }
        }
        $this->render('manuais/create', [
            'clientes' => $clientes,
            'departamentos' => $allDepartamentos,
            'selectedEmpresa' => $empresa,
            'canManageManuais' => true,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        if (!ManualModel::canManage()) {
            http_response_code(403);
            echo 'Sem permissão para enviar manuais.';
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $empresaId = (int)($this->resolveScopedClienteId((int)($_POST['empresa_id'] ?? 0) ?: null) ?? 0);
        $departamentoId = (int)($_POST['departamento_id'] ?? 0);
        $nome = trim((string)($_POST['nome'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? ''));

        if ($empresaId <= 0 || $departamentoId <= 0 || $nome === '') {
            http_response_code(400);
            echo 'Campos obrigatórios faltando.';
            return;
        }
        if (mb_strlen($descricao) > 500) {
            http_response_code(400);
            echo 'Descrição deve ter no máximo 500 caracteres.';
            return;
        }

        $departamento = $this->departamentos->find($departamentoId);
        if (!$departamento || (int)($departamento['cliente_id'] ?? 0) !== $empresaId) {
            http_response_code(400);
            echo 'Departamento inválido para a empresa selecionada.';
            return;
        }
        if (empty($_FILES['arquivo']['name']) || !is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
            http_response_code(400);
            echo 'Arquivo obrigatório.';
            return;
        }
        if ((int)($_FILES['arquivo']['size'] ?? 0) > 10 * 1024 * 1024) {
            http_response_code(400);
            echo 'Arquivo excede o limite de 10MB.';
            return;
        }

        $ext = ManualModel::extensionFromUpload((string)($_FILES['arquivo']['name'] ?? ''));
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? (string)finfo_file($finfo, $_FILES['arquivo']['tmp_name']) : (string)($_FILES['arquivo']['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowedMime = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        ];
        if ($ext === null || !in_array($mime, $allowedMime[$ext] ?? [], true)) {
            http_response_code(400);
            echo 'Tipo de arquivo inválido. Permitidos: pdf, doc, docx.';
            return;
        }

        $dir = ManualModel::storageDirFor($empresaId, $departamentoId);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            http_response_code(500);
            echo 'Não foi possível criar diretório de armazenamento.';
            return;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $storedName;
        if (!@move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
            http_response_code(500);
            echo 'Falha ao salvar arquivo.';
            return;
        }

        $relativePath = 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/' . $storedName;
        $manualId = $this->manuais->create([
            'empresa_id' => $empresaId,
            'departamento_id' => $departamentoId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'arquivo' => $relativePath,
            'tipo_arquivo' => $ext,
            'tamanho' => (int)($_FILES['arquivo']['size'] ?? 0),
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        if ($manualId <= 0) {
            @unlink($dest);
            http_response_code(500);
            echo 'Falha ao registrar manual no banco.';
            return;
        }

        AuditLogger::log('manual_upload', 'manual', $manualId, [
            'empresa_id' => $empresaId,
            'departamento_id' => $departamentoId,
            'nome' => $nome,
            'tipo_arquivo' => $ext,
            'tamanho' => (int)($_FILES['arquivo']['size'] ?? 0),
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        $_SESSION['flash_success'] = 'Manual enviado com sucesso.';
        header('Location: index.php?route=manuais/index&empresa_id=' . $empresaId);
    }

    public function download(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $manual = isset($_SESSION['user']) ? $this->manuais->find($id) : $this->manuais->findAny($id);
        if (!$manual) {
            http_response_code(404);
            echo 'Manual não encontrado.';
            return;
        }
        $portal = $this->validatePortalSession();
        if (isset($_SESSION['user'])) {
            $this->requireLogin();
            if (!$this->canAccessCliente((int)$manual['empresa_id'])) {
                http_response_code(403);
                echo 'Sem permissão.';
                return;
            }
        } elseif ($portal !== null) {
            if (!in_array((int)$manual['empresa_id'], $portal['empresa_ids'], true)) {
                http_response_code(403);
                echo 'Sem permissão.';
                return;
            }
            $limiter = new PublicRateLimiter();
            $key = 'manual-portal-download:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            if (!$limiter->allow($key, 60, 300)) {
                http_response_code(429);
                echo 'Muitas requisições. Tente novamente em instantes.';
                return;
            }
        } else {
            http_response_code(403);
            echo 'Sem permissão.';
            return;
        }

        $path = dirname(__DIR__, 2) . '/' . ltrim((string)$manual['arquivo'], '/');
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Arquivo indisponível.';
            return;
        }

        AuditLogger::log('manual_download', 'manual', (int)$manual['id'], [
            'empresa_id' => (int)$manual['empresa_id'],
            'departamento_id' => (int)$manual['departamento_id'],
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);

        $downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$manual['nome']) ?: ('manual_' . $id);
        $downloadName .= '.' . ($manual['tipo_arquivo'] ?? pathinfo($path, PATHINFO_EXTENSION));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . (string)filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function portal(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        if ($token !== '') {
            $session = $this->startPortalSession($token);
            if ($session === null) {
                http_response_code(403);
                echo 'Link do portal inválido ou expirado.';
                return;
            }
        }
        $session = $this->validatePortalSession();
        if ($session === null) {
            http_response_code(403);
            echo 'Sessão do portal inválida ou expirada.';
            return;
        }

        $limiter = new PublicRateLimiter();
        $key = 'manual-portal:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!$limiter->allow($key, 120, 300)) {
            http_response_code(429);
            echo 'Muitas requisições. Tente novamente em instantes.';
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $departamentoId = (int)($_GET['departamento_id'] ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        $dataDe = trim((string)($_GET['data_de'] ?? ''));
        $dataAte = trim((string)($_GET['data_ate'] ?? ''));
        $filters = [
            'departamento_id' => $departamentoId > 0 ? $departamentoId : null,
            'q' => $q,
            'data_de' => $dataDe,
            'data_ate' => $dataAte,
        ];
        $total = $this->manuais->portalCount($session['empresa_ids'], $filters);
        $items = $this->manuais->portalList($session['empresa_ids'], $filters, $page, $perPage);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $departamentos = [];
        foreach ($session['empresa_ids'] as $empresaId) {
            foreach ($this->departamentos->allByCliente((int)$empresaId) as $dep) {
                $departamentos[(int)$dep['id']] = $dep;
            }
        }
        $empresa = $this->clientes->findAny((int)$session['empresa_id']);
        AuditLogger::log('manual_portal_access', 'manual_portal', null, [
            'empresa_id' => (int)$session['empresa_id'],
            'token' => substr((string)$session['token'], 0, 12),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $params = [
            'pageTitle' => 'Portal de Manuais',
            'empresa' => $empresa,
            'items' => $items,
            'departamentos' => array_values($departamentos),
            'selectedDepartamento' => $departamentoId,
            'q' => $q,
            'dataDe' => $dataDe,
            'dataAte' => $dataAte,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ];
        extract($params, EXTR_SKIP);
        require __DIR__ . '/../views/manuais/portal.php';
    }

    public function generatePortalLink(): void
    {
        $this->requireLogin();
        if (!ManualModel::canManage()) {
            http_response_code(403);
            echo 'Sem permissão.';
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $empresaId = (int)($this->resolveScopedClienteId((int)($_POST['empresa_id'] ?? 0) ?: null) ?? 0);
        if ($empresaId <= 0) {
            http_response_code(400);
            echo 'Empresa inválida.';
            return;
        }
        $expiraEm = date('Y-m-d H:i:s', strtotime('+30 days'));
        $token = $this->portalTokens->issue($empresaId, $expiraEm);
        AuditLogger::log('manual_portal_token_issue', 'manual_portal', null, [
            'empresa_id' => $empresaId,
            'expira_em' => $expiraEm,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        $_SESSION['flash_success'] = 'Link do portal gerado com sucesso.';
        header('Location: index.php?route=manuais/index&empresa_id=' . $empresaId . '&portal_token=' . $token);
    }

    private function startPortalSession(string $token): ?array
    {
        $record = $this->portalTokens->findValid($token);
        if (!$record) {
            return null;
        }
        $empresaIds = $this->clientes->manualPortalScopeIds((int)$record['empresa_id']);
        if (empty($empresaIds)) {
            return null;
        }
        $_SESSION['manual_portal'] = [
            'token' => $record['token'],
            'empresa_id' => (int)$record['empresa_id'],
            'empresa_ids' => $empresaIds,
            'expires_at' => time() + 1800,
        ];
        return $_SESSION['manual_portal'];
    }

    private function validatePortalSession(): ?array
    {
        $session = $_SESSION['manual_portal'] ?? null;
        if (!is_array($session) || empty($session['token']) || empty($session['empresa_id'])) {
            return null;
        }
        if ((int)($session['expires_at'] ?? 0) < time()) {
            unset($_SESSION['manual_portal']);
            return null;
        }
        $record = $this->portalTokens->findValid((string)$session['token']);
        if (!$record || (int)$record['empresa_id'] !== (int)$session['empresa_id']) {
            unset($_SESSION['manual_portal']);
            return null;
        }
        $session['empresa_ids'] = $this->clientes->manualPortalScopeIds((int)$record['empresa_id']);
        $session['expires_at'] = time() + 1800;
        $_SESSION['manual_portal'] = $session;
        return $session;
    }

    private function buildPortalLink(string $token): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $basePath . '/manuais/portal/' . rawurlencode($token);
    }
}
