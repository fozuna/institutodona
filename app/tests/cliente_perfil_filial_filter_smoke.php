<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ClientesController;
use App\Controllers\ColaboradoresController;
use App\Controllers\DepartamentosController;
use App\Controllers\FuncoesController;
use App\Controllers\SetoresController;
use App\Database\Database;
use App\Models\PlanoAcaoTaskModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$tasks = new PlanoAcaoTaskModel();
$suffix = 'perfil_filial_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$taskIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];

try {
    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $makeCnpj = static function () : string {
        $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
        return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
    };
    $insCli->execute(['n' => 'Matriz ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $matrizId = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizId;

    $insCli->execute(['n' => 'Filial A ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => $matrizId]);
    $filialAId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialAId;

    $insCli->execute(['n' => 'Filial B ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => $matrizId]);
    $filialBId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialBId;

    $taskIds[] = $tasks->create([
        'id_cliente' => $matrizId,
        'titulo' => 'Plano Matriz ' . $suffix,
        'status' => 'Planejado',
        'progresso' => 0,
    ]);
    $taskIds[] = $tasks->create([
        'id_cliente' => $filialAId,
        'titulo' => 'Plano Filial A ' . $suffix,
        'status' => 'Em Andamento',
        'progresso' => 50,
    ]);
    $taskIds[] = $tasks->create([
        'id_cliente' => $filialBId,
        'titulo' => 'Plano Filial B ' . $suffix,
        'status' => 'Pendente',
        'progresso' => 0,
    ]);

    $controller = new ClientesController();

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [
        'route' => 'clientes/show',
        'id' => $matrizId,
    ];
    ob_start();
    $controller->show();
    $htmlAgregado = (string)ob_get_clean();

    if (!str_contains($htmlAgregado, 'name="filial_id"')) {
        failFast('Perfil da matriz deveria exibir dropdown de filial');
    }
    if (!str_contains($htmlAgregado, 'Todas as filiais')) {
        failFast('Dropdown deveria oferecer opção agregada para toda a empresa');
    }
    if (!str_contains($htmlAgregado, 'Plano Matriz ' . $suffix)
        || !str_contains($htmlAgregado, 'Plano Filial A ' . $suffix)
        || !str_contains($htmlAgregado, 'Plano Filial B ' . $suffix)) {
        failFast('Sem filial selecionada a tela deveria agregar matriz e filiais');
    }
    ok('Perfil agrega dados de toda a empresa quando nenhuma filial é selecionada');

    $_GET = [
        'route' => 'clientes/show',
        'id' => $matrizId,
        'filial_id' => $filialAId,
    ];
    ob_start();
    $controller->show();
    $htmlFilial = (string)ob_get_clean();

    if (!str_contains($htmlFilial, 'Plano Filial A ' . $suffix)) {
        failFast('Perfil filtrado deveria exibir os dados da filial selecionada');
    }
    if (str_contains($htmlFilial, 'Plano Matriz ' . $suffix) || str_contains($htmlFilial, 'Plano Filial B ' . $suffix)) {
        failFast('Perfil filtrado não deveria exibir dados de outras unidades');
    }
    ok('Perfil restringe a listagem para a filial selecionada');

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:c)')
        ->execute(['n' => 'Departamento ' . $suffix, 'c' => $matrizId]);
    $depId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $depId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor ' . $suffix, 'd' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao ' . $suffix, 's' => $setorId]);
    $funcaoId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n,:e,:f,:c)')
        ->execute(['n' => 'Colaborador ' . $suffix, 'e' => 'colab.' . $suffix . '@test.local', 'f' => $funcaoId, 'c' => $matrizId]);
    $colabId = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colabId;

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $backHref = 'href="index.php?route=clientes/show&amp;id=' . $matrizId . '"';

    $_GET = ['route' => 'departamentos/index', 'cliente' => $matrizId];
    ob_start();
    (new DepartamentosController())->index();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Departamentos index deveria voltar para perfil do cliente');
    }
    ok('Voltar em Departamentos index ok');

    $_GET = ['route' => 'departamentos/create', 'cliente' => $matrizId];
    ob_start();
    (new DepartamentosController())->create();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Departamentos create deveria voltar para perfil do cliente');
    }
    ok('Voltar em Departamentos create ok');

    $_GET = ['route' => 'departamentos/edit', 'id' => $depId, 'cliente' => $matrizId];
    ob_start();
    (new DepartamentosController())->edit();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Departamentos edit deveria voltar para perfil do cliente');
    }
    ok('Voltar em Departamentos edit ok');

    $_GET = ['route' => 'setores/index', 'cliente' => $matrizId];
    ob_start();
    (new SetoresController())->index();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Setores index deveria voltar para perfil do cliente');
    }
    ok('Voltar em Setores index ok');

    $_GET = ['route' => 'setores/create', 'cliente' => $matrizId];
    ob_start();
    (new SetoresController())->create();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Setores create deveria voltar para perfil do cliente');
    }
    ok('Voltar em Setores create ok');

    $_GET = ['route' => 'setores/edit', 'id' => $setorId, 'cliente' => $matrizId];
    ob_start();
    (new SetoresController())->edit();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Setores edit deveria voltar para perfil do cliente');
    }
    ok('Voltar em Setores edit ok');

    $_GET = ['route' => 'funcoes/index', 'cliente' => $matrizId];
    ob_start();
    (new FuncoesController())->index();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Funcoes index deveria voltar para perfil do cliente');
    }
    ok('Voltar em Funcoes index ok');

    $_GET = ['route' => 'funcoes/create', 'cliente' => $matrizId];
    ob_start();
    (new FuncoesController())->create();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Funcoes create deveria voltar para perfil do cliente');
    }
    ok('Voltar em Funcoes create ok');

    $_GET = ['route' => 'funcoes/edit', 'id' => $funcaoId, 'cliente' => $matrizId];
    ob_start();
    (new FuncoesController())->edit();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Funcoes edit deveria voltar para perfil do cliente');
    }
    ok('Voltar em Funcoes edit ok');

    $_GET = ['route' => 'colaboradores/index', 'cliente' => $matrizId, 'page' => 1, 'per' => 10];
    ob_start();
    (new ColaboradoresController())->index();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Colaboradores index deveria voltar para perfil do cliente');
    }
    ok('Voltar em Colaboradores index ok');

    $_GET = ['route' => 'colaboradores/create', 'cliente' => $matrizId];
    ob_start();
    (new ColaboradoresController())->create();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Colaboradores create deveria voltar para perfil do cliente');
    }
    ok('Voltar em Colaboradores create ok');

    $_GET = ['route' => 'colaboradores/edit', 'id' => $colabId, 'cliente' => $matrizId];
    ob_start();
    (new ColaboradoresController())->edit();
    $html = (string)ob_get_clean();
    if (!str_contains($html, $backHref)) {
        failFast('Colaboradores edit deveria voltar para perfil do cliente');
    }
    ok('Voltar em Colaboradores edit ok');

    echo "Cliente perfil filial filter smoke passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if (!empty($colaboradorIds)) {
        $pdo->exec('DELETE FROM colaboradores WHERE id IN (' . implode(',', array_map('intval', array_filter($colaboradorIds))) . ')');
    }
    if (!empty($funcaoIds)) {
        $pdo->exec('DELETE FROM funcoes WHERE id IN (' . implode(',', array_map('intval', array_filter($funcaoIds))) . ')');
    }
    if (!empty($setorIds)) {
        $pdo->exec('DELETE FROM setores WHERE id IN (' . implode(',', array_map('intval', array_filter($setorIds))) . ')');
    }
    if (!empty($departamentoIds)) {
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', array_filter($departamentoIds))) . ')');
    }
    if (!empty($taskIds)) {
        $pdo->exec('DELETE FROM pdca_tasks WHERE id IN (' . implode(',', array_map('intval', array_filter($taskIds))) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
}
