<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\ManualModel;

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();

// Achado na Sprint 01 (Item 04): este teste era um script linear, sem
// try/finally nem register_shutdown_function - se o processo fosse
// interrompido (timeout, kill, crash) antes do bloco de limpeza no final
// do arquivo, as fixtures ficavam orfas no banco permanentemente (foi
// exatamente o que causou o residuo manuais.id=27 datado de 2026-07-19,
// que colidiu com um id de Ata em execucoes futuras). Corrigido para o
// mesmo padrao ja usado nos demais testes desta sessao: os ids criados
// sao acumulados em $cleanup conforme surgem, e a limpeza roda via
// register_shutdown_function - executa mesmo que uma assercao futura
// chame exit()/uma excecao interrompa o script no meio.
$cleanup = ['manual_ids' => [], 'departamento_ids' => [], 'cliente_ids' => []];
register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        foreach ($cleanup['manual_ids'] as $id) {
            $pdo->prepare('DELETE FROM manuais WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['departamento_ids'] as $id) {
            $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['cliente_ids'] as $id) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]);
        }
    } catch (\Throwable $e) {
    }
});

$empresaId = (int)$pdo->query('SELECT id FROM clientes ORDER BY id ASC LIMIT 1')->fetchColumn();
$stmt = $pdo->prepare('SELECT id FROM departamentos WHERE cliente_id = :cid ORDER BY id ASC LIMIT 1');
$stmt->execute(['cid' => $empresaId]);
$departamentoId = (int)$stmt->fetchColumn();
$createdEmpresa = false;
$createdDepartamento = false;

if ($empresaId <= 0 || $departamentoId <= 0) {
    if ($empresaId <= 0) {
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
        $stmt->execute([
            'nome' => 'Cliente Smoke Manual ' . $suffix,
            'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
            'contato' => 'Smoke',
        ]);
        $empresaId = (int)$pdo->lastInsertId();
        $createdEmpresa = true;
        $cleanup['cliente_ids'][] = $empresaId;
    }
    if ($departamentoId <= 0) {
        $stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
        $stmt->execute([
            'nome' => 'Departamento Smoke',
            'cid' => $empresaId,
        ]);
        $departamentoId = (int)$pdo->lastInsertId();
        $createdDepartamento = true;
        $cleanup['departamento_ids'][] = $departamentoId;
    }
}

$model = new ManualModel();
$prefix = 'Manual Smoke ' . uniqid('', true);
$id = $model->create([
    'empresa_id' => $empresaId,
    'departamento_id' => $departamentoId,
    'nome' => $prefix,
    'descricao' => 'Descricao smoke',
    'arquivo' => 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/fake.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 123,
    'usuario_id' => 1,
]);
$cleanup['manual_ids'][] = $id;
$sortPrefix = 'Manual Sort ' . uniqid('', true);
$sortAId = $model->create([
    'empresa_id' => $empresaId,
    'departamento_id' => $departamentoId,
    'nome' => $sortPrefix . ' A',
    'descricao' => 'Alpha',
    'arquivo' => 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/sort-a.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 10,
    'usuario_id' => 1,
]);
$cleanup['manual_ids'][] = $sortAId;
$sortZId = $model->create([
    'empresa_id' => $empresaId,
    'departamento_id' => $departamentoId,
    'nome' => $sortPrefix . ' Z',
    'descricao' => 'Zulu',
    'arquivo' => 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/sort-z.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 20,
    'usuario_id' => 1,
]);
$cleanup['manual_ids'][] = $sortZId;

$all = $model->list(['empresa_id' => $empresaId, 'nome' => $prefix]);
$filtered = $model->list(['empresa_id' => $empresaId, 'departamento_id' => $departamentoId, 'nome' => $prefix]);
$sortAsc = $model->list([
    'empresa_id' => $empresaId,
    'nome' => $sortPrefix,
    'sort_col' => 'nome',
    'sort_dir' => 'asc',
]);
$sortDesc = $model->list([
    'empresa_id' => $empresaId,
    'nome' => $sortPrefix,
    'sort_col' => 'nome',
    'sort_dir' => 'desc',
]);
$sortDateAsc = $model->list([
    'empresa_id' => $empresaId,
    'nome' => $sortPrefix,
    'sort_col' => 'data',
    'sort_dir' => 'asc',
]);
$sortedNamesAsc = array_values(array_map(static fn(array $row): string => (string)($row['nome'] ?? ''), $sortAsc));
$sortedNamesDesc = array_values(array_map(static fn(array $row): string => (string)($row['nome'] ?? ''), $sortDesc));
$sortByNameOk = ($sortedNamesAsc[0] ?? '') === $sortPrefix . ' A' && ($sortedNamesDesc[0] ?? '') === $sortPrefix . ' Z';
$sortByDateOk = ((int)($sortDateAsc[0]['id'] ?? 0) === $sortAId) && ((int)($sortDateAsc[1]['id'] ?? 0) === $sortZId);

