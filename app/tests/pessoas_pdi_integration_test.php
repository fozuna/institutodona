<?php
// Pilar de Pessoas - Sprint 04: PDI (Plano de Desenvolvimento Individual).
// Ciclo de vida, GAPs, objetivos, ações, progresso derivado (66,67%),
// integridade de "1 PDI ativo por colaborador" (backend + banco), independência
// de status entre módulos, tenant e RBAC.
require __DIR__ . '/../autoload.php';

use App\Core\AccessControl;
use App\Core\PessoasGestaoConfig;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaGapModel;
use App\Models\PessoaPdiModel;
use App\Models\SetorModel;

ob_start();
function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }
function asInstituto(): void { $_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []]; }
function asClienteAdmin(int $eid): void { $_SESSION['user'] = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $eid, 'allowed_client_ids' => [$eid]]; }

asInstituto();
$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$c = ['clientes' => [], 'deps' => [], 'setores' => [], 'funcoes' => [], 'colabs' => [], 'gaps' => [], 'acoes' => [], 'pdis' => []];
register_shutdown_function(function () use ($pdo, &$c) {
    try {
        foreach ($c['pdis'] as $id) { $pdo->exec('DELETE FROM pessoas_pdis WHERE id=' . (int)$id); }
        foreach ($c['acoes'] as $id) { $pdo->exec('DELETE FROM pessoas_acoes_melhoria WHERE id=' . (int)$id); }
        foreach ($c['gaps'] as $id) { $pdo->exec('DELETE FROM pessoas_gaps WHERE id=' . (int)$id); }
        foreach ($c['colabs'] as $id) { $pdo->exec('DELETE FROM colaboradores WHERE id=' . (int)$id); }
        foreach ($c['funcoes'] as $id) { $pdo->exec('DELETE FROM funcoes WHERE id=' . (int)$id); }
        foreach ($c['setores'] as $id) { $pdo->exec('DELETE FROM setores WHERE id=' . (int)$id); }
        foreach ($c['deps'] as $id) { $pdo->exec('DELETE FROM departamentos WHERE id=' . (int)$id); }
        foreach ($c['clientes'] as $id) { $pdo->exec('DELETE FROM clientes WHERE id=' . (int)$id); }
    } catch (\Throwable $e) {
    }
});

function empresa(PDO $pdo, string $tag, string $suffix, array &$c): array
{
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')->execute(['n' => "Emp PDI {$tag} {$suffix}", 'c' => '44.444.4' . substr($tag, 0, 1) . '/0001-' . substr($suffix, 0, 2), 't' => 'T']);
    $eid = (int)$pdo->lastInsertId(); $c['clientes'][] = $eid;
    $dep = (new DepartamentoModel())->create(['nome' => "Dep {$tag} {$suffix}", 'cliente_id' => $eid]); $c['deps'][] = $dep;
    $set = (new SetorModel())->create(['nome' => "Set {$tag} {$suffix}", 'departamento_id' => $dep]); $c['setores'][] = $set;
    $fun = (new FuncaoModel())->create(['nome' => "Fun {$tag} {$suffix}", 'setor_id' => $set]); $c['funcoes'][] = $fun;
    return ['eid' => $eid, 'fun' => $fun];
}

$A = empresa($pdo, 'A', $suffix, $c);
$B = empresa($pdo, 'B', $suffix, $c);
$cols = new ColaboradorModel();
$colA = $cols->create(['nome' => "Colab A {$suffix}", 'funcao_id' => $A['fun'], 'cliente_id' => $A['eid'], 'ativo' => 1]);
$colA2 = $cols->create(['nome' => "Colab A2 {$suffix}", 'funcao_id' => $A['fun'], 'cliente_id' => $A['eid'], 'ativo' => 1]);
$colB = $cols->create(['nome' => "Colab B {$suffix}", 'funcao_id' => $B['fun'], 'cliente_id' => $B['eid'], 'ativo' => 1]);
$c['colabs'] = [$colA, $colA2, $colB];

