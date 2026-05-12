<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\DateHelper;
use App\Core\Security;
use App\Database\Database;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;
use App\Services\TreinamentoDocumentService;

class TreinamentosController extends BaseController
{
    private TreinamentoModel $model;
    private TreinamentoAgendaModel $agendaModel;
    private TreinamentoDocumentService $documents;

    public function __construct()
    {
        $this->model = new TreinamentoModel();
        $this->agendaModel = new TreinamentoAgendaModel();
        $this->documents = new TreinamentoDocumentService();
    }

    public function index(): void
    {
        $this->requireLogin();
        $filters = [
            'cliente_id' => (int)($_GET['cliente_id'] ?? 0),
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $this->render('treinamentos/index', [
            'pageTitle' => 'Pilar de Treinamentos',
            'items' => $this->model->all($filters),
            'clientes' => $this->clienteOptions(),
            'filters' => $filters,
        ]);
    }

    public function dashboard(): void
    {
        $this->requireLogin();
        $filters = $this->dashboardFilters();
        $this->render('treinamentos/dashboard', [
            'pageTitle' => 'Dashboard de Treinamentos',
            'dashboard' => $this->model->dashboard($filters),
            'filters' => $filters,
            'setores' => $this->setorOptions(),
            'tipoTreinamentoOptions' => $this->tipoTreinamentoOptions(),
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
        $eligibleFilters = $this->eligibleFilters();
        $eligibleRows = $this->model->eligibleColaboradoresForTraining((int)$item['id'], $eligibleFilters);
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
            'setores' => $this->setorOptions(),
            'funcoes' => $this->funcaoOptions(),
            'statusAtualOptions' => $this->statusAtualOptions(),
        ]);
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
        $ids = $_POST['colaborador_ids'] ?? [];
        $this->model->syncSelectedColaboradores($treinamentoId, is_array($ids) ? $ids : [$ids]);
        $_SESSION['flash_success'] = 'Lista pré-cadastrada do treinamento atualizada.';
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
        $agendaId = $this->agendaModel->create([
            'treinamento_id' => $treinamentoId,
            'data' => (string)($_POST['data'] ?? ''),
            'unidade_id' => (int)($_POST['unidade_id'] ?? 0),
            'responsavel_id' => (int)($_POST['responsavel_id'] ?? 0),
            'instrutor' => (string)($_POST['instrutor'] ?? ''),
            'local' => (string)($_POST['local'] ?? ''),
            'observacoes' => (string)($_POST['observacoes'] ?? ''),
        ]);
        $participantIds = $_POST['participante_ids'] ?? [];
        $this->agendaModel->syncParticipants($agendaId, is_array($participantIds) ? $participantIds : [$participantIds]);
        $_SESSION['flash_success'] = 'Agendamento criado com participantes iniciais.';
        $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
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
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage();
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
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage();
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
        foreach ($participantIds as $colaboradorId) {
            $issued = $this->agendaModel->issueCertificate($agendaId, $colaboradorId);
            if (!$issued) {
                continue;
            }
            $path = $this->documents->generateCertificateFile($treinamento, $agenda, $issued);
            $this->agendaModel->updateCertificateFile($agendaId, $colaboradorId, $path);
            $emitidos++;
        }
        $_SESSION['flash_success'] = $emitidos . ' certificado(s) emitido(s) em lote.';
        $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
    }

    public function exportElegiveis(): void
    {
        $this->requireLogin();
        if (!\App\Core\PdfSupport::isDompdfAvailable() && strtolower(trim((string)($_GET['format'] ?? 'pdf'))) !== 'xlsx') {
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage();
            return;
        }
        $treinamento = $this->findOrRedirect((int)($_GET['id'] ?? 0));
        if (!$treinamento) {
            return;
        }
        $filters = $this->eligibleFilters();
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
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage();
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
            http_response_code(503);
            echo \App\Core\PdfSupport::missingDompdfMessage();
            return;
        }
        $filters = $this->dashboardFilters();
        $dashboard = $this->model->dashboard($filters);
        $this->sendBinaryPdf(
            'dashboard-treinamentos-' . date('Ymd-His') . '.pdf',
            $this->documents->renderDashboardPdf($dashboard, $filters),
            true
        );
    }

