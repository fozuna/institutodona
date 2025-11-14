<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Security;
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
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $csrf = $_POST['csrf'] ?? null;

        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $user = $this->usuarios->findByEmail($email);
        if ($user && password_verify($senha, $user['senha_hash'])) {
            Auth::login($user);
            header('Location: index.php?route=dashboard/index');
        } else {
            $this->render('auth/login', ['error' => 'Credenciais inválidas']);
        }
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: index.php?route=auth/login');
    }
}