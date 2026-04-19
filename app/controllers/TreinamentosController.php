<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Security;
use App\Database\Database;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;

class TreinamentosController extends BaseController
{
    private TreinamentoModel $model;
    private TreinamentoAgendaModel $agendaModel;

    public function __construct()
    {
        $this->model = new TreinamentoModel();
        $this->agendaModel = new TreinamentoAgendaModel();
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
        $this->render('treinamentos/dashboard', [
            'pageTitle' => 'Dashboard de Treinamentos',
            'dashboard' => $this->model->dashboard(),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->renderForm('treinamentos/create', [
            'nome' => '',
            'objetivo' => '',
            'publico' => '',
            'carga_horaria' => '',
            'departamento_id' => 0,
            'periodicidade' => 'avulso',
            'fornecedor' => '',
            'setor_ids' => [],
            'funcao_ids' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
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
        $this->requireLogin();
        $item = $this->findOrRedirect((int)($_GET['id'] ?? 0));
        if (!$item) {
            return;
        }
        $this->renderForm('treinamentos/edit', $item);
    }

    public function update(): void
    {
        $this->requireLogin();
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
        $this->requireLogin();
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
        $this->render('treinamentos/show', [
            'pageTitle' => 'Treinamento',
            'item' => $item,
            'linked' => $this->model->linkedColaboradores((int)$item['id']),
            'availableColaboradores' => $this->model->availableColaboradores((int)$item['id']),
            'agendas' => $this->agendaModel->listByTreinamento((int)$item['id']),
            'pendingParticipants' => $this->agendaModel->pendingParticipantsForTreinamento((int)$item['id']),
            'alerts' => $this->model->pendingAlerts((int)$item['id']),
            'unidades' => $this->clienteOptions(),
            'usuarios' => $this->usuarioOptions(),
        ]);
    }

    public function addColaboradores(): void
    {
        $this->requireLogin();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $treinamentoId = (int)($_POST['treinamento_id'] ?? 0);
        $ids = $_POST['colaborador_ids'] ?? [];
        $this->model->syncColaboradores($treinamentoId, is_array($ids) ? $ids : [$ids]);
        $_SESSION['flash_success'] = 'Colaboradores vinculados ao treinamento.';
        $this->redirect('index.php?route=treinamentos/show&id=' . $treinamentoId);
    }

    public function removeColaborador(): void
    {
        $this->requireLogin();
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
        $this->requireLogin();
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
        $this->requireLogin();
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
        $this->requireLogin();
        if (!$this->isPost() || !Security::verifyCsrf($_POST['csrf'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $agendaId = (int)($_POST['agenda_id'] ?? 0);
        $this->agendaModel->savePresence($agendaId, $_POST['presenca'] ?? [], $_POST['certificado_emitido'] ?? []);
        $_SESSION['flash_success'] = 'Lista de presença atualizada.';
        $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
    }

    public function certificado(): void
    {
        $this->requireLogin();
        $agendaId = (int)($_GET['agenda_id'] ?? 0);
        $colaboradorId = (int)($_GET['colaborador_id'] ?? 0);
        $agenda = $this->agendaModel->find($agendaId);
        if (!$agenda) {
            $_SESSION['flash_error'] = 'Agendamento não encontrado.';
            $this->redirect('index.php?route=treinamentos/index');
            return;
        }
        $participant = null;
        foreach ($this->agendaModel->participants($agendaId) as $row) {
            if ((int)$row['colaborador_id'] === $colaboradorId) {
                $participant = $row;
                break;
            }
        }
        if (!$participant) {
            $_SESSION['flash_error'] = 'Participante não encontrado.';
            $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
            return;
        }
        if (empty($participant['presenca']) && empty($participant['certificado_emitido'])) {
            $_SESSION['flash_error'] = 'Marque a presença antes de emitir o certificado.';
            $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
            return;
        }
        if (!$this->agendaModel->issueCertificate($agendaId, $colaboradorId)) {
            $_SESSION['flash_error'] = 'Não foi possível emitir o certificado para este participante.';
            $this->redirect('index.php?route=treinamentos/presenca&agenda_id=' . $agendaId);
            return;
        }
        $participant['certificado_emitido'] = 1;
        $this->render('treinamentos/certificado', [
            'agenda' => $agenda,
            'participant' => $participant,
        ]);
    }

    private function renderForm(string $view, array $values, array $errors = []): void
    {
        $this->render($view, [
            'pageTitle' => str_contains($view, 'edit') ? 'Editar Treinamento' : 'Novo Treinamento',
            'values' => $values,
            'errors' => $errors,
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
            'departamento_id' => (int)($_POST['departamento_id'] ?? 0),
            'periodicidade' => trim((string)($_POST['periodicidade'] ?? 'avulso')),
            'fornecedor' => trim((string)($_POST['fornecedor'] ?? '')),
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
        if ((int)$payload['departamento_id'] <= 0) {
            $errors['departamento_id'] = 'Selecione um departamento.';
        }
        if ($payload['carga_horaria'] !== '' && !is_numeric($payload['carga_horaria'])) {
            $errors['carga_horaria'] = 'Informe uma carga horária numérica.';
        }
        if (!isset(TreinamentoModel::periodicidadeOptions()[$payload['periodicidade']])) {
            $errors['periodicidade'] = 'Periodicidade inválida.';
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
        $sql = "SELECT d.id, d.nome, c.nome_empresa
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
        $sql = "SELECT s.id, s.nome, d.nome AS departamento_nome
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
        $sql = "SELECT f.id, f.nome, s.nome AS setor_nome
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

    private function usuarioOptions(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
        return $stmt->fetchAll() ?: [];
    }

    private function isPost(): bool
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }
}
