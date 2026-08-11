<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Database\MigrationRunner;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\IndicadorEventoModel;
use App\Models\IndicadorModel;
use App\Models\SetorModel;

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
try {
    $runner->applyAll();
} catch (\RuntimeException $e) {
    try {
        $runner->repairChecksumMismatches();
        $runner->applyAll();
    } catch (\Throwable $inner) {
    }
}

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$indicadores = new IndicadorModel();
$eventos = new IndicadorEventoModel();

$suffix = uniqid('indpc', true);
$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Percentual Sem Teto ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Contato',
]);
assert_true($clienteId > 0, 'Criou cliente para o teste');

$departamentoId = $departamentos->create(['nome' => 'Departamento ' . $suffix, 'cliente_id' => $clienteId]);
$setorId = $setores->create(['nome' => 'Setor ' . $suffix, 'departamento_id' => $departamentoId]);
assert_true($departamentoId > 0 && $setorId > 0, 'Criou departamento e setor');

$unitPercent = (int)$pdo->query("SELECT id FROM unidades_medida WHERE tipo = 'percentual' ORDER BY id LIMIT 1")->fetchColumn();
$unitInteger = (int)$pdo->query("SELECT id FROM unidades_medida WHERE tipo = 'inteiro' ORDER BY id LIMIT 1")->fetchColumn();
$unitMoney = (int)$pdo->query("SELECT id FROM unidades_medida WHERE tipo = 'monetaria' ORDER BY id LIMIT 1")->fetchColumn();
assert_true($unitPercent > 0 && $unitInteger > 0 && $unitMoney > 0, 'Carregou unidades de medida');

$base = [
    'cliente_id' => $clienteId,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-08-31',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unitPercent,
];

// ===================== PERCENTUAIS =====================

// 1) 80% -- dentro da faixa comum, continua funcionando.
$e1 = $indicadores->validate(array_merge($base, ['indicador' => 'Pct 80 ' . $suffix, 'valor' => '80']));
assert_true($e1 === [], 'Percentual 1: aceita meta 80%');

// 2) 100% -- limite antigo, continua funcionando.
$e2 = $indicadores->validate(array_merge($base, ['indicador' => 'Pct 100 ' . $suffix, 'valor' => '100']));
assert_true($e2 === [], 'Percentual 2: aceita meta 100%');

// 3) 120% -- acima do antigo teto fixo, agora permitido.
$e3 = $indicadores->validate(array_merge($base, ['indicador' => 'Pct 120 ' . $suffix, 'valor' => '120']));
assert_true($e3 === [], 'Percentual 3: aceita meta 120% (antes bloqueado pelo teto fixo)');

// 4) 150,5% -- decimal com vírgula acima de 100%.
$e4 = $indicadores->validate(array_merge($base, ['indicador' => 'Pct 150,5 ' . $suffix, 'valor' => '150,5']));
assert_true($e4 === [], 'Percentual 4: aceita meta 150,5%');

// 5) valor mínimo (referência > 0, já que meta precisa ser positiva) coerente com percentual baixo.
$e5 = $indicadores->validate(array_merge($base, ['indicador' => 'Pct Minimo ' . $suffix, 'valor' => '0,01']));
assert_true($e5 === [], 'Percentual 5: aceita meta percentual mínima positiva (0,01%)');

// 6) valor_maximo configurado: passa a ser IGNORADO como teto de validação (é só banda visual).
$e6 = $indicadores->validate(array_merge($base, [
    'indicador' => 'Pct Com Maximo ' . $suffix,
    'valor' => '80',
    'valor_minimo' => '0',
    'valor_maximo' => '90',
]));
assert_true($e6 === [], 'Percentual 6: aceita indicador com valor_maximo configurado, dentro da faixa');

// 7) valor acima do valor_maximo configurado -- decisão de negócio confirmada: NÃO bloqueia.
$e7 = $indicadores->validate(array_merge($base, [
    'indicador' => 'Pct Acima Maximo ' . $suffix,
    'valor' => '120',
    'valor_minimo' => '0',
    'valor_maximo' => '90',
]));
assert_true($e7 === [], 'Percentual 7: aceita meta 120% mesmo com valor_maximo=90 configurado (banda é só visual, não é teto de validação)');

// 8) ausência de valor_maximo -- não bloqueia por ultrapassar 100.
$e8 = $indicadores->validate(array_merge($base, [
    'indicador' => 'Pct Sem Maximo ' . $suffix,
    'valor' => '200',
    'valor_minimo' => null,
    'valor_maximo' => null,
]));
assert_true($e8 === [], 'Percentual 8: aceita meta 200% sem valor_maximo configurado');

