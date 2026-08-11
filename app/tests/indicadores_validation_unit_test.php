<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Database\MigrationRunner;
use App\Core\ValueFormatter;
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
    echo "WARN: {$e->getMessage()}\n";
    try {
        $runner->repairChecksumMismatches();
        $runner->applyAll();
    } catch (\Throwable $inner) {
        echo "WARN: {$inner->getMessage()}\n";
    }
}

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$indicadores = new IndicadorModel();
$indicadorEventos = new IndicadorEventoModel();

$suffix = uniqid('ind', true);
$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Indicadores ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Contato',
]);
assert_true($clienteId > 0, 'Criou cliente principal para indicadores');

$outroClienteId = $clientes->create([
    'nome_empresa' => 'Cliente Externo ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Contato',
]);
assert_true($outroClienteId > 0, 'Criou cliente secundário para validações');

$departamentoId = $departamentos->create(['nome' => 'Operações ' . $suffix, 'cliente_id' => $clienteId]);
$outroDepartamentoId = $departamentos->create(['nome' => 'Financeiro ' . $suffix, 'cliente_id' => $clienteId]);
assert_true($departamentoId > 0 && $outroDepartamentoId > 0, 'Criou departamentos ativos');

$setorId = $setores->create(['nome' => 'Linha A ' . $suffix, 'departamento_id' => $departamentoId]);
$setorInvalidoId = $setores->create(['nome' => 'Linha B ' . $suffix, 'departamento_id' => $outroDepartamentoId]);
assert_true($setorId > 0 && $setorInvalidoId > 0, 'Criou setores para validação dependente');

$funcaoStmt = $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:nome, :setor_id)');
$funcaoStmt->execute(['nome' => 'Supervisor ' . $suffix, 'setor_id' => $setorId]);
$funcaoId = (int)$pdo->lastInsertId();
$funcaoStmt->execute(['nome' => 'Supervisor Externo ' . $suffix, 'setor_id' => $setorId]);
$funcaoIdExterno = (int)$pdo->lastInsertId();
assert_true($funcaoId > 0 && $funcaoIdExterno > 0, 'Criou funções de apoio aos colaboradores');

$colabStmt = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, lider, cliente_id, ativo) VALUES (:nome, :email, :funcao_id, :lider, :cliente_id, :ativo)');
$colabStmt->execute([
    'nome' => 'Ana Responsável ' . $suffix,
    'email' => 'ana.' . str_replace('.', '', $suffix) . '@example.com',
    'funcao_id' => $funcaoId,
    'lider' => 'sim',
    'cliente_id' => $clienteId,
    'ativo' => 1,
]);
$responsavelId = (int)$pdo->lastInsertId();
$colabStmt->execute([
    'nome' => 'Carlos Externo ' . $suffix,
    'email' => 'carlos.' . str_replace('.', '', $suffix) . '@example.com',
    'funcao_id' => $funcaoIdExterno,
    'lider' => 'não',
    'cliente_id' => $outroClienteId,
    'ativo' => 1,
]);
$responsavelExternoId = (int)$pdo->lastInsertId();
assert_true($responsavelId > 0 && $responsavelExternoId > 0, 'Criou colaboradores para validar responsáveis');

$unitPercent = (int)$pdo->query("SELECT id FROM unidades_medida WHERE tipo = 'percentual' ORDER BY id LIMIT 1")->fetchColumn();
$unitInteger = (int)$pdo->query("SELECT id FROM unidades_medida WHERE tipo = 'inteiro' ORDER BY id LIMIT 1")->fetchColumn();
$unitMoney = (int)$pdo->query("SELECT id FROM unidades_medida WHERE tipo = 'monetaria' ORDER BY id LIMIT 1")->fetchColumn();
assert_true($unitPercent > 0 && $unitInteger > 0 && $unitMoney > 0, 'Carregou unidades de medida padrão da migration');
assert_true(ValueFormatter::decimal(85) === '85,00', 'Formata valores com duas casas decimais');
assert_true(ValueFormatter::decimal(85.5) === '85,50', 'Completa zeros quando necessário na formatação');

$baseData = [
    'cliente_id' => $clienteId,
    'cliente_nome' => 'Cliente Indicadores ' . $suffix,
    'indicador' => 'Taxa de Qualidade ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [$responsavelId],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-12-31',
    'valor' => '85',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unitPercent,
    'valor_minimo' => '70',
    'valor_maximo' => '95',
];

$errors = $indicadores->validate($baseData);
assert_true($errors === [], 'Payload base passa pelas validações');

$commaErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Vírgula ' . $suffix,
    'valor' => '85,5',
]));
assert_true($commaErrors === [], 'Aceita valores decimais com vírgula');

