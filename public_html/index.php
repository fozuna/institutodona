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
use App\Controllers\CronogramaController;
use App\Controllers\PdcaController;
use App\Controllers\BibliotecaController;
use App\Models\UsuarioModel;
use App\Controllers\UsuariosController;
use App\Controllers\DepartamentosController;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;
use App\Controllers\SetoresController;
use App\Controllers\FuncoesController;
use App\Controllers\ColaboradoresController;
use App\Controllers\AvaliacoesController;
use App\Controllers\LogsController;
use App\Controllers\AboutController;

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
    case 'clientes/storeFilial':
        (new ClientesController())->storeFilial();
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
    case 'indicadores/index':
        (new \App\Controllers\IndicadoresController())->index();
        break;
    case 'indicadores/create':
        (new \App\Controllers\IndicadoresController())->create();
        break;
    case 'indicadores/store':
        (new \App\Controllers\IndicadoresController())->store();
        break;
    case 'indicadores/edit':
        (new \App\Controllers\IndicadoresController())->edit();
        break;
    case 'indicadores/update':
        (new \App\Controllers\IndicadoresController())->update();
        break;
    case 'indicadores/delete':
        (new \App\Controllers\IndicadoresController())->delete();
        break;
    case 'indicadores/charts':
        (new \App\Controllers\IndicadoresController())->charts();
        break;
    case 'indicadores/updateRealizado':
        (new \App\Controllers\IndicadoresController())->updateRealizado();
        break;
    case 'indicadores/realizado':
        (new \App\Controllers\IndicadoresController())->realizado();
        break;
    case 'indicadores/painel':
        (new \App\Controllers\IndicadoresController())->painel();
        break;
    case 'biblioteca/index':
        (new BibliotecaController())->index();
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
    case 'aplicacoes/create':
        (new AplicacoesController())->create();
        break;
    case 'aplicacoes/update':
        (new AplicacoesController())->update();
        break;
    case 'cronograma/index':
        (new CronogramaController())->index();
        break;
    case 'cronograma/selectCliente':
        (new CronogramaController())->selectCliente();
        break;
    case 'cronograma/create':
        (new CronogramaController())->create();
        break;
    case 'cronograma/store':
        (new CronogramaController())->store();
        break;
    case 'cronograma/show':
        (new CronogramaController())->show();
        break;
    case 'cronograma/addEvento':
        (new CronogramaController())->addEvento();
        break;
    case 'cronograma/addEventoForm':
        (new CronogramaController())->addEventoForm();
        break;
    case 'cronograma/updateEvento':
        (new CronogramaController())->updateEvento();
        break;
    case 'cronograma/deleteEvento':
        (new CronogramaController())->deleteEvento();
        break;
    case 'pdca/index':
        (new PdcaController())->index();
        break;
    case 'pdca/create':
        (new PdcaController())->create();
        break;
    case 'pdca/store':
        (new PdcaController())->store();
        break;
    case 'pdca/show':
        (new PdcaController())->show();
        break;
    case 'pdca/upsertMetric':
        (new PdcaController())->upsertMetric();
        break;
    case 'pdca/addCheck':
        (new PdcaController())->addCheck();
        break;
    case 'pdca/createAction':
        (new PdcaController())->createAction();
        break;
    case 'aplicacoes/set_status':
        (new AplicacoesController())->set_status();
        break;
    case 'aplicacoes/upload':
        (new AplicacoesController())->upload();
        break;
    case 'about/index':
        (new AboutController())->index();
        break;
    case 'aplicacoes/delete_update':
        (new AplicacoesController())->delete_update();
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
    case 'usuarios/index':
        (new UsuariosController())->index();
        break;
    case 'usuarios/create':
        (new UsuariosController())->create();
        break;
    case 'usuarios/store':
        (new UsuariosController())->store();
        break;
    case 'usuarios/edit':
        (new UsuariosController())->edit();
        break;
    case 'usuarios/update':
        (new UsuariosController())->update();
        break;
    case 'usuarios/delete':
        (new UsuariosController())->delete();
        break;
    case 'departamentos/index':
        (new DepartamentosController())->index();
        break;
    case 'departamentos/create':
        (new DepartamentosController())->create();
        break;
    case 'departamentos/store':
        (new DepartamentosController())->store();
        break;
    case 'departamentos/edit':
        (new DepartamentosController())->edit();
        break;
    case 'departamentos/update':
        (new DepartamentosController())->update();
        break;
    case 'departamentos/delete':
        (new DepartamentosController())->delete();
        break;
    case 'setores/index':
        (new SetoresController())->index();
        break;
    case 'setores/create':
        (new SetoresController())->create();
        break;
    case 'setores/store':
        (new SetoresController())->store();
        break;
    case 'setores/edit':
        (new SetoresController())->edit();
        break;
    case 'setores/update':
        (new SetoresController())->update();
        break;
    case 'setores/delete':
        (new SetoresController())->delete();
        break;
    case 'funcoes/index':
        (new FuncoesController())->index();
        break;
    case 'funcoes/create':
        (new FuncoesController())->create();
        break;
    case 'funcoes/store':
        (new FuncoesController())->store();
        break;
    case 'funcoes/edit':
        (new FuncoesController())->edit();
        break;
    case 'funcoes/update':
        (new FuncoesController())->update();
        break;
    case 'funcoes/delete':
        (new FuncoesController())->delete();
        break;
    case 'avaliacoes/index':
        (new AvaliacoesController())->index();
        break;
    case 'avaliacoes/create':
        (new AvaliacoesController())->create();
        break;
    case 'avaliacoes/store':
        (new AvaliacoesController())->store();
        break;
    case 'avaliacoes/show':
        (new AvaliacoesController())->show();
        break;
    case 'avaliacoes/pdca':
        (new AvaliacoesController())->pdca();
        break;
    case 'colaboradores/index':
        (new ColaboradoresController())->index();
        break;
    case 'colaboradores/create':
        (new ColaboradoresController())->create();
        break;
    case 'colaboradores/store':
        (new ColaboradoresController())->store();
        break;
    case 'colaboradores/edit':
        (new ColaboradoresController())->edit();
        break;
    case 'colaboradores/update':
        (new ColaboradoresController())->update();
        break;
    case 'colaboradores/delete':
        (new ColaboradoresController())->delete();
        break;
    case 'colaboradores/search':
        (new ColaboradoresController())->search();
        break;
    case 'setup/seedAdmin':
        $token = $_GET['t'] ?? '';
        $expected = getenv('SEED_TOKEN') ?: '';
        if (!$expected || !hash_equals($expected, $token)) {
            http_response_code(403);
            echo 'Token inválido';
            break;
        }
        $email = trim($_GET['email'] ?? '');
        $pass = $_GET['pass'] ?? '';
        if (!$email || !$pass) {
            http_response_code(400);
            echo 'Parâmetros faltando';
            break;
        }
        $model = new UsuarioModel();
        $existing = $model->findByEmail($email);
        if ($existing) {
            echo 'EXISTS';
            break;
        }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $model->create([
            'nome' => 'Admin',
            'email' => $email,
            'senha_hash' => $hash,
            'tipo_acesso' => 'instituto',
            'id_cliente' => null,
        ]);
        echo 'OK';
        break;
    case 'setup/seedDepartamentos':
        $token = $_GET['t'] ?? '';
        $expected = getenv('SEED_TOKEN') ?: '';
        if (!$expected || !hash_equals($expected, $token)) {
            http_response_code(403);
            echo 'Token inválido';
            break;
        }
        $names = [
            'PEÇAS','PRODUÇÃO','FINANCEIRO','DEPARTAMENTO PESSOAL','TI','COMPRAS','CONTABILIDADE',
            'CORPORATIVO','ADMINISTRATIVO','MANUTENÇÃO','QUALIDADE','SESMT','GERAL'
        ];
        $deps = new DepartamentoModel();
        $clientes = (new ClienteModel())->all();
        $pdo = \App\Database\Database::getConnection();
        foreach ($clientes as $c) {
            $cid = (int)$c['id'];
            foreach ($names as $n) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM departamentos WHERE cliente_id = :cid AND nome = :n');
                $stmt->execute(['cid' => $cid, 'n' => $n]);
                if ((int)$stmt->fetchColumn() === 0) {
                    try { $deps->create(['nome' => $n, 'cliente_id' => $cid]); } catch (\Throwable $e) {}
                }
            }
        }
        echo 'OK';
        break;
    case 'dashboard/index':
    case 'logs/index':
        if ($route === 'logs/index') { (new LogsController())->index(); break; }
    default:
        (new DashboardController())->index();
        break;
}
