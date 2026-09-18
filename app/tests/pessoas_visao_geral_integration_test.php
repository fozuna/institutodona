<?php
// Pilar de Pessoas - Sprint 04: Visão Geral (PessoaDashboardModel) com fixtures
// controladas: métrica de estado atual vs. evento no período, exclusão da
// Empresa B, filtros organizacionais, distribuição por faixas de
// PessoasAvaliacaoScale, ações vencidas (regra centralizada), tenant e
// número constante de consultas.
require __DIR__ . '/../autoload.php';

use App\Core\PessoasAvaliacaoScale;
use App\Core\PessoasGestaoConfig;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\PessoaDashboardModel;
use App\Models\SetorModel;

ob_start();
function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }
function asInstituto(): void { $_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []]; }
function asClienteAdmin(int $eid): void { $_SESSION['user'] = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $eid, 'allowed_client_ids' => [$eid]]; }
function eq($got, $exp, string $what): void { if ($got !== $exp) { failFast("$what: esperado " . json_encode($exp) . ' obtido ' . json_encode($got)); } }

asInstituto();
$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$c = ['clientes' => [], 'deps' => [], 'setores' => [], 'funcoes' => [], 'colabs' => [], 'tasks' => [], 'trein' => []];
register_shutdown_function(function () use ($pdo, &$c) {
    try {
        // FKs com ON DELETE CASCADE a partir de clientes/colaboradores removem avaliações, GAPs, ações, PDIs e vínculos.
        foreach ($c['tasks'] as $id) { $pdo->exec('DELETE FROM pdca_tasks WHERE id=' . (int)$id); }
        foreach ($c['trein'] as $id) { $pdo->exec('DELETE FROM treinamentos WHERE id=' . (int)$id); }
        foreach ($c['clientes'] as $id) {
            foreach (['pessoas_ciclos_avaliacao', 'pessoas_modelos_avaliacao'] as $t) { $pdo->exec("DELETE FROM $t WHERE empresa_id=" . (int)$id); }
        }
        foreach ($c['colabs'] as $id) { $pdo->exec('DELETE FROM colaboradores WHERE id=' . (int)$id); }
        foreach ($c['funcoes'] as $id) { $pdo->exec('DELETE FROM funcoes WHERE id=' . (int)$id); }
        foreach ($c['setores'] as $id) { $pdo->exec('DELETE FROM setores WHERE id=' . (int)$id); }
        foreach ($c['deps'] as $id) { $pdo->exec('DELETE FROM departamentos WHERE id=' . (int)$id); }
        foreach ($c['clientes'] as $id) { $pdo->exec('DELETE FROM clientes WHERE id=' . (int)$id); }
    } catch (\Throwable $e) {
    }
});

function ins(PDO $pdo, string $table, array $row): int
{
    $cols = array_keys($row);
    $pdo->prepare("INSERT INTO $table (" . implode(',', $cols) . ') VALUES (:' . implode(',:', $cols) . ')')->execute($row);
    return (int)$pdo->lastInsertId();
}

function empresa(PDO $pdo, string $tag, string $suffix, array &$c): array
{
    $eid = ins($pdo, 'clientes', ['nome_empresa' => "Emp Visao {$tag} {$suffix}", 'CNPJ' => '33.333.3' . substr($tag, 0, 1) . '/0001-' . substr($suffix, 0, 2), 'contato' => 'T']);
    $c['clientes'][] = $eid;
    $out = ['eid' => $eid, 'deps' => [], 'funs' => [], 'sets' => []];
    foreach ([1, 2] as $n) {
        $dep = (new DepartamentoModel())->create(['nome' => "Dep{$n} {$tag} {$suffix}", 'cliente_id' => $eid]); $c['deps'][] = $dep;
        $set = (new SetorModel())->create(['nome' => "Set{$n} {$tag} {$suffix}", 'departamento_id' => $dep]); $c['setores'][] = $set;
        $fun = (new FuncaoModel())->create(['nome' => "Fun{$n} {$tag} {$suffix}", 'setor_id' => $set]); $c['funcoes'][] = $fun;
        $out['deps'][$n] = $dep; $out['sets'][$n] = $set; $out['funs'][$n] = $fun;
    }
    $modelo = ins($pdo, 'pessoas_modelos_avaliacao', ['empresa_id' => $eid, 'nome' => "Modelo {$tag} {$suffix}"]);
    $out['ciclo'] = ins($pdo, 'pessoas_ciclos_avaliacao', ['empresa_id' => $eid, 'modelo_id' => $modelo, 'nome' => "Ciclo {$tag} {$suffix}", 'status' => 'aberto']);
    return $out;
}