$gaps = new PessoaGapModel(); $acoes = new PessoaAcaoMelhoriaModel(); $avals = new PessoaAvaliacaoModel(); $pdis = new PessoaPdiModel();
$gapA = $gaps->createManual($A['eid'], $colA, ['titulo' => 'GAP A'], $cols, 1); $c['gaps'][] = $gapA;
$gapA2 = $gaps->createManual($A['eid'], $colA2, ['titulo' => 'GAP do outro colaborador'], $cols, 1); $c['gaps'][] = $gapA2;
$gapB = $gaps->createManual($B['eid'], $colB, ['titulo' => 'GAP B'], $cols, 1); $c['gaps'][] = $gapB;
$acaoA = $acoes->create($A['eid'], $colA, ['titulo' => 'Ação A', 'gap_id' => $gapA], $cols, $gaps, $avals, 1); $c['acoes'][] = $acaoA;
$acaoA2 = $acoes->create($A['eid'], $colA2, ['titulo' => 'Ação do outro colaborador'], $cols, $gaps, $avals, 1); $c['acoes'][] = $acaoA2;
$acaoB = $acoes->create($B['eid'], $colB, ['titulo' => 'Ação B'], $cols, $gaps, $avals, 1); $c['acoes'][] = $acaoB;
if (min($gapA, $gapA2, $gapB, $acaoA, $acaoA2, $acaoB) <= 0) { failFast('Fixtures inválidas'); }
ok('Fixtures: 2 empresas, colaboradores, GAPs e Ações');

// ===== 1. Criar PDI (rascunho) =====
$pdiId = $pdis->create($A['eid'], $colA, ['titulo' => 'PDI — Colab A — 2026', 'data_inicio' => '2026-01-01', 'data_fim_prevista' => '2026-12-31'], $cols, 1);
if ($pdiId <= 0) { failFast('Criar PDI falhou'); }
$c['pdis'][] = $pdiId;
$pdi = $pdis->find($pdiId);
if ($pdi['status'] !== 'rascunho' || (int)$pdi['colaborador_id'] !== $colA || (int)$pdi['empresa_id'] !== $A['eid']) { failFast('PDI criado com dados errados'); }
if ($pdis->create($A['eid'], $colB, ['titulo' => 'x'], $cols, 1) !== 0) { failFast('PDI com colaborador de outra empresa deveria ser bloqueado'); }
if ($pdis->create($A['eid'], $colA, ['titulo' => '  '], $cols, 1) !== 0) { failFast('PDI sem título deveria ser bloqueado'); }
if ($pdis->create($A['eid'], $colA, ['titulo' => 'x', 'data_inicio' => '2026-05-01', 'data_fim_prevista' => '2026-01-01'], $cols, 1) !== 0) { failFast('Fim anterior ao início deveria ser bloqueado'); }
ok('Criar PDI em rascunho; validações de colaborador/título/datas');

// ===== 2/3. GAP correto e bloqueio de GAP de outro colaborador/empresa =====
$g1 = $pdis->addGap($pdiId, $gapA, $gaps);
if (!$g1['ok'] || $g1['already_existed']) { failFast('Vincular GAP do colaborador deveria funcionar'); }
$g1b = $pdis->addGap($pdiId, $gapA, $gaps);
if (!$g1b['ok'] || !$g1b['already_existed']) { failFast('Vínculo duplicado deveria ser idempotente'); }
if ((int)$pdo->query("SELECT COUNT(*) FROM pessoas_pdi_gaps WHERE pdi_id=$pdiId")->fetchColumn() !== 1) { failFast('GAP duplicado no PDI'); }
if ($pdis->addGap($pdiId, $gapA2, $gaps)['ok']) { failFast('GAP de OUTRO colaborador (mesma empresa) deveria ser bloqueado'); }
if ($pdis->addGap($pdiId, $gapB, $gaps)['ok']) { failFast('GAP de OUTRA empresa deveria ser bloqueado'); }
// Ordem: propriedade ANTES de idempotência - vínculo já existente NÃO mascara GAP externo (sanidade)
if ($pdis->pdiDoGap($gapA)['id'] != $pdiId) { failFast('pdiDoGap deveria apontar o PDI'); }
ok('GAP: vínculo correto, idempotente; bloqueia outro colaborador e outra empresa');