$indicadorId = $indicadores->create($baseData, 1);
assert_true($indicadorId > 0, 'Criou indicador com responsáveis');

$created = $indicadores->find($indicadorId);
assert_true(!empty($created['responsavel_ids']) && in_array($responsavelId, $created['responsavel_ids'], true), 'Persistiu responsáveis na tabela associativa');
assert_true(($created['tipo_meta'] ?? '') === 'minimo', 'Persistiu tipo de meta minimo no indicador');
assert_true(($created['control_status_key'] ?? '') === 'normal', 'Classificou valor dentro da faixa de controle');
$createdEvents = $indicadorEventos->byIndicador($indicadorId);
assert_true(count($createdEvents) === 12, 'Gerou eventos mensais do indicador da data inicial até a data final');
assert_true(($createdEvents[0]['meta_status_key'] ?? '') === 'pendente', 'Eventos novos iniciam com meta pendente');

$duplicateErrors = $indicadores->validate($baseData);
assert_true(isset($duplicateErrors['indicador']), 'Bloqueia duplicidade de indicador por cliente');

$intervalErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Intervalo ' . $suffix,
    'data_inicial' => '2026-12-31',
    'data_final' => '2026-01-01',
]));
assert_true(isset($intervalErrors['data_final']), 'Valida intervalo lógico entre data inicial e final');

$limitErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Limites ' . $suffix,
    'valor_minimo' => '90',
    'valor_maximo' => '80',
]));
assert_true(isset($limitErrors['valor_minimo']), 'Valida valor mínimo menor que valor máximo');

$negativePercentageErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Percentual Negativo ' . $suffix,
    'valor' => '-15',
]));
assert_true(isset($negativePercentageErrors['valor']), 'Bloqueia meta negativa no cadastro do indicador');

$zeroReferenceErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Referencia Zero ' . $suffix,
    'valor' => '0',
]));
assert_true(isset($zeroReferenceErrors['valor']), 'Bloqueia meta com valor zero');

// Mudanca de regra de negocio (2026-08-11): indicadores percentuais deixaram de
// ter teto fixo em 100 tanto no cadastro quanto no lancamento de resultado (ver
// item 06 do backlog de auditoria). valor_maximo/valor_minimo continuam servindo
// apenas para o badge visual de status de controle, nunca como limite de validacao.
$percentageErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Percentual ' . $suffix,
    'valor' => '110',
]));
assert_true($percentageErrors === [], 'Permite cadastrar meta percentual acima de 100');

$percentageAboveMaxErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Percentual Acima do Maximo ' . $suffix,
    'valor' => '150,5',
    'valor_minimo' => '70',
    'valor_maximo' => '95',
]));
assert_true($percentageAboveMaxErrors === [], 'Permite meta percentual acima do valor_maximo configurado (banda e so visual)');

$integerErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Inteiro ' . $suffix,
    'unidade_medida_id' => $unitInteger,
    'valor' => '10.5',
]));
assert_true(isset($integerErrors['valor']), 'Exige valor inteiro quando a unidade é do tipo inteiro');

$sectorErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Setor ' . $suffix,
    'setor_id' => $setorInvalidoId,
]));
assert_true(isset($sectorErrors['setor_id']), 'Valida compatibilidade entre setor e departamento');

$responsavelErrors = $indicadores->validate(array_merge($baseData, [
    'indicador' => 'Indicador Responsável ' . $suffix,
    'responsavel_ids' => [$responsavelExternoId],
]));
assert_true(isset($responsavelErrors['responsavel_ids']), 'Valida escopo do responsável vinculado ao cliente');

