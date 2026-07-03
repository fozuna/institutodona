<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\DateHelper;
use App\Core\Security;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;
use App\Services\TreinamentoDocumentService;

class TreinamentosController extends BaseController
{
    private TreinamentoModel $model;
    private TreinamentoAgendaModel $agendaModel;
    private TreinamentoDocumentService $documents;
    private ClienteModel $clientesModel;
    private DepartamentoModel $departamentosModel;
    private SetorModel $setoresModel;
    private FuncaoModel $funcoesModel;

    public function __construct()
    {
        $this->model = new TreinamentoModel();
        $this->agendaModel = new TreinamentoAgendaModel();
        $this->documents = new TreinamentoDocumentService();
        $this->clientesModel = new ClienteModel();
        $this->departamentosModel = new DepartamentoModel();
        $this->setoresModel = new SetorModel();
        $this->funcoesModel = new FuncaoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $filters = [
            'cliente_id' => (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0) ?: null) ?? 0),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(5, min(25, (int)($_GET['per'] ?? 10)));
        $total = $this->model->countIndex($filters);
        $totalPages = max(1, (int)ceil($total / $per));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $this->render('treinamentos/index', [
            'pageTitle' => 'Pilar de Treinamentos',
            'items' => $this->model->paginateIndex($filters, $page, $per),
            'clientes' => $this->clienteOptions(),
            'filters' => $filters,
            'page' => $page,
            'per' => $per,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    public function dashboard(): void
    {
        $this->requireLogin();
        $filters = $this->dashboardFilters();
        AuditLogger::log('treinamentos_dashboard_access', 'treinamentos', null, [
            'cliente_id' => (int)($filters['cliente_id'] ?? 0),
            'setor_id' => (int)($filters['setor_id'] ?? 0),
            'periodo_inicio' => (string)($filters['periodo_inicio'] ?? ''),
            'periodo_fim' => (string)($filters['periodo_fim'] ?? ''),
            'tipo_treinamento' => (string)($filters['tipo_treinamento'] ?? ''),
            'instrutor' => (string)($filters['instrutor'] ?? ''),
            'allowed_client_ids' => Auth::allowedClientIds(),
            'tipo_acesso' => (string)($_SESSION['user']['tipo_acesso'] ?? ''),
        ]);
        $clientes = $this->clienteOptions();
        $setores = $this->setorOptions();
        if (!empty($filters['cliente_id'])) {
            $cid = (int)$filters['cliente_id'];
            $setores = $this->catalogOptionsForCliente($cid)['setores'];
        }
        $this->render('treinamentos/dashboard', [
            'pageTitle' => 'Dashboard de Treinamentos',
            'dashboard' => $this->model->dashboard($filters),
            'filters' => $filters,
            'clientes' => $clientes,
            'setores' => $setores,
            'tipoTreinamentoOptions' => $this->tipoTreinamentoOptions((int)($filters['cliente_id'] ?? 0)),
        ]);
    }

    public function create(): void
    {
        $this->requireManagePermission();
        $prefCliente = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : (isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0);
        $prefCliente = (int)($this->resolveScopedClienteId($prefCliente > 0 ? $prefCliente : null) ?? 0);
        $this->renderForm('treinamentos/create', [
            'nome' => '',
            'objetivo' => '',
            'publico' => '',
            'carga_horaria' => '',
            'cliente_id' => $prefCliente,
            'departamento_id' => 0,
            'periodicidade' => 'avulso',
            'fornecedor' => '',
            'tipo_treinamento' => '',
            'template_certificado' => '',
            'assinatura_responsavel' => '',
            'setor_ids' => [],
            'funcao_ids' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $payload = $this->payload();
        $errors = $this->validatePayload($payload);
        if ($errors) {
            $this->renderForm('treinamentos/create', $payload, $errors);
            return;
        }
        $id = $this->model->create($payload);
        $_SESSION['flash_success'] = 'Treinamento cadastrado com sucesso.';
        $this->redirect('index.php?route=treinamentos/show&id=' . $id);
    }

    public function edit(): void
    {
        $this->requireManagePermission();
        $item = $this->findOrRedirect((int)($_GET['id'] ?? 0));
        if (!$item) {
            return;
        }
        $this->renderForm('treinamentos/edit', $item);
    }

    public function update(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $item = $this->findOrRedirect($id);
        if (!$item) {
            return;
        }
        $payload = $this->payload();
        $errors = $this->validatePayload($payload);
        if ($errors) {
            $payload['id'] = $id;
            $this->renderForm('treinamentos/edit', $payload, $errors);
            return;
        }
        $this->model->update($id, $payload);
        $_SESSION['flash_success'] = 'Treinamento atualizado com sucesso.';
        $this->redirect('index.php?route=treinamentos/show&id=' . $id);
    }

    public function delete(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->model->delete($id);
            $_SESSION['flash_success'] = 'Treinamento removido com sucesso.';
        }
        $this->redirect('index.php?route=treinamentos/index');
    }

    public function show(): void
    {
        $this->requireLogin();
        $item = $this->findOrRedirect((int)($_GET['id'] ?? 0));
        if (!$item) {
            return;
        }
        $eligibleFilters = $this->normalizeEligibleFilters((int)($item['cliente_id'] ?? 0), $this->eligibleFilters());
        $eligibleRows = $this->model->eligibleColaboradoresForTraining((int)$item['id'], $eligibleFilters);
        $clienteId = (int)($item['cliente_id'] ?? 0);
        $catalogo = $this->catalogOptionsForCliente($clienteId);
        $this->render('treinamentos/show', [
            'pageTitle' => 'Treinamento',
            'item' => $item,
            'linked' => $this->model->linkedColaboradores((int)$item['id']),
            'availableColaboradores' => $this->model->availableColaboradores((int)$item['id']),
            'eligibleRows' => $eligibleRows,
            'eligibleFilters' => $eligibleFilters,
            'agendas' => $this->agendaModel->listByTreinamento((int)$item['id']),
            'pendingParticipants' => $this->agendaModel->pendingParticipantsForTreinamento((int)$item['id']),
            'alerts' => $this->model->pendingAlerts((int)$item['id']),
            'unidades' => $this->clienteOptions(),
            'usuarios' => $this->usuarioOptions(),
            'departamentos' => $catalogo['departamentos'],
            'setores' => $catalogo['setores'],
            'funcoes' => $catalogo['funcoes'],
            'statusAtualOptions' => $this->statusAtualOptions(),
        ]);
    }

    public function catalogoOptionsAjax(): void
    {
        $this->requireManagePermission();
        header('Content-Type: application/json; charset=utf-8');
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        if ($clienteId <= 0 || !$this->canAccessCliente($clienteId)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Empresa inválida.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'ok' => true,
            'cliente_id' => $clienteId,
            'catalogo' => $this->catalogOptionsForCliente($clienteId),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function eligibleAjax(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $item = $this->findOrRedirect((int)($_GET['id'] ?? 0));
        if (!$item) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Treinamento não encontrado.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $filters = $this->normalizeEligibleFilters((int)($item['cliente_id'] ?? 0), $this->eligibleFilters());
        $rows = $this->model->eligibleColaboradoresForTraining((int)$item['id'], $filters);
        ob_start();
        $eligibleRows = $rows;
        require __DIR__ . '/../views/treinamentos/_eligible_rows.php';
        $html = (string)ob_get_clean();
        echo json_encode(['ok' => true, 'count' => count($rows), 'html' => $html], JSON_UNESCAPED_UNICODE);
    }

    public function addColaboradores(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $treinamento = $this->model->find($treinamentoId);
        if (!$treinamento) {
            $_SESSION['flash_error'] = 'Treinamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $ids = $_POST['colaborador_ids'] ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $validIds = $this->model->filterColaboradoresByCliente((int)($treinamento['cliente_id'] ?? 0), $ids);
        if (count($validIds) !== count($ids)) {
            $_SESSION['flash_error'] = 'Existem colaboradores inválidos para a unidade do treinamento.';
            $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
            return;
        }
        $this->model->syncSelectedColaboradores($treinamentoId, $validIds);
        $_SESSION['flash_success'] = 'Lista pré-cadastrada do treinamento atualizada.';
        $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
    }

    public function exportSelecionados(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $treinamento = $this->model->find($treinamentoId);
        if (!$treinamento) {
            $_SESSION['flash_error'] = 'Treinamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $ids = $_POST['colaborador_ids'] ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            $_SESSION['flash_error'] = 'Selecione ao menos um colaborador para exportar.';
            $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
            return;
        }
        $validIds = $this->model->filterColaboradoresByCliente((int)($treinamento['cliente_id'] ?? 0), $ids);
        if (empty($validIds)) {
            $_SESSION['flash_error'] = 'Nenhum colaborador válido para exportar.';
            $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
            return;
        }
        $rows = $this->model->eligibleColaboradoresForTraining((int)$treinamentoId, ['colaborador_ids' => $validIds]);

        AuditLogger::log('treinamentos_export_selecionados', 'treinamento', $treinamentoId, [
            'cliente_id' => (int)($treinamento['cliente_id'] ?? 0),
            'total' => count($rows),
        ]);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $filename = 'treinamento-' . $treinamentoId . '-selecionados.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['Nome', 'Matrícula', 'Setor', 'Cargo', 'CPF', 'E-mail', 'Status', 'Elegibilidade', 'Última conclusão'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                (string)($r['nome'] ?? ''),
                (string)($r['matricula'] ?? ''),
                (string)($r['setor'] ?? ''),
                (string)($r['cargo'] ?? ''),
                (string)($r['cpf'] ?? ''),
                (string)($r['email_corporativo'] ?? ''),
                (string)($r['status_atual'] ?? ''),
                (string)($r['status_elegibilidade'] ?? ''),
                (string)($r['ultima_conclusao'] ?? ''),
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function rhSync(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $treinamento = $this->model->find($treinamentoId);
        if (!$treinamento) {
            $_SESSION['flash_error'] = 'Treinamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }

        $url = trim((string)getenv('RH_WEBHOOK_URL'));
        if ($url === '') {
            $_SESSION['flash_error'] = 'Integração com RH não configurada (RH_WEBHOOK_URL).';
            $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
            return;
        }
        $token = trim((string)getenv('RH_WEBHOOK_TOKEN'));

        $linked = $this->model->linkedColaboradores($treinamentoId, null);
        $payload = [
            'event' => 'treinamento.sync',
            'treinamento' => [
                'id' => $treinamentoId,
                'nome' => (string)($treinamento['nome'] ?? ''),
                'cliente_id' => (int)($treinamento['cliente_id'] ?? 0),
                'unidade_nome' => (string)($treinamento['unidade_nome'] ?? ''),
                'tipo_treinamento' => (string)($treinamento['tipo_treinamento'] ?? ''),
                'carga_horaria' => $treinamento['carga_horaria'] ?? null,
            ],
            'colaboradores' => array_map(static function (array $row): array {
                return [
                    'id' => (int)($row['colaborador_id'] ?? 0),
                    'nome' => (string)($row['colaborador_nome'] ?? ''),
                    'email' => (string)($row['colaborador_email'] ?? ''),
                    'status' => (string)($row['status'] ?? ''),
                    'ultima_conclusao' => $row['ultima_conclusao'] ?? null,
                    'funcao' => $row['funcao_nome'] ?? null,
                    'setor' => $row['setor_nome'] ?? null,
                    'unidade' => $row['unidade_nome'] ?? null,
                ];
            }, $linked),
        ];

        $result = $this->postJson($url, $payload, $token);
        if (!$result['ok']) {
            AuditLogger::log('rh_sync_failed', 'treinamento', $treinamentoId, [
                'url' => $url,
                'error' => $result['error'] ?? null,
                'http_code' => $result['http_code'] ?? null,
            ]);
            $_SESSION['flash_error'] = 'Falha ao sincronizar com RH: ' . (string)($result['error'] ?? 'Erro desconhecido');
            $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
            return;
        }

        AuditLogger::log('rh_sync', 'treinamento', $treinamentoId, [
            'url' => $url,
            'http_code' => $result['http_code'] ?? null,
            'total' => count($linked),
        ]);
        $_SESSION['flash_success'] = 'Sincronização com RH enviada com sucesso.';
        $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
    }

    public function removeColaborador(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $colaboradorId = (int)($_POST['colaborador_id'] ?? 0);
        $this->model->unlinkColaborador($treinamentoId, $colaboradorId);
        $_SESSION['flash_success'] = 'Vínculo removido do treinamento.';
        $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
    }

    public function storeAgenda(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $dataInicio = $this->normalizeDateTimeLocal((string)($_POST['data'] ?? ''));
        $dataFim = $this->normalizeDateTimeLocal((string)($_POST['data_fim'] ?? ''));
        if ($dataInicio === '' || $dataFim === '') {
            http_response_code(400);
            echo 'Data/hora inicial e final são obrigatórias.';
            return;
        }
        if (strtotime($dataFim) !== false && strtotime($dataInicio) !== false && strtotime($dataFim) <= strtotime($dataInicio)) {
            http_response_code(400);
            echo 'Data/hora final deve ser maior que a inicial.';
            return;
        }
        $agendaId = $this->agendaModel->create([
            'treinamento_id' => $treinamentoId,
            'data' => $dataInicio,
            'data_fim' => $dataFim,
            'unidade_id' => (int)($_POST['unidade_id'] ?? 0),
            'responsavel_id' => (int)($_POST['responsavel_id'] ?? 0),
            'instrutor' => (string)($_POST['instrutor'] ?? ''),
            'local' => (string)($_POST['local'] ?? ''),
            'observacoes' => (string)($_POST['observacoes'] ?? ''),
        ]);
        if ($agendaId <= 0) {
            http_response_code(500);
            echo 'Falha ao criar agendamento.';
            return;
        }
        $participantIds = $_POST['participante_ids'] ?? [];
        $this->agendaModel->syncParticipants($agendaId, is_array($participantIds) ? $participantIds : [$participantIds]);
        $_SESSION['flash_success'] = 'Agendamento criado com participantes iniciais.';
        $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
    }

    public function updateAgenda(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $agendaId = (int)($_POST['agenda_id'] ?? 0);
        $agenda = $this->agendaModel->find($agendaId);
        if (!$agenda) {
            http_response_code(404);
            echo 'Agendamento não encontrado.';
            return;
        }
        $dataInicio = $this->normalizeDateTimeLocal((string)($_POST['data'] ?? ''));
        $dataFim = $this->normalizeDateTimeLocal((string)($_POST['data_fim'] ?? ''));
        if ($dataInicio === '' || $dataFim === '') {
            http_response_code(400);
            echo 'Data/hora inicial e final são obrigatórias.';
            return;
        }
        if (strtotime($dataFim) !== false && strtotime($dataInicio) !== false && strtotime($dataFim) <= strtotime($dataInicio)) {
            http_response_code(400);
            echo 'Data/hora final deve ser maior que a inicial.';
            return;
        }
        $ok = $this->agendaModel->update($agendaId, [
            'data' => $dataInicio,
            'data_fim' => $dataFim,
            'unidade_id' => (int)($_POST['unidade_id'] ?? 0),
            'responsavel_id' => (int)($_POST['responsavel_id'] ?? 0),
            'instrutor' => (string)($_POST['instrutor'] ?? ''),
            'local' => (string)($_POST['local'] ?? ''),
            'observacoes' => (string)($_POST['observacoes'] ?? ''),
        ]);
        if (!$ok) {
            http_response_code(500);
            echo 'Falha ao atualizar agendamento.';
            return;
        }
        $_SESSION['flash_success'] = 'Agendamento atualizado com sucesso.';
        $this->redirect('index.php?route=treinamentos/show&id=' . (int)($agenda['treinamento_id'] ?? 0));
    }

    public function deleteAgenda(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $agendaId = (int)($_POST['agenda_id'] ?? 0);
        $agenda = $this->agendaModel->find($agendaId);
        if (!$agenda) {
            http_response_code(404);
            echo 'Agendamento não encontrado.';
            return;
        }
        $ok = $this->agendaModel->delete($agendaId);
        if (!$ok) {
            http_response_code(500);
            echo 'Falha ao excluir agendamento.';
            return;
        }
        $_SESSION['flash_success'] = 'Agendamento excluído com sucesso.';
        $this->redirect('index.php?route=treinamentos/show&id=' . (int)($agenda['treinamento_id'] ?? 0));
    }

    public function presenca(): void
    {
        $this->requireLogin();
        $agenda = $this->agendaModel->find((int)($_GET['agenda_id'] ?? 0));
        if (!$agenda) {
            $_SESSION['flash_error'] = 'Agendamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $this->render('treinamentos/presenca', [
            'pageTitle' => 'Lista de Presenca',
            'agenda' => $agenda,
            'participants' => $this->agendaModel->participants((int)$agenda['id']),
            'pendingParticipants' => $this->agendaModel->pendingParticipantsForTreinamento((int)$agenda['treinamento_id'], (int)$agenda['id']),
        ]);
    }

    public function addParticipantes(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $agendaId = (int)($_POST['agenda_id'] ?? 0);
        $ids = $_POST['colaborador_ids'] ?? [];
        $this->agendaModel->syncParticipants($agendaId, is_array($ids) ? $ids : [$ids]);
        $_SESSION['flash_success'] = 'Participantes adicionados ao agendamento.';
        $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
    }

    public function savePresence(): void
    {
        $this->requireManagePermission();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $agendaId = (int)($_POST['agenda_id'] ?? 0);
        $this->agendaModel->savePresence(
            $agendaId,
            $_POST['presenca'] ?? [],
            $_POST['hora_entrada'] ?? [],
            $_POST['hora_saida'] ?? [],
            $_POST['observacao'] ?? []
        );
        $_SESSION['flash_success'] = 'Lista de presença atualizada.';
        $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
    }

    public function certificado(): void
    {
        $this->requireManagePermission();
        if (!\App\Core\PdfSupport::isDompdfAvailable()) {
            $errorId = \App\Core\PdfSupport::newErrorId();
            \App\Core\AuditLogger::log('pdf_unavailable', 'treinamentos', null, [
                'error_id' => $errorId,
                'route' => 'treinamentos/certificado',
                'reason' => 'dompdf_missing',
                'diagnostics' => \App\Core\PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }
        $agendaId = (int)($_GET['agenda_id'] ?? 0);
        $colaboradorId = (int)($_GET['colaborador_id'] ?? 0);
        $agenda = $this->agendaModel->find($agendaId);
        if (!$agenda) {
            $_SESSION['flash_error'] = 'Agendamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $participant = $this->agendaModel->findParticipant($agendaId, $colaboradorId);
        if (!$participant) {
            $_SESSION['flash_error'] = 'Participante não encontrado.';
            $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
            return;
        }
        $treinamento = $this->model->find((int)$agenda['treinamento_id']);
        if (!$treinamento) {
            $_SESSION['flash_error'] = 'Treinamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }

        $issued = $this->agendaModel->issueCertificate($agendaId, $colaboradorId);
        if (!$issued) {
            $_SESSION['flash_error'] = 'Não foi possível emitir o certificado para este participante.';
            $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
            return;
        }
        $path = $this->documents->generateCertificateFile($treinamento, $agenda, $issued);
        $this->agendaModel->updateCertificateFile($agendaId, $colaboradorId, $path);
        $binary = is_file($path) ? (string)file_get_contents($path) : $this->documents->renderCertificatePdf($treinamento, $agenda, $issued);
        $this->sendBinaryPdf(
            'certificado-' . $agendaId . '-' . $colaboradorId . '.pdf',
            $binary,
            !empty($_GET['download'])
        );
    }

    public function certificadoLote(): void
    {
        $this->requireManagePermission();
        if (!\App\Core\PdfSupport::isDompdfAvailable()) {
            $errorId = \App\Core\PdfSupport::newErrorId();
            \App\Core\AuditLogger::log('pdf_unavailable', 'treinamentos', null, [
                'error_id' => $errorId,
                'route' => 'treinamentos/certificadoLote',
                'reason' => 'dompdf_missing',
                'diagnostics' => \App\Core\PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $agendaId = (int)($_POST['agenda_id'] ?? 0);
        $agenda = $this->agendaModel->find($agendaId);
        if (!$agenda) {
            $_SESSION['flash_error'] = 'Agendamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $treinamento = $this->model->find((int)$agenda['treinamento_id']);
        if (!$treinamento) {
            $_SESSION['flash_error'] = 'Treinamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $participantIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['colaborador_ids'] ?? [])))));
        if (empty($participantIds)) {
            $participantIds = array_map(static fn(array $row): int => (int)$row['colaborador_id'], $this->agendaModel->participants($agendaId));
        }

        $emitidos = 0;
        $files = [];
        foreach ($participantIds as $colaboradorId) {
            $issued = $this->agendaModel->issueCertificate($agendaId, $colaboradorId);
            if (!$issued) {
                continue;
            }
            $path = $this->documents->generateCertificateFile($treinamento, $agenda, $issued);
            $this->agendaModel->updateCertificateFile($agendaId, $colaboradorId, $path);
            if (is_file($path)) {
                $files[] = $path;
            }
            $emitidos++;
        }
        if ($emitidos <= 0 || empty($files)) {
            $_SESSION['flash_error'] = 'Nenhum certificado pôde ser emitido.';
            $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
            return;
        }
        $_SESSION['flash_success'] = $emitidos . ' certificado(s) emitido(s) em lote.';
        try {
            $zipPath = $this->buildCertificatesZip($agendaId, $files);
            $this->sendFile($zipPath, 'application/zip', 'certificados-agenda-' . $agendaId . '.zip');
        } catch (\Throwable $e) {
            $first = $files[0] ?? '';
            if ($first !== '' && is_file($first)) {
                $this->sendFile($first, 'application/pdf', 'certificado-agenda-' . $agendaId . '.pdf');
            }
            http_response_code(500);
            echo 'Falha ao preparar arquivo em lote.';
        }
    }

    public function exportElegiveis(): void
    {
        $this->requireLogin();
        if (!\App\Core\PdfSupport::isDompdfAvailable() && strtolower(trim((string)($_GET['format'] ?? 'pdf'))) !== 'xlsx') {
            $errorId = \App\Core\PdfSupport::newErrorId();
            \App\Core\AuditLogger::log('pdf_unavailable', 'treinamentos', (int)($_GET['id'] ?? 0), [
                'error_id' => $errorId,
                'route' => 'treinamentos/exportElegiveis',
                'reason' => 'dompdf_missing',
                'diagnostics' => \App\Core\PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }
        $treinamento = $this->findOrRedirect((int)($_GET['id'] ?? 0));
        if (!$treinamento) {
            return;
        }
        $filters = $this->normalizeEligibleFilters((int)($treinamento['cliente_id'] ?? 0), $this->eligibleFilters());
        $rows = $this->model->eligibleColaboradoresForTraining((int)$treinamento['id'], $filters);
        $format = strtolower(trim((string)($_GET['format'] ?? 'pdf')));
        if ($format === 'xlsx') {
            $path = $this->documents->exportEligibleXlsx($treinamento, $rows);
            $this->sendFile($path, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'elegiveis-' . (int)$treinamento['id'] . '.xlsx');
            return;
        }
        $this->sendBinaryPdf(
            'elegiveis-' . (int)$treinamento['id'] . '.pdf',
            $this->documents->renderEligiblePdf($treinamento, $rows, $filters),
            true
        );
    }

    public function presencaPdf(): void
    {
        $this->requireLogin();
        if (!\App\Core\PdfSupport::isDompdfAvailable()) {
            $errorId = \App\Core\PdfSupport::newErrorId();
            \App\Core\AuditLogger::log('pdf_unavailable', 'treinamentos', (int)($_GET['agenda_id'] ?? 0), [
                'error_id' => $errorId,
                'route' => 'treinamentos/presencaPdf',
                'reason' => 'dompdf_missing',
                'diagnostics' => \App\Core\PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }
        $agenda = $this->agendaModel->find((int)($_GET['agenda_id'] ?? 0));
        if (!$agenda) {
            $_SESSION['flash_error'] = 'Agendamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $participants = $this->agendaModel->participants((int)$agenda['id']);
        $this->sendBinaryPdf(
            'lista-presenca-' . (int)$agenda['id'] . '.pdf',
            $this->documents->renderPresencePdf($agenda, $participants),
            true
        );
    }

    public function dashboardPdf(): void
    {
        $this->requireLogin();
        if (!\App\Core\PdfSupport::isDompdfAvailable()) {
            $errorId = \App\Core\PdfSupport::newErrorId();
            \App\Core\AuditLogger::log('pdf_unavailable', 'treinamentos', null, [
                'error_id' => $errorId,
                'route' => 'treinamentos/dashboardPdf',
                'reason' => 'dompdf_missing',
                'diagnostics' => \App\Core\PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }
        $filters = $this->dashboardFilters();
        AuditLogger::log('treinamentos_dashboard_pdf', 'treinamentos', null, [
            'cliente_id' => (int)($filters['cliente_id'] ?? 0),
            'setor_id' => (int)($filters['setor_id'] ?? 0),
            'periodo_inicio' => (string)($filters['periodo_inicio'] ?? ''),
            'periodo_fim' => (string)($filters['periodo_fim'] ?? ''),
            'tipo_treinamento' => (string)($filters['tipo_treinamento'] ?? ''),
            'instrutor' => (string)($filters['instrutor'] ?? ''),
            'allowed_client_ids' => Auth::allowedClientIds(),
            'tipo_acesso' => (string)($_SESSION['user']['tipo_acesso'] ?? ''),
        ]);
        $dashboard = $this->model->dashboard($filters);
        $this->sendBinaryPdf(
            'dashboard-treinamentos-' . date('Ymd-His') . '.pdf',
            $this->documents->renderDashboardPdf($dashboard, $filters),
            true
        );
    }

    private function renderForm(string $view, array $values, array $errors = []): void
    {
        $clienteId = (int)($values['cliente_id'] ?? 0);
        $catalogo = $this->catalogOptionsForCliente($clienteId);
        $this->render($view, [
            'pageTitle' => str_contains($view, 'edit') ? 'Editar Treinamento' : 'Novo Treinamento',
            'values' => $values,
            'errors' => $errors,
            'clientes' => $this->clienteOptions(),
            'departamentos' => $catalogo['departamentos'],
            'setores' => $catalogo['setores'],
            'funcoes' => $catalogo['funcoes'],
            'catalogoEndpoint' => 'index.php?route=treinamentos/catalogoOptionsAjax',
            'periodicidades' => TreinamentoModel::periodicidadeOptions(),
        ]);
    }

    private function payload(): array
    {
        return [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'objetivo' => trim((string)($_POST['objetivo'] ?? '')),
            'publico' => trim((string)($_POST['publico'] ?? '')),
            'carga_horaria' => trim((string)($_POST['carga_horaria'] ?? '')),
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'departamento_id' => (int)($_POST['departamento_id'] ?? 0),
            'periodicidade' => trim((string)($_POST['periodicidade'] ?? 'avulso')),
            'fornecedor' => trim((string)($_POST['fornecedor'] ?? '')),
            'tipo_treinamento' => trim((string)($_POST['tipo_treinamento'] ?? '')),
            'template_certificado' => trim((string)($_POST['template_certificado'] ?? '')),
            'assinatura_responsavel' => trim((string)($_POST['assinatura_responsavel'] ?? '')),
            'setor_ids' => array_values(array_unique(array_filter(array_map('intval', (array)($_POST['setor_ids'] ?? []))))),
            'funcao_ids' => array_values(array_unique(array_filter(array_map('intval', (array)($_POST['funcao_ids'] ?? []))))),
        ];
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];
        if ($payload['nome'] === '') {
            $errors['nome'] = 'Informe o nome do treinamento.';
        }
        if ((int)($payload['cliente_id'] ?? 0) <= 0) {
            $errors['cliente_id'] = 'Selecione uma empresa.';
        }
        if ((int)$payload['departamento_id'] <= 0) {
            $errors['departamento_id'] = 'Selecione um departamento.';
        }
        if ($payload['carga_horaria'] !== '' && !is_numeric($payload['carga_horaria'])) {
            $errors['carga_horaria'] = 'Informe uma carga horária numérica.';
        }
        if (!isset(TreinamentoModel::periodicidadeOptions()[$payload['periodicidade']])) {
            $errors['periodicidade'] = 'Periodicidade inválida.';
        }
        if ($payload['assinatura_responsavel'] === '') {
            $errors['assinatura_responsavel'] = 'Informe o responsável pela assinatura digital.';
        }
        if (empty($errors['cliente_id']) && empty($errors['departamento_id'])) {
            $clienteId = (int)$payload['cliente_id'];
            if (!$this->departamentoBelongsToCliente((int)$payload['departamento_id'], $clienteId)) {
                $errors['departamento_id'] = 'Departamento inválido para a empresa selecionada.';
            }
            if (!empty($payload['setor_ids']) && !$this->setoresBelongToCliente($payload['setor_ids'], $clienteId, (int)$payload['departamento_id'])) {
                $errors['setor_ids'] = 'Existem setores que não pertencem à empresa selecionada.';
            }
            if (!empty($payload['funcao_ids']) && !$this->funcoesBelongToCliente($payload['funcao_ids'], $clienteId, (int)$payload['departamento_id'])) {
                $errors['funcao_ids'] = 'Existem funções que não pertencem à empresa selecionada.';
            }
        }
        return $errors;
    }

    private function findOrRedirect(int $id): ?array
    {
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Treinamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return null;
        }
        $item = $this->model->find($id);
        if (!$item) {
            $_SESSION['flash_error'] = 'Treinamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return null;
        }
        return $item;
    }

    private function clienteOptions(): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $sql = "SELECT id, nome_empresa FROM clientes WHERE 1=1";
        if (!Auth::isInstituto()) {
            $ids = Auth::allowedClientIds();
            if (empty($ids)) {
                return [];
            }
            $holders = [];
            foreach (array_values($ids) as $i => $id) {
                $key = 'tc' . $i;
                $holders[] = ':' . $key;
                $params[$key] = (int)$id;
            }
            $sql .= " AND id IN (" . implode(',', $holders) . ")";
        }
        $sql .= " ORDER BY nome_empresa";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function departamentoOptions(): array
    {
        if (Auth::isInstituto()) {
            $rows = $this->departamentosModel->all();
        } else {
            $rows = $this->departamentosModel->allByClientes(Auth::allowedClientIds());
        }
        return array_map(function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'nome' => (string)($row['nome'] ?? ''),
                'cliente_id' => (int)($row['cliente_id'] ?? 0),
                'label' => trim((string)($row['nome'] ?? '')),
            ];
        }, $rows);
    }

    private function setorOptions(): array
    {
        if (Auth::isInstituto()) {
            $rows = [];
            foreach ($this->clienteOptions() as $cliente) {
                $rows = array_merge($rows, $this->setoresModel->activeByCliente((int)($cliente['id'] ?? 0)));
            }
        } else {
            $rows = [];
            foreach (Auth::allowedClientIds() as $clienteId) {
                $rows = array_merge($rows, $this->setoresModel->activeByCliente((int)$clienteId));
            }
        }
        $unique = [];
        foreach ($rows as $row) {
            $unique[(int)($row['id'] ?? 0)] = [
                'id' => (int)($row['id'] ?? 0),
                'nome' => (string)($row['nome'] ?? ''),
                'departamento_id' => (int)($row['departamento_id'] ?? 0),
                'departamento_nome' => (string)($row['departamento'] ?? ''),
                'cliente_id' => (int)($row['cliente_id'] ?? 0),
                'label' => trim((string)(($row['departamento'] ?? '') . ' • ' . ($row['nome'] ?? ''))),
            ];
        }
        return array_values($unique);
    }

    private function funcaoOptions(): array
    {
        if (Auth::isInstituto()) {
            $rows = [];
            foreach ($this->clienteOptions() as $cliente) {
                $rows = array_merge($rows, $this->funcoesModel->activeByCliente((int)($cliente['id'] ?? 0)));
            }
        } else {
            $rows = [];
            foreach (Auth::allowedClientIds() as $clienteId) {
                $rows = array_merge($rows, $this->funcoesModel->activeByCliente((int)$clienteId));
            }
        }
        $unique = [];
        foreach ($rows as $row) {
            $unique[(int)($row['id'] ?? 0)] = [
                'id' => (int)($row['id'] ?? 0),
                'nome' => (string)($row['nome'] ?? ''),
                'setor_id' => (int)($row['setor_id'] ?? 0),
                'departamento_id' => (int)($row['departamento_id'] ?? 0),
                'setor_nome' => (string)($row['setor'] ?? ''),
                'cliente_id' => (int)($row['cliente_id'] ?? 0),
                'label' => trim((string)(($row['setor'] ?? '') . ' • ' . ($row['nome'] ?? ''))),
            ];
        }
        return array_values($unique);
    }

    private function departamentoBelongsToCliente(int $departamentoId, int $clienteId): bool
    {
        return $this->departamentosModel->findActive($departamentoId, $clienteId) !== null;
    }

    private function setoresBelongToCliente(array $setorIds, int $clienteId, int $departamentoId = 0): bool
    {
        $setorIds = array_values(array_unique(array_filter(array_map('intval', $setorIds))));
        if (empty($setorIds) || $clienteId <= 0) {
            return true;
        }
        if (!$this->canAccessCliente($clienteId)) {
            return false;
        }
        foreach ($setorIds as $id) {
            $row = $this->setoresModel->findActive($id, $departamentoId);
            if (!$row) {
                return false;
            }
            if (!$this->departamentoBelongsToCliente((int)($row['departamento_id'] ?? 0), $clienteId)) {
                return false;
            }
        }
        return true;
    }

    private function funcoesBelongToCliente(array $funcaoIds, int $clienteId, int $departamentoId = 0): bool
    {
        $funcaoIds = array_values(array_unique(array_filter(array_map('intval', $funcaoIds))));
        if (empty($funcaoIds) || $clienteId <= 0) {
            return true;
        }
        if (!$this->canAccessCliente($clienteId)) {
            return false;
        }
        foreach ($funcaoIds as $id) {
            $row = $this->funcoesModel->find($id);
            if (!$row) {
                return false;
            }
            if ((int)($row['departamento_id'] ?? 0) !== $departamentoId) {
                return false;
            }
            if (!$this->departamentoBelongsToCliente($departamentoId, $clienteId)) {
                return false;
            }
            if (array_key_exists('ativo', $row) && (int)($row['ativo'] ?? 1) !== 1) {
                return false;
            }
        }
        return true;
    }

    private function usuarioOptions(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
        return $stmt->fetchAll() ?: [];
    }

    private function tipoTreinamentoOptions(int $clienteId = 0): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $sql = "SELECT DISTINCT t.tipo_treinamento
                FROM treinamentos t
                JOIN departamentos d ON d.id = t.departamento_id
                WHERE t.tipo_treinamento IS NOT NULL AND TRIM(t.tipo_treinamento) <> ''";
        if ($clienteId > 0) {
            $sql .= " AND COALESCE(t.cliente_id, d.cliente_id) = :cid";
            $params['cid'] = $clienteId;
        }
        if (!Auth::isInstituto()) {
            $ids = Auth::allowedClientIds();
            if (empty($ids)) {
                return [];
            }
            $holders = [];
            foreach (array_values($ids) as $i => $id) {
                $key = 'tt' . $i;
                $holders[] = ':' . $key;
                $params[$key] = (int)$id;
            }
            $sql .= " AND COALESCE(t.cliente_id, d.cliente_id) IN (" . implode(',', $holders) . ")";
        }
        $sql .= " ORDER BY t.tipo_treinamento";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return array_values(array_filter(array_map(static fn(array $row): string => (string)($row['tipo_treinamento'] ?? ''), $stmt->fetchAll() ?: [])));
    }

    private function statusAtualOptions(): array
    {
        return ['ativo', 'inativo', 'afastado', 'desligado'];
    }

    private function eligibleFilters(): array
    {
        $arr = static function ($v): array {
            if (is_array($v)) {
                return array_values(array_unique(array_filter(array_map('intval', $v))));
            }
            if ($v === null || $v === '') {
                return [];
            }
            return array_values(array_unique(array_filter([intval($v)])));
        };

        $setorIds = $arr($_GET['setor_ids'] ?? []);
        $funcaoIds = $arr($_GET['funcao_ids'] ?? []);
        if (empty($setorIds) && !empty($_GET['setor_id'])) {
            $setorIds = [(int)$_GET['setor_id']];
        }
        if (empty($funcaoIds) && !empty($_GET['funcao_id'])) {
            $funcaoIds = [(int)$_GET['funcao_id']];
        }

        return [
            'q' => trim((string)($_GET['q'] ?? '')),
            'departamento_ids' => $arr($_GET['departamento_ids'] ?? []),
            'setor_ids' => $setorIds,
            'funcao_ids' => $funcaoIds,
            'data_admissao_inicio' => trim((string)($_GET['data_admissao_inicio'] ?? '')),
            'data_admissao_fim' => trim((string)($_GET['data_admissao_fim'] ?? '')),
            'tempo_meses_min' => (int)($_GET['tempo_meses_min'] ?? 0),
            'tempo_meses_max' => (int)($_GET['tempo_meses_max'] ?? 0),
            'status_atual' => trim((string)($_GET['status_atual'] ?? '')),
            'status_elegibilidade' => trim((string)($_GET['status_elegibilidade'] ?? '')),
            'lideranca' => trim((string)($_GET['lideranca'] ?? '')),
            'historico' => trim((string)($_GET['historico'] ?? '')),
            'historico_dias' => (int)($_GET['historico_dias'] ?? 0),
        ];
    }

    private function normalizeEligibleFilters(int $clienteId, array $filters): array
    {
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return $filters;
        }

        $filters['departamento_ids'] = array_values(array_unique(array_filter(array_map('intval', (array)($filters['departamento_ids'] ?? [])))));
        $filters['setor_ids'] = array_values(array_unique(array_filter(array_map('intval', (array)($filters['setor_ids'] ?? [])))));
        $filters['funcao_ids'] = array_values(array_unique(array_filter(array_map('intval', (array)($filters['funcao_ids'] ?? [])))));

        $validDate = static function (string $s): string {
            $s = trim($s);
            if ($s === '') {
                return '';
            }
            $dt = \DateTime::createFromFormat('Y-m-d', $s);
            return $dt && $dt->format('Y-m-d') === $s ? $s : '';
        };
        $filters['data_admissao_inicio'] = $validDate((string)($filters['data_admissao_inicio'] ?? ''));
        $filters['data_admissao_fim'] = $validDate((string)($filters['data_admissao_fim'] ?? ''));

        $filters['tempo_meses_min'] = max(0, (int)($filters['tempo_meses_min'] ?? 0));
        $filters['tempo_meses_max'] = max(0, (int)($filters['tempo_meses_max'] ?? 0));
        if ($filters['tempo_meses_min'] > 0 && $filters['tempo_meses_max'] > 0 && $filters['tempo_meses_max'] < $filters['tempo_meses_min']) {
            $filters['tempo_meses_max'] = 0;
        }

        $statusAtual = trim((string)($filters['status_atual'] ?? ''));
        if ($statusAtual !== '' && !in_array($statusAtual, $this->statusAtualOptions(), true)) {
            $filters['status_atual'] = '';
        }
        $statusEleg = trim((string)($filters['status_elegibilidade'] ?? ''));
        if ($statusEleg !== '' && $statusEleg !== 'Elegivel' && $statusEleg !== 'Inelegivel') {
            $filters['status_elegibilidade'] = '';
        }
        $lider = strtolower(trim((string)($filters['lideranca'] ?? '')));
        if ($lider !== '' && $lider !== 'sim' && $lider !== 'nao') {
            $filters['lideranca'] = '';
        }
        $hist = strtolower(trim((string)($filters['historico'] ?? '')));
        if ($hist !== '' && $hist !== 'nunca' && $hist !== 'ja' && $hist !== 'dias') {
            $filters['historico'] = '';
        }
        $filters['historico_dias'] = max(0, (int)($filters['historico_dias'] ?? 0));
        if (($filters['historico'] ?? '') !== 'dias') {
            $filters['historico_dias'] = 0;
        }

        $departamentos = $this->catalogOptionsForCliente($clienteId)['departamentos'];
        $allowedDep = [];
        foreach ($departamentos as $d) {
            $allowedDep[(int)$d['id']] = true;
        }
        $filters['departamento_ids'] = array_values(array_filter($filters['departamento_ids'], static fn(int $id): bool => isset($allowedDep[$id])));
        $selectedDep = [];
        foreach ($filters['departamento_ids'] as $id) {
            $selectedDep[(int)$id] = true;
        }

        $setores = $this->catalogOptionsForCliente($clienteId)['setores'];
        $setorToDep = [];
        foreach ($setores as $s) {
            $setorToDep[(int)$s['id']] = (int)($s['departamento_id'] ?? 0);
        }
        $filters['setor_ids'] = array_values(array_filter($filters['setor_ids'], static function (int $id) use ($setorToDep, $selectedDep): bool {
            if (!isset($setorToDep[$id])) {
                return false;
            }
            if (empty($selectedDep)) {
                return true;
            }
            return isset($selectedDep[(int)$setorToDep[$id]]);
        }));
        $selectedSet = [];
        foreach ($filters['setor_ids'] as $id) {
            $selectedSet[(int)$id] = true;
        }

        $funcoes = $this->catalogOptionsForCliente($clienteId)['funcoes'];
        $funcaoMeta = [];
        foreach ($funcoes as $f) {
            $funcaoMeta[(int)$f['id']] = [
                'setor_id' => (int)($f['setor_id'] ?? 0),
                'departamento_id' => (int)($f['departamento_id'] ?? 0),
            ];
        }
        $filters['funcao_ids'] = array_values(array_filter($filters['funcao_ids'], static function (int $id) use ($funcaoMeta, $selectedSet, $selectedDep): bool {
            if (!isset($funcaoMeta[$id])) {
                return false;
            }
            $meta = $funcaoMeta[$id];
            if (!empty($selectedSet)) {
                return isset($selectedSet[(int)$meta['setor_id']]);
            }
            if (!empty($selectedDep)) {
                return isset($selectedDep[(int)$meta['departamento_id']]);
            }
            return true;
        }));

        return $filters;
    }

    private function postJson(string $url, array $payload, string $token = ''): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($body)) {
            return ['ok' => false, 'error' => 'Payload inválido.'];
        }
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $resp = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($resp === false) {
                return ['ok' => false, 'error' => $err ?: 'Falha ao enviar requisição.', 'http_code' => $code];
            }
            if ($code < 200 || $code >= 300) {
                return ['ok' => false, 'error' => 'HTTP ' . $code, 'http_code' => $code];
            }
            return ['ok' => true, 'http_code' => $code];
        }

        $headerStr = implode("\r\n", $headers);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headerStr,
                'content' => $body,
                'timeout' => 10,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                    $code = (int)$m[1];
                    break;
                }
            }
        }
        if ($resp === false) {
            return ['ok' => false, 'error' => 'Falha ao enviar requisição.', 'http_code' => $code];
        }
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'HTTP ' . $code, 'http_code' => $code];
        }
        return ['ok' => true, 'http_code' => $code];
    }

    private function dashboardFilters(): array
    {
        if (!empty($_GET['reset'])) {
            unset($_SESSION['treinamentos_dashboard_cliente_id']);
            return [
                'cliente_id' => 0,
                'periodo_inicio' => '',
                'periodo_fim' => '',
                'setor_id' => 0,
                'tipo_treinamento' => '',
                'instrutor' => '',
            ];
        }

        $clienteId = null;
        if (array_key_exists('cliente_id', $_GET)) {
            $clienteId = (int)($_GET['cliente_id'] ?? 0);
            $_SESSION['treinamentos_dashboard_cliente_id'] = $clienteId;
        } elseif (isset($_SESSION['treinamentos_dashboard_cliente_id'])) {
            $clienteId = (int)($_SESSION['treinamentos_dashboard_cliente_id'] ?? 0);
        }
        $clienteId = (int)($clienteId ?? 0);
        if ($clienteId > 0 && !$this->canAccessCliente($clienteId)) {
            $clienteId = 0;
        }
        $setorId = (int)($_GET['setor_id'] ?? 0);
        if ($setorId > 0 && $clienteId > 0 && !$this->setoresBelongToCliente([$setorId], $clienteId)) {
            $setorId = 0;
        }

        return [
            'cliente_id' => $clienteId,
            'periodo_inicio' => trim((string)($_GET['periodo_inicio'] ?? '')),
            'periodo_fim' => trim((string)($_GET['periodo_fim'] ?? '')),
            'setor_id' => $setorId,
            'tipo_treinamento' => trim((string)($_GET['tipo_treinamento'] ?? '')),
            'instrutor' => trim((string)($_GET['instrutor'] ?? '')),
        ];
    }

    private function catalogOptionsForCliente(int $clienteId): array
    {
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0 || !$this->canAccessCliente($clienteId)) {
            return [
                'departamentos' => [],
                'setores' => [],
                'funcoes' => [],
            ];
        }

        $departamentos = array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'nome' => (string)($row['nome'] ?? ''),
                'cliente_id' => (int)($row['cliente_id'] ?? 0),
                'label' => (string)($row['nome'] ?? ''),
            ];
        }, $this->departamentosModel->activeByCliente($clienteId));

        $setores = array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'nome' => (string)($row['nome'] ?? ''),
                'cliente_id' => (int)($row['cliente_id'] ?? 0),
                'departamento_id' => (int)($row['departamento_id'] ?? 0),
                'departamento_nome' => (string)($row['departamento'] ?? ''),
                'label' => trim((string)(($row['departamento'] ?? '') . ' • ' . ($row['nome'] ?? ''))),
            ];
        }, $this->setoresModel->activeByCliente($clienteId));

        $funcoes = array_map(static function (array $row): array {
            return [
                'id' => (int)($row['id'] ?? 0),
                'nome' => (string)($row['nome'] ?? ''),
                'cliente_id' => (int)($row['cliente_id'] ?? 0),
                'setor_id' => (int)($row['setor_id'] ?? 0),
                'departamento_id' => (int)($row['departamento_id'] ?? 0),
                'setor_nome' => (string)($row['setor'] ?? ''),
                'label' => trim((string)(($row['setor'] ?? '') . ' • ' . ($row['nome'] ?? ''))),
            ];
        }, $this->funcoesModel->activeByCliente($clienteId));

        return [
            'departamentos' => $departamentos,
            'setores' => $setores,
            'funcoes' => $funcoes,
        ];
    }

    private function requireManagePermission(): void
    {
        $this->requireRole('instituto');
    }

    private function sendBinaryPdf(string $filename, string $binary, bool $download): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($binary));
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        echo $binary;
        exit;
    }

    private function sendFile(string $path, string $mime, string $filename): void
    {
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($path);
        exit;
    }

    private function normalizeDateTimeLocal(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }
        $v = str_replace('T', ' ', $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
            $v .= ':00';
        }
        return $v;
    }

    private function buildCertificatesZip(int $agendaId, array $paths): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Extensão ZipArchive indisponível para gerar o arquivo em lote.');
        }
        $agendaId = (int)$agendaId;
        $paths = array_values(array_unique(array_filter(array_map('strval', $paths))));
        $baseDir = dirname(__DIR__, 2) . '/storage/pdfs/treinamentos/certificados/lote';
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0775, true);
        }
        $zipPath = $baseDir . '/certificados-agenda-' . $agendaId . '-' . date('Ymd_His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Falha ao criar ZIP de certificados.');
        }
        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }
            $name = basename($path);
            $zip->addFile($path, $name);
        }
        $zip->close();
        return $zipPath;
    }

    private function isPost(): bool
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }
}
