<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Controllers\IndicadoresController;
use App\Models\IndicadorModel;

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
$cleanup = ['indicador_id' => 0, 'unidade_id' => 0, 'setor_id' => 0, 'departamento_id' => 0, 'cliente_id' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['indicador_id'])) { $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $cleanup['indicador_id']]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        if (!empty($cleanup['setor_id'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_id']]); }
        if (!empty($cleanup['departamento_id'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]); }
        if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
    } catch (\Throwable $e) {}
});

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute(['nome' => 'Cliente Realizado ' . $suffix, 'cnpj' => '88.888.888/0001-' . substr($suffix, 0, 2), 'contato' => 'Test']);
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
    'indicador' => 'Indicador Realizado ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = $model->validate($payload);
if ($errors) failFast('Payload inválido: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
$indicadorId = $model->create($payload, 1);
if ($indicadorId <= 0) failFast('Falha ao criar indicador');
$cleanup['indicador_id'] = $indicadorId;
ok('Indicador criado com evento do mês corrente gerado automaticamente');

// Simula entrada direta na tela "Lançar Valor" (sem passar por outra tela antes):
// sem parâmetros de período na querystring e sem nada em sessão para este cliente.
unset($_SESSION['__indicadores_event_filters']);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'indicadores/realizado', 'cliente' => (string)$clienteId];
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

ob_start();
(new IndicadoresController())->realizado();
$html = (string)ob_get_clean();

if (strpos($html, 'Nenhum indicador disponível para lançamento de valor.') !== false) {
    failFast('Tela "Lançar Valor" caiu no estado vazio na primeira visita direta ao módulo (sem período aplicado)');
}
if (strpos($html, 'data-indicador-decimal') === false) {
    failFast('Tela "Lançar Valor" não renderizou a linha de lançamento do indicador do mês corrente na primeira visita direta ao módulo: ' . substr($html, 0, 4000));
}
ok('Tela "Lançar Valor" exibe a linha de lançamento ao entrar diretamente no módulo, sem exigir navegação prévia');

if (strpos($html, 'value="' . date('Y-m-01') . '"') === false) {
    failFast('Campo de período inicial não foi preenchido com o mês corrente por padrão');
}
ok('Período inicial padrão preenchido com o mês corrente');

echo "Indicadores realizado default period regression tests passed.\n";
