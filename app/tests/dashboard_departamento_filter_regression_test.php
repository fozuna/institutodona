<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\DashboardController;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\IndicadorEventoModel;
use App\Models\IndicadorModel;
use App\Models\ManualModel;
use App\Models\SetorModel;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;

$GLOBALS['depOkLog'] = [];
function dep_ok(string $msg): void { $GLOBALS['depOkLog'][] = $msg; }
function dep_fail(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

try {
    Database::getConnection();
} catch (\Throwable $e) {
    echo "SKIP: Sem conexão com DB no ambiente atual.\n";
    exit(0);
}

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'manual_ids' => [], 'auditoria_ids' => [], 'agenda_ids' => [], 'treinamento_ids' => [],
    'indicador_ids' => [], 'setor_ids' => [], 'departamento_ids' => [], 'cliente_ids' => [], 'unidade_ids' => [],
];

register_shutdown_function(function () use ($pdo, &$cleanup): void {
    try {
        foreach ($cleanup['manual_ids'] as $id) { $pdo->prepare('DELETE FROM manuais WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['auditoria_ids'] as $id) { $pdo->prepare('DELETE FROM auditorias WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['agenda_ids'] as $id) { $pdo->prepare('DELETE FROM treinamentos_agenda WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['treinamento_ids'] as $id) { $pdo->prepare('DELETE FROM treinamentos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['indicador_ids'] as $id) {
            $pdo->prepare('DELETE FROM indicador_eventos WHERE indicador_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['unidade_ids'] as $id) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {
    }
});

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$manuais = new ManualModel();
$indicadorModel = new IndicadorModel();
$eventoModel = new IndicadorEventoModel();
$treinamentoModel = new TreinamentoModel();
$agendaModel = new TreinamentoAgendaModel();

$clienteId = $clientes->create(['nome_empresa' => 'Cliente Depto ' . $suffix, 'CNPJ' => '11.222.333/0001-' . substr($suffix, 0, 2), 'contato' => 'Teste']);
if ($clienteId <= 0) dep_fail('Falha ao criar cliente A');
$cleanup['cliente_ids'][] = $clienteId;

$outroClienteId = $clientes->create(['nome_empresa' => 'Cliente Depto Outro ' . $suffix, 'CNPJ' => '11.222.334/0001-' . substr($suffix, 0, 2), 'contato' => 'Teste']);
if ($outroClienteId <= 0) dep_fail('Falha ao criar cliente B (outro tenant)');
$cleanup['cliente_ids'][] = $outroClienteId;

$depA1 = $departamentos->create(['nome' => 'Dep A1 ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$depA2 = $departamentos->create(['nome' => 'Dep A2 ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$depB1 = $departamentos->create(['nome' => 'Dep B1 ' . $suffix, 'cliente_id' => $outroClienteId, 'cliente_ids' => [$outroClienteId]]);
if ($depA1 <= 0 || $depA2 <= 0 || $depB1 <= 0) dep_fail('Falha ao criar departamentos de teste');
$cleanup['departamento_ids'] = [$depA1, $depA2, $depB1];
dep_ok('Criou 2 departamentos no Cliente A e 1 departamento no Cliente B (outro tenant)');

$setorA1 = $setores->create(['nome' => 'Setor A1 ' . $suffix, 'departamento_id' => $depA1]);
$setorA2 = $setores->create(['nome' => 'Setor A2 ' . $suffix, 'departamento_id' => $depA2]);
$cleanup['setor_ids'] = [$setorA1, $setorA2];

$monthStart = date('Y-m');
$monthEnd = date('Y-m');
$today = date('Y-m-d');

// Biblioteca: 1 manual em cada departamento.
$manualA1 = $manuais->create(['empresa_id' => $clienteId, 'departamento_id' => $depA1, 'nome' => 'Manual A1 ' . $suffix, 'descricao' => '', 'arquivo' => 'a1.pdf', 'tipo_arquivo' => 'pdf', 'tamanho' => 100, 'usuario_id' => 1]);
$manualA2 = $manuais->create(['empresa_id' => $clienteId, 'departamento_id' => $depA2, 'nome' => 'Manual A2 ' . $suffix, 'descricao' => '', 'arquivo' => 'a2.pdf', 'tipo_arquivo' => 'pdf', 'tamanho' => 100, 'usuario_id' => 1]);
if ($manualA1 <= 0 || $manualA2 <= 0) dep_fail('Falha ao criar manuais de teste');
$cleanup['manual_ids'] = [$manualA1, $manualA2];
dep_ok('Criou 1 manual em cada departamento (Biblioteca)');

// Auditorias: 1 realizada em cada departamento (via setor), dentro do período.
$stmt = $pdo->prepare("INSERT INTO auditorias (cliente_id, setor_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, conformidade_pct, created_by, updated_by)
    VALUES (:cid, :sid, :data, :nome, 'Pergunta teste', 'Objetivo teste', 'Referencia teste', 'Realizada', :pct, 1, 1)");
$stmt->execute(['cid' => $clienteId, 'sid' => $setorA1, 'data' => $today, 'nome' => 'Auditoria A1 ' . $suffix, 'pct' => 90]);
$auditoriaA1 = (int)$pdo->lastInsertId();
$stmt->execute(['cid' => $clienteId, 'sid' => $setorA2, 'data' => $today, 'nome' => 'Auditoria A2 ' . $suffix, 'pct' => 70]);
$auditoriaA2 = (int)$pdo->lastInsertId();
$cleanup['auditoria_ids'] = [$auditoriaA1, $auditoriaA2];
dep_ok('Criou 1 auditoria realizada em cada departamento (via setor)');

// Indicadores: 1 indicador com evento lançado em cada departamento.
$stmtUnidade = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:n, :s, :t, 1)');
$stmtUnidade->execute(['n' => 'Unidade Depto ' . $suffix, 's' => 'un', 't' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_ids'][] = $unidadeId;

$indicadorIdsPorDep = [];
foreach ([$depA1 => $setorA1, $depA2 => $setorA2] as $dep => $setor) {
    $payload = [
        'cliente_id' => $clienteId,
        'indicador' => 'Indicador Depto ' . $dep . ' ' . $suffix,
        'departamento_id' => $dep,
        'setor_id' => $setor,
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
    $errors = $indicadorModel->validate($payload);
    if ($errors) dep_fail('Payload de indicador inválido: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
    $indicadorId = $indicadorModel->create($payload, 1);
    if ($indicadorId <= 0) dep_fail('Falha ao criar indicador do departamento ' . $dep);
    $cleanup['indicador_ids'][] = $indicadorId;
    $eventos = $eventoModel->byIndicador($indicadorId);
    if (empty($eventos)) dep_fail('Indicador não gerou eventos automaticamente');
    if (!$eventoModel->updateAchievedValue((int)$eventos[0]['id'], '50', 1)) dep_fail('Falha ao lançar valor no evento do indicador');
    $indicadorIdsPorDep[$dep] = $indicadorId;
}
dep_ok('Criou 1 indicador com valor lançado em cada departamento');

// Treinamentos: 1 sessão de agenda em cada departamento.
$agendaIdsPorDep = [];
foreach ([$depA1, $depA2] as $dep) {
    $treinamentoId = $treinamentoModel->create([
        'nome' => 'Treinamento Depto ' . $dep . ' ' . $suffix,
        'cliente_id' => $clienteId,
        'departamento_id' => $dep,
        'carga_horaria' => '',
        'setor_ids' => [],
        'funcao_ids' => [],
    ]);
    if ($treinamentoId <= 0) dep_fail('Falha ao criar treinamento do departamento ' . $dep);
    $cleanup['treinamento_ids'][] = $treinamentoId;
    $agendaId = $agendaModel->create(['treinamento_id' => $treinamentoId, 'data' => $today, 'unidade_id' => $clienteId]);
    if ($agendaId <= 0) dep_fail('Falha ao agendar treinamento do departamento ' . $dep);
    $cleanup['agenda_ids'][] = $agendaId;
}
dep_ok('Criou 1 sessão de treinamento agendada em cada departamento');

// --- apiDepartamentos: escopo por tenant ---
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'dashboard/apiDepartamentos', 'clientes' => [$clienteId]];
ob_start();
(new DashboardController())->apiDepartamentos();
$json = json_decode((string)ob_get_clean(), true);
$ids = array_column($json['departamentos'] ?? [], 'id');
if (!in_array($depA1, $ids, true) || !in_array($depA2, $ids, true)) dep_fail('apiDepartamentos não retornou os departamentos do cliente selecionado');
if (in_array($depB1, $ids, true)) dep_fail('apiDepartamentos vazou departamento de outro cliente/tenant');
dep_ok('apiDepartamentos retorna somente departamentos do cliente selecionado, sem vazar de outro tenant');

$_GET = ['route' => 'dashboard/apiDepartamentos', 'clientes' => [$outroClienteId]];
ob_start();
(new DashboardController())->apiDepartamentos();
$json = json_decode((string)ob_get_clean(), true);
$ids = array_column($json['departamentos'] ?? [], 'id');
if (in_array($depA1, $ids, true) || in_array($depA2, $ids, true)) dep_fail('apiDepartamentos misturou departamentos entre clientes diferentes');
dep_ok('apiDepartamentos ao trocar de empresa não mistura departamentos da empresa anterior');

// --- metrics: sem filtro de departamento = consolidado ---
$_GET = ['route' => 'dashboard/metrics', 'month_start' => $monthStart, 'month_end' => $monthEnd, 'clientes' => [$clienteId]];
ob_start();
(new DashboardController())->metrics();
$payload = json_decode((string)ob_get_clean(), true);
if (($payload['biblioteca']['total_itens'] ?? 0) !== 2) dep_fail('Sem filtro de departamento, Biblioteca deveria consolidar os 2 departamentos (obtido: ' . ($payload['biblioteca']['total_itens'] ?? 'null') . ')');
if (($payload['auditorias']['total_realizadas'] ?? 0) !== 2) dep_fail('Sem filtro de departamento, Auditorias deveria consolidar os 2 departamentos');
if (($payload['indicadores']['total_eventos'] ?? 0) !== 2) dep_fail('Sem filtro de departamento, Indicadores deveria consolidar os 2 departamentos');
if (($payload['treinamentos']['planejados'] ?? 0) !== 2) dep_fail('Sem filtro de departamento, Treinamentos deveria consolidar os 2 departamentos');
dep_ok('Sem departamento selecionado, todos os 4 blocos mostram o consolidado dos 2 departamentos');

// --- metrics: filtrando pelo departamento A1 ---
$_GET = ['route' => 'dashboard/metrics', 'month_start' => $monthStart, 'month_end' => $monthEnd, 'clientes' => [$clienteId], 'departamento_id' => $depA1];
ob_start();
(new DashboardController())->metrics();
$payloadA1 = json_decode((string)ob_get_clean(), true);
if (($payloadA1['biblioteca']['total_itens'] ?? -1) !== 1) dep_fail('Filtro por Dep A1 deveria retornar 1 item de Biblioteca (obtido: ' . ($payloadA1['biblioteca']['total_itens'] ?? 'null') . ')');
if (($payloadA1['auditorias']['total_realizadas'] ?? -1) !== 1) dep_fail('Filtro por Dep A1 deveria retornar 1 auditoria realizada');
if (abs((float)($payloadA1['auditorias']['media_conformidade_pct'] ?? -1) - 90.0) > 0.01) dep_fail('Filtro por Dep A1 deveria usar somente a conformidade da auditoria do Dep A1 (90%)');
if (($payloadA1['indicadores']['total_eventos'] ?? -1) !== 1) dep_fail('Filtro por Dep A1 deveria retornar 1 evento de indicador');
if (($payloadA1['treinamentos']['planejados'] ?? -1) !== 1) dep_fail('Filtro por Dep A1 deveria retornar 1 sessão de treinamento');
dep_ok('Filtro por departamento A1 restringe corretamente Biblioteca, Auditorias, Indicadores e Treinamentos');

// --- metrics: departamento de outro tenant é ignorado com segurança (cai no consolidado, não quebra e não vaza) ---
$_GET = ['route' => 'dashboard/metrics', 'month_start' => $monthStart, 'month_end' => $monthEnd, 'clientes' => [$clienteId], 'departamento_id' => $depB1];
ob_start();
(new DashboardController())->metrics();
$payloadRejected = json_decode((string)ob_get_clean(), true);
if (($payloadRejected['biblioteca']['total_itens'] ?? -1) !== 2) dep_fail('Departamento de outro tenant deveria ser ignorado e cair no consolidado (Biblioteca)');
if (($payloadRejected['ok'] ?? false) !== true) dep_fail('Departamento inválido/de outro tenant não deveria quebrar a resposta');
dep_ok('Departamento pertencente a outro cliente/tenant é rejeitado com segurança e cai no consolidado, sem erro');

foreach ($GLOBALS['depOkLog'] as $msg) {
    echo "OK: {$msg}\n";
}
echo "Dashboard departamento filter regression tests passed.\n";
