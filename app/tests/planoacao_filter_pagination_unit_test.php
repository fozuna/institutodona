<?php
require __DIR__ . '/../autoload.php';

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
$model = new PlanoAcaoTaskModel();
$clienteIds = [];
$taskPrefix = 'pag-filter-' . uniqid('', true);
$taskIds = [];

try {
    $stmtCliente = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,1,NULL)');
    $cnpjBase = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    $cnpjFmt = substr($cnpjBase, 0, 2) . '.' . substr($cnpjBase, 2, 3) . '.' . substr($cnpjBase, 5, 3) . '/' . substr($cnpjBase, 8, 4) . '-' . substr($cnpjBase, 12, 2);
    $stmtCliente->execute([
        'n' => 'Cliente Paginacao ' . uniqid('', true),
        'c' => $cnpjFmt,
        'ct' => 'Contato',
    ]);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    for ($i = 1; $i <= 120; $i++) {
        $status = ($i % 3 === 0) ? 'Concluído' : (($i % 2 === 0) ? 'Em Andamento' : 'Pendente');
        $titulo = $taskPrefix . ' ' . (($i % 5 === 0) ? 'alpha ' : 'beta ') . $i;
        $taskIds[] = $model->create([
            'id_cliente' => $clienteId,
            'titulo' => $titulo,
            'status' => $status,
            'progresso' => $status === 'Concluído' ? 100 : 0,
            'responsavel' => 'Resp ' . $i,
        ]);
    }

    $datasetTotal = $model->countByClientesMulti([$clienteId], [], '');
    $filteredDone = $model->countByClientesMulti([$clienteId], ['Concluído'], '');
    $filteredSearch = $model->countByClientesMulti([$clienteId], [], 'alpha');
    $pageDone = $model->paginateByClientesMulti([$clienteId], 1, 10, ['Concluído'], '');
    $allDoneStart = microtime(true);
    $allDone = $model->filteredByClientesMulti([$clienteId], ['Concluído'], '');
    $allDoneElapsed = microtime(true) - $allDoneStart;
    $pageSearch = $model->paginateByClientesMulti([$clienteId], 2, 10, [], 'alpha');

    if ($datasetTotal !== 120) {
        failFast('Dataset total deveria permanecer em 120 registros');
    }
    ok('Contagem base independente dos filtros');

    if ($filteredDone !== count($allDone)) {
        failFast('Modo todos deveria retornar exatamente o conjunto filtrado completo');
    }
    ok('Modo todos respeita apenas o resultado filtrado');

    if (count($pageDone) !== 10 || $filteredDone <= 10) {
        failFast('Paginação deveria operar sobre o conjunto já filtrado');
    }
    ok('Paginação atua sobre dataset filtrado');

    $expectedPageSearchCount = max(0, min(10, $filteredSearch - 10));
    if ($filteredSearch <= 10 || count($pageSearch) !== $expectedPageSearchCount) {
        failFast('Busca textual deveria paginar apenas a fatia exibida');
    }
    ok('Busca textual também mantém separação entre total filtrado e página exibida');

    if ($allDoneElapsed > 5.0) {
        failFast('Carregamento em modo todos excedeu limite aceitável para o teste');
    }
    ok('Performance aceitável em modo todos');

    echo "PlanoAcao filter pagination unit test passed.\n";
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
