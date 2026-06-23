<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuditLogger;
use App\Core\Security;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;

class DepartamentosController extends BaseController
{
    private DepartamentoModel $deps;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->deps = new DepartamentoModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $items = $cliente > 0 ? $this->deps->allByCliente($cliente) : $this->deps->all();
        $clientes = $this->clientes->all();
        $this->render('departamentos/index', ['items' => $items, 'clientes' => $clientes, 'selectedCliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $clientes = $this->clientes->all();
        if ($cliente <= 0 && !empty($clientes)) {
            $cliente = (int)($clientes[0]['id'] ?? 0);
        }
        $clientRootMap = [];
        foreach ($clientes as $clienteRow) {
            $clienteIdMap = (int)($clienteRow['id'] ?? 0);
            if ($clienteIdMap > 0) {
                $clientRootMap[$clienteIdMap] = $this->clientes->catalogRootIdFor($clienteIdMap);
            }
        }
        $selectedClienteIds = $cliente > 0 ? [$cliente] : [];
        $this->render('departamentos/create', [
            'clientes' => $clientes,
            'cliente' => $cliente,
            'clientRootMap' => $clientRootMap,
            'selectedClienteIds' => $selectedClienteIds,
            'compartilharTodasFiliais' => false,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $nome = trim($_POST['nome'] ?? '');
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $clienteIds = isset($_POST['cliente_ids']) && is_array($_POST['cliente_ids']) ? $_POST['cliente_ids'] : [];
        $compartilharTodasFiliais = isset($_POST['compartilhar_todas_filiais']) && (int)$_POST['compartilhar_todas_filiais'] === 1;
        if (!$nome || !$clienteId) {
            http_response_code(400);
            echo 'Campos obrigatórios faltando';
            return;
        }
        $id = $this->deps->create([
            'nome' => $nome,
            'cliente_id' => $clienteId,
            'cliente_ids' => $clienteIds,
            'compartilhar_todas_filiais' => $compartilharTodasFiliais ? 1 : 0,
        ]);
        AuditLogger::log('create', 'departamentos', $id ?: null, [
            'cliente_id' => $clienteId,
            'cliente_ids' => array_values(array_unique(array_map('intval', $clienteIds))),
            'compartilhar_todas_filiais' => $compartilharTodasFiliais,
        ]);
        header('Location: index.php?route=departamentos/index&cliente=' . $clienteId);
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->deps->find($id);
        $clientes = $this->clientes->all();
        $cliente = (int)($_GET['cliente'] ?? (($item['cliente_id'] ?? 0)));
        if ($cliente <= 0 && !empty($clientes)) {
            $cliente = (int)($clientes[0]['id'] ?? 0);
        }
        $clientRootMap = [];
        foreach ($clientes as $clienteRow) {
            $clienteIdMap = (int)($clienteRow['id'] ?? 0);
            if ($clienteIdMap > 0) {
                $clientRootMap[$clienteIdMap] = $this->clientes->catalogRootIdFor($clienteIdMap);
            }
        }
        $selectedClienteIds = $item ? ($item['cliente_ids'] ?? [$cliente]) : [$cliente];
        $this->render('departamentos/edit', [
            'item' => $item,
            'clientes' => $clientes,
            'cliente' => $cliente,
            'clientRootMap' => $clientRootMap,
            'selectedClienteIds' => $selectedClienteIds,
            'compartilharTodasFiliais' => !empty($item['compartilhar_todas_filiais']),
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $clienteIds = isset($_POST['cliente_ids']) && is_array($_POST['cliente_ids']) ? $_POST['cliente_ids'] : [];
        $compartilharTodasFiliais = isset($_POST['compartilhar_todas_filiais']) && (int)$_POST['compartilhar_todas_filiais'] === 1;
        $this->deps->update($id, [
            'nome' => $nome,
            'cliente_id' => $clienteId,
            'cliente_ids' => $clienteIds,
            'compartilhar_todas_filiais' => $compartilharTodasFiliais ? 1 : 0,
        ]);
        AuditLogger::log('update', 'departamentos', $id ?: null, [
            'cliente_id' => $clienteId,
            'cliente_ids' => array_values(array_unique(array_map('intval', $clienteIds))),
            'compartilhar_todas_filiais' => $compartilharTodasFiliais,
        ]);
        header('Location: index.php?route=departamentos/index&cliente=' . $clienteId);
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $dep = $this->deps->find($id);
        $this->deps->delete($id);
        $clienteId = $dep['cliente_id'] ?? 0;
        header('Location: index.php?route=departamentos/index' . ($clienteId ? '&cliente=' . (int)$clienteId : ''));
    }
}