$clientes = new ClienteModel();
$deps = new DepartamentoModel();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:nome, :cnpj, :contato, :is_matriz, :matriz_id)');
$stmt->execute([
    'nome' => 'Matriz Manual Link ' . $suffix,
    'cnpj' => '55.555.555/0001-' . substr($suffix, 0, 2),
    'contato' => 'Smoke',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
$matrizId = (int)$pdo->lastInsertId();
$cleanup['cliente_ids'][] = $matrizId;
$stmt->execute([
    'nome' => 'Filial Manual Link ' . $suffix,
    'cnpj' => '44.444.444/0001-' . substr($suffix, 0, 2),
    'contato' => 'Smoke',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
$filialId = (int)$pdo->lastInsertId();
$cleanup['cliente_ids'][] = $filialId;
$depMatrizId = $deps->create(['nome' => 'Dep Matriz Link ' . $suffix, 'cliente_id' => $matrizId]);
$cleanup['departamento_ids'][] = $depMatrizId;
$depFilialId = $deps->create(['nome' => 'Dep Filial Link ' . $suffix, 'cliente_id' => $filialId]);
$cleanup['departamento_ids'][] = $depFilialId;
$manualMatriz = $model->create([
    'empresa_id' => $matrizId,
    'departamento_id' => $depMatrizId,
    'nome' => 'Manual Matriz Link ' . $suffix,
    'descricao' => 'Teste',
    'arquivo' => 'storage/manuais/' . $matrizId . '/' . $depMatrizId . '/m.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
    'usuario_id' => 1,
]);
$cleanup['manual_ids'][] = $manualMatriz;
$manualFilial = $model->create([
    'empresa_id' => $filialId,
    'departamento_id' => $depFilialId,
    'nome' => 'Manual Filial Link ' . $suffix,
    'descricao' => 'Teste',
    'arquivo' => 'storage/manuais/' . $filialId . '/' . $depFilialId . '/f.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
    'usuario_id' => 1,
]);
$cleanup['manual_ids'][] = $manualFilial;
$model->replaceFilialLinks($manualMatriz, [$filialId]);
$updatedName = 'Manual Matriz Link Updated ' . $suffix;
$model->update($manualMatriz, [
    'empresa_id' => $matrizId,
    'departamento_id' => $depMatrizId,
    'nome' => $updatedName,
    'descricao' => 'Teste 2',
    'arquivo' => 'storage/manuais/' . $matrizId . '/' . $depMatrizId . '/m.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
]);
$matrizResolved = $clientes->catalogRootIdFor($filialId);
$listFilial = $model->listBiblioteca($filialId, $matrizResolved, ['nome' => 'Manual']);
$seenLinked = false;
$seenLocal = false;
$syncedUpdate = false;
foreach ($listFilial as $row) {
    if ((int)$row['id'] === $manualMatriz && (int)($row['is_linked_from_matriz'] ?? 0) === 1) {
        $seenLinked = true;
        if ((string)($row['nome'] ?? '') === $updatedName) {
            $syncedUpdate = true;
        }
    }
    if ((int)$row['id'] === $manualFilial && (int)($row['is_linked_from_matriz'] ?? 0) === 0) {
        $seenLocal = true;
    }
}
$linkOk = $model->isLinkedToFilial($manualMatriz, $filialId);

// Limpeza feita pelo register_shutdown_function registrado no topo do
// arquivo (roda mesmo se o exit(1) abaixo disparar) - ids ja acumulados
// em $cleanup conforme cada fixture foi criada.

echo json_encode([
    'created_id_positive' => $id > 0,
    'list_by_empresa_found' => count($all) >= 1,
    'list_by_empresa_departamento_nome_found' => count($filtered) >= 1,
    'filial_sees_linked_manual' => $seenLinked,
    'filial_sees_local_manual' => $seenLocal,
    'link_exists' => $linkOk,
    'synced_update' => $syncedUpdate,
    'sort_by_name_ok' => $sortByNameOk,
    'sort_by_date_ok' => $sortByDateOk,
], JSON_UNESCAPED_UNICODE);

if ($id <= 0 || count($all) < 1 || count($filtered) < 1 || !$seenLinked || !$seenLocal || !$linkOk || !$syncedUpdate || !$sortByNameOk || !$sortByDateOk) {
    exit(1);
}
