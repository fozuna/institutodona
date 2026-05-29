<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Controllers\ManuaisController;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\ManualModel;
use App\Models\ManualPortalTokenModel;

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$manuais = new ManualModel();
$tokens = new ManualPortalTokenModel();
$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:nome, :cnpj, :contato, :is_matriz, :matriz_id)');
$stmt->execute([
    'nome' => 'Matriz Portal ' . $suffix,
    'cnpj' => '77.777.777/0001-' . substr($suffix, 0, 2),
    'contato' => 'Smoke',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
$matrizId = (int)$pdo->lastInsertId();
$stmt->execute([
    'nome' => 'Filial Portal ' . $suffix,
    'cnpj' => '66.666.666/0001-' . substr($suffix, 0, 2),
    'contato' => 'Smoke',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
$filialId = (int)$pdo->lastInsertId();

$depMatrizId = $departamentos->create(['nome' => 'Dep Matriz ' . $suffix, 'cliente_id' => $matrizId]);
$depFilialId = $departamentos->create(['nome' => 'Dep Filial ' . $suffix, 'cliente_id' => $filialId]);

$manualMatriz = $manuais->create([
    'empresa_id' => $matrizId,
    'departamento_id' => $depMatrizId,
    'nome' => 'Manual Matriz ' . $suffix,
    'descricao' => 'Teste',
    'arquivo' => 'storage/manuais/' . $matrizId . '/' . $depMatrizId . '/m.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
    'usuario_id' => 1,
]);
$manualFilial = $manuais->create([
    'empresa_id' => $filialId,
    'departamento_id' => $depFilialId,
    'nome' => 'Manual Filial ' . $suffix,
    'descricao' => 'Teste',
    'arquivo' => 'storage/manuais/' . $filialId . '/' . $depFilialId . '/f.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
    'usuario_id' => 1,
]);

$token = $tokens->issue($matrizId, date('Y-m-d H:i:s', strtotime('+1 day')));
$validToken = $tokens->findValid($token);
$scopeMatriz = $clientes->manualPortalScopeIds($matrizId);
$scopeFilial = $clientes->manualPortalScopeIds($filialId);
$countMatriz = $manuais->portalCount($scopeMatriz);
$countFilial = $manuais->portalCount($scopeFilial);

$tokenLocked = $tokens->issue($matrizId, date('Y-m-d H:i:s', strtotime('+1 day')), [$matrizId], [
    'departamento_id' => $depMatrizId,
    'q' => 'Manual Matriz ' . $suffix,
]);
$lockedRecord = $tokens->findValid($tokenLocked);
$lockedScope = [];
$lockedFilters = [];
if (!empty($lockedRecord['scope_ids_json'])) {
    $decoded = json_decode((string)$lockedRecord['scope_ids_json'], true);
    $lockedScope = is_array($decoded) ? array_values(array_unique(array_map('intval', $decoded))) : [];
}
if (!empty($lockedRecord['filters_json'])) {
    $decoded = json_decode((string)$lockedRecord['filters_json'], true);
    $lockedFilters = is_array($decoded) ? $decoded : [];
}
$countLocked = $manuais->portalCount($lockedScope, $lockedFilters);

$_GET = [
    'token' => $tokenLocked,
    'q' => 'alterado',
];
http_response_code(200);
ob_start();
(new ManuaisController())->portal();
$portalHtml = (string)ob_get_clean();
$portalMismatchBlocked = str_contains($portalHtml, 'Link inválido');

$pdo->prepare('DELETE FROM manual_portal_tokens WHERE empresa_id = :id')->execute(['id' => $matrizId]);
$pdo->prepare('DELETE FROM manual_portal_tokens WHERE empresa_id = :id')->execute(['id' => $filialId]);
$pdo->prepare('DELETE FROM manuais WHERE id = :id')->execute(['id' => $manualMatriz]);
$pdo->prepare('DELETE FROM manuais WHERE id = :id')->execute(['id' => $manualFilial]);
$pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $depMatrizId]);
$pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $depFilialId]);
$pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $filialId]);
$pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $matrizId]);

echo json_encode([
    'valid_token_found' => !empty($validToken),
    'locked_token_found' => !empty($lockedRecord),
    'locked_scope_is_only_matriz' => $lockedScope === [$matrizId],
    'locked_filters_have_dep' => (int)($lockedFilters['departamento_id'] ?? 0) === $depMatrizId,
    'locked_filters_have_q' => (string)($lockedFilters['q'] ?? '') === ('Manual Matriz ' . $suffix),
    'matriz_scope_has_filial' => in_array($filialId, $scopeMatriz, true),
    'filial_scope_only_self' => $scopeFilial === [$filialId],
    'matriz_sees_both' => $countMatriz === 2,
    'filial_sees_one' => $countFilial === 1,
    'locked_count_is_one' => $countLocked === 1,
    'portal_mismatch_blocked' => $portalMismatchBlocked,
], JSON_UNESCAPED_UNICODE);

if (empty($validToken) || empty($lockedRecord) || $lockedScope !== [$matrizId] || (int)($lockedFilters['departamento_id'] ?? 0) !== $depMatrizId || (string)($lockedFilters['q'] ?? '') !== ('Manual Matriz ' . $suffix) || !in_array($filialId, $scopeMatriz, true) || $scopeFilial !== [$filialId] || $countMatriz !== 2 || $countFilial !== 1 || $countLocked !== 1 || !$portalMismatchBlocked) {
    exit(1);
}