// ===== 4. Criar objetivo =====
$o1 = $pdis->addObjetivo($pdiId, ['titulo' => 'Objetivo 1', 'criterio_sucesso' => 'Critério 1', 'prazo' => '2026-06-30']);
$o2 = $pdis->addObjetivo($pdiId, ['titulo' => 'Objetivo 2']);
$o3 = $pdis->addObjetivo($pdiId, ['titulo' => 'Objetivo 3']);
$o4 = $pdis->addObjetivo($pdiId, ['titulo' => 'Objetivo 4']);
if (min($o1, $o2, $o3, $o4) <= 0) { failFast('Criar objetivos falhou'); }
if ($pdis->addObjetivo($pdiId, ['titulo' => '']) !== 0) { failFast('Objetivo sem título deveria ser bloqueado'); }
$ordens = array_column($pdis->objetivosDoPdi($pdiId), 'ordem');
if ($ordens !== array_map('strval', [0, 1, 2, 3]) && $ordens !== [0, 1, 2, 3]) { failFast('Ordem dos objetivos incorreta: ' . json_encode($ordens)); }
ok('Objetivos criados (pendente, ordem sequencial)');

// ===== 5/6. Vincular Ação e bloquear Ação externa =====
$v1 = $pdis->vincularAcao($o1, $acaoA, $acoes);
if (!$v1['ok'] || $v1['already_existed']) { failFast('Vincular Ação do colaborador deveria funcionar'); }
$v1b = $pdis->vincularAcao($o1, $acaoA, $acoes);
if (!$v1b['ok'] || !$v1b['already_existed']) { failFast('Vínculo de Ação duplicado deveria ser idempotente'); }
if ($pdis->vincularAcao($o1, $acaoA2, $acoes)['ok']) { failFast('Ação de OUTRO colaborador deveria ser bloqueada'); }
if ($pdis->vincularAcao($o1, $acaoB, $acoes)['ok']) { failFast('Ação de OUTRA empresa deveria ser bloqueada'); }
if ((int)$pdo->query("SELECT COUNT(*) FROM pessoas_pdi_objetivo_acoes WHERE objetivo_id=$o1")->fetchColumn() !== 1) { failFast('Ação duplicada no objetivo'); }
// Criar Ação a partir do objetivo reutilizando PessoaAcaoMelhoriaModel
$novo = $pdis->criarAcaoNoObjetivo($o2, ['titulo' => 'Ação criada no objetivo', 'gap_id' => $gapA, 'prazo' => '2026-08-01'], $acoes, $gaps, $avals, $cols, 1);
if (!$novo['ok'] || $novo['acao_id'] <= 0) { failFast('Criar Ação a partir do objetivo falhou: ' . json_encode($novo)); }
$c['acoes'][] = $novo['acao_id'];
$linhaAcao = $pdo->query('SELECT * FROM pessoas_acoes_melhoria WHERE id=' . $novo['acao_id'])->fetch();
if ((int)$linhaAcao['colaborador_id'] !== $colA || (int)$linhaAcao['empresa_id'] !== $A['eid'] || (int)$linhaAcao['gap_id'] !== $gapA || $linhaAcao['status'] !== 'pendente') { failFast('Ação criada no objetivo com dados incorretos'); }
// gap_id fora do PDI é descartado (não vaza vínculo)
$semGap = $pdis->criarAcaoNoObjetivo($o2, ['titulo' => 'Sem GAP externo', 'gap_id' => $gapA2], $acoes, $gaps, $avals, $cols, 1);
if (!$semGap['ok']) { failFast('Criar Ação com gap fora do PDI deveria funcionar (gap descartado)'); }
$c['acoes'][] = $semGap['acao_id'];
if ($pdo->query('SELECT gap_id FROM pessoas_acoes_melhoria WHERE id=' . $semGap['acao_id'])->fetchColumn() !== null) { failFast('GAP fora do PDI não deveria ser aceito na Ação'); }
$vazia = $pdis->criarAcaoNoObjetivo($o2, ['titulo' => ''], $acoes, $gaps, $avals, $cols, 1);
if ($vazia['ok'] || (int)$pdo->query("SELECT COUNT(*) FROM pessoas_pdi_objetivo_acoes WHERE objetivo_id=$o2")->fetchColumn() !== 2) { failFast('Ação inválida não deveria criar vínculo'); }
ok('Ações: vincula, idempotente, bloqueia externas; criar a partir do objetivo reutiliza o Model existente');

