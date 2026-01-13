<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\UsuarioModel;
use App\Models\ClienteModel;
use App\Models\ConsultorModel;

class UsuariosController extends BaseController
{
    private UsuarioModel $usuarios;
    private ClienteModel $clientes;
    private ConsultorModel $consultores;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
        $this->clientes = new ClienteModel();
        $this->consultores = new ConsultorModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $items = $this->usuarios->all();
        // Enriquecer com pertencimento
        $mapClientes = [];
        foreach ($this->clientes->all() as $c) {
            $mapClientes[(int)$c['id']] = $c['nome_empresa'];
        }
        $mapConsultoresEmailNome = [];
        foreach ($this->consultores->all() as $c) {
            $mapConsultoresEmailNome[$c['email']] = $c['nome'];
        }
        foreach ($items as &$u) {
            if ($u['tipo_acesso'] === 'cliente' && !empty($u['id_cliente'])) {
                $u['pertence'] = $mapClientes[(int)$u['id_cliente']] ?? 'Cliente';
            } elseif ($u['tipo_acesso'] === 'consultor') {
                $urow = $this->usuarios->find((int)$u['id']);
                $u['pertence'] = $mapConsultoresEmailNome[$urow['email']] ?? 'Consultor';
            } else {
                $u['pertence'] = 'Instituto';
            }
        }
        $this->render('usuarios/index', ['items' => $items, 'pageTitle' => 'Usuários']);
    }

    public function create(): void
    {
        $this->requireLogin();
        $clientes = $this->clientes->all();
        $consultores = $this->consultores->all();
        $this->render('usuarios/create', ['clientes' => $clientes, 'consultores' => $consultores]);
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
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $tipo = $_POST['tipo_acesso'] ?? 'cliente';
        $idCliente = $_POST['id_cliente'] ?? null;
        $idConsultor = $_POST['id_consultor'] ?? null;
        if (!$nome || !$email || !$senha) {
            http_response_code(400);
            echo 'Campos obrigatórios faltando';
            return;
        }
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $id = $this->usuarios->create([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $hash,
            'tipo_acesso' => $tipo,
            'id_cliente' => $tipo === 'cliente' ? ($idCliente ? (int)$idCliente : null) : null,
        ]);
        if ($tipo === 'consultor' && $idConsultor) {
            $this->consultores->linkUser((int)$idConsultor, $id);
        }
        header('Location: index.php?route=usuarios/index');
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->usuarios->find($id);
        $clientes = $this->clientes->all();
        $consultores = $this->consultores->all();
        $this->render('usuarios/edit', ['item' => $item, 'clientes' => $clientes, 'consultores' => $consultores]);
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
        $email = trim($_POST['email'] ?? '');
        $tipo = $_POST['tipo_acesso'] ?? 'cliente';
        $idCliente = $_POST['id_cliente'] ?? null;
        $senha = $_POST['senha'] ?? null;
        $this->usuarios->update($id, [
            'nome' => $nome,
            'email' => $email,
            'tipo_acesso' => $tipo,
            'id_cliente' => $tipo === 'cliente' ? ($idCliente ? (int)$idCliente : null) : null,
        ]);
        if ($senha) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $this->usuarios->updatePassword($id, $hash);
        }
        header('Location: index.php?route=usuarios/index');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $this->usuarios->delete($id);
        header('Location: index.php?route=usuarios/index');
    }
}
