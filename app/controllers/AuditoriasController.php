<?php
namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\AuditoriaValidator;
use App\Core\Auth;
use App\Core\BaseController;
use App\Core\JwtService;
use App\Core\Security;
use App\Core\SimplePdfReport;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\SetorModel;
use App\Models\UsuarioModel;

class AuditoriasController extends BaseController
{
    private AuditoriaModel $auditorias;
    private ClienteModel $clientes;
    private SetorModel $setores;
    private ColaboradorModel $colaboradores;
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->auditorias = new AuditoriaModel();
        $this->clientes = new ClienteModel();
        $this->setores = new SetorModel();
        $this->colaboradores = new ColaboradorModel();
        $this->usuarios = new UsuarioModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $canManage = $this->canManageAuditorias();
        $filters = $this->collectFilters();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(10, min(50, (int)($_GET['per'] ?? 15)));
        $result = $this->auditorias->list($filters, $page, $per);
        $total = (int)$result['total'];
        $totalPages = max(1, (int)ceil($total / $per));
        $clientes = $this->clientesCached();
        $setores = !empty($filters['cliente']) ? $this->setoresCached((int)$filters['cliente']) : [];
        $this->render('auditorias/index', [
            'items' => $result['items'],
            'filters' => $filters,
            'clientes' => $clientes,
            'setores' => $setores,
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
        $clientes = $this->clientesCached();
        AuditLogger::log('auditoria_create_dropdown_init', 'auditoria', null, [
            'total_clientes' => count($clientes),
            'scope_clientes' => Auth::allowedClientIds(),
            'is_instituto' => Auth::isInstituto(),
        ]);
        $this->render('auditorias/create', [
            'clientes' => $clientes,
            'setores' => $cliente > 0 ? $this->setoresCached($cliente) : [],
            'selectedCliente' => $cliente,
            'selectedSetor' => 0,
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
        $this->appendResponsavelValidationErrors($payload, $errors);
        if (!empty($errors)) {
            $this->render('auditorias/create', [
                'clientes' => $this->clientesCached(),
                'setores' => !empty($payload['cliente_id']) ? $this->setoresCached((int)$payload['cliente_id']) : [],
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
        $item = $this->auditorias->findWithQuestoes((int)($_GET['id'] ?? 0));
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
        $clientes = $this->clientesCached();
        AuditLogger::log('auditoria_edit_dropdown_init', 'auditoria', (int)$item['id'], [
            'total_clientes' => count($clientes),
            'scope_clientes' => Auth::allowedClientIds(),
        ]);
        $this->render('auditorias/edit', [
            'item' => $item,
            'clientes' => $clientes,
            'setores' => $this->setoresCached((int)$item['cliente_id']),
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
        $this->appendResponsavelValidationErrors($payload, $errors);
        if (!empty($errors)) {
            $item = $this->auditorias->findWithQuestoes($id);
            $this->render('auditorias/edit', [
                'item' => $item ?: ['id' => $id] + $payload,
                'clientes' => $this->clientesCached(),
                'setores' => !empty($payload['cliente_id']) ? $this->setoresCached((int)$payload['cliente_id']) : [],
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

    public function show(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $respostas = $this->auditorias->respostasByAuditoria($id);
        $this->render('auditorias/show', [
            'item' => $item,
            'respostas' => $respostas,
            'canManage' => $this->canManageAuditorias(),
        ]);
    }

    public function auditar(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        if (($item['status'] ?? '') === 'Realizada') {
            $_SESSION['flash_error'] = 'Esta auditoria já foi finalizada.';
            $this->redirect('index.php?route=auditorias/show&id=' . $id);
            return;
        }
        $respostas = $this->auditorias->respostasByAuditoria($id);
        $this->render('auditorias/auditar', [
            'item' => $item,
            'respostas' => $respostas,
            'errors' => [],
        ]);
    }

    public function finalizar(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Auditoria não encontrada.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        $avaliacoes = AuditoriaValidator::normalizeAvaliacoes($_POST['avaliacoes_json'] ?? '[]');
        $errors = AuditoriaValidator::validateExecucao($avaliacoes, count($item['questoes'] ?? []));
        if (!empty($errors)) {
            $respostas = $this->auditorias->respostasByAuditoria($id);
            foreach ($avaliacoes as $a) {
                $respostas[(int)$a['questao_id']] = [
                    'conformidade' => $a['conformidade'],
                    'observacoes' => $a['observacoes'],
                    'auto_saved_at' => null,
                    'finalized_at' => null,
                ];
            }
            $this->render('auditorias/auditar', [
                'item' => $item,
                'respostas' => $respostas,
                'errors' => $errors,
            ]);
            return;
        }
        $ok = $this->auditorias->finalizarAuditoria($id, $avaliacoes, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            $_SESSION['flash_error'] = 'Não foi possível finalizar a auditoria.';
            $this->redirect('index.php?route=auditorias/index');
            return;
        }
        AuditLogger::log('auditoria_finalize', 'auditoria', $id, []);
        $_SESSION['flash_success'] = 'Auditoria finalizada com sucesso.';
        $this->redirect('index.php?route=auditorias/index');
    }

    public function relatorioPdf(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            http_response_code(404);
            echo 'Auditoria não encontrada.';
            return;
        }
        $respostas = $this->auditorias->respostasByAuditoria($id);
        $lines = [
            'Relatório de Auditoria',
            'Nome: ' . ($item['nome_auditoria'] ?? ''),
            'Empresa: ' . ($item['cliente_nome'] ?? ''),
            'Setor: ' . ($item['setor_nome'] ?? ''),
            'Data agendada: ' . date('d/m/Y', strtotime((string)$item['data_auditoria'])),
            'Status: ' . ($item['status'] ?? ''),
            '',
        ];
        $i = 1;
        foreach ($item['questoes'] as $questao) {
            $resposta = $respostas[(int)$questao['id']] ?? null;
            $lines[] = $i . '. ' . (string)$questao['pergunta'];
            $lines[] = 'Responsável: ' . (string)$questao['responsavel_nome'];
            $lines[] = 'Referência: ' . (string)$questao['referencia_esperada'];
            $lines[] = 'Conformidade: ' . (string)($resposta['conformidade'] ?? 'pendente');
            $obs = trim((string)($resposta['observacoes'] ?? ''));
            if ($obs !== '') {
                $lines[] = 'Observações: ' . $obs;
            }
            $lines[] = '';
            $i++;
        }
        $pdf = SimplePdfReport::fromLines($lines);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="auditoria-' . $id . '.pdf"');
        echo $pdf;
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            return;
        }
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
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        if ($cliente <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->setoresCached($cliente)], JSON_UNESCAPED_UNICODE);
    }

    public function apiClientes(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $items = $this->clientesCached(true);
        AuditLogger::log('auditoria_api_clientes', 'auditoria', null, [
            'total_clientes' => count($items),
            'scope_clientes' => Auth::allowedClientIds(),
            'is_instituto' => Auth::isInstituto(),
        ]);
        echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function apiResponsaveis(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $setor = (int)($_GET['setor_id'] ?? 0);
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        if ($setor <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->responsaveisBySetorCached($setor, $cliente)], JSON_UNESCAPED_UNICODE);
    }

    public function apiColaboradores(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $setor = (int)($_GET['setor_id'] ?? 0);
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        if ($setor <= 0 || $cliente <= 0 || mb_strlen($q) < 2) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        $items = $this->colaboradores->searchActiveBySetor($setor, $cliente, $q, 15);
        AuditLogger::log('auditoria_api_colaboradores', 'auditoria', null, [
            'setor_id' => $setor,
            'cliente_id' => $cliente,
            'q_len' => mb_strlen($q),
            'total' => count($items),
        ]);
        echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function apiList(): void
    {
        $this->requireApiAuth(false);
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

    public function apiShow(): void
    {
        $this->requireApiAuth(false);
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auditoria não encontrada'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $item['avaliacoes'] = $this->auditorias->respostasByAuditoria($id);
        echo json_encode(['success' => true, 'item' => $item], JSON_UNESCAPED_UNICODE);
    }

    public function apiStore(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        $this->appendResponsavelValidationErrors($payload, $errors);
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
        AuditLogger::log('auditoria_api_create', 'auditoria', $id, ['cliente_id' => (int)$payload['cliente_id']]);
        echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    }

    public function apiUpdate(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $payload = $this->payloadFromRequest($_POST);
        $errors = AuditoriaValidator::validateCadastro($payload);
        $this->appendResponsavelValidationErrors($payload, $errors);
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
        AuditLogger::log('auditoria_api_update', 'auditoria', $id, []);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiDelete(): void
    {
        $this->requireApiAuth(true);
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
        AuditLogger::log('auditoria_api_delete', 'auditoria', $id, []);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiAutosave(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $avaliacoes = AuditoriaValidator::normalizeAvaliacoes($_POST['avaliacoes_json'] ?? '[]');
        $ok = $this->auditorias->autosaveAvaliacoes($id, $avaliacoes, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Não foi possível salvar rascunho.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('auditoria_api_autosave', 'auditoria', $id, ['total' => count($avaliacoes)]);
        echo json_encode(['success' => true, 'saved_at' => date('c')], JSON_UNESCAPED_UNICODE);
    }

    public function apiFinalize(): void
    {
        $this->requireApiAuth(true);
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'CSRF inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->auditorias->findWithQuestoes($id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auditoria não encontrada'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $avaliacoes = AuditoriaValidator::normalizeAvaliacoes($_POST['avaliacoes_json'] ?? '[]');
        $errors = AuditoriaValidator::validateExecucao($avaliacoes, count($item['questoes'] ?? []));
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->auditorias->finalizarAuditoria($id, $avaliacoes, (int)($_SESSION['user']['id'] ?? 0));
        if (!$ok) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Não foi possível finalizar.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('auditoria_api_finalize', 'auditoria', $id, []);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    public function apiAuditar(): void
    {
        $this->apiFinalize();
    }

    private function collectFilters(): array
    {
        $inicio = AuditoriaValidator::normalizeDate((string)($_GET['inicio'] ?? ''));
        $fim = AuditoriaValidator::normalizeDate((string)($_GET['fim'] ?? ''));
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente'] ?? 0)) ?? 0);
        return [
            'cliente' => $cliente > 0 ? $cliente : null,
            'setor' => (int)($_GET['setor'] ?? 0) ?: null,
            'status' => ($_GET['status'] ?? '') ?: null,
            'inicio' => $inicio,
            'fim' => $fim,
            'sort_col' => ($_GET['sort_col'] ?? 'data'),
            'sort_dir' => ($_GET['sort_dir'] ?? 'desc'),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
    }

    private function payloadFromRequest(array $src): array
    {
        return [
            'cliente_id' => (int)($src['cliente_id'] ?? 0),
            'setor_id' => (int)($src['setor_id'] ?? 0),
            'nome_auditoria' => trim((string)($src['nome_auditoria'] ?? '')),
            'data_auditoria' => AuditoriaValidator::normalizeDate((string)($src['data_auditoria'] ?? '')),
            'questoes' => AuditoriaValidator::normalizeQuestoes($src['questoes_json'] ?? ($src['questoes'] ?? [])),
        ];
    }

    private function appendResponsavelValidationErrors(array $payload, array &$errors): void
    {
        $clienteId = (int)($payload['cliente_id'] ?? 0);
        $setorId = (int)($payload['setor_id'] ?? 0);
        $questoes = is_array($payload['questoes'] ?? null) ? $payload['questoes'] : [];
        if ($clienteId <= 0 || $setorId <= 0 || empty($questoes)) {
            return;
        }
        $validationErrors = AuditoriaValidator::validateResponsaveisCadastrados($questoes, function (string $nome) use ($setorId, $clienteId): bool {
            return $this->colaboradores->existsActiveNomeBySetor($setorId, $clienteId, $nome);
        });
        if (!empty($validationErrors)) {
            AuditLogger::log('auditoria_responsavel_invalid', 'auditoria', null, [
                'cliente_id' => $clienteId,
                'setor_id' => $setorId,
                'total_invalidos' => count($validationErrors),
            ]);
        }
        foreach ($validationErrors as $key => $message) {
            $errors[$key] = $message;
        }
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

    private function requireApiAuth(bool $manage): void
    {
        if ($this->authenticateByBearer()) {
            if ($manage && !$this->canManageAuditorias()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Perfil sem permissão para escrita.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            return;
        }
        $this->requireLogin();
        if ($manage) {
            $this->requireManagePermission(true);
        }
    }

    private function authenticateByBearer(): bool
    {
        $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '');
        if ($header === '' || stripos($header, 'Bearer ') !== 0) {
            return false;
        }
        $token = trim(substr($header, 7));
        if ($token === '') {
            return false;
        }
        $payload = JwtService::decode($token);
        if (!$payload) {
            return false;
        }
        $userId = (int)($payload['sub'] ?? 0);
        if ($userId <= 0) {
            return false;
        }
        $user = $this->usuarios->find($userId);
        if (!$user) {
            return false;
        }
        Auth::login($user);
        return true;
    }

    private function clientesCached(bool $force = false): array
    {
        $scopeIds = Auth::allowedClientIds();
        $scopeKey = Auth::isInstituto() ? 'instituto' : ('scoped:' . implode(',', $scopeIds));
        $cache = $_SESSION['cache_auditoria_clientes'] ?? null;
        $cacheScope = $_SESSION['cache_auditoria_clientes_scope'] ?? null;
        $cacheTs = (int)($_SESSION['cache_auditoria_clientes_ts'] ?? 0);
        $expired = ($cacheTs <= 0 || (time() - $cacheTs) > 120);
        $needsRefresh = $force || !is_array($cache) || $cacheScope !== $scopeKey || $expired;
        if ($needsRefresh) {
            $cache = $this->clientes->all();
            $_SESSION['cache_auditoria_clientes'] = $cache;
            $_SESSION['cache_auditoria_clientes_scope'] = $scopeKey;
            $_SESSION['cache_auditoria_clientes_ts'] = time();
            AuditLogger::log('auditoria_clientes_cache_refresh', 'auditoria', null, [
                'total_clientes' => count($cache),
                'scope' => $scopeKey,
                'expired' => $expired,
            ]);
        }
        if (empty($cache)) {
            $cache = $this->clientes->all();
            $_SESSION['cache_auditoria_clientes'] = $cache;
            $_SESSION['cache_auditoria_clientes_scope'] = $scopeKey;
            $_SESSION['cache_auditoria_clientes_ts'] = time();
            AuditLogger::log('auditoria_clientes_empty_refetch', 'auditoria', null, [
                'total_clientes' => count($cache),
                'scope' => $scopeKey,
            ]);
        }
        return $cache;
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
