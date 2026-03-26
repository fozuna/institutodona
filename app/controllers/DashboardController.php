<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ClienteModel;
use App\Models\AplicacaoModel;

class DashboardController extends BaseController
{
    private ClienteModel $clientes;
    private AplicacaoModel $aplicacoes;

    public function __construct()
    {
        $this->clientes = new ClienteModel();
        $this->aplicacoes = new AplicacaoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $user = $_SESSION['user'];
        $requestedCliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : null;
        $selectedCliente = $this->resolveScopedClienteId($requestedCliente);

        $clientes = $this->clientes->all();
        $kanbanData = [
            'Planejado' => [],
            'Em Andamento' => [],
            'Concluído' => [],
        ];

        $stats = $this->aplicacoes->statsByPilar($selectedCliente);
        $totalsByStatus = ['Planejado' => 0, 'Em Andamento' => 0, 'Concluído' => 0];
        foreach ($stats as $s) {
            $st = $s['status'];
            $totalsByStatus[$st] = ($totalsByStatus[$st] ?? 0) + (int)$s['total'];
        }

        if ($selectedCliente) {
            \App\Core\AuditLogger::log('dashboard_view_cliente', 'dashboard', null, ['cliente_id' => (int)$selectedCliente]);
            foreach ($this->aplicacoes->byCliente($selectedCliente) as $row) {
                $kanbanData[$row['status']][] = $row;
            }
        } else {
            \App\Core\AuditLogger::log('dashboard_view_global', 'dashboard', null, []);
            foreach ($this->aplicacoes->all() as $row) {
                $kanbanData[$row['status']][] = $row;
            }
        }

        $this->render('dashboard/kanban', compact('clientes', 'selectedCliente', 'kanbanData', 'stats', 'totalsByStatus', 'user'));
    }
}
