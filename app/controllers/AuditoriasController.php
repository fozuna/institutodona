<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\AuditoriaValidator;
use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Security;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\SetorModel;

class AuditoriasController extends BaseController
{
    private AuditoriaModel $auditorias;
    private ClienteModel $clientes;
    private SetorModel $setores;
    private ColaboradorModel $colaboradores;

    public function __construct()
    {
        $this->auditorias = new AuditoriaModel();
        $this->clientes = new ClienteModel();
        $this->setores = new SetorModel();
        $this->colaboradores = new ColaboradorModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $canManage = $this->canManageAuditorias();
        $filters = $this->collectFilters();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 10;
        $result = $this->auditorias->list($filters, $page, $per);
        $total = (int)$result['total'];
        $totalPages = max(1, (int)ceil($total / $per));
        $clientes = $this->clientesCached();
        $setores = !empty($filters['cliente']) ? $this->setoresCached((int)$filters['cliente']) : [];
        if (!empty($filters['setor'])) {
            $responsaveis = $this->responsaveisBySetorCached((int)$filters['setor'], (int)($filters['cliente'] ?? 0));
        } elseif (!empty($filters['cliente'])) {
            $responsaveis = $this->colaboradores->allByCliente((int)$filters['cliente']);
        } else {
            $responsaveis = [];
        }
        $this->render('auditorias/index', [
            'items' => $result['items'],
            'filters' => $filters,
            'clientes' => $clientes,
            'setores' => $setores,
            'responsaveis' => $responsaveis,
            'page' => $page,
            'per' => $per,
            'total' => $total,
            'totalPages' => $totalPages,
            'canManage' => $canManage,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $setor = isset($_GET['setor']) ? (int)$_GET['setor'] : 0;
        $this->render('auditorias/create', [
            'clientes' => $this->clientesCached(),
            'setores' => $cliente > 0 ? $this->setoresCached($cliente) : [],
            'responsaveis' => $setor > 0 ? $this->responsaveisBySetorCached($setor, $cliente) : [],
            'selectedCliente' => $cliente,
            'selectedSetor' => $setor,
            'values' => [],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        if (!empty($errors)) {
            $this->render('auditorias/create', [
                'clientes' => $this->clientesCached(),
                'setores' => !empty($payload['cliente_id']) ? $this->setoresCached((int)$payload['cliente_id']) : [],
                'responsaveis' => !empty($payload['setor_id']) ? $this->responsaveisBySetorCached((int)$payload['setor_id'], (int)$payload['cliente_id']) : [],
                'selectedCliente' => (int)$payload['cliente_id'],
                'selectedSetor' => (int)$payload['setor_id'],
                'values' => $payload,
                'errors' => $errors,
            ]);
            return;
        }
        $id = $this->auditorias->create($payload, (int)($_SESSION['user']['id'] ?? 0));
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Não foi possível cadastrar a auditoria no escopo selecionado.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_create', 'auditoria', $id, ['cliente_id' => (int)$payload['cliente_id']]);
        $_SESSION['flash_success'] = 'Auditoria cadastrada com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function edit(): void
    {
        $this->requireLogin();
        $item = $this->auditorias->find((int)($_GET['id'] ?? 0));
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        if ($item['status'] !== 'Agendada' || !empty($item['realizada_at'])) {
            $_SESSION['flash_error'] = 'Auditorias realizadas não podem ter os campos de cadastro editados.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $this->requireManagePermission();
        $this->render('auditorias/edit', [
            'item' => $item,
            'clientes' => $this->clientesCached(),
            'setores' => $this->setoresCached((int)$item['cliente_id']),
            'responsaveis' => $this->responsaveisBySetorCached((int)$item['setor_id'], (int)$item['cliente_id']),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        if (!empty($errors)) {
            $item = $this->auditorias->find($id);
            $this->render('auditorias/edit', [
                'item' => $item ?: ['id' => $id] + $payload,
                'clientes' => $this->clientesCached(),
                'setores' => !empty($payload['cliente_id']) ? $this->setoresCached((int)$payload['cliente_id']) : [],
                'responsaveis' => !empty($payload['setor_id']) ? $this->responsaveisBySetorCached((int)$payload['setor_id'], (int)$payload['cliente_id']) : [],
                'errors' => $errors,
            ]);
            return;
        }
        $ok = $this->auditorias->updateAgendada($id, $payload, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível atualizar. Auditoria pode já ter sido realizada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_update', 'auditoria', $id, ['cliente_id' => (int)$payload['cliente_id']]);
        $_SESSION['flash_success'] = 'Auditoria atualizada com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function auditar(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $payload = [
            'avaliacao' => trim((string)($_POST['avaliacao'] ?? '')),
            'obs' => trim((string)($_POST['obs'] ?? '')),
        ];
        $errors = AuditoriaValidator::validateExecucao($payload);
        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(' ', array_values($errors));
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $ok = $this->auditorias->auditar($id, $payload, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível auditar. Verifique o status da auditoria.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_execute', 'auditoria', $id, []);
        $_SESSION['flash_success'] = 'Auditoria realizada e registrada.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['flash_error'] = 'Auditoria inválida.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $vinculos = $this->auditorias->countRelatoriosVinculados($id);
        if ($vinculos > 0) {
            $_SESSION['flash_error'] = 'Não é possível excluir auditoria vinculada a relatórios.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $ok = $this->auditorias->softDelete($id, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível excluir auditoria.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_delete', 'auditoria', $id, []);
        $_SESSION['flash_success'] = 'Auditoria excluída com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function apiSetores(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        if ($cliente <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->setoresCached($cliente)], JSON_UNESCAPED_UNICODE);
    }

    public function apiResponsaveis(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $setor = (int)($_GET['setor_id'] ?? 0);
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        if ($setor <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->responsaveisBySetorCached($setor, $cliente)], JSON_UNESCAPED_UNICODE);
    }

    public function apiList(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $filters = $this->collectFilters();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(1, min(50, (int)($_GET['per'] ?? 10)));
        $result = $this->auditorias->list($filters, $page, $per);
        echo json_encode([
            'success' => true,
            'items' => $result['items'],
            'total' => (int)$result['total'],
            'page' => $page,
            'per' => $per,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function apiStore(): void
    {
        $this->requireLogin();
        $this->requireManagePermission(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = $this->auditorias->create($payload, (int)($_SESSION['user']['id'] ?? 0));
        if ($id <= 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Escopo inválido para criação.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    }

    public function apiUpdate(): void
    {
        $this->requireLogin();
        $this->requireManagePermission(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->auditorias->updateAgendada($id, $payload, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Auditoria não pode ser atualizada.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiDelete(): void
    {
        $this->requireLogin();
        $this->requireManagePermission(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($this->auditorias->countRelatoriosVinculados($id) > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Auditoria vinculada a relatórios.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->auditorias->softDelete($id, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auditoria não encontrada.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiAuditar(): void
    {
        $this->requireLogin();
        $this->requireManagePermission(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $payload = [
            'avaliacao' => trim((string)($_POST['avaliacao'] ?? '')),
            'obs' => trim((string)($_POST['obs'] ?? '')),
        ];
        $errors = AuditoriaValidator::validateExecucao($payload);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->auditorias->auditar($id, $payload, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Auditoria já realizada ou indisponível.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    private function collectFilters(): array
    {
        $inicio = AuditoriaValidator::normalizeDate((string)($_GET['inicio'] ?? ''));
        $fim = AuditoriaValidator::normalizeDate((string)($_GET['fim'] ?? ''));
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente'] ?? 0)) ?? 0);
        return [
            'cliente' => $cliente > 0 ? $cliente : null,
            'setor' => (int)($_GET['setor'] ?? 0) ?: null,
            'responsavel' => (int)($_GET['responsavel'] ?? 0) ?: null,
            'status' => ($_GET['status'] ?? '') ?: null,
            'inicio' => $inicio,
            'fim' => $fim,
            'sort' => ($_GET['sort'] ?? 'data_desc'),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
    }

    private function payloadFromRequest(array $src): array
    {
        return [
            'cliente_id' => (int)($src['cliente_id'] ?? 0),
            'setor_id' => (int)($src['setor_id'] ?? 0),
            'responsavel_id' => (int)($src['responsavel_id'] ?? 0),
            'data_auditoria' => AuditoriaValidator::normalizeDate((string)($src['data_auditoria'] ?? '')),
            'pergunta' => trim((string)($src['pergunta'] ?? '')),
            'objetivo' => trim((string)($src['objetivo'] ?? '')),
            'referencia_esperada' => trim((string)($src['referencia_esperada'] ?? '')),
        ];
    }

    private function isPost(): bool
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }

    private function canManageAuditorias(): bool
    {
        return Auth::isConsultor() || Auth::isInstituto();
    }

    private function requireManagePermission(bool $json = false): void
    {
        if (!$this->canManageAuditorias()) {
            $message = 'Acesso negado: apenas consultores e usuários Instituto podem criar, editar ou excluir auditorias.';
            if ($json) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
                exit;
            }
            http_response_code(403);
            echo $message;
            exit;
        }
    }

    private function clientesCached(): array
    {
        if (!isset($_SESSION['cache_auditoria_clientes']) || !is_array($_SESSION['cache_auditoria_clientes'])) {
            $_SESSION['cache_auditoria_clientes'] = $this->clientes->all();
        }
        return $_SESSION['cache_auditoria_clientes'];
    }

    private function setoresCached(int $clienteId): array
    {
        if (!isset($_SESSION['cache_auditoria_setores']) || !is_array($_SESSION['cache_auditoria_setores'])) {
            $_SESSION['cache_auditoria_setores'] = [];
        }
        if (!array_key_exists($clienteId, $_SESSION['cache_auditoria_setores'])) {
            $_SESSION['cache_auditoria_setores'][$clienteId] = $this->setores->allByCliente($clienteId);
        }
        return $_SESSION['cache_auditoria_setores'][$clienteId];
    }

    private function responsaveisBySetorCached(int $setorId, int $clienteId = 0): array
    {
        if (!isset($_SESSION['cache_auditoria_responsaveis']) || !is_array($_SESSION['cache_auditoria_responsaveis'])) {
            $_SESSION['cache_auditoria_responsaveis'] = [];
        }
        $key = $setorId . ':' . max(0, $clienteId);
        if (!array_key_exists($key, $_SESSION['cache_auditoria_responsaveis'])) {
            $_SESSION['cache_auditoria_responsaveis'][$key] = $this->colaboradores->allBySetor($setorId, $clienteId > 0 ? $clienteId : null);
        }
        return $_SESSION['cache_auditoria_responsaveis'][$key];
    }
}
