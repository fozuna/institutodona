<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\AplicacaoModel;
use App\Models\ConsultorModel;
use App\Models\FuncaoModel;

class AplicacoesController extends BaseController
{
    private AplicacaoModel $aplicacoes;

    public function __construct()
    {
        $this->aplicacoes = new AplicacaoModel();
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $consultores = (new ConsultorModel())->all();
        $metModel = new \App\Models\MetodologiaModel();
        $funModel = new FuncaoModel();
        $metodologias = $cliente ? $metModel->byCliente($cliente) : $metModel->all();
        $funcoes = $cliente ? $funModel->allByCliente($cliente) : [];
        $clientes = (new \App\Models\ClienteModel())->all();
        $this->render('aplicacoes/create', [
            'cliente' => $cliente,
            'clientes' => $clientes,
            'metodologias' => $metodologias,
            'funcoes' => $funcoes,
            'consultores' => $consultores,
        ]);
    }

    public function show(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $app = $this->aplicacoes->find($id);
        $consultores = (new ConsultorModel())->all();
        $funcoes = $app ? $this->aplicacoes->functionsForAplicacao((int)$app['id']) : [];
        $this->render('aplicacoes/show', [
            'app' => $app,
            'consultores' => $consultores,
            'funcoes' => $funcoes,
        ]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idAplicacao = (int)($_POST['id_aplicacao'] ?? 0);
        $status = $_POST['status'] ?? 'A Fazer';
        $dataPrevista = $_POST['data_prevista'] ?? null;
        $consultorId = isset($_POST['consultor_id']) ? (int)$_POST['consultor_id'] : null;
        $colabIds = isset($_POST['colaborador_ids']) ? (array)$_POST['colaborador_ids'] : [];
        $colabIds = array_values(array_filter(array_map('intval', $colabIds)));
        if ($idAplicacao) {
            $this->aplicacoes->updateStatus($idAplicacao, $status);
            $this->aplicacoes->updateSchedule($idAplicacao, $dataPrevista, $consultorId);
            if (!empty($colabIds)) {
                $this->aplicacoes->setColaboradores($idAplicacao, $colabIds);
            }
        }
        header('Location: index.php?route=aplicacoes/show&id=' . $idAplicacao);
    }
}
