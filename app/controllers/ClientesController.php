<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\AplicacaoModel;

class ClientesController extends BaseController
{
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $items = $this->clientes->matrizes();
        $this->render('clientes/index', ['items' => $items]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $this->render('clientes/create');
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
        ];
        // Matriz/Filial
        $tipo = $_POST['tipo_unidade'] ?? 'matriz';
        $matrizId = isset($_POST['matriz_id']) ? (int)$_POST['matriz_id'] : null;
        $data['is_matriz'] = $tipo === 'matriz' ? 1 : 0;
        $data['matriz_id'] = $tipo === 'filial' ? $matrizId : null;
        $data['logo_path'] = null;
        if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $allow = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            $type = $_FILES['logo']['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if ($ext) {
                $dir = __DIR__ . '/../../public_html/assets/img/clients';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($data['nome_empresa']));
                $name = $safe ? $safe : 'cliente';
                $file = $name . '-' . uniqid() . '.' . $ext;
                $dest = $dir . '/' . $file;
                if (@move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $data['logo_path'] = 'assets/img/clients/' . $file;
                }
            }
        }
        if ($data['nome_empresa'] && $data['CNPJ']) {
            $id = $this->clientes->create($data);
            \App\Core\AuditLogger::log('create', 'cliente', $id, $data);
        }
        header('Location: index.php?route=clientes/index');
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->clientes->find($id);
        $this->render('clientes/edit', ['item' => $item]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $current = $this->clientes->find($id);
        if (!$current) { header('Location: index.php?route=clientes/index'); return; }

        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
        ];
        $tipo = $_POST['tipo_unidade'] ?? 'matriz';
        $matrizId = isset($_POST['matriz_id']) ? (int)$_POST['matriz_id'] : null;
        $data['is_matriz'] = $tipo === 'matriz' ? 1 : 0;
        $data['matriz_id'] = $tipo === 'filial' ? $matrizId : null;

        // Mantém logo atual por padrão
        $data['logo_path'] = $current['logo_path'];

        if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $allow = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            $type = $_FILES['logo']['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if ($ext) {
                $dir = __DIR__ . '/../../public_html/assets/img/clients';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($data['nome_empresa']));
                $name = $safe ? $safe : 'cliente';
                $file = $name . '-' . uniqid() . '.' . $ext;
                $dest = $dir . '/' . $file;
                if (@move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $data['logo_path'] = 'assets/img/clients/' . $file;
                }
            }
        }

        if (!$id) { header('Location: index.php?route=clientes/index'); return; }

        $currentAtivo = (int)($current['ativo'] ?? 1);
        $desiredAtivo = isset($_POST['ativo']) ? ((int)$_POST['ativo'] === 1 ? 1 : 0) : $currentAtivo;
        $statusReason = trim((string)($_POST['status_reason'] ?? ''));

        if ($currentAtivo !== $desiredAtivo) {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $res = $this->clientes->updateWithStatusAudit($id, $data, $desiredAtivo, $userId, $statusReason, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
            if (empty($res['ok'])) {
                $_SESSION['flash_error'] = (string)($res['message'] ?? 'Não foi possível alterar o status do cliente.');
                header('Location: index.php?route=clientes/edit&id=' . $id);
                return;
            }
            \App\Core\AuditLogger::log('update', 'cliente', $id, $data + ['ativo' => $desiredAtivo]);
            $_SESSION['flash_success'] = (string)($res['message'] ?? 'Cliente atualizado com sucesso.');
        } else {
            $this->clientes->update($id, $data);
            \App\Core\AuditLogger::log('update', 'cliente', $id, $data);
            $_SESSION['flash_success'] = 'Cliente atualizado com sucesso.';
        }
        header('Location: index.php?route=clientes/index');
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $this->clientes->delete($id); \App\Core\AuditLogger::log('delete', 'cliente', $id, []); }
        header('Location: index.php?route=clientes/index');
    }

    public function show(): void
    {
        $this->requireLogin();
        $requestedId = (int)($_GET['id'] ?? 0);
        $id = (int)($this->resolveScopedClienteId($requestedId > 0 ? $requestedId : null) ?? 0);
        if ($id <= 0) {
            header('Location: index.php?route=clientes/index');
            return;
        }
        \App\Core\AuditLogger::log('cliente_view', 'cliente', $id, ['route' => 'clientes/show']);
        $item = $this->clientes->find($id);
        if (!$item) {
            header('Location: index.php?route=clientes/index');
            return;
        }
        $matrizes = $this->clientes->matrizes();
        $filiais = ((int)($item['is_matriz'] ?? 1) === 1) ? $this->clientes->filiaisByMatriz($id) : [];
        $selectedFilialId = $this->resolveSelectedFilialId($id, $filiais);
        $scopeClienteIds = $this->buildClienteScopeIds($id, $filiais, $selectedFilialId);
        $clienteAlvoId = $selectedFilialId > 0 ? $selectedFilialId : $id;
        $planoPage = isset($_GET['plano_page']) ? max(1, (int)$_GET['plano_page']) : 1;
        [$planoPer, $planoPerValue] = $this->normalizePlanoPerSelection($_GET['plano_per'] ?? null);
        $planoStatusFilters = $_GET['plano_status'] ?? [];
        if (!is_array($planoStatusFilters)) {
            $planoStatusFilters = [$planoStatusFilters];
        }
        $planoStatusFilters = array_values(array_filter(array_map('trim', $planoStatusFilters)));
        $planoSearch = trim((string)($_GET['plano_q'] ?? ''));
        $taskModel = new \App\Models\PlanoAcaoTaskModel();
        $planoResumoBase = $taskModel->summarizeByClientesMulti($scopeClienteIds, [], '');
        $planoResumo = $taskModel->summarizeByClientesMulti($scopeClienteIds, $planoStatusFilters, $planoSearch);
        $planoDeadlines = $taskModel->summarizeDeadlineCountersByClientes($scopeClienteIds, [], '');
        $planoDatasetTotal = $taskModel->countByClientesMulti($scopeClienteIds, [], '');
        $planoFilteredTotal = $taskModel->countByClientesMulti($scopeClienteIds, $planoStatusFilters, $planoSearch);
        $planoTasks = $planoPerValue === null
            ? $taskModel->filteredByClientesMulti($scopeClienteIds, $planoStatusFilters, $planoSearch)
            : $taskModel->paginateByClientesMulti($scopeClienteIds, $planoPage, $planoPerValue, $planoStatusFilters, $planoSearch);
        $planoDisplayedCount = count($planoTasks);
        $planoTotalPages = $planoPerValue === null
            ? 1
            : max(1, (int)ceil($planoFilteredTotal / $planoPerValue));
        if ($planoPerValue !== null && $planoPage > $planoTotalPages) {
            $planoPage = $planoTotalPages;
            $planoTasks = $taskModel->paginateByClientesMulti($scopeClienteIds, $planoPage, $planoPerValue, $planoStatusFilters, $planoSearch);
            $planoDisplayedCount = count($planoTasks);
        }
        $this->render('clientes/show', [
            'item' => $item,
            'matrizes' => $matrizes,
            'filiais' => $filiais,
            'selectedFilialId' => $selectedFilialId,
            'clienteAlvoId' => $clienteAlvoId,
            'scopeClienteIds' => $scopeClienteIds,
            'planoTasks' => $planoTasks,
            'planoResumoBase' => $planoResumoBase,
            'planoResumo' => $planoResumo,
            'planoDeadlines' => $planoDeadlines,
            'planoDatasetTotal' => $planoDatasetTotal,
            'planoFilteredTotal' => $planoFilteredTotal,
            'planoDisplayedCount' => $planoDisplayedCount,
            'planoPage' => $planoPage,
            'planoPer' => $planoPer,
            'planoTotalPages' => $planoTotalPages,
            'planoStatusFilters' => $planoStatusFilters,
            'planoSearch' => $planoSearch,
        ]);
    }

    public function exportPlanos(): void
    {
        $this->requireLogin();
        if (!Auth::canExportPlanosAcao()) {
            http_response_code(403);
            echo 'Sem permissao para exportar planos de acao.';
            return;
        }
        $requestedId = (int)($_GET['id'] ?? 0);
        $id = (int)($this->resolveScopedClienteId($requestedId > 0 ? $requestedId : null) ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Cliente inválido para exportação.';
            return;
        }
        $item = $this->clientes->find($id);
        if (!$item) {
            http_response_code(400);
            echo 'Cliente inválido para exportação.';
            return;
        }
        $filiais = ((int)($item['is_matriz'] ?? 1) === 1) ? $this->clientes->filiaisByMatriz($id) : [];
        $selectedFilialId = $this->resolveSelectedFilialId($id, $filiais);
        $scopeClienteIds = $this->buildClienteScopeIds($id, $filiais, $selectedFilialId);
        $statusFilters = $_GET['plano_status'] ?? [];
        if (!is_array($statusFilters)) {
            $statusFilters = [$statusFilters];
        }
        $statusFilters = array_values(array_filter(array_map('trim', $statusFilters)));

        $taskModel = new \App\Models\PlanoAcaoTaskModel();
        $rows = $taskModel->filterForExportByClientes($scopeClienteIds, $statusFilters);
        if (empty($rows)) {
            http_response_code(400);
            echo 'Nenhum plano de ação encontrado para os filtros selecionados.';
            return;
        }
        $filename = 'planos_acao_cliente_' . $id . '_' . date('Ymd_His') . '.xlsx';
        $path = \App\Core\XlsxExport::exportPlanos($rows, $filename);
        \App\Core\AuditLogger::log('planoacao_export', 'pdca_tasks', null, [
            'cliente_id' => $id,
            'filial_id' => $selectedFilialId > 0 ? $selectedFilialId : null,
            'scope_cliente_ids' => $scopeClienteIds,
            'status_filters' => $statusFilters,
            'count' => count($rows),
            'file' => $filename,
        ]);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        @unlink($path);
        exit;
    }

    private function resolveSelectedFilialId(int $clienteId, array $filiais): int
    {
        $sessionKey = 'clientes_show_filial_' . $clienteId;
        $selected = array_key_exists('filial_id', $_GET)
            ? (int)$_GET['filial_id']
            : (int)($_SESSION[$sessionKey] ?? 0);

        $validIds = array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $filiais);
        if ($selected > 0 && !in_array($selected, $validIds, true)) {
            $selected = 0;
        }
        $_SESSION[$sessionKey] = $selected;
        return $selected;
    }

    private function buildClienteScopeIds(int $clienteId, array $filiais, int $selectedFilialId): array
    {
        if ($selectedFilialId > 0) {
            return [$selectedFilialId];
        }
        $ids = [$clienteId];
        foreach ($filiais as $filial) {
            $ids[] = (int)($filial['id'] ?? 0);
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function normalizePlanoPerSelection($raw): array
    {
        $raw = trim((string)($raw ?? '20'));
        if ($raw === '' || $raw === '20') {
            return ['20', 20];
        }
        if (in_array($raw, ['10', '25', '50', '100'], true)) {
            return [$raw, (int)$raw];
        }
        if (in_array(strtolower($raw), ['all', 'todos'], true)) {
            return ['all', null];
        }
        return ['20', 20];
    }

    public function storeFilial(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $matrizId = (int)($_POST['matriz_id'] ?? 0);
        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
            'is_matriz' => 0,
            'matriz_id' => $matrizId ?: null,
        ];
        if ($data['nome_empresa'] && $data['CNPJ'] && $matrizId) {
            $this->clientes->create($data);
        }
        header('Location: index.php?route=clientes/show&id=' . $matrizId);
    }

    public function attach(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idCliente = (int)($this->resolveScopedClienteId((int)($_POST['id_cliente'] ?? 0)) ?? 0);
        $idMetodologia = (int)($_POST['id_metodologia'] ?? 0);
        $status = $_POST['status'] ?? 'Planejado';
        $consultorId = isset($_POST['consultor_id']) ? (int)$_POST['consultor_id'] : null;
        $dataPrevista = $_POST['data_prevista'] ?? null;
        $colabIds = isset($_POST['colaborador_ids']) ? (array)$_POST['colaborador_ids'] : [];
        $colabIds = array_values(array_filter(array_map('intval', $colabIds)));
        if (empty($colabIds)) { http_response_code(400); echo 'Selecione ao menos um Colaborador'; return; }
        if (in_array($status, ['Em Andamento','Concluído'], true) && (!$consultorId || $consultorId === 0)) {
            http_response_code(400); echo 'Selecione um consultor para iniciar a tarefa'; return;
        }
        if ($idCliente && $idMetodologia) {
            $aplId = (new AplicacaoModel())->create($idCliente, $idMetodologia, $status, $consultorId, $dataPrevista);
            (new AplicacaoModel())->addColaboradores($aplId, $colabIds);
            \App\Core\AuditLogger::log('create', 'aplicacao', $aplId, ['id_cliente'=>$idCliente,'id_metodologia'=>$idMetodologia,'status'=>$status,'consultor_id'=>$consultorId,'data_prevista'=>$dataPrevista,'colabs'=>$colabIds]);
        }
        header('Location: index.php?route=clientes/show&id=' . $idCliente);
    }

    public function updateAplicacao(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idCliente = (int)($this->resolveScopedClienteId((int)($_POST['id_cliente'] ?? 0)) ?? 0);
        $idAplicacao = (int)($_POST['id_aplicacao'] ?? 0);
        $status = $_POST['status'] ?? 'Planejado';
        $dataPrevista = $_POST['data_prevista'] ?? null;
        $consultorId = isset($_POST['consultor_id']) ? (int)$_POST['consultor_id'] : null;
        $colabIds = isset($_POST['colaborador_ids']) ? (array)$_POST['colaborador_ids'] : [];
        $colabIds = array_values(array_filter(array_map('intval', $colabIds)));
        if (in_array($status, ['Em Andamento','Concluído'], true) && (!$consultorId || $consultorId === 0)) {
            http_response_code(400); echo 'Selecione um consultor para iniciar/avançar a tarefa'; return;
        }
        if ($idAplicacao) {
            (new AplicacaoModel())->updateStatus($idAplicacao, $status);
            (new AplicacaoModel())->updateSchedule($idAplicacao, $dataPrevista, $consultorId);
            if (!empty($colabIds)) { (new AplicacaoModel())->setColaboradores($idAplicacao, $colabIds); }
            \App\Core\AuditLogger::log('update', 'aplicacao', $idAplicacao, ['status'=>$status,'data_prevista'=>$dataPrevista,'consultor_id'=>$consultorId,'colabs'=>$colabIds]);
        }
        header('Location: index.php?route=clientes/show&id=' . $idCliente);
    }

    public function deleteAplicacao(): void
    {
        $this->requireRole('instituto');
        $idCliente = (int)($this->resolveScopedClienteId((int)($_GET['id_cliente'] ?? 0)) ?? 0);
        $idAplicacao = (int)($_GET['id_aplicacao'] ?? 0);
        if ($idAplicacao) {
            (new AplicacaoModel())->delete($idAplicacao);
            \App\Core\AuditLogger::log('delete', 'aplicacao', $idAplicacao, ['id_cliente'=>$idCliente]);
        }
        header('Location: index.php?route=clientes/show&id=' . $idCliente);
    }
}