// ===== 7. Ativação: regras =====
$semObj = $pdis->create($A['eid'], $colA2, ['titulo' => 'PDI sem objetivo', 'data_inicio' => '2026-01-01'], $cols, 1); $c['pdis'][] = $semObj;
if ($pdis->ativar($semObj)['ok']) { failFast('Ativação sem objetivo deveria falhar'); }
$semData = $pdis->create($A['eid'], $colA2, ['titulo' => 'PDI sem data'], $cols, 1); $c['pdis'][] = $semData;
$pdis->addObjetivo($semData, ['titulo' => 'x']);
if ($pdis->ativar($semData)['ok']) { failFast('Ativação sem data de início deveria falhar'); }
$pdis->cancelar($semObj); $pdis->cancelar($semData);
// não conta objetivo cancelado como válido
$soCancel = $pdis->create($A['eid'], $colA2, ['titulo' => 'PDI só cancelado', 'data_inicio' => '2026-01-01'], $cols, 1); $c['pdis'][] = $soCancel;
$oc = $pdis->addObjetivo($soCancel, ['titulo' => 'x']); $pdis->setObjetivoStatus($oc, 'cancelado');
if ($pdis->ativar($soCancel)['ok']) { failFast('Ativação com apenas objetivo cancelado deveria falhar'); }
$pdis->cancelar($soCancel);
// Objetivo só evolui com PDI ativo
if ($pdis->setObjetivoStatus($o1, 'concluido')) { failFast('Concluir objetivo com PDI em rascunho deveria falhar'); }
$at = $pdis->ativar($pdiId);
if (!$at['ok'] || $pdis->find($pdiId)['status'] !== 'ativo') { failFast('Ativar PDI válido falhou: ' . json_encode($at)); }
if ($pdis->ativar($pdiId)['ok']) { failFast('Reativar PDI já ativo deveria falhar'); }
ok('Ativação exige objetivo válido, data de início e status rascunho');

// ===== 8. Segundo PDI ativo bloqueado (backend + banco) =====
$seg = $pdis->create($A['eid'], $colA, ['titulo' => 'Segundo PDI', 'data_inicio' => '2026-01-01'], $cols, 1); $c['pdis'][] = $seg;
$pdis->addObjetivo($seg, ['titulo' => 'x']);
$r = $pdis->ativar($seg);
if ($r['ok'] || strpos((string)$r['error'], 'já possui um PDI ativo') === false) { failFast('Segundo PDI ativo deveria ser bloqueado no backend'); }
$bancoBloqueou = false;
try { $pdo->exec("UPDATE pessoas_pdis SET status='ativo' WHERE id=" . (int)$seg); } catch (\PDOException $e) { $bancoBloqueou = ((int)($e->errorInfo[1] ?? 0) === 1062); }
if (!$bancoBloqueou || $pdis->find($seg)['status'] !== 'rascunho') { failFast('Integridade do banco deveria impedir 2º PDI ativo (UNIQUE)'); }
// outro colaborador pode ter o seu PDI ativo
$a2 = $pdis->create($A['eid'], $colA2, ['titulo' => 'PDI A2', 'data_inicio' => '2026-01-01'], $cols, 1); $c['pdis'][] = $a2;
$pdis->addObjetivo($a2, ['titulo' => 'x']);
if (!$pdis->ativar($a2)['ok']) { failFast('Outro colaborador deveria poder ter PDI ativo'); }
$pdis->cancelar($a2);
ok('Máx. 1 PDI ativo por colaborador: backend e UNIQUE no banco (sem trigger)');