$A = empresa($pdo, 'A', $suffix, $c);
$B = empresa($pdo, 'B', $suffix, $c);
$cols = new ColaboradorModel();
function colab(ColaboradorModel $cols, array &$c, string $nome, array $E, int $dep, int $ativo = 1): int
{
    $id = $cols->create(['nome' => $nome, 'funcao_id' => $E['funs'][$dep], 'cliente_id' => $E['eid'], 'ativo' => $ativo]);
    $c['colabs'][] = $id;
    return $id;
}
$eA = $A['eid'];
$a1 = colab($cols, $c, "A1 {$suffix}", $A, 1); $a2 = colab($cols, $c, "A2 {$suffix}", $A, 1);
$a3 = colab($cols, $c, "A3 {$suffix}", $A, 2); $a4 = colab($cols, $c, "A4 inativo {$suffix}", $A, 1, 0);
$a5 = colab($cols, $c, "A5 {$suffix}", $A, 2); $a6 = colab($cols, $c, "A6 {$suffix}", $A, 2);

$IN = '2026-03-15 10:00:00'; $OUT = '2026-01-10 10:00:00'; $past = '2020-01-01'; $future = '2099-12-31';
$av = fn(int $col, string $status, ?float $res, ?string $fin, int $eid, int $ciclo) => ins($pdo, 'pessoas_avaliacoes', ['empresa_id' => $eid, 'ciclo_id' => $ciclo, 'colaborador_id' => $col, 'status' => $status, 'resultado' => $res, 'finalizado_em' => $fin]);
$av($a1, 'finalizada', 4.50, $IN, $eA, $A['ciclo']);
$av($a2, 'finalizada', 2.50, $IN, $eA, $A['ciclo']);
$av($a3, 'finalizada', 5.00, $OUT, $eA, $A['ciclo']);
$av($a4, 'pendente', null, null, $eA, $A['ciclo']);
$av($a5, 'em_andamento', null, null, $eA, $A['ciclo']);
$av($a6, 'finalizada', null, $IN, $eA, $A['ciclo']);

$gap = fn(int $col, string $status, ?string $res, int $eid, string $t) => ins($pdo, 'pessoas_gaps', ['empresa_id' => $eid, 'colaborador_id' => $col, 'titulo' => $t, 'status' => $status, 'resolvido_em' => $res]);
$gap($a1, 'aberto', null, $eA, 'g1'); $gap($a2, 'aberto', null, $eA, 'g2'); $gap($a3, 'em_tratamento', null, $eA, 'g3');
$gap($a1, 'resolvido', $IN, $eA, 'g4'); $gap($a1, 'resolvido', $OUT, $eA, 'g5');

$acao = fn(int $col, string $status, ?string $prazo, ?string $conc, int $eid, string $t) => ins($pdo, 'pessoas_acoes_melhoria', ['empresa_id' => $eid, 'colaborador_id' => $col, 'titulo' => $t, 'status' => $status, 'prazo' => $prazo, 'concluido_em' => $conc]);
$ac1 = $acao($a1, 'pendente', $past, null, $eA, 'ac1');
$ac2 = $acao($a2, 'em_andamento', $future, null, $eA, 'ac2');
$ac3 = $acao($a3, 'concluida', $past, $IN, $eA, 'ac3');
$acao($a3, 'concluida', $past, $OUT, $eA, 'ac4');
$acao($a5, 'cancelada', $past, null, $eA, 'ac5');
$acao($a5, 'pendente', null, null, $eA, 'ac6');

