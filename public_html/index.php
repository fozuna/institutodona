<?php
session_start();

require __DIR__ . '/../app/autoload.php';

use App\Controllers\MetodologiaController;
use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\ClientesController;
use App\Controllers\PilaresController;
use App\Controllers\AgendaController;
use App\Controllers\ConsultoresController;
use App\Controllers\AplicacoesController;

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
    case 'clientes/show':
        (new ClientesController())->show();
        break;
    case 'clientes/attach':
        (new ClientesController())->attach();
        break;
    case 'clientes/updateAplicacao':
        (new ClientesController())->updateAplicacao();
        break;
    case 'clientes/deleteAplicacao':
        (new ClientesController())->deleteAplicacao();
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
    case 'agenda/index':
        (new AgendaController())->index();
        break;
    case 'consultores/index':
        (new ConsultoresController())->index();
        break;
    case 'consultores/create':
        (new ConsultoresController())->create();
        break;
    case 'consultores/store':
        (new ConsultoresController())->store();
        break;
    case 'consultores/edit':
        (new ConsultoresController())->edit();
        break;
    case 'consultores/update':
        (new ConsultoresController())->update();
        break;
    case 'consultores/delete':
        (new ConsultoresController())->delete();
        break;
    case 'aplicacoes/show':
        (new AplicacoesController())->show();
        break;
    case 'aplicacoes/update':
        (new AplicacoesController())->update();
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
    case 'dashboard/index':
    default:
        (new DashboardController())->index();
        break;
}