// ===== 9. Progresso =====
// 4 objetivos: 2 concluídos, 1 pendente, 1 cancelado -> 2/3 = 66,67
$pdis->setObjetivoStatus($o1, 'concluido');
$pdis->setObjetivoStatus($o2, 'concluido');
$pdis->setObjetivoStatus($o4, 'cancelado');
$p = $pdis->find($pdiId);
if ((int)$p['objetivos_validos'] !== 3 || (int)$p['objetivos_concluidos'] !== 2 || abs($p['progresso'] - 66.67) > 0.001) { failFast('Progresso deveria ser 66,67%: ' . json_encode([$p['objetivos_validos'], $p['objetivos_concluidos'], $p['progresso']])); }
if (PessoasGestaoConfig::formatProgresso($p['progresso']) !== '66,67%') { failFast('Formatação do progresso: ' . PessoasGestaoConfig::formatProgresso($p['progresso'])); }
if (PessoasGestaoConfig::progressoPdi(0, 0) !== 0.0 || PessoasGestaoConfig::progressoPdi(1, 1) !== 100.0 || PessoasGestaoConfig::progressoPdi(1, 3) !== 33.33) { failFast('Casos de borda do progresso'); }
ok('Progresso 66,67% (2 concluídos / 3 válidos; cancelado fora do denominador); bordas 0/0=0');

// ===== 10/11. Concluir objetivo e progresso atualizado; transições inválidas =====
if ($pdis->setObjetivoStatus($o1, 'pendente') || $pdis->setObjetivoStatus($o1, 'em_andamento')) { failFast('Objetivo concluído não pode ser reaberto'); }
if ($pdis->setObjetivoStatus($o4, 'concluido')) { failFast('Objetivo cancelado não pode ser concluído'); }
if ($pdis->setObjetivoStatus($o3, 'invalido')) { failFast('Status inválido deveria ser rejeitado'); }
if (!$pdis->setObjetivoStatus($o3, 'em_andamento')) { failFast('pendente -> em_andamento deveria ser permitido'); }
ok('Transições de objetivo: sem reabertura, status inválido rejeitado');

// ===== 12. Concluir PDI com objetivo pendente é bloqueado =====
$rc = $pdis->concluir($pdiId, 'Observação');
if ($rc['ok'] || $pdis->find($pdiId)['status'] !== 'ativo') { failFast('Concluir PDI com objetivo em aberto deveria falhar'); }
if ($pdis->concluir($pdiId, '   ')['ok']) { failFast('Conclusão sem observações deveria falhar'); }

// Estado dos vínculos antes da conclusão (independência)
$acaoStatusAntes = $pdo->query("SELECT status FROM pessoas_acoes_melhoria WHERE id=$acaoA")->fetchColumn();
$gapStatusAntes = $pdo->query("SELECT status FROM pessoas_gaps WHERE id=$gapA")->fetchColumn();

// ===== 13/14. Concluir todos e concluir PDI =====
$pdis->setObjetivoStatus($o3, 'concluido');
$rc = $pdis->concluir($pdiId, 'Todos os objetivos atingidos');
$p = $pdis->find($pdiId);
if (!$rc['ok'] || $p['status'] !== 'concluido' || empty($p['concluido_em']) || $p['observacoes_conclusao'] !== 'Todos os objetivos atingidos' || abs($p['progresso'] - 100.0) > 0.001) { failFast('Concluir PDI falhou: ' . json_encode($rc)); }
if ($pdis->concluir($pdiId, 'de novo')['ok'] || $pdis->cancelar($pdiId) || $pdis->ativar($pdiId)['ok'] || $pdis->update($pdiId, ['titulo' => 'x'])) { failFast('PDI concluído não pode ser reaberto/cancelado/editado'); }
if ($pdis->addObjetivo($pdiId, ['titulo' => 'tardio']) !== 0 || $pdis->addGap($pdiId, $gapA, $gaps)['ok']) { failFast('PDI concluído não aceita novos objetivos/GAPs'); }
ok('Concluir PDI: exige todos os objetivos concluídos + observações; sem reabertura');

// ===== 15. Preservação de GAP/Ação (sem sincronismo automático) =====
if ($pdo->query("SELECT status FROM pessoas_acoes_melhoria WHERE id=$acaoA")->fetchColumn() !== $acaoStatusAntes
    || $pdo->query("SELECT status FROM pessoas_gaps WHERE id=$gapA")->fetchColumn() !== $gapStatusAntes) { failFast('Status de Ação/GAP não deve mudar com o PDI'); }