$task = ins($pdo, 'pdca_tasks', ['id_cliente' => $eA, 'titulo' => "Plano visao {$suffix}"]); $c['tasks'][] = $task;
$trein = ins($pdo, 'treinamentos', ['nome' => "Trein visao {$suffix}", 'cliente_id' => $eA, 'departamento_id' => $A['deps'][1]]); $c['trein'][] = $trein;
ins($pdo, 'pessoas_acao_planos', ['empresa_id' => $eA, 'acao_melhoria_id' => $ac1, 'plano_task_id' => $task, 'created_at' => $IN]);
ins($pdo, 'pessoas_acao_treinamentos', ['empresa_id' => $eA, 'acao_melhoria_id' => $ac2, 'treinamento_id' => $trein, 'created_at' => $OUT]);
ins($pdo, 'pessoas_acao_treinamentos', ['empresa_id' => $eA, 'acao_melhoria_id' => $ac3, 'treinamento_id' => $trein, 'created_at' => $IN]);
foreach ([['pendente', 'n1'], ['pendente', 'n2'], ['atendida', 'n3']] as [$st, $t]) {
    ins($pdo, 'pessoas_necessidades_treinamento', ['empresa_id' => $eA, 'colaborador_id' => $a1, 'acao_melhoria_id' => $ac1, 'titulo' => $t, 'status' => $st]);
}

$pdi = fn(int $col, string $status, ?string $fimPrev, ?string $conc, int $eid, string $t) => ins($pdo, 'pessoas_pdis', ['empresa_id' => $eid, 'colaborador_id' => $col, 'titulo' => $t, 'status' => $status, 'data_inicio' => '2026-01-01', 'data_fim_prevista' => $fimPrev, 'concluido_em' => $conc]);
$obj = fn(int $pdiId, string $status, ?string $prazo) => ins($pdo, 'pessoas_pdi_objetivos', ['pdi_id' => $pdiId, 'titulo' => 'o', 'status' => $status, 'prazo' => $prazo]);
$p1 = $pdi($a1, 'rascunho', null, null, $eA, 'p1'); $obj($p1, 'pendente', $past); // rascunho: objetivo vencido NÃO conta
$p2 = $pdi($a2, 'ativo', $past, null, $eA, 'p2');
$obj($p2, 'pendente', $past); $obj($p2, 'em_andamento', $future); $obj($p2, 'concluido', $past); $obj($p2, 'cancelado', $past);
$pdi($a3, 'concluido', null, $IN, $eA, 'p3'); $pdi($a1, 'concluido', null, $OUT, $eA, 'p4'); $pdi($a5, 'cancelado', null, null, $eA, 'p5');

// ---- Empresa B: volume que NÃO pode aparecer nos números de A ----
$eB = $B['eid'];
$b1 = colab($cols, $c, "B1 {$suffix}", $B, 1);
for ($i = 0; $i < 3; $i++) {
    $bx = colab($cols, $c, "Bx{$i} {$suffix}", $B, 1);
    $av($bx, 'pendente', null, null, $eB, $B['ciclo']);
}
$av($b1, 'finalizada', 1.00, $IN, $eB, $B['ciclo']);
$gap($b1, 'aberto', null, $eB, 'gb'); $acao($b1, 'pendente', $past, null, $eB, 'acb');
$pb = $pdi($b1, 'ativo', $past, null, $eB, 'pb'); $obj($pb, 'pendente', $past);
ok('Fixtures controladas: Empresa A (5 ativos + 1 inativo) e Empresa B (volume que deve ser excluído)');

$dash = new PessoaDashboardModel();
$P = ['inicio' => '2026-03-01', 'fim' => '2026-03-31'];
$R = $dash->resumo([$eA], $P);