// Cria o indicador do caso 3 para testar o LANÇAMENTO de resultado acima de 100%.
$indicadorId = $indicadores->create(array_merge($base, ['indicador' => 'Pct 120 ' . $suffix, 'valor' => '120']), 1);
assert_true($indicadorId > 0, 'Criou indicador percentual com meta 120% para testar lançamento');
$eventosDoIndicador = $eventos->byIndicador($indicadorId);
assert_true(!empty($eventosDoIndicador), 'Indicador gerou eventos');

$launched = $eventos->updateAchievedValue((int)$eventosDoIndicador[0]['id'], '135,25', 1, 'Resultado acima da meta');
assert_true($launched, 'Permite lançar resultado percentual acima de 100% (135,25%)');
$eventoLancado = $eventos->find((int)$eventosDoIndicador[0]['id']);
assert_true(abs((float)($eventoLancado['valor_atingido'] ?? 0) - 135.25) < 0.0001, 'Persistiu o valor lançado corretamente (135,25)');
assert_true(($eventoLancado['meta_status_key'] ?? '') === 'atingida', 'Resultado acima da meta (120%) fica classificado como meta atingida');

// ===================== DECIMAL (via fluxo real de lançamento) =====================

// 9/10) vírgula e ponto aceitos igualmente no mesmo fluxo de lançamento.
$eventos9 = $eventos->byIndicador($indicadorId);
assert_true($eventos->updateAchievedValue((int)$eventos9[1]['id'], '97,5', 1, 'vírgula'), 'Decimal 9: lançamento aceita "97,5" (vírgula)');
assert_true($eventos->updateAchievedValue((int)$eventos9[2]['id'], '97.5', 1, 'ponto'), 'Decimal 10: lançamento aceita "97.5" (ponto) — bug relatado no item 15');
$v9 = $eventos->find((int)$eventos9[1]['id']);
$v10 = $eventos->find((int)$eventos9[2]['id']);
assert_true(abs((float)($v9['valor_atingido'] ?? 0) - 97.5) < 0.0001, 'Decimal 9: persistiu 97,5 corretamente');
assert_true(abs((float)($v10['valor_atingido'] ?? 0) - 97.5) < 0.0001, 'Decimal 10: persistiu 97.5 corretamente (mesmo valor que 97,5)');

// 13) zero é aceito na atualização de meta (updateValor tem sua própria regra de "> 0"; aqui testamos o parser via lançamento de evento, que aceita 0).
assert_true($eventos->updateAchievedValue((int)$eventos9[3]['id'], '0', 1, 'zero'), 'Decimal 13: lançamento aceita "0"');

// 14) valor negativo continua permitido no lançamento (já era o comportamento correto).
assert_true($eventos->updateAchievedValue((int)$eventos9[4]['id'], '-25,5', 1, 'negativo'), 'Decimal 14: lançamento aceita valor negativo ("-25,5")');

// 15) valor inválido continua sendo rejeitado, nunca vira zero silenciosamente.
$antesInvalido = $eventos->find((int)$eventos9[5]['id']);
$rejeitado = $eventos->updateAchievedValue((int)$eventos9[5]['id'], '12.34,56', 1, 'ambíguo');
assert_true(!$rejeitado, 'Decimal 15: rejeita entrada ambígua ("12.34,56") em vez de aceitar');
$depoisInvalido = $eventos->find((int)$eventos9[5]['id']);
assert_true(($depoisInvalido['valor_atingido'] ?? null) === ($antesInvalido['valor_atingido'] ?? null), 'Decimal 15: entrada rejeitada não altera o valor previamente salvo (não vira 0)');

// ===================== REGRESSÃO =====================

// 16) indicadores inteiros continuam exigindo número inteiro (comportamento inalterado).
$intId = $indicadores->create(array_merge($base, [
    'indicador' => 'Regressao Inteiro ' . $suffix,
    'unidade_medida_id' => $unitInteger,
    'valor' => '10',
    'valor_minimo' => null,
    'valor_maximo' => null,
]), 1);
assert_true($intId > 0, 'Regressão 16: cria indicador inteiro normalmente');
$intEventos = $eventos->byIndicador($intId);
assert_true(!$eventos->updateAchievedValue((int)$intEventos[0]['id'], '10,5', 1, 'fracionário'), 'Regressão 16: continua rejeitando decimal em indicador inteiro');
assert_true($eventos->updateAchievedValue((int)$intEventos[0]['id'], '150', 1, 'inteiro grande'), 'Regressão 16: indicador inteiro aceita valor grande (150), sem relação com o teto percentual removido');

