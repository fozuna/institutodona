<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ClientesController;
use App\Database\Database;
use App\Models\PlanoAcaoTaskModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$tasks = new PlanoAcaoTaskModel();
$clienteIds = [];
$taskIds = [];
$prefix = 'render-pag-' . uniqid('', true);

try {
    $stmtCliente = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,1,NULL)');
    $cnpjBase = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    $cnpjFmt = substr($cnpjBase, 0, 2) . '.' . substr($cnpjBase, 2, 3) . '.' . substr($cnpjBase, 5, 3) . '/' . substr($cnpjBase, 8, 4) . '-' . substr($cnpjBase, 12, 2);
    $stmtCliente->execute([
        'n' => 'Cliente Render Paginacao ' . uniqid('', true),
        'c' => $cnpjFmt,
        'ct' => 'Contato',
    ]);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    for ($i = 1; $i <= 30; $i++) {
        $status = $i <= 12 ? 'Pendente' : 'Planejado';
        $taskIds[] = $tasks->create([
            'id_cliente' => $clienteId,
            'titulo' => $prefix . ' tarefa ' . $i,
            'status' => $status,
            'progresso' => 0,
            'responsavel' => 'Resp ' . $i,
        ]);
    }

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $controller = new ClientesController();

    $_GET = [
        'route' => 'clientes/show',
        'id' => $clienteId,
        'plano_status' => ['Pendente'],
        'plano_per' => 'all',
    ];
    ob_start();
    $controller->show();
    $htmlAll = (string)ob_get_clean();

    if (!str_contains($htmlAll, 'Total disponível: 30')
        || !str_contains($htmlAll, 'Filtrados: 12')
        || !str_contains($htmlAll, 'Exibindo: 12')
        || !str_contains($htmlAll, 'Modo: Todos os itens')) {
        failFast('Modo todos deveria exibir totais base, filtrado e exibido sem paginação');
    }
    ok('Render em modo todos mantém separação entre dataset e filtro');

    $_GET = [
        'route' => 'clientes/show',
        'id' => $clienteId,
        'plano_status' => ['Pendente'],
        'plano_per' => '10',
        'plano_page' => '2',
    ];
    ob_start();
    $controller->show();
    $htmlPage2 = (string)ob_get_clean();

    if (!str_contains($htmlPage2, 'Total filtrado: 12')
        || !str_contains($htmlPage2, 'plano_per=10')
        || !str_contains($htmlPage2, 'plano_status%5B0%5D=Pendente')) {
        failFast('Links de paginação deveriam preservar filtros ativos e tamanho de página');
    }
    ok('Paginação preserva estado dos filtros');

    echo "Cliente planos paginacao render smoke passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if (!empty($taskIds)) {
        $pdo->exec('DELETE FROM pdca_tasks WHERE id IN (' . implode(',', array_map('intval', array_filter($taskIds))) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
}
