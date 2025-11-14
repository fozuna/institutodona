<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\PilarModel;

class PilaresController extends BaseController
{
    private PilarModel $pilares;

    public function __construct()
    {
        $this->pilares = new PilarModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $items = $this->pilares->all();
        $this->render('pilares/index', ['items' => $items]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $this->render('pilares/create');
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $nome = trim($_POST['nome'] ?? '');
        if ($nome !== '') {
            $this->pilares->create($nome);
        }
        header('Location: index.php?route=pilares/index');
        exit;
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->pilares->find($id);
        $this->render('pilares/edit', ['item' => $item]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        if ($id && $nome !== '') {
            $this->pilares->update($id, $nome);
        }
        header('Location: index.php?route=pilares/index');
        exit;
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->pilares->delete($id);
        }
        header('Location: index.php?route=pilares/index');
        exit;
    }
}