<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\CronogramaModel;
use App\Models\CronogramaEventoModel;
use App\Models\ClienteModel;

class CronogramaController extends BaseController
{
    private CronogramaModel $cronogramas;
    private CronogramaEventoModel $eventos;

    public function __construct()
    {
        $this->cronogramas = new CronogramaModel();
        $this->eventos = new CronogramaEventoModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $items = $this->cronogramas->all();
        $this->render('cronograma/index', ['items' => $items]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $pref = (int)($_GET['id_cliente'] ?? 0);
        $this->render('cronograma/create', ['clientes' => $clientes, 'pref' => $pref]);
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'ano' => (int)($_POST['ano'] ?? date('Y')),
        ];
        if ($data['id_cliente'] && $data['ano']) {
            $id = $this->cronogramas->create($data);
            header('Location: index.php?route=cronograma/show&id=' . $id);
            return;
        }
        header('Location: index.php?route=cronograma/index');
    }

    public function selectCliente(): void
    {
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $this->render('cronograma/select_cliente', ['clientes' => $clientes]);
    }

    public function show(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $events = $this->eventos->byCronograma($id);

        // Monta grid por linha (tópico/unidade/atividade/responsável/modelo) x meses
        $grid = [];
        foreach ($events as $ev) {
            $key = implode('|', [
                $ev['topico'],
                $ev['unidade'] ?? '',
                $ev['atividade'],
                $ev['responsavel'] ?? '',
                $ev['modelo'] ?? '',
            ]);
            if (!isset($grid[$key])) {
                $grid[$key] = [
                    'topico' => $ev['topico'],
                    'unidade' => $ev['unidade'] ?? '',
                    'atividade' => $ev['atividade'],
                    'responsavel' => $ev['responsavel'] ?? '',
                    'modelo' => $ev['modelo'] ?? '',
                    'meses' => array_fill(1, 12, null),
                ];
            }
            $month = (int)date('n', strtotime($ev['data']));
            $grid[$key]['meses'][$month] = $ev; // armazena evento do mês
        }

        $this->render('cronograma/show', ['crono' => $crono, 'events' => $events, 'grid' => $grid]);
    }

    public function addEvento(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $data = [
            'data' => $_POST['data'] ?? null,
            'topico' => trim($_POST['topico'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'atividade' => trim($_POST['atividade'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'modelo' => $_POST['modelo'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        if ($idCronograma && $data['data'] && $data['topico'] && $data['atividade']) {
            $this->eventos->create($idCronograma, $data);
        }
        header('Location: index.php?route=cronograma/show&id=' . $idCronograma);
    }

    public function addEventoForm(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $this->render('cronograma/add_evento', ['crono' => $crono]);
    }

    public function updateEvento(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $data = [
            'data' => $_POST['data'] ?? null,
            'topico' => trim($_POST['topico'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'atividade' => trim($_POST['atividade'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'modelo' => $_POST['modelo'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        if ($id) { $this->eventos->update($id, $data); }
        header('Location: index.php?route=cronograma/show&id=' . $idCronograma);
    }

    public function deleteEvento(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $idCronograma = (int)($_GET['id_cronograma'] ?? 0);
        if ($id) { $this->eventos->delete($id); }
        header('Location: index.php?route=cronograma/show&id=' . $idCronograma);
    }
}
