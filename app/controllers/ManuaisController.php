<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Auth;
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
        $sortCol = ManualModel::normalizeSortColumn((string)($_GET['sort_col'] ?? 'data'));
        $sortDir = ManualModel::normalizeSortDirection((string)($_GET['sort_dir'] ?? 'desc'));
        $empresaId = (int)($this->resolveScopedClienteId($empresaId > 0 ? $empresaId : null) ?? 0);

        $clientes = $this->clientes->all();
        $departamentos = $empresaId > 0 ? $this->departamentos->allByCliente($empresaId) : $this->departamentos->all();
        $filters = [
            'empresa_id' => $empresaId > 0 ? $empresaId : null,
            'departamento_id' => $departamentoId > 0 ? $departamentoId : null,
            'nome' => $nome,
            'sort_col' => $sortCol,
            'sort_dir' => $sortDir,
        ];
        $items = $this->manuais->list($filters);
        $isFilial = $empresaId > 0 ? $this->clientes->isFilial($empresaId) : false;
        if ($empresaId > 0 && $isFilial) {
            $matrizId = $this->clientes->catalogRootIdFor($empresaId);
            if ($matrizId > 0 && $matrizId !== $empresaId) {
                $departamentos = array_merge($this->departamentos->allByCliente($empresaId), $this->departamentos->allByCliente($matrizId));
                $items = $this->manuais->listBiblioteca($empresaId, $matrizId, $filters);
            }
        }

        $this->render('manuais/index', [
            'items' => $items,
            'clientes' => $clientes,
            'departamentos' => $departamentos,
            'selectedEmpresa' => $empresaId,
            'selectedDepartamento' => $departamentoId,
            'searchNome' => $nome,
            'selectedSortCol' => $sortCol,
            'selectedSortDir' => $sortDir,
            'canManageManuais' => ManualModel::canManage(),
            'canDeleteManuais' => ManualModel::canDelete(),
            'portalLink' => $empresaId > 0 && ManualModel::canManage() && !empty($_GET['portal_token'])
                ? $this->buildPortalLink((string)$_GET['portal_token'], [
                    'departamento_id' => $departamentoId > 0 ? $departamentoId : null,
                    'q' => $nome !== '' ? $nome : null,
                ])
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

    public function edit(): void
    {
        $this->requireLogin();
        if (!ManualModel::canManage()) {
            http_response_code(403);
            echo 'Sem permissão para editar manuais.';
            return;
        }
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->manuais->find($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Manual não encontrado.';
            $this->redirect('index.php?route=manuais/index');
            return;
        }
        $empresaId = (int)($item['empresa_id'] ?? 0);
        $clientes = $this->clientes->all();
        $allDepartamentos = [];
        foreach ($clientes as $cliente) {
            $clienteId = (int)($cliente['id'] ?? 0);
            foreach ($this->departamentos->allByCliente($clienteId) as $dep) {
                $allDepartamentos[] = $dep;
            }
        }
        $filiais = [];
        $linkedFiliais = [];
        $empresa = $this->clientes->find($empresaId);
        if ($empresa && (int)($empresa['is_matriz'] ?? 1) === 1) {
            $filiais = $this->clientes->filiaisByMatriz($empresaId);
            $linkedFiliais = $this->manuais->linkedFilialIds($id);
        }
        $this->render('manuais/edit', [
            'item' => $item,
            'clientes' => $clientes,
            'departamentos' => $allDepartamentos,
            'filiais' => $filiais,
            'linkedFiliais' => $linkedFiliais,
            'canManageManuais' => true,
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        if (!ManualModel::canManage()) {
            http_response_code(403);
            echo 'Sem permissão para editar manuais.';
            return;
        }
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST' || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $current = $this->manuais->find($id);
        if (!$current) {
            http_response_code(404);
            echo 'Manual não encontrado.';
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

        $arquivoRel = (string)($current['arquivo'] ?? '');
        $tipoArquivo = (string)($current['tipo_arquivo'] ?? '');
        $tamanho = (int)($current['tamanho'] ?? 0);
        $oldAbsPath = dirname(__DIR__, 2) . '/' . ltrim($arquivoRel, '/');

        $file = $_FILES['arquivo'] ?? null;
        $newAbsPath = null;
        if (is_array($file) && !empty($file['name'])) {
            $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err !== UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
                http_response_code(400);
                echo 'Falha no upload do arquivo.';
                return;
            }
            $sizeBytes = (int)($file['size'] ?? 0);
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
            $mime = $finfo ? (string)finfo_file($finfo, (string)$file['tmp_name']) : (string)($file['type'] ?? '');
            if ($finfo) { finfo_close($finfo); }
            $validated = ManualModel::validateUpload((string)$file['name'], $sizeBytes, $mime, 50 * 1024 * 1024);
            if (empty($validated['ok'])) {
                http_response_code(400);
                echo (string)($validated['message'] ?? 'Arquivo inválido.');
                return;
            }
            $ext = (string)$validated['ext'];
            $category = (string)$validated['category'];
            $dir = ManualModel::storageDirFor($empresaId, $departamentoId, $category);
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                http_response_code(500);
                echo 'Não foi possível criar diretório de armazenamento.';
                return;
            }
            $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
            $newAbsPath = $dir . '/' . $storedName;
            if (!@move_uploaded_file((string)$file['tmp_name'], $newAbsPath)) {
                http_response_code(500);
                echo 'Falha ao salvar arquivo.';
                return;
            }
            $arquivoRel = 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/' . rawurlencode($category) . '/' . $storedName;
            $tipoArquivo = $ext;
            $tamanho = $sizeBytes;
        }

        $ok = $this->manuais->update($id, [
            'empresa_id' => $empresaId,
            'departamento_id' => $departamentoId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'arquivo' => $arquivoRel,
            'tipo_arquivo' => $tipoArquivo,
            'tamanho' => $tamanho,
        ]);
        if (!$ok) {
            if ($newAbsPath && is_file($newAbsPath)) {
                @unlink($newAbsPath);
            }
            http_response_code(500);
            echo 'Falha ao atualizar manual.';
            return;
        }
        if ($newAbsPath && is_file($oldAbsPath)) {
            @unlink($oldAbsPath);
        }

        $empresa = $this->clientes->find($empresaId);
        $filiaisIds = $_POST['filiais_ids'] ?? [];
        if ($empresa && (int)($empresa['is_matriz'] ?? 1) === 1 && is_array($filiaisIds)) {
            $allowed = array_values(array_map('intval', array_column($this->clientes->filiaisByMatriz($empresaId), 'id')));
            $selected = array_values(array_unique(array_filter(array_map('intval', $filiaisIds))));
            $selected = array_values(array_intersect($selected, $allowed));
            $this->manuais->replaceFilialLinks($id, $selected);
        } else {
            $this->manuais->replaceFilialLinks($id, []);
        }

        AuditLogger::log('manual_update', 'manual', $id, [
            'empresa_id' => $empresaId,
            'departamento_id' => $departamentoId,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        $_SESSION['flash_success'] = 'Manual atualizado com sucesso.';
        $this->redirect('index.php?route=manuais/index&empresa_id=' . $empresaId);
    }

    public function delete(): void
    {
        $this->requireLogin();
        if (!ManualModel::canDelete()) {
            http_response_code(403);
            echo 'Sem permissão para excluir manuais.';
            return;
        }
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST' || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $manual = $this->manuais->find($id);
        if (!$manual) {
            http_response_code(404);
            echo 'Manual não encontrado.';
            return;
        }
        $absPath = dirname(__DIR__, 2) . '/' . ltrim((string)($manual['arquivo'] ?? ''), '/');
        $empresaId = (int)($manual['empresa_id'] ?? 0);
        $ok = $this->manuais->delete($id);
        if (!$ok) {
            http_response_code(500);
            echo 'Falha ao excluir manual.';
            return;
        }
        if (is_file($absPath)) {
            @unlink($absPath);
        }
        AuditLogger::log('manual_delete', 'manual', $id, [
            'empresa_id' => $empresaId,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        $_SESSION['flash_success'] = 'Manual excluído com sucesso.';
        $this->redirect('index.php?route=manuais/index&empresa_id=' . $empresaId);
    }

    public function store(): void
    {
        $this->requireLogin();
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if (!ManualModel::canManage()) {
            http_response_code(403);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Sem permissão para enviar arquivos.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Sem permissão para enviar arquivos.';
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'CSRF inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'CSRF inválido';
            return;
        }

        $empresaId = (int)($this->resolveScopedClienteId((int)($_POST['empresa_id'] ?? 0) ?: null) ?? 0);
        $departamentoId = (int)($_POST['departamento_id'] ?? 0);
        $nome = trim((string)($_POST['nome'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? ''));

        if ($empresaId <= 0 || $departamentoId <= 0 || $nome === '') {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Campos obrigatórios faltando.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Campos obrigatórios faltando.';
            return;
        }
        if (mb_strlen($descricao) > 500) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Descrição deve ter no máximo 500 caracteres.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Descrição deve ter no máximo 500 caracteres.';
            return;
        }

        $departamento = $this->departamentos->find($departamentoId);
        if (!$departamento || (int)($departamento['cliente_id'] ?? 0) !== $empresaId) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Departamento inválido para a empresa selecionada.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Departamento inválido para a empresa selecionada.';
            return;
        }
        $file = $_FILES['arquivo'] ?? null;
        if (!is_array($file) || empty($file['name'])) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo obrigatório.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo obrigatório.';
            return;
        }
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            http_response_code(400);
            $msg = 'Falha no upload do arquivo.';
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'Arquivo excede o limite permitido pelo servidor.';
            } elseif ($err === UPLOAD_ERR_PARTIAL) {
                $msg = 'Upload incompleto. Tente novamente.';
            } elseif ($err === UPLOAD_ERR_NO_TMP_DIR) {
                $msg = 'Servidor sem diretório temporário para upload.';
            } elseif ($err === UPLOAD_ERR_CANT_WRITE) {
                $msg = 'Servidor não conseguiu gravar o arquivo.';
            } elseif ($err === UPLOAD_ERR_EXTENSION) {
                $msg = 'Upload bloqueado por extensão no servidor.';
            } elseif ($err === UPLOAD_ERR_NO_FILE) {
                $msg = 'Arquivo obrigatório.';
            }
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo $msg;
            return;
        }
        if (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo inválido.';
            return;
        }
        $sizeBytes = (int)($file['size'] ?? 0);
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? (string)finfo_file($finfo, (string)$file['tmp_name']) : (string)($file['type'] ?? '');
        if ($finfo) { finfo_close($finfo); }
        $validated = ManualModel::validateUpload((string)$file['name'], $sizeBytes, $mime, 50 * 1024 * 1024);
        if (empty($validated['ok'])) {
            http_response_code(400);
            $message = (string)($validated['message'] ?? 'Arquivo inválido.');
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $message, 'error' => $validated['error'] ?? null], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo $message;
            return;
        }
        $ext = (string)$validated['ext'];
        $category = (string)$validated['category'];

        $dir = ManualModel::storageDirFor($empresaId, $departamentoId, $category);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Não foi possível criar diretório de armazenamento.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Não foi possível criar diretório de armazenamento.';
            return;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $storedName;
        if (!@move_uploaded_file((string)$file['tmp_name'], $dest)) {
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Falha ao salvar arquivo.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Falha ao salvar arquivo.';
            return;
        }

        $relativePath = 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/' . rawurlencode($category) . '/' . $storedName;
        $manualId = $this->manuais->create([
            'empresa_id' => $empresaId,
            'departamento_id' => $departamentoId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'arquivo' => $relativePath,
            'tipo_arquivo' => $ext,
            'tamanho' => $sizeBytes,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        if ($manualId <= 0) {
            @unlink($dest);
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Falha ao registrar item no banco.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Falha ao registrar item no banco.';
            return;
        }

        $empresa = $this->clientes->find($empresaId);
        $filiaisIds = $_POST['filiais_ids'] ?? [];
        if ($empresa && (int)($empresa['is_matriz'] ?? 1) === 1 && is_array($filiaisIds)) {
            $allowed = array_values(array_map('intval', array_column($this->clientes->filiaisByMatriz($empresaId), 'id')));
            $selected = array_values(array_unique(array_filter(array_map('intval', $filiaisIds))));
            $selected = array_values(array_intersect($selected, $allowed));
            $this->manuais->replaceFilialLinks($manualId, $selected);
        }

        AuditLogger::log('manual_upload', 'manual', $manualId, [
            'empresa_id' => $empresaId,
            'departamento_id' => $departamentoId,
            'nome' => $nome,
            'tipo_arquivo' => $ext,
            'tamanho' => $sizeBytes,
            'mime' => $mime,
            'category' => $category,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        $redirect = 'index.php?route=manuais/index&empresa_id=' . $empresaId;
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'redirect' => $redirect], JSON_UNESCAPED_UNICODE);
            return;
        }
        $_SESSION['flash_success'] = 'Arquivo enviado com sucesso.';
        header('Location: ' . $redirect);
    }

    public function apiFiliais(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        if (!ManualModel::canManage()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'items' => [], 'message' => 'Sem permissão.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $matrizId = (int)($this->resolveScopedClienteId((int)($_GET['matriz_id'] ?? 0) ?: null) ?? 0);
        if ($matrizId <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        $matriz = $this->clientes->find($matrizId);
        if (!$matriz || (int)($matriz['is_matriz'] ?? 1) !== 1) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->clientes->filiaisByMatriz($matrizId)], JSON_UNESCAPED_UNICODE);
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
                $allowed = Auth::allowedClientIds();
                $linked = false;
                foreach ($allowed as $cid) {
                    if ($this->manuais->isLinkedToFilial((int)$manual['id'], (int)$cid)) {
                        $linked = true;
                        break;
                    }
                }
                if (!$linked) {
                    http_response_code(403);
                    echo 'Sem permissão.';
                    return;
                }
            }
        } elseif ($portal !== null) {
            if (!in_array((int)$manual['empresa_id'], $portal['empresa_ids'], true)) {
                http_response_code(403);
                echo 'Sem permissão.';
                return;
            }
            $filters = $portal['filters'] ?? null;
            if (is_array($filters) && !$this->manuais->portalAllowsManual((int)$manual['id'], $portal['empresa_ids'], $filters)) {
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
        $requestedDepartamentoId = (int)($_GET['departamento_id'] ?? 0);
        $requestedQ = trim((string)($_GET['q'] ?? ''));
        $requestedDataDe = trim((string)($_GET['data_de'] ?? ''));
        $requestedDataAte = trim((string)($_GET['data_ate'] ?? ''));
        $linkError = '';
        $locked = !empty($session['locked']);
        $filters = [];
        if ($locked && isset($session['filters']) && is_array($session['filters'])) {
            $lock = $this->normalizePortalFilters($session['filters']);
            $req = $this->normalizePortalFilters([
                'departamento_id' => $requestedDepartamentoId > 0 ? $requestedDepartamentoId : null,
                'q' => $requestedQ,
                'data_de' => $requestedDataDe,
                'data_ate' => $requestedDataAte,
            ]);
            if ($lock !== $req) {
                $linkError = 'Link inválido: os filtros do endereço não correspondem ao link gerado.';
                $filters = $lock;
            } else {
                $filters = $lock;
            }
        } else {
            $filters = $this->normalizePortalFilters([
                'departamento_id' => $requestedDepartamentoId > 0 ? $requestedDepartamentoId : null,
                'q' => $requestedQ,
                'data_de' => $requestedDataDe,
                'data_ate' => $requestedDataAte,
            ]);
        }
        $departamentoId = (int)($filters['departamento_id'] ?? 0);
        $q = (string)($filters['q'] ?? '');
        $dataDe = (string)($filters['data_de'] ?? '');
        $dataAte = (string)($filters['data_ate'] ?? '');
        $total = 0;
        $items = [];
        $totalPages = 1;
        if ($linkError === '') {
            $total = $this->manuais->portalCount($session['empresa_ids'], $filters);
            $items = $this->manuais->portalList($session['empresa_ids'], $filters, $page, $perPage);
            $totalPages = max(1, (int)ceil($total / $perPage));
        }

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
            'pageTitle' => 'Portal da Biblioteca',
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
            'lockedFilters' => $locked,
            'linkError' => $linkError,
            'portalToken' => (string)($session['token'] ?? ''),
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
        $departamentoId = (int)($_POST['departamento_id'] ?? 0);
        $q = trim((string)($_POST['q'] ?? $_POST['nome'] ?? ''));
        $filters = $this->normalizePortalFilters([
            'departamento_id' => $departamentoId > 0 ? $departamentoId : null,
            'q' => $q,
        ]);
        $scopeIds = [$empresaId];
        $expiraEm = date('Y-m-d H:i:s', strtotime('+30 days'));
        $token = $this->portalTokens->issue($empresaId, $expiraEm, $scopeIds, $filters);
        AuditLogger::log('manual_portal_token_issue', 'manual_portal', null, [
            'empresa_id' => $empresaId,
            'expira_em' => $expiraEm,
            'usuario_id' => (int)($_SESSION['user']['id'] ?? 0),
        ]);
        $_SESSION['flash_success'] = 'Link do portal gerado com sucesso.';
        $redirectParams = [
            'route' => 'manuais/index',
            'empresa_id' => $empresaId,
            'portal_token' => $token,
        ];
        if ($departamentoId > 0) {
            $redirectParams['departamento_id'] = $departamentoId;
        }
        if ($q !== '') {
            $redirectParams['nome'] = $q;
        }
        header('Location: index.php?' . http_build_query($redirectParams));
    }

    private function startPortalSession(string $token): ?array
    {
        $record = $this->portalTokens->findValid($token);
        if (!$record) {
            return null;
        }
        $empresaIds = [];
        $locked = false;
        if (!empty($record['scope_ids_json'])) {
            $decoded = json_decode((string)$record['scope_ids_json'], true);
            if (is_array($decoded)) {
                $empresaIds = array_values(array_unique(array_filter(array_map('intval', $decoded))));
                $locked = true;
            }
        }
        if (empty($empresaIds)) {
            $empresaIds = $this->clientes->manualPortalScopeIds((int)$record['empresa_id']);
        }
        if (empty($empresaIds)) {
            return null;
        }
        $filters = [];
        if (!empty($record['filters_json'])) {
            $decoded = json_decode((string)$record['filters_json'], true);
            if (is_array($decoded)) {
                $filters = $this->normalizePortalFilters($decoded);
                $locked = true;
            }
        }
        $_SESSION['manual_portal'] = [
            'token' => $record['token'],
            'empresa_id' => (int)$record['empresa_id'],
            'empresa_ids' => $empresaIds,
            'filters' => $filters,
            'locked' => $locked,
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
        $empresaIds = [];
        $locked = false;
        if (!empty($record['scope_ids_json'])) {
            $decoded = json_decode((string)$record['scope_ids_json'], true);
            if (is_array($decoded)) {
                $empresaIds = array_values(array_unique(array_filter(array_map('intval', $decoded))));
                $locked = true;
            }
        }
        if (empty($empresaIds)) {
            $empresaIds = $this->clientes->manualPortalScopeIds((int)$record['empresa_id']);
        }
        $filters = [];
        if (!empty($record['filters_json'])) {
            $decoded = json_decode((string)$record['filters_json'], true);
            if (is_array($decoded)) {
                $filters = $this->normalizePortalFilters($decoded);
                $locked = true;
            }
        }
        $session['empresa_ids'] = $empresaIds;
        $session['filters'] = $filters;
        $session['locked'] = $locked;
        $session['expires_at'] = time() + 1800;
        $_SESSION['manual_portal'] = $session;
        return $session;
    }

    private function buildPortalLink(string $token, array $query = []): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url = $scheme . '://' . $host . $basePath . '/biblioteca/portal/' . rawurlencode($token);
        $query = array_filter($query, static fn($v): bool => $v !== null && $v !== '');
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    private function normalizePortalFilters(array $filters): array
    {
        $departamentoId = isset($filters['departamento_id']) ? (int)$filters['departamento_id'] : 0;
        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        $dataDe = isset($filters['data_de']) ? trim((string)$filters['data_de']) : '';
        $dataAte = isset($filters['data_ate']) ? trim((string)$filters['data_ate']) : '';
        $out = [
            'departamento_id' => $departamentoId > 0 ? $departamentoId : null,
            'q' => $q,
            'data_de' => $dataDe,
            'data_ate' => $dataAte,
        ];
        if ($out['data_de'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $out['data_de'])) {
            $out['data_de'] = '';
        }
        if ($out['data_ate'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $out['data_ate'])) {
            $out['data_ate'] = '';
        }
        if ($out['q'] !== '' && mb_strlen($out['q']) > 255) {
            $out['q'] = mb_substr($out['q'], 0, 255);
        }
        return $out;
    }
}
