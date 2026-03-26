<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\UsuarioModel;
use App\Models\ClienteModel;
use App\Models\ConsultorModel;
use App\Models\UsuarioEmpresaModel;

class UsuariosController extends BaseController
{
    private const CLIENT_SCOPED_ROLES = ['cliente', 'cliente_admin', 'reader'];
    private UsuarioModel $usuarios;
    private ClienteModel $clientes;
    private ConsultorModel $consultores;
    private UsuarioEmpresaModel $usuarioEmpresas;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
        $this->clientes = new ClienteModel();
        $this->consultores = new ConsultorModel();
        $this->usuarioEmpresas = new UsuarioEmpresaModel();
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
            if (in_array((string)$u['tipo_acesso'], self::CLIENT_SCOPED_ROLES, true) && !empty($u['id_cliente'])) {
                $assigned = $this->usuarioEmpresas->allForUser((int)$u['id']);
                if (!empty($assigned)) {
                    $u['pertence'] = implode(', ', array_map(static fn(array $r): string => (string)$r['nome_empresa'], $assigned));
                } else {
                    $u['pertence'] = $mapClientes[(int)$u['id_cliente']] ?? 'Cliente';
                }
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
        $this->render('usuarios/create', ['clientes' => $clientes, 'consultores' => $consultores, 'selectedClientes' => []]);
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
        $tipo = $_POST['tipo_acesso'] ?? 'cliente_admin';
        $selectedClientes = $this->parseSelectedClientes($_POST);
        $idConsultor = $_POST['id_consultor'] ?? null;
        if (!$nome || !$email || !$senha) {
            http_response_code(400);
            echo 'Campos obrigatórios faltando';
            return;
        }
        if (in_array($tipo, self::CLIENT_SCOPED_ROLES, true) && empty($selectedClientes)) {
            http_response_code(400);
            echo 'Selecione ao menos uma empresa para o usuário';
            return;
        }
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $id = $this->usuarios->create([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $hash,
            'tipo_acesso' => $tipo,
            'id_cliente' => in_array($tipo, self::CLIENT_SCOPED_ROLES, true) ? (int)($selectedClientes[0] ?? 0) : null,
        ]);
        if (in_array($tipo, self::CLIENT_SCOPED_ROLES, true) && $id > 0) {
            $allAllowed = $this->usuarioEmpresas->syncForUser($id, $selectedClientes);
            if (empty($allAllowed)) {
                http_response_code(400);
                echo 'Nenhuma empresa elegível foi vinculada. Verifique filiais inativas ou com acesso restrito.';
                return;
            }
        } else {
            $this->usuarioEmpresas->syncForUser($id, []);
        }
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
        $selectedClientes = $item ? $this->usuarioEmpresas->selectedForUser((int)$item['id']) : [];
        if (empty($selectedClientes) && !empty($item['id_cliente'])) {
            $selectedClientes = [(int)$item['id_cliente']];
        }
        $this->render('usuarios/edit', ['item' => $item, 'clientes' => $clientes, 'consultores' => $consultores, 'selectedClientes' => $selectedClientes]);
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
        $tipo = $_POST['tipo_acesso'] ?? 'cliente_admin';
        $selectedClientes = $this->parseSelectedClientes($_POST);
        $senha = $_POST['senha'] ?? null;
        if (in_array($tipo, self::CLIENT_SCOPED_ROLES, true) && empty($selectedClientes)) {
            http_response_code(400);
            echo 'Selecione ao menos uma empresa para o usuário';
            return;
        }
        $this->usuarios->update($id, [
            'nome' => $nome,
            'email' => $email,
            'tipo_acesso' => $tipo,
            'id_cliente' => in_array($tipo, self::CLIENT_SCOPED_ROLES, true) ? (int)($selectedClientes[0] ?? 0) : null,
        ]);
        $this->usuarioEmpresas->syncForUser($id, in_array($tipo, self::CLIENT_SCOPED_ROLES, true) ? $selectedClientes : []);
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

    private function parseSelectedClientes(array $payload): array
    {
        $selected = $payload['id_clientes'] ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }
        if (empty($selected) && !empty($payload['id_cliente'])) {
            $selected = [$payload['id_cliente']];
        }
        $selected = array_values(array_unique(array_filter(array_map('intval', $selected), fn(int $id): bool => $id > 0)));
        sort($selected);
        return $selected;
    }
}
