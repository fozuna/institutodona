<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\AplicacaoModel;
use App\Models\ConsultorModel;

class AplicacoesController extends BaseController
{
    private AplicacaoModel $aplicacoes;

    public function __construct()
    {
        $this->aplicacoes = new AplicacaoModel();
    }

    public function show(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $app = $this->aplicacoes->find($id);
        $consultores = (new ConsultorModel())->all();
        $this->render('aplicacoes/show', [
            'app' => $app,
            'consultores' => $consultores,
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
        if ($idAplicacao) {
            $this->aplicacoes->updateStatus($idAplicacao, $status);
            $this->aplicacoes->updateSchedule($idAplicacao, $dataPrevista, $consultorId);
        }
        header('Location: index.php?route=aplicacoes/show&id=' . $idAplicacao);
    }
}
