<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\IndicadoresController;
use App\Core\PdfSupport;
use App\Database\Database;
use App\Database\MigrationRunner;
use App\Models\IndicadorModel;

function assert_true($condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

if (!PdfSupport::isDompdfAvailable()) {
    echo "SKIP: Dompdf indisponível no ambiente atual.\n";
    exit(0);
}

try {
    $pdo = Database::getConnection();
} catch (\Throwable $e) {
    echo "SKIP: Sem conexão com DB no ambiente atual.\n";
    exit(0);
}

(new MigrationRunner())->applyAll();

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'indicador_id' => 0,
    'unidade_id' => 0,
    'setor_id' => 0,
    'departamento_id' => 0,
    'cliente_id' => 0,
];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        if (!empty($cleanup['indicador_id'])) {
            $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $cleanup['indicador_id']]);
        }
        if (!empty($cleanup['unidade_id'])) {
            $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]);
        }
        if (!empty($cleanup['setor_id'])) {
            $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_id']]);
        }
        if (!empty($cleanup['departamento_id'])) {
            $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]);
        }
        if (!empty($cleanup['cliente_id'])) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]);
        }
    } catch (\Throwable $e) {
    }
});

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute([
    'nome' => 'Cliente Painel PDF ' . $suffix,
    'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
    'contato' => 'Test',
]);
$clienteId = (int)$pdo->lastInsertId();
assert_true($clienteId > 0, 'Criou cliente');
$cleanup['cliente_id'] = $clienteId;

$stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
$stmt->execute(['nome' => 'Dep ' . $suffix, 'cid' => $clienteId]);
$departamentoId = (int)$pdo->lastInsertId();
assert_true($departamentoId > 0, 'Criou departamento');
$cleanup['departamento_id'] = $departamentoId;

$stmt = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :did)');
$stmt->execute(['nome' => 'Setor ' . $suffix, 'did' => $departamentoId]);
$setorId = (int)$pdo->lastInsertId();
assert_true($setorId > 0, 'Criou setor');
$cleanup['setor_id'] = $setorId;

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade Painel ' . $suffix, 'simbolo' => '', 'tipo' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
assert_true($unidadeId > 0, 'Criou unidade');
$cleanup['unidade_id'] = $unidadeId;

$ano = (int)date('Y');
$periodoInicio = date('Y-m-01');
$periodoFim = date('Y-m-t');

$model = new IndicadorModel();
$payload = [
    'cliente_id' => $clienteId,
    'indicador' => 'Indicador Painel PDF ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => $periodoInicio,
    'data_final' => $periodoFim,
    'valor' => '10',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = $model->validate($payload);
assert_true(empty($errors), 'Payload válido para criação de indicador');
$indicadorId = $model->create($payload, 1);
assert_true($indicadorId > 0, 'Criou indicador');
$cleanup['indicador_id'] = $indicadorId;

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [
    'route' => 'indicadores/painelPdf',
    'cliente' => $clienteId,
    'ano' => $ano,
    'indicador_id' => $indicadorId,
    'periodo_inicio' => $periodoInicio,
    'periodo_fim' => $periodoFim,
];

header_remove();
ob_start();
(new IndicadoresController())->painelPdf();
$pdf = (string)ob_get_clean();

assert_true(substr($pdf, 0, 4) === '%PDF', 'indicadores/painelPdf retorna PDF');
assert_true(strlen($pdf) > 1200, 'indicadores/painelPdf retorna um PDF com tamanho mínimo');

$pageCount = 0;
if (preg_match_all('/\/Type\s*\/Page\b(?!s)/', $pdf, $m)) {
    $pageCount = count($m[0] ?? []);
}
assert_true($pageCount >= 1, 'indicadores/painelPdf deve gerar ao menos 1 página');

if (preg_match('/\/MediaBox\s*\\[\s*0\s+0\s+([0-9.]+)\s+([0-9.]+)\s*\\]/', $pdf, $mm)) {
    $w = (float)$mm[1];
    $h = (float)$mm[2];
    assert_true($w > $h, 'PDF deve estar em paisagem (MediaBox width > height)');
}

echo "Indicadores painel PDF smoke tests passed.\n";