// ---- Estado atual ----
eq($R['colaboradores_ativos'], 5, 'colaboradores ativos (inativo excluído)');
eq($R['avaliacoes']['pendentes'], 1, 'avaliações pendentes');
eq($R['avaliacoes']['em_andamento'], 1, 'avaliações em andamento');
eq($R['gaps']['abertos'], 2, 'GAPs abertos'); eq($R['gaps']['em_tratamento'], 1, 'GAPs em tratamento');
eq($R['acoes']['pendentes'], 2, 'ações pendentes'); eq($R['acoes']['em_andamento'], 1, 'ações em andamento');
eq($R['acoes']['vencidas'], 1, 'ações vencidas (só ac1: pendente e prazo passado; cancelada/concluída não contam)');
eq($R['necessidades_pendentes'], 2, 'necessidades pendentes');
eq($R['pdis']['rascunho'], 1, 'PDIs rascunho'); eq($R['pdis']['ativos'], 1, 'PDIs ativos');
eq($R['pdis']['ativos_atrasados'], 1, 'PDIs ativos atrasados');
eq($R['objetivos_vencidos'], 1, 'objetivos vencidos (só pendente do PDI ativo)');
ok('Métricas de estado atual corretas');

// ---- Evento no período (março/2026) ----
eq($R['avaliacoes']['finalizadas'], 3, 'avaliações finalizadas no período (inclui uma sem resultado)');
eq($R['avaliacoes']['media'], 3.5, 'resultado médio (ignora NULL e fora do período)');
eq($R['gaps']['resolvidos'], 1, 'GAPs resolvidos no período');
eq($R['acoes']['concluidas'], 1, 'ações concluídas no período');
eq($R['pdis']['concluidos'], 1, 'PDIs concluídos no período');
eq($R['encaminhadas'], ['planos' => 1, 'treinamentos' => 1], 'ações encaminhadas no período (vínculo fora do período não conta)');
ok('Métricas de evento no período corretas (finalizado_em / resolvido_em / concluido_em / created_at do vínculo)');

// ---- Distribuição via PessoasAvaliacaoScale ----
$bands = PessoasAvaliacaoScale::classificationBands();
eq(count($R['distribuicao']), count($bands), 'nº de faixas');
foreach ($R['distribuicao'] as $i => $d) { eq($d['label'], $bands[$i]['label'], "label da faixa $i (vem da Scale)"); }
$porLabel = array_column($R['distribuicao'], 'total', 'label');
eq($porLabel['Excelente'], 1, 'faixa Excelente (4,50)'); eq($porLabel['Abaixo do esperado'], 1, 'faixa Abaixo (2,50)');
eq(array_sum($porLabel), 2, 'soma da distribuição = avaliações finalizadas com resultado no período');
ok('Distribuição de desempenho usa as faixas de PessoasAvaliacaoScale (sem limites duplicados)');

// ---- Empresa B excluída / somada corretamente ----
$RB = $dash->resumo([$eB], $P);
eq($RB['colaboradores_ativos'], 4, 'B: colaboradores ativos'); eq($RB['avaliacoes']['pendentes'], 3, 'B: pendentes');
eq($RB['avaliacoes']['media'], 1.0, 'B: média isolada');
$RAB = $dash->resumo([$eA, $eB], $P);
eq($RAB['colaboradores_ativos'], 9, 'A+B: colaboradores'); eq($RAB['acoes']['vencidas'], 2, 'A+B: ações vencidas');
eq($RAB['objetivos_vencidos'], 2, 'A+B: objetivos vencidos');
ok('Empresa B não contamina A; A+B soma corretamente');

// ---- Período ----
$R2 = $dash->resumo([$eA], ['inicio' => '2026-01-01', 'fim' => '2026-01-31']);
eq($R2['avaliacoes']['finalizadas'], 1, 'jan: finalizadas'); eq($R2['avaliacoes']['media'], 5.0, 'jan: média');
eq($R2['gaps']['resolvidos'], 1, 'jan: GAPs resolvidos'); eq($R2['acoes']['concluidas'], 1, 'jan: ações concluídas');
eq($R2['pdis']['concluidos'], 1, 'jan: PDIs concluídos'); eq($R2['encaminhadas'], ['planos' => 0, 'treinamentos' => 1], 'jan: encaminhadas');
foreach ([['avaliacoes', 'pendentes'], ['avaliacoes', 'em_andamento'], ['gaps', 'abertos'], ['gaps', 'em_tratamento'], ['acoes', 'pendentes'], ['acoes', 'em_andamento'], ['acoes', 'vencidas'], ['pdis', 'rascunho'], ['pdis', 'ativos']] as [$g, $k]) {
    eq($R2[$g][$k], $R[$g][$k], "estado atual '$g.$k' NÃO depende do período");
}
eq($R2['colaboradores_ativos'], $R['colaboradores_ativos'], 'colaboradores ativos independe do período');
eq($R2['objetivos_vencidos'], $R['objetivos_vencidos'], 'objetivos vencidos independe do período');
$R3 = $dash->resumo([$eA], ['inicio' => '2026-03-31', 'fim' => '2026-03-31']);
eq($R3['avaliacoes']['finalizadas'], 0, 'período de 1 dia sem eventos');
$R4 = $dash->resumo([$eA], ['inicio' => '2026-03-15', 'fim' => '2026-03-15']);
eq($R4['avaliacoes']['finalizadas'], 3, 'limite inclusivo do último dia (fim exclusivo = dia seguinte 00:00)');
ok('Período: eventos filtram; estado atual não muda');

