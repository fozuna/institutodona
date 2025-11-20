<?php
session_start();

require __DIR__ . '/../app/autoload.php';

use App\Controllers\MetodologiaController;
use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\ClientesController;
use App\Controllers\PilaresController;
use App\Controllers\AvaliacaoController;
use App\Models\UsuarioModel;

$route = $_GET['route'] ?? 'auth/login';

// Router bem simples
switch ($route) {
    case 'metodologias/index':
        (new MetodologiaController())->index();
        break;
    case 'metodologias/create':
        (new MetodologiaController())->create();
        break;
    case 'metodologias/store':
        (new MetodologiaController())->store();
        break;
    case 'metodologias/edit':
        (new MetodologiaController())->edit();
        break;
    case 'metodologias/update':
        (new MetodologiaController())->update();
        break;
    case 'metodologias/delete':
        (new MetodologiaController())->delete();
        break;
    case 'clientes/index':
        (new ClientesController())->index();
        break;
    case 'clientes/create':
        (new ClientesController())->create();
        break;
    case 'clientes/store':
        (new ClientesController())->store();
        break;
    case 'clientes/edit':
        (new ClientesController())->edit();
        break;
    case 'clientes/update':
        (new ClientesController())->update();
        break;
    case 'clientes/delete':
        (new ClientesController())->delete();
        break;
    case 'pilares/index':
        (new PilaresController())->index();
        break;
    case 'pilares/create':
        (new PilaresController())->create();
        break;
    case 'pilares/store':
        (new PilaresController())->store();
        break;
    case 'pilares/edit':
        (new PilaresController())->edit();
        break;
    case 'pilares/update':
        (new PilaresController())->update();
        break;
    case 'pilares/delete':
        (new PilaresController())->delete();
        break;
    case 'auth/login':
        (new AuthController())->login();
        break;
    case 'auth/doLogin':
        (new AuthController())->doLogin();
        break;
    case 'auth/logout':
        (new AuthController())->logout();
        break;
    case 'avaliacao/create':
        (new AvaliacaoController())->create();
        break;
    case 'avaliacao/store':
        (new AvaliacaoController())->store();
        break;
    case 'dev/seedAdmin':
        $email = 'admin@institutodona.local';
        $model = new UsuarioModel();
        $existing = $model->findByEmail($email);
        if (!$existing) {
            $hash = password_hash('admin123789', PASSWORD_DEFAULT);
            $model->create([
                'nome' => 'Admin',
                'email' => $email,
                'senha_hash' => $hash,
                'tipo_acesso' => 'instituto',
                'id_cliente' => null,
            ]);
            echo 'OK';
        } else {
            echo 'EXISTS';
        }
        break;
    case 'dashboard/index':
    default:
        (new DashboardController())->index();
        break;
}