$outOfRangeId = $indicadores->create(array_merge($baseData, [
    'indicador' => 'Indicador Fora Faixa ' . $suffix,
    'valor' => '120',
    'valor_minimo' => '60',
    'valor_maximo' => '90',
]), 1);
assert_true($outOfRangeId > 0, 'Permite salvar indicador fora da faixa para gerar alerta visual');
assert_true(($indicadores->find($outOfRangeId)['control_status_key'] ?? '') === 'high', 'Classifica indicador acima do máximo');
assert_true($indicadorEventos->updateAchievedValue((int)$createdEvents[0]['id'], '68', 1, 'Resultado parcial'), 'Atualiza valor atingido em evento do indicador');
$updatedEvent = $indicadorEventos->find((int)$createdEvents[0]['id']);
assert_true(($updatedEvent['meta_status_key'] ?? '') === 'nao_atingida', 'Calcula status não atingido quando valor fica abaixo do mínimo');
assert_true((float)($updatedEvent['percentual_cumprimento'] ?? 0) === 80.0, 'Calcula percentual de cumprimento do evento');
assert_true($indicadorEventos->updateAchievedValue((int)$createdEvents[2]['id'], '68,50', 1, 'Decimais pt-BR'), 'Atualiza evento aceitando decimal com vírgula');
$updatedEventComma = $indicadorEventos->find((int)$createdEvents[2]['id']);
assert_true($updatedEventComma !== null && abs((float)($updatedEventComma['valor_atingido'] ?? 0) - 68.5) < 0.0001, 'Persistiu valor com vírgula corretamente');
assert_true(!$indicadorEventos->updateAchievedValue((int)$createdEvents[3]['id'], '10,0,0', 1, 'Inválido'), 'Rejeita formato com múltiplas vírgulas');
// Mudanca de regra de negocio (2026-08-11): ver comentario acima sobre o item 06.
assert_true($indicadorEventos->updateAchievedValue((int)$createdEvents[4]['id'], '110', 1, 'Percentual acima de 100'), 'Permite lançar percentual acima de 100 no evento');
$eventoAcimaDeCem = $indicadorEventos->find((int)$createdEvents[4]['id']);
assert_true(($eventoAcimaDeCem['meta_status_key'] ?? '') === 'atingida', 'Percentual acima de 100 (meta 85) fica classificado como meta atingida');
assert_true(abs((float)($eventoAcimaDeCem['percentual_cumprimento'] ?? 0) - 129.41) < 0.01, 'Calcula cumprimento acima de 100% corretamente (110/85 = 129,41%)');
assert_true($indicadorEventos->updateAchievedValue((int)$createdEvents[5]['id'], '-10', 1, 'Percentual negativo'), 'Aceita valor negativo no evento quando o valor medido assim exigir');
assert_true(($indicadorEventos->find((int)$createdEvents[5]['id'])['meta_status_key'] ?? '') === 'nao_atingida', 'Valor negativo no evento continua classificado como abaixo do mínimo');
assert_true($indicadorEventos->updateAchievedValue((int)$createdEvents[1]['id'], '85', 1, 'Meta atendida'), 'Atualiza evento com meta atingida');
assert_true(($indicadorEventos->find((int)$createdEvents[1]['id'])['meta_status_key'] ?? '') === 'atingida', 'Marca meta atingida quando valor alcança o mínimo configurado');

$integerIndicadorId = $indicadores->create(array_merge($baseData, [
    'indicador' => 'Indicador Evento Inteiro ' . $suffix,
    'unidade_medida_id' => $unitInteger,
    'valor' => '10',
    'valor_minimo' => null,
    'valor_maximo' => null,
]), 1);
assert_true($integerIndicadorId > 0, 'Criou indicador inteiro para validar evento');
$integerEvents = $indicadorEventos->byIndicador($integerIndicadorId);
assert_true(count($integerEvents) === 12, 'Gerou eventos para indicador inteiro');
assert_true(!$indicadorEventos->updateAchievedValue((int)$integerEvents[0]['id'], '10,50', 1, 'Inteiro com decimal'), 'Rejeita evento com decimal quando unidade é inteira');
assert_true($indicadorEventos->updateAchievedValue((int)$integerEvents[0]['id'], '10', 1, 'Inteiro válido'), 'Aceita evento com inteiro quando unidade é inteira');

$moneyIndicadorId = $indicadores->create(array_merge($baseData, [
    'indicador' => 'Indicador Evento Monetário ' . $suffix,
    'unidade_medida_id' => $unitMoney,
    'valor' => '1000',
    'valor_minimo' => null,
    'valor_maximo' => null,
]), 1);
assert_true($moneyIndicadorId > 0, 'Criou indicador monetário para validar evento');
$moneyEvents = $indicadorEventos->byIndicador($moneyIndicadorId);
assert_true(!empty($moneyEvents), 'Gerou eventos para indicador monetário');
assert_true($indicadorEventos->updateAchievedValue((int)$moneyEvents[0]['id'], '6495', 1, 'Valor monetário sem separador'), 'Aceita evento monetário com número simples');
assert_true(abs((float)($indicadorEventos->find((int)$moneyEvents[0]['id'])['valor_atingido'] ?? 0) - 6495.0) < 0.0001, 'Persistiu valor monetário corretamente');

