<?php
require __DIR__ . '/../autoload.php';

use App\Core\Security;
use App\Database\Database;
use App\Controllers\IndicadoresController;
use App\Models\IndicadorModel;

ob_start();

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
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'indicador_id' => 0,
    'unidade_id' => 0,
    'setor_id' => 0,
    'departamento_id' => 0,
    'cliente_id' => 0,
];

register_shutdown_function(function() use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['indicador_id'])) { $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $cleanup['indicador_id']]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        if (!empty($cleanup['setor_id'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_id']]); }
        if (!empty($cleanup['departamento_id'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]); }
        if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
    } catch (\Throwable $e) {}
});

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute([
    'nome' => 'Cliente Indicadores ' . $suffix,
    'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
    'contato' => 'Test',
]);
$clienteId = (int)$pdo->lastInsertId();
if ($clienteId <= 0) failFast('Falha ao criar cliente');
$cleanup['cliente_id'] = $clienteId;

$stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
$stmt->execute(['nome' => 'Dep ' . $suffix, 'cid' => $clienteId]);
$departamentoId = (int)$pdo->lastInsertId();
$cleanup['departamento_id'] = $departamentoId;

$stmt = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :did)');
$stmt->execute(['nome' => 'Setor ' . $suffix, 'did' => $departamentoId]);
$setorId = (int)$pdo->lastInsertId();
$cleanup['setor_id'] = $setorId;

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade Teste ' . $suffix, 'simbolo' => '', 'tipo' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_id'] = $unidadeId;

$model = new IndicadorModel();
$payload = [
    'cliente_id' => $clienteId,
    'indicador' => 'Indicador ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = $model->validate($payload);
if ($errors) failFast('Payload inválido: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
$indicadorId = $model->create($payload, 1);
if ($indicadorId <= 0) failFast('Falha ao criar indicador');
$cleanup['indicador_id'] = $indicadorId;
ok('Indicador criado');

$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['csrf' => $csrf, 'id' => $indicadorId];
$_GET = ['route' => 'indicadores/deleteAjax'];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

ob_start();
(new IndicadoresController())->deleteAjax();
$out = ob_get_clean();
$payload = json_decode((string)$out, true);
if (!is_array($payload) || empty($payload['ok'])) {
    failFast('Resposta inválida: ' . $out);
}
ok('Delete ok');

$fresh = $model->find($indicadorId);
if ($fresh !== null) {
    failFast('Indicador ainda aparece no find após soft delete.');
}
ok('Soft delete efetivo');

echo "All indicadores deleteAjax integration tests passed.\n";
ob_end_flush();