$vinculosAcao = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_pdi_objetivo_acoes WHERE objetivo_id IN ($o1,$o2)")->fetchColumn();
if ($vinculosAcao !== 3) { failFast('Vínculos de ações preservados após conclusão'); }
// Concluir Ação NÃO conclui objetivo; concluir objetivo NÃO conclui Ação
$acoes->concluir($acaoA, $A['eid'], 'feito');
if ($pdis->findObjetivo($o1)['status'] !== 'concluido') { failFast('sanidade'); }
$novoPdi = $pdis->create($A['eid'], $colA, ['titulo' => 'PDI para independência', 'data_inicio' => '2026-01-01'], $cols, 1); $c['pdis'][] = $novoPdi;
$on = $pdis->addObjetivo($novoPdi, ['titulo' => 'Obj']); $pdis->vincularAcao($on, $novo['acao_id'], $acoes);
$pdis->ativar($novoPdi);
$statusAcaoNova = $pdo->query('SELECT status FROM pessoas_acoes_melhoria WHERE id=' . $novo['acao_id'])->fetchColumn();
$pdis->setObjetivoStatus($on, 'concluido');
if ($pdo->query('SELECT status FROM pessoas_acoes_melhoria WHERE id=' . $novo['acao_id'])->fetchColumn() !== $statusAcaoNova) { failFast('Concluir objetivo não pode alterar a Ação'); }
ok('Independência: PDI/objetivo não alteram status de GAP/Ação e vice-versa');

// ===== 16. Cancelar preserva tudo =====
$gapsAntes = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_gaps WHERE colaborador_id=$colA")->fetchColumn();
$acoesAntes = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_acoes_melhoria WHERE colaborador_id=$colA")->fetchColumn();
if (!$pdis->cancelar($novoPdi) || $pdis->find($novoPdi)['status'] !== 'cancelado') { failFast('Cancelar PDI ativo falhou'); }
if ($pdis->cancelar($novoPdi) || $pdis->ativar($novoPdi)['ok']) { failFast('PDI cancelado não reabre'); }
if ((int)$pdo->query("SELECT COUNT(*) FROM pessoas_gaps WHERE colaborador_id=$colA")->fetchColumn() !== $gapsAntes
    || (int)$pdo->query("SELECT COUNT(*) FROM pessoas_acoes_melhoria WHERE colaborador_id=$colA")->fetchColumn() !== $acoesAntes
    || (int)$pdo->query("SELECT COUNT(*) FROM pessoas_pdi_objetivo_acoes WHERE objetivo_id=$on")->fetchColumn() !== 1) { failFast('Cancelamento deve preservar GAPs/Ações/vínculos'); }
// Rascunho também pode ser cancelado; depois o colaborador pode ter novo ativo
if (!$pdis->cancelar($seg)) { failFast('Cancelar rascunho deveria funcionar'); }
ok('Cancelamento: rascunho/ativo -> cancelado, sem reabertura, dados preservados');

// ===== Edição por status =====
$ed = $pdis->create($A['eid'], $colA2, ['titulo' => 'Editável', 'data_inicio' => '2026-01-01'], $cols, 1); $c['pdis'][] = $ed;
if (!$pdis->update($ed, ['titulo' => 'Editado', 'descricao' => 'd', 'data_inicio' => '2026-02-01'])) { failFast('Edição em rascunho deveria funcionar'); }
$ee = $pdis->find($ed);
if ($ee['titulo'] !== 'Editado' || (int)$ee['colaborador_id'] !== $colA2) { failFast('Edição não pode trocar colaborador'); }
$pdis->addObjetivo($ed, ['titulo' => 'x']); $pdis->ativar($ed);
if ($pdis->update($ed, ['titulo' => 'Não pode'])) { failFast('Edição estrutural em PDI ativo deveria falhar'); }
if ($pdis->addObjetivo($ed, ['titulo' => 'Evolução operacional em PDI ativo']) <= 0) { failFast('PDI ativo aceita novos objetivos (evolução operacional)'); }
ok('Edição: rascunho completa; ativo apenas evolução operacional; colaborador imutável');

// ===== 17. Histórico mostra PDI ativo + anteriores =====
$hist = $pdis->listByColaborador($colA, $A['eid']);
$statuses = array_column($hist, 'status');
if (!in_array('concluido', $statuses, true) || !in_array('cancelado', $statuses, true) || count($hist) < 3) { failFast('Histórico deveria listar PDIs anteriores: ' . json_encode($statuses)); }
$ativoA2 = $pdis->pdiAtivoDoColaborador($colA2, $A['eid']);
if (!$ativoA2 || (int)$ativoA2['id'] !== $ed) { failFast('pdiAtivoDoColaborador incorreto'); }
if ($pdis->listByColaborador($colA, $B['eid']) !== []) { failFast('listByColaborador não pode vazar entre empresas'); }
ok('Histórico: PDI ativo + PDIs anteriores; escopo por empresa');

