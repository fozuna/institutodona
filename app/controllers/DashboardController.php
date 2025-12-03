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
        $selectedCliente = null;

        if ($user['tipo_acesso'] === 'cliente') {
            $selectedCliente = (int)$user['id_cliente'];
        } elseif (isset($_GET['cliente'])) {
            $selectedCliente = (int)$_GET['cliente'];
        }

        $clientes = $this->clientes->all();
        $kanbanData = [
            'A Fazer' => [],
            'Em Andamento' => [],
            'Concluído' => [],
        ];

        $stats = $this->aplicacoes->statsByPilar($selectedCliente);
        $totalsByStatus = ['A Fazer' => 0, 'Em Andamento' => 0, 'Concluído' => 0];
        foreach ($stats as $s) {
            $st = $s['status'];
            $totalsByStatus[$st] = ($totalsByStatus[$st] ?? 0) + (int)$s['total'];
        }

        if ($selectedCliente) {
            foreach ($this->aplicacoes->byCliente($selectedCliente) as $row) {
                $kanbanData[$row['status']][] = $row;
            }
        } else {
            foreach ($this->aplicacoes->all() as $row) {
                $kanbanData[$row['status']][] = $row;
            }
        }

        $this->render('dashboard/kanban', compact('clientes', 'selectedCliente', 'kanbanData', 'stats', 'totalsByStatus', 'user'));
    }
}