// 17) indicadores monetários continuam funcionando sem teto (nunca tiveram).
$moneyId = $indicadores->create(array_merge($base, [
    'indicador' => 'Regressao Monetario ' . $suffix,
    'unidade_medida_id' => $unitMoney,
    'valor' => '1000',
    'valor_minimo' => null,
    'valor_maximo' => null,
]), 1);
assert_true($moneyId > 0, 'Regressão 17: cria indicador monetário normalmente');
$moneyEventos = $eventos->byIndicador($moneyId);
assert_true($eventos->updateAchievedValue((int)$moneyEventos[0]['id'], '1.234.567,89', 1, 'monetário grande'), 'Regressão 17: indicador monetário aceita valor grande com milhar');

// 18) indicador com teto máximo (tipo_meta = maximo) continua com sua lógica original.
$maxId = $indicadores->create(array_merge($base, [
    'indicador' => 'Regressao Teto ' . $suffix,
    'tipo_meta' => 'maximo',
    'valor' => '50',
    'valor_minimo' => null,
    'valor_maximo' => null,
]), 1);
assert_true($maxId > 0, 'Regressão 18: cria indicador com tipo_meta=maximo');
$maxEventos = $eventos->byIndicador($maxId);
assert_true($eventos->updateAchievedValue((int)$maxEventos[0]['id'], '40', 1, 'dentro do teto'), 'Regressão 18: lança valor dentro do teto');
assert_true((($eventos->find((int)$maxEventos[0]['id']))['meta_status_key'] ?? '') === 'atingida', 'Regressão 18: valor dentro do teto continua "atingida"');

// 19) indicador com piso (tipo_meta = minimo) continua com sua lógica original.
$minId = $indicadores->create(array_merge($base, [
    'indicador' => 'Regressao Piso ' . $suffix,
    'tipo_meta' => 'minimo',
    'valor' => '60',
    'valor_minimo' => null,
    'valor_maximo' => null,
]), 1);
assert_true($minId > 0, 'Regressão 19: cria indicador com tipo_meta=minimo (piso)');
$minEventos = $eventos->byIndicador($minId);
assert_true($eventos->updateAchievedValue((int)$minEventos[0]['id'], '30', 1, 'abaixo do piso'), 'Regressão 19: lança valor abaixo do piso');
assert_true((($eventos->find((int)$minEventos[0]['id']))['meta_status_key'] ?? '') === 'nao_atingida', 'Regressão 19: valor abaixo do piso continua "não atingida"');

// 20) cálculo de atingimento acima de 100% continua correto (meta 120, atingido 135,25 -> ~112,71%).
$cumprimento = (float)($eventoLancado['percentual_cumprimento'] ?? 0);
assert_true(abs($cumprimento - 112.71) < 0.01, 'Regressão 20: calcula cumprimento acima de 100% corretamente (135,25/120 = 112,71%)');

// 21/22) o percentual de cumprimento (não a meta bruta) já é exibido sem cap em ValueFormatter — sem mudança necessária.
assert_true(\App\Core\ValueFormatter::percent(129.41) === '129,41%', 'Regressão 21/22: ValueFormatter::percent formata valores acima de 100% sem cortar (gráficos/dashboard)');

// 23) histórico (indicatorHistorySummary) não quebra com valores acima de 100%.
$summary = $eventos->indicatorHistorySummary($indicadorId);
assert_true($summary['lancados'] >= 1, 'Regressão 23: histórico contabiliza os lançamentos mesmo acima de 100%');

// 24) edição (update()) de indicador percentual aceita meta acima de 100%.
$updated = $indicadores->update($indicadorId, array_merge($indicadores->find($indicadorId) ?: [], [
    'cliente_id' => $clienteId,
    'indicador' => 'Pct 120 ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-08-31',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unitPercent,
    'valor' => '175',
]), 1);
assert_true($updated, 'Regressão 24: edição de indicador percentual aceita nova meta 175%');
assert_true((float)($indicadores->find($indicadorId)['valor'] ?? 0) === 175.0, 'Regressão 24: persistiu a nova meta 175% corretamente');

// 25) lançamento contínuo (updateValor / card inline) também aceita valor percentual alto.
$updatedValor = $indicadores->updateValor($indicadorId, '210,75', 1);
assert_true($updatedValor, 'Regressão 25: updateValor (edição inline do card) aceita percentual acima de 100 (210,75)');
assert_true(abs((float)($indicadores->find($indicadorId)['valor'] ?? 0) - 210.75) < 0.0001, 'Regressão 25: persistiu o valor 210,75 corretamente via updateValor');

echo "Indicadores percentual sem teto (item 06 + 15a) regression tests passed.\n";