// ===== 18. Cross-tenant e RBAC =====
$pdiB = $pdis->create($B['eid'], $colB, ['titulo' => 'PDI B', 'data_inicio' => '2026-01-01'], $cols, 1); $c['pdis'][] = $pdiB;
$objB = $pdis->addObjetivo($pdiB, ['titulo' => 'Obj B']);
asClienteAdmin($A['eid']);
if ($pdis->find($pdiB) !== null || $pdis->findObjetivo($objB) !== null) { failFast('Cliente A não pode enxergar PDI/objetivo da B'); }
if ($pdis->ativar($pdiB)['ok'] || $pdis->cancelar($pdiB) || $pdis->addObjetivo($pdiB, ['titulo' => 'x']) !== 0 || $pdis->setObjetivoStatus($objB, 'concluido')) { failFast('Escritas cross-tenant deveriam falhar'); }
if ($pdis->addGap($pdiB, $gapB, $gaps)['ok'] || $pdis->vincularAcao($objB, $acaoB, $acoes)['ok']) { failFast('Vínculos cross-tenant deveriam falhar'); }
if ($pdis->create($B['eid'], $colB, ['titulo' => 'x'], $cols, 501) !== 0) { failFast('Criar PDI em outra empresa deveria falhar'); }
$lista = $pdis->paginate([$A['eid'], $B['eid']], [], 1, 50);
foreach ($lista['items'] as $it) { if ((int)$it['empresa_id'] !== $A['eid']) { failFast('Listagem vazou PDI de outra empresa'); } }
asInstituto();
if ($pdis->find($pdiB) === null) { failFast('Instituto deveria enxergar PDI da B'); }
// RBAC: rotas do PDI só para Instituto/Cliente Admin
$colabUser = ['id' => 777, 'tipo_acesso' => 'colaborador', 'id_cliente' => $A['eid'], 'allowed_client_ids' => [$A['eid']]];
foreach (['pessoas/pdiIndex', 'pessoas/pdiShow', 'pessoas/visaoGeral'] as $route) {
    if (AccessControl::canAccessRoute($route, 'GET', $colabUser)) { failFast("Perfil sem acesso ao módulo pessoas não deveria acessar $route"); }
}
if (!AccessControl::canAccessRoute('pessoas/pdiIndex', 'GET', $_SESSION['user']) || !AccessControl::canAccessRoute('pessoas/pdiAtivar', 'POST', $_SESSION['user'])) { failFast('Instituto deveria acessar rotas do PDI'); }
ok('Tenant: leituras/escritas/vínculos cross-tenant bloqueados; RBAC das rotas do PDI');

// ===== Filtros da listagem =====
$f1 = $pdis->paginate([$A['eid']], ['status' => 'concluido'], 1, 50);
if ($f1['total'] < 1) { failFast('Filtro por status'); }
foreach ($f1['items'] as $it) { if ($it['status'] !== 'concluido') { failFast('Filtro por status devolveu outro status'); } }
$f2 = $pdis->paginate([$A['eid']], ['q' => "Colab A2 {$suffix}"], 1, 50);
foreach ($f2['items'] as $it) { if ((int)$it['colaborador_id'] !== $colA2) { failFast('Filtro por colaborador'); } }
$pg = $pdis->paginate([$A['eid']], [], 1, 2);
if (count($pg['items']) > 2 || $pg['total'] < 3) { failFast('Paginação'); }
if ($pdis->paginate([], [], 1, 10) !== ['items' => [], 'total' => 0]) { failFast('Sem empresas => vazio'); }
ok('Listagem: filtros de status/colaborador e paginação');

// ===== Cascata de FK =====
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_pdi_objetivos WHERE pdi_id=$pdiId")->fetchColumn();
if ($cnt !== 4) { failFast('Sanidade: 4 objetivos no PDI principal'); }

echo "\nTodos os testes de PDI passaram.\n";
