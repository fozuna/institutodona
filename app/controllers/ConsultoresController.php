<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ConsultorModel;

class ConsultoresController extends BaseController
{
    private ConsultorModel $consultores;

    public function __construct()
    {
        $this->consultores = new ConsultorModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $items = $this->consultores->all();
        $this->render('consultores/index', ['items' => $items]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $this->render('consultores/create');
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'nome' => trim($_POST['nome'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'telefone' => trim($_POST['telefone'] ?? ''),
            'especialidade' => trim($_POST['especialidade'] ?? ''),
            'senioridade' => $_POST['senioridade'] ?? null,
            'cidade' => trim($_POST['cidade'] ?? ''),
            'estado' => strtoupper(substr(trim($_POST['estado'] ?? ''), 0, 2)),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
        if ($data['nome'] && $data['email']) {
            $this->consultores->create($data);
        }
        header('Location: index.php?route=consultores/index');
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->consultores->find($id);
        $this->render('consultores/edit', ['item' => $item]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'nome' => trim($_POST['nome'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'telefone' => trim($_POST['telefone'] ?? ''),
            'especialidade' => trim($_POST['especialidade'] ?? ''),
            'senioridade' => $_POST['senioridade'] ?? null,
            'cidade' => trim($_POST['cidade'] ?? ''),
            'estado' => strtoupper(substr(trim($_POST['estado'] ?? ''), 0, 2)),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
        if ($id) { $this->consultores->update($id, $data); }
        header('Location: index.php?route=consultores/index');
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $this->consultores->delete($id); }
        header('Location: index.php?route=consultores/index');
    }
}
