<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\MigrationRunner;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\IndicadorEventoModel;
use App\Models\IndicadorModel;
use App\Models\SetorModel;
use App\Database\Database;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$runner = new MigrationRunner();
$runner->applyAll();

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$indicadores = new IndicadorModel();
$indicadorEventos = new IndicadorEventoModel();

$suffix = uniqid('indf', true);
$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Filtros ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Contato',
]);
assert_true($clienteId > 0, 'Criou cliente para teste de filtros');

$depId = $departamentos->create(['nome' => 'Depto ' . $suffix, 'cliente_id' => $clienteId]);
assert_true($depId > 0, 'Criou departamento');

$setorId = $setores->create(['nome' => 'Setor ' . $suffix, 'departamento_id' => $depId]);
assert_true($setorId > 0, 'Criou setor');

$unidadeId = (int)$pdo->query('SELECT id FROM unidades_medida LIMIT 1')->fetchColumn();
assert_true($unidadeId > 0, 'Obteve unidade de medida padrão');

$base = [
    'cliente_id' => $clienteId,
    'departamento_id' => $depId,
    'setor_id' => $setorId,
    'periodicidade_tipo' => 'mensal',
    'valor' => 10,
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => null,
    'valor_maximo' => null,
    'responsavel_ids' => [],
];

$idA = $indicadores->create(array_merge($base, [
    'indicador' => 'Produtividade ' . $suffix,
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-12-31',
]), 1);
assert_true($idA > 0, 'Criou indicador A');

$idB = $indicadores->create(array_merge($base, [
    'indicador' => 'Qualidade ' . $suffix,
    'data_inicial' => '2025-01-01',
    'data_final' => '2025-12-31',
]), 1);
assert_true($idB > 0, 'Criou indicador B');

$idC = $indicadores->create(array_merge($base, [
    'indicador' => 'Produtividade Operacional ' . $suffix,
    'data_inicial' => '2026-06-01',
    'data_final' => '2026-06-30',
]), 1);
assert_true($idC > 0, 'Criou indicador C');

$result = $indicadores->search(['cliente_id' => $clienteId, 'q' => 'Produtividade', 'date_start' => '', 'date_end' => '']);
assert_true(count($result) >= 2, 'Filtro por nome retorna múltiplos indicadores');

$result = $indicadores->search(['cliente_id' => $clienteId, 'q' => 'Qualidade', 'date_start' => '', 'date_end' => '']);
assert_true(count($result) === 1, 'Filtro por nome exato retorna apenas um indicador');
assert_true((int)$result[0]['id'] === $idB, 'Filtro por nome trouxe o indicador correto');

$result = $indicadores->search(['cliente_id' => $clienteId, 'q' => '', 'date_start' => '2026-06-01', 'date_end' => '2026-06-30']);
$ids = array_map(static fn(array $row): int => (int)$row['id'], $result);
assert_true(in_array($idA, $ids, true), 'Filtro por período (interseção) inclui indicador com período abrangente');
assert_true(in_array($idC, $ids, true), 'Filtro por período inclui indicador dentro do range');
assert_true(!in_array($idB, $ids, true), 'Filtro por período exclui indicador fora do range');

$result = $indicadores->autocomplete($clienteId, 'Pro', 10);
assert_true(count($result) >= 2, 'Autocomplete retorna sugestões coerentes');

$_SESSION['user']['allowed_client_ids'] = [];
$eventsAll = $indicadorEventos->searchByCliente($clienteId, []);
assert_true(count($eventsAll) > 0, 'Carrega eventos do cliente para filtros');

$eventsOnlyA = $indicadorEventos->searchByCliente($clienteId, ['indicador_id' => $idA]);
assert_true(!empty($eventsOnlyA) && array_reduce($eventsOnlyA, static fn(bool $ok, array $row): bool => $ok && (int)$row['indicador_id'] === $idA, true), 'Filtro por indicador restringe eventos corretamente');

$periodosA = $indicadorEventos->periodOptionsByCliente($clienteId, ['indicador_id' => $idA]);
assert_true(!empty($periodosA), 'Carrega opções de período de apuração para o indicador');
$firstPeriodo = $periodosA[0];
$eventsPeriodo = $indicadorEventos->searchByCliente($clienteId, [
    'indicador_id' => $idA,
    'periodo_inicio' => $firstPeriodo['periodo_inicio'],
    'periodo_fim' => $firstPeriodo['periodo_fim'],
]);
assert_true(!empty($eventsPeriodo) && array_reduce($eventsPeriodo, static function (bool $ok, array $row) use ($firstPeriodo): bool {
    return $ok
        && (string)$row['periodo_inicio'] === (string)$firstPeriodo['periodo_inicio']
        && (string)$row['periodo_fim'] === (string)$firstPeriodo['periodo_fim'];
}, true), 'Filtro por período de apuração restringe eventos corretamente');

$invalidRange = $indicadorEventos->searchByCliente($clienteId, [
    'indicador_id' => $idA,
    'periodo_inicio' => '2026-12-31',
    'periodo_fim' => '2026-01-01',
]);
assert_true($invalidRange === [], 'Bloqueia consulta quando período é inválido (fim < início)');

echo "Indicadores filters unit tests passed.\n";
