<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Security;
use App\Core\AuditLogger;
use App\Core\JwtService;
use App\Models\UsuarioModel;

class AuthController extends BaseController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    public function login(): void
    {
        $this->render('auth/login');
    }

    public function doLogin(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: index.php?route=auth/login');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $csrf = $_POST['csrf'] ?? null;

        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            AuditLogger::log('auth_login_csrf_fail', 'usuario', null, [
                'has_session_csrf' => isset($_SESSION['csrf']),
            ]);
            $this->render('auth/login', ['error' => 'Sessão expirada ou CSRF inválido. Tente novamente.']);
            return;
        }

        $user = $this->usuarios->findByEmail($email);
        if ($user && password_verify($senha, $user['senha_hash'])) {
            Auth::login($user);
            AuditLogger::log('auth_login_success', 'usuario', (int)$user['id'], [
                'tipo_acesso' => $user['tipo_acesso'] ?? null,
                'cliente_id' => isset($user['id_cliente']) ? (int)$user['id_cliente'] : null,
                'scope_clientes' => Auth::allowedClientIds(),
            ]);
            header('Location: index.php?route=dashboard/index');
        } else {
            AuditLogger::log('auth_login_fail', 'usuario', null, ['email' => $email]);
            $this->render('auth/login', ['error' => 'Credenciais inválidas']);
        }
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: index.php?route=auth/login');
    }

    public function apiToken(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $email = trim((string)($_POST['email'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');
        if ($email === '' || $senha === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Informe e-mail e senha'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $user = $this->usuarios->findByEmail($email);
        if (!$user || !password_verify($senha, $user['senha_hash'])) {
            AuditLogger::log('auth_token_fail', 'usuario', null, ['email' => $email]);
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Credenciais inválidas'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $token = JwtService::issue($user, 28800);
        AuditLogger::log('auth_token_issue', 'usuario', (int)$user['id'], ['tipo_acesso' => $user['tipo_acesso'] ?? null]);
        echo json_encode([
            'success' => true,
            'token_type' => 'Bearer',
            'expires_in' => 28800,
            'access_token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'tipo_acesso' => $user['tipo_acesso'],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
}