    private function renderForm(string $view, array $values, array $errors = []): void
    {
        $this->render($view, [
            'pageTitle' => str_contains($view, 'edit') ? 'Editar Treinamento' : 'Novo Treinamento',
            'values' => $values,
            'errors' => $errors,
            'clientes' => $this->clienteOptions(),
            'departamentos' => $this->departamentoOptions(),
            'setores' => $this->setorOptions(),
            'funcoes' => $this->funcaoOptions(),
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
            if (!empty($payload['setor_ids']) && !$this->setoresBelongToCliente($payload['setor_ids'], $clienteId)) {
                $errors['setor_ids'] = 'Existem setores que não pertencem à empresa selecionada.';
            }
            if (!empty($payload['funcao_ids']) && !$this->funcoesBelongToCliente($payload['funcao_ids'], $clienteId)) {
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
        $pdo = Database::getConnection();
        $params = [];
        $sql = "SELECT d.id, d.nome, c.nome_empresa, d.cliente_id
                FROM departamentos d
                JOIN clientes c ON c.id = d.cliente_id
                WHERE 1=1";
        if (!Auth::isInstituto()) {
            $ids = Auth::allowedClientIds();
            if (empty($ids)) {
                return [];
            }
            $holders = [];
            foreach (array_values($ids) as $i => $id) {
                $key = 'td' . $i;
                $holders[] = ':' . $key;
                $params[$key] = (int)$id;
            }
            $sql .= " AND d.cliente_id IN (" . implode(',', $holders) . ")";
        }
        $sql .= " ORDER BY c.nome_empresa, d.nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function setorOptions(): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $sql = "SELECT s.id, s.nome, d.nome AS departamento_nome, d.cliente_id
                FROM setores s
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE 1=1";
        if (!Auth::isInstituto()) {
            $ids = Auth::allowedClientIds();
            if (empty($ids)) {
                return [];
            }
            $holders = [];
            foreach (array_values($ids) as $i => $id) {
                $key = 'ts' . $i;
                $holders[] = ':' . $key;
                $params[$key] = (int)$id;
            }
            $sql .= " AND d.cliente_id IN (" . implode(',', $holders) . ")";
        }
        $sql .= " ORDER BY d.nome, s.nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function funcaoOptions(): array
    {
        $pdo = Database::getConnection();
        $params = [];
        $sql = "SELECT f.id, f.nome, s.nome AS setor_nome, d.cliente_id
                FROM funcoes f
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE 1=1";
        if (!Auth::isInstituto()) {
            $ids = Auth::allowedClientIds();
            if (empty($ids)) {
                return [];
            }
            $holders = [];
            foreach (array_values($ids) as $i => $id) {
                $key = 'tf' . $i;
                $holders[] = ':' . $key;
                $params[$key] = (int)$id;
            }
            $sql .= " AND d.cliente_id IN (" . implode(',', $holders) . ")";
        }
        $sql .= " ORDER BY f.nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function departamentoBelongsToCliente(int $departamentoId, int $clienteId): bool
    {
        if ($departamentoId <= 0 || $clienteId <= 0) {
            return false;
        }
        if (!$this->canAccessCliente($clienteId)) {
            return false;
        }
        $pdo = Database::getConnection();
        $params = ['dep' => $departamentoId, 'cid' => $clienteId];
        $stmt = $pdo->prepare("SELECT d.id FROM departamentos d WHERE d.id = :dep AND d.cliente_id = :cid LIMIT 1");
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    private function setoresBelongToCliente(array $setorIds, int $clienteId): bool
    {
        $setorIds = array_values(array_unique(array_filter(array_map('intval', $setorIds))));
        if (empty($setorIds) || $clienteId <= 0) {
            return true;
        }
        if (!$this->canAccessCliente($clienteId)) {
            return false;
        }
        $pdo = Database::getConnection();
        $params = ['cid' => $clienteId];
        $holders = [];
        foreach ($setorIds as $i => $id) {
            $key = 's' . $i;
            $holders[] = ':' . $key;
            $params[$key] = $id;
        }
        $sql = "SELECT COUNT(*) FROM setores s
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE s.id IN (" . implode(',', $holders) . ")
                  AND d.cliente_id = :cid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === count($setorIds);
    }

    private function funcoesBelongToCliente(array $funcaoIds, int $clienteId): bool
    {
        $funcaoIds = array_values(array_unique(array_filter(array_map('intval', $funcaoIds))));
        if (empty($funcaoIds) || $clienteId <= 0) {
            return true;
        }
        if (!$this->canAccessCliente($clienteId)) {
            return false;
        }
        $pdo = Database::getConnection();
        $params = ['cid' => $clienteId];
        $holders = [];
        foreach ($funcaoIds as $i => $id) {
            $key = 'f' . $i;
            $holders[] = ':' . $key;
            $params[$key] = $id;
        }
        $sql = "SELECT COUNT(*) FROM funcoes f
                JOIN setores s ON s.id = f.setor_id
                JOIN departamentos d ON d.id = s.departamento_id
                WHERE f.id IN (" . implode(',', $holders) . ")
                  AND d.cliente_id = :cid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === count($funcaoIds);
    }

    private function usuarioOptions(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
        return $stmt->fetchAll() ?: [];
    }

    private function tipoTreinamentoOptions(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT DISTINCT tipo_treinamento FROM treinamentos WHERE tipo_treinamento IS NOT NULL AND TRIM(tipo_treinamento) <> '' ORDER BY tipo_treinamento");
        return array_values(array_filter(array_map(static fn(array $row): string => (string)$row['tipo_treinamento'], $stmt->fetchAll() ?: [])));
    }

    private function statusAtualOptions(): array
    {
        return ['ativo', 'inativo', 'afastado', 'desligado'];
    }

    private function eligibleFilters(): array
    {
        return [
            'setor_id' => (int)($_GET['setor_id'] ?? 0),
            'funcao_id' => (int)($_GET['funcao_id'] ?? 0),
            'data_admissao_inicio' => trim((string)($_GET['data_admissao_inicio'] ?? '')),
            'data_admissao_fim' => trim((string)($_GET['data_admissao_fim'] ?? '')),
            'status_atual' => trim((string)($_GET['status_atual'] ?? '')),
            'status_elegibilidade' => trim((string)($_GET['status_elegibilidade'] ?? '')),
        ];
    }

    private function dashboardFilters(): array
    {
        return [
            'periodo_inicio' => trim((string)($_GET['periodo_inicio'] ?? '')),
            'periodo_fim' => trim((string)($_GET['periodo_fim'] ?? '')),
            'setor_id' => (int)($_GET['setor_id'] ?? 0),
            'tipo_treinamento' => trim((string)($_GET['tipo_treinamento'] ?? '')),
            'instrutor' => trim((string)($_GET['instrutor'] ?? '')),
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

    private function isPost(): bool
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }
}