$maximoIndicadorId = $indicadores->create(array_merge($baseData, [
    'indicador' => 'Indicador Teto Maximo ' . $suffix,
    'tipo_meta' => 'maximo',
    'valor' => '40',
    'valor_minimo' => null,
    'valor_maximo' => null,
]), 1);
assert_true($maximoIndicadorId > 0, 'Cria indicador com tipo de meta teto máximo permitido');
$maximo = $indicadores->find($maximoIndicadorId);
assert_true(($maximo['tipo_meta'] ?? '') === 'maximo', 'Persistiu tipo de meta maximo');
$maximoEvents = $indicadorEventos->byIndicador($maximoIndicadorId);
assert_true(!empty($maximoEvents), 'Gerou eventos para indicador com teto máximo');
assert_true($indicadorEventos->updateAchievedValue((int)$maximoEvents[0]['id'], '35', 1, 'Dentro do teto'), 'Permite lançar valor abaixo do teto máximo');
assert_true(($indicadorEventos->find((int)$maximoEvents[0]['id'])['meta_status_key'] ?? '') === 'atingida', 'Valor abaixo do teto máximo fica com status bom');
assert_true($indicadorEventos->updateAchievedValue((int)$maximoEvents[1]['id'], '45', 1, 'Acima do teto'), 'Permite lançar valor acima do teto máximo');
assert_true(($indicadorEventos->find((int)$maximoEvents[1]['id'])['meta_status_key'] ?? '') === 'nao_atingida', 'Valor acima do teto máximo fica com status ruim');

$legacyIndicadorNome = 'Indicador Legado ' . $suffix;
$legacyStmt = $pdo->prepare('INSERT INTO indicadores
    (cliente_id, indicador, nome, departamento_id, setor_id, periodicidade_tipo, data_inicial, data_final, valor, unidade_medida_id, valor_minimo, valor_maximo, created_at, updated_at)
    VALUES
    (:cliente_id, :indicador, :nome, :departamento_id, :setor_id, :periodicidade_tipo, :data_inicial, :data_final, :valor, :unidade_medida_id, :valor_minimo, :valor_maximo, NOW(), NOW())');
$legacyStmt->execute([
    'cliente_id' => $clienteId,
    'indicador' => $legacyIndicadorNome,
    'nome' => $legacyIndicadorNome,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-12-31',
    'valor' => '50',
    'unidade_medida_id' => $unitPercent,
    'valor_minimo' => null,
    'valor_maximo' => null,
]);
$legacyIndicadorId = (int)$pdo->lastInsertId();
assert_true($legacyIndicadorId > 0, 'Cria indicador legado sem informar tipo de meta explicitamente');
$legacy = $indicadores->find($legacyIndicadorId);
assert_true(($legacy['tipo_meta'] ?? '') === 'minimo', 'Indicador legado assume o tipo minimo para manter retrocompatibilidade');
$indicadorEventos->syncForIndicator($legacy ?: ['id' => $legacyIndicadorId], 1);
$legacyEvents = $indicadorEventos->byIndicador($legacyIndicadorId);
assert_true(!empty($legacyEvents), 'Gera eventos para indicador legado');
assert_true($indicadorEventos->updateAchievedValue((int)$legacyEvents[0]['id'], '45', 1, 'Legado abaixo da meta'), 'Permite lançar valor em indicador legado');
assert_true(($indicadorEventos->find((int)$legacyEvents[0]['id'])['meta_status_key'] ?? '') === 'nao_atingida', 'Indicador legado continua usando a regra original de mínimo');

$deleted = $indicadores->softDelete($indicadorId, 1);
assert_true($deleted, 'Aplica soft delete no indicador');
assert_true($indicadorEventos->find((int)$createdEvents[0]['id']) === null, 'Oculta eventos do indicador após soft delete');

$duplicateAfterDelete = $indicadores->validate($baseData);
assert_true($duplicateAfterDelete === [], 'Soft delete libera novo cadastro com o mesmo nome por cliente');

assert_true($indicadores->updateValor($outOfRangeId, '88', 1), 'Atualiza valor lançado do indicador');
assert_true((float)($indicadores->find($outOfRangeId)['valor'] ?? 0) === 88.0, 'Persistiu atualização do valor lançado');
assert_true(!$indicadores->updateValor($outOfRangeId, '-12', 1), 'Bloqueia atualização negativa da meta do indicador');
assert_true($indicadores->update($outOfRangeId, array_merge($indicadores->find($outOfRangeId) ?: [], [
    'cliente_id' => $clienteId,
    'cliente_nome' => 'Cliente Indicadores ' . $suffix,
    'indicador' => 'Indicador Fora Faixa ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [$responsavelId],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-12-31',
    'valor' => '72',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unitPercent,
    'valor_minimo' => '60',
    'valor_maximo' => '90',
]), 1), 'Permite salvar indicador já existente mesmo sem mudança efetiva em todas as colunas');

echo "All indicadores validation tests passed.\n";