// ---- Filtros organizacionais ----
$d1 = $dash->resumo([$eA], $P + ['departamento_id' => $A['deps'][1]]);
eq($d1['colaboradores_ativos'], 2, 'dep1: colaboradores ativos'); eq($d1['avaliacoes']['pendentes'], 1, 'dep1: pendentes');
eq($d1['avaliacoes']['finalizadas'], 2, 'dep1: finalizadas'); eq($d1['avaliacoes']['media'], 3.5, 'dep1: média');
eq($d1['gaps']['abertos'], 2, 'dep1: gaps abertos'); eq($d1['acoes']['vencidas'], 1, 'dep1: ações vencidas');
$d2 = $dash->resumo([$eA], $P + ['departamento_id' => $A['deps'][2]]);
eq($d2['colaboradores_ativos'], 3, 'dep2: colaboradores ativos'); eq($d2['avaliacoes']['em_andamento'], 1, 'dep2: em andamento');
eq($d2['avaliacoes']['finalizadas'], 1, 'dep2: finalizadas'); eq($d2['avaliacoes']['media'], null, 'dep2: média nula (sem resultado)');
eq($d2['pdis']['concluidos'], 1, 'dep2: PDI concluído (A3)');
$s2 = $dash->resumo([$eA], $P + ['setor_id' => $A['sets'][2]]);
eq($s2['colaboradores_ativos'], 3, 'setor2: colaboradores');
$f1 = $dash->resumo([$eA], $P + ['funcao_id' => $A['funs'][1]]);
eq($f1['colaboradores_ativos'], 2, 'função1: colaboradores');
ok('Filtros Departamento/Setor/Função atuam sobre o colaborador');

// ---- Tenant ----
asClienteAdmin($eA);
$RT = $dash->resumo([$eA, $eB], $P);
eq($RT, $R, 'Cliente Admin de A pedindo A+B recebe somente A');
eq($dash->resumo([$eB], $P)['colaboradores_ativos'], 0, 'Cliente Admin de A não enxerga B');
eq($dash->resumo([], $P)['colaboradores_ativos'], 0, 'sem empresas => zero');
asInstituto();
ok('Tenant: escopo do usuário sempre aplicado no Model');

// ---- Consultas constantes (sem N+1) ----
$q = function () use ($pdo): int { return (int)$pdo->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch(PDO::FETCH_NUM)[1]; };
$q0 = $q(); $dash->resumo([$eA], $P); $qa = $q() - $q0 - 1;
$q0 = $q(); $dash->resumo([$eA, $eB], $P); $qab = $q() - $q0 - 1;
if ($qa !== $qab || $qa > 40) { failFast("Número de consultas deveria ser constante e baixo: A=$qa, A+B=$qab"); }
ok("Número de consultas constante ($qa) independente do volume");

// ---- Regra de vencido centralizada ----
if (!PessoasGestaoConfig::isObjetivoVencido($past, 'pendente') || PessoasGestaoConfig::isObjetivoVencido($past, 'concluido') || PessoasGestaoConfig::isObjetivoVencido($future, 'pendente') || PessoasGestaoConfig::isObjetivoVencido(null, 'pendente')) { failFast('isObjetivoVencido'); }
ok('Regra de vencido centralizada em PessoasGestaoConfig');

echo "\nTodos os testes da Visão Geral passaram.\n";
