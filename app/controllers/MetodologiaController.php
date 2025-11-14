<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\MetodologiaModel;
use App\Models\PilarModel;

class MetodologiaController extends BaseController
{
    private MetodologiaModel $model;
    private PilarModel $pilares;

    public function __construct()
    {
        $this->model = new MetodologiaModel();
        $this->pilares = new PilarModel();
    }

    public function index(): void
    {
        $metodologias = $this->model->all();
        $this->render('metodologias/index', compact('metodologias'));
    }

    public function create(): void
    {
        $pilares = $this->pilares->all();
        $this->render('metodologias/create', compact('pilares'));
    }

    public function store(): void
    {
        $idPilar = (int)($_POST['id_pilar'] ?? 0);
        $itemPilar = trim($_POST['item_pilar'] ?? '');
        if ($idPilar && $itemPilar !== '') {
            $this->model->create(['id_pilar' => $idPilar, 'item_pilar' => $itemPilar]);
        }
        header('Location: index.php?route=metodologias/index');
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $metodologia = $this->model->find($id);
        $pilares = $this->pilares->all();
        $this->render('metodologias/edit', compact('metodologia', 'pilares'));
    }

    public function update(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $idPilar = (int)($_POST['id_pilar'] ?? 0);
        $itemPilar = trim($_POST['item_pilar'] ?? '');
        if ($id && $idPilar && $itemPilar !== '') {
            $this->model->update($id, ['id_pilar' => $idPilar, 'item_pilar' => $itemPilar]);
        }
        header('Location: index.php?route=metodologias/index');
    }

    public function delete(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->model->delete($id);
        }
        header('Location: index.php?route=metodologias/index');
    }
}