<?php
// Pilar de Pessoas - Sprint 03: Ação de Melhoria -> Plano de Ação / Treinamento /
// Necessidade de Treinamento, com rastreabilidade estrutural, tenant,
// idempotência, atomicidade, RBAC e independência de status.
require __DIR__ . '/../autoload.php';

use App\Core\AccessControl;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaDesenvolvimentoModel;
use App\Models\PessoaGapModel;
use App\Models\PlanoAcaoTaskModel;
use App\Models\SetorModel;
use App\Models\TreinamentoModel;

ob_start();
function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }

$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$c = ['clientes' => [], 'deps' => [], 'setores' => [], 'funcoes' => [], 'colabs' => [], 'gaps' => [], 'acoes' => [], 'tasks' => [], 'trein' => [], 'necs' => []];
register_shutdown_function(function () use ($pdo, &$c) {
    try {
        foreach ($c['necs'] as $id) { $pdo->exec("DELETE FROM pessoas_necessidades_treinamento WHERE id=" . (int)$id); }
        foreach ($c['acoes'] as $id) {
            $pdo->exec("DELETE FROM pessoas_acao_planos WHERE acao_melhoria_id=" . (int)$id);
            $pdo->exec("DELETE FROM pessoas_acao_treinamentos WHERE acao_melhoria_id=" . (int)$id);
            $pdo->exec("DELETE FROM pessoas_necessidades_treinamento WHERE acao_melhoria_id=" . (int)$id);
            $pdo->exec("DELETE FROM pessoas_acoes_melhoria WHERE id=" . (int)$id);
        }
        foreach ($c['gaps'] as $id) { $pdo->exec("DELETE FROM pessoas_gaps WHERE id=" . (int)$id); }
        foreach ($c['tasks'] as $id) { $pdo->exec("DELETE FROM pdca_tasks WHERE id=" . (int)$id); }
        foreach ($c['trein'] as $id) { $pdo->exec("DELETE FROM treinamento_colaboradores WHERE treinamento_id=" . (int)$id); $pdo->exec("DELETE FROM treinamentos WHERE id=" . (int)$id); }
        foreach ($c['colabs'] as $id) { $pdo->exec("DELETE FROM colaboradores WHERE id=" . (int)$id); }
        foreach ($c['funcoes'] as $id) { $pdo->exec("DELETE FROM funcoes WHERE id=" . (int)$id); }
        foreach ($c['setores'] as $id) { $pdo->exec("DELETE FROM setores WHERE id=" . (int)$id); }
        foreach ($c['deps'] as $id) { $pdo->exec("DELETE FROM departamentos WHERE id=" . (int)$id); }
        foreach ($c['clientes'] as $id) { $pdo->exec("DELETE FROM clientes WHERE id=" . (int)$id); }
    } catch (\Throwable $e) {
    }
});

function empresa(PDO $pdo, string $tag, string $suffix, array &$c): array
{
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')->execute(['n' => "Emp Dev {$tag} {$suffix}", 'c' => '55.555.5' . substr($tag, 0, 1) . '/0001-' . substr($suffix, 0, 2), 't' => 'T']);
    $eid = (int)$pdo->lastInsertId(); $c['clientes'][] = $eid;
    $dep = (new DepartamentoModel())->create(['nome' => "Dep {$tag} {$suffix}", 'cliente_id' => $eid]); $c['deps'][] = $dep;
    $set = (new SetorModel())->create(['nome' => "Set {$tag} {$suffix}", 'departamento_id' => $dep]); $c['setores'][] = $set;
    $fun = (new FuncaoModel())->create(['nome' => "Fun {$tag} {$suffix}", 'setor_id' => $set]); $c['funcoes'][] = $fun;
    $trein = $pdo->prepare('INSERT INTO treinamentos (nome, cliente_id, departamento_id) VALUES (:n,:c,:d)');
    $trein->execute(['n' => "Comunicação Assertiva {$tag} {$suffix}", 'c' => $eid, 'd' => $dep]);
    $tid = (int)$pdo->lastInsertId(); $c['trein'][] = $tid;
    return ['eid' => $eid, 'dep' => $dep, 'fun' => $fun, 'trein' => $tid];
}

$A = empresa($pdo, 'A', $suffix, $c);
$B = empresa($pdo, 'B', $suffix, $c);
$cols = new ColaboradorModel();
$colA = $cols->create(['nome' => "Colab A {$suffix}", 'funcao_id' => $A['fun'], 'cliente_id' => $A['eid'], 'ativo' => 1]);
$colB = $cols->create(['nome' => "Colab B {$suffix}", 'funcao_id' => $B['fun'], 'cliente_id' => $B['eid'], 'ativo' => 1]);
$c['colabs'] = [$colA, $colB];

$gaps = new PessoaGapModel(); $acoes = new PessoaAcaoMelhoriaModel(); $avals = new PessoaAvaliacaoModel();
$dev = new PessoaDesenvolvimentoModel(); $tasks = new PlanoAcaoTaskModel(); $treins = new TreinamentoModel();

$gapA = $gaps->createManual($A['eid'], $colA, ['titulo' => 'Comunicação abaixo do esperado', 'descricao' => 'DESCRICAO SENSIVEL DO GAP'], $cols, 1); $c['gaps'][] = $gapA;
$gapB = $gaps->createManual($B['eid'], $colB, ['titulo' => 'GAP de B'], $cols, 1); $c['gaps'][] = $gapB;
$acaoA = $acoes->create($A['eid'], $colA, ['titulo' => 'Desenvolver comunicação profissional', 'descricao' => 'Ação de A', 'prazo' => date('Y-m-d', strtotime('+30 days')), 'gap_id' => $gapA], $cols, $gaps, $avals, 1); $c['acoes'][] = $acaoA;
$acaoB = $acoes->create($B['eid'], $colB, ['titulo' => 'Ação de B', 'gap_id' => $gapB], $cols, $gaps, $avals, 1); $c['acoes'][] = $acaoB;
if ($gapA <= 0 || $acaoA <= 0 || $acaoB <= 0) { failFast('Fixtures inválidas'); }
ok('Fixtures: Empresa A (colaborador, GAP, Ação, treinamento) e Empresa B');

// ===================== PLANO DE AÇÃO =====================
$r1 = $dev->encaminharPlanoAcao($acaoA, ['titulo' => 'Apresentações mensais 90 dias'], $acoes, $tasks, 1);
if (!$r1['ok'] || $r1['already_existed'] || $r1['plano_id'] <= 0) { failFast('Encaminhar para Plano de Ação deveria criar o Plano: ' . json_encode($r1)); }
$c['tasks'][] = $r1['plano_id'];
$task = $pdo->query("SELECT * FROM pdca_tasks WHERE id = " . $r1['plano_id'])->fetch();
if ((int)$task['id_cliente'] !== $A['eid'] || $task['titulo'] !== 'Apresentações mensais 90 dias' || strpos((string)$task['descricao'], 'Originado do Pilar de Pessoas') === false) {
    failFast('Plano de Ação criado com dados incorretos');
}
if (strpos((string)$task['descricao'], 'DESCRICAO SENSIVEL DO GAP') !== false) { failFast('Descrição do GAP não deveria ser copiada para o Plano'); }
$vinculo = $dev->planoDaAcao($acaoA);
if (!$vinculo || (int)$vinculo['plano_task_id'] !== $r1['plano_id']) { failFast('Vínculo estrutural Ação<->Plano ausente'); }
ok('Plano de Ação criado via PlanoAcaoTaskModel + vínculo estrutural; GAP não copiado');

$r2 = $dev->encaminharPlanoAcao($acaoA, [], $acoes, $tasks, 1);
$totalTasks = (int)$pdo->query("SELECT COUNT(*) FROM pdca_tasks WHERE id_cliente = {$A['eid']}")->fetchColumn();
if (!$r2['already_existed'] || $r2['plano_id'] !== $r1['plano_id'] || $totalTasks !== 1) { failFast('POST repetido não deveria duplicar Plano (tasks=' . $totalTasks . ')'); }
ok('Idempotência: segundo encaminhamento devolve o mesmo Plano, sem duplicar');

$acaoDepois = $acoes->find($acaoA); $gapDepois = $gaps->find($gapA);
if ($acaoDepois['status'] !== 'pendente' || $gapDepois['status'] !== 'aberto') { failFast('Criar Plano não deveria alterar Ação/GAP'); }
ok('Criar Plano NÃO conclui a Ação nem resolve o GAP');

// Cross-tenant: Ação B/Plano B por A e vice-versa
$antes = (int)$pdo->query('SELECT COUNT(*) FROM pdca_tasks')->fetchColumn();
$_SESSION['user'] = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $A['eid'], 'allowed_client_ids' => [$A['eid']]];
$rx = $dev->encaminharPlanoAcao($acaoB, [], $acoes, new PlanoAcaoTaskModel(), 501);
$depois = (int)$pdo->query('SELECT COUNT(*) FROM pdca_tasks')->fetchColumn();
if ($rx['ok'] || $antes !== $depois) { failFast('Cliente Admin de A não pode encaminhar Ação de B'); }
ok('Tenant: Cliente Admin de A não encaminha Ação de B a Plano (nada criado)');

// ===================== ATOMICIDADE (compensação) =====================
$acaoAtom = $acoes->create($A['eid'], $colA, ['titulo' => 'Ação atomicidade'], $cols, $gaps, $avals, 1); $c['acoes'][] = $acaoAtom;
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
// Plano "ocupado": outro Plano já reivindicado pelo UNIQUE de plano_task_id força falha do INSERT do vínculo.
$fake = new class extends PlanoAcaoTaskModel {
    public int $created = 0;
    public function create(array $data): int
    {
        // Devolve o id de um Plano JÁ vinculado a outra Ação => o INSERT do vínculo viola UNIQUE(plano_task_id).
        $this->created = (int)\App\Database\Database::getConnection()->query('SELECT plano_task_id FROM pessoas_acao_planos ORDER BY id LIMIT 1')->fetchColumn();
        return $this->created;
    }
    public array $deleted = [];
    public function delete(int $id): bool { $this->deleted[] = $id; return true; }
};
$rAtom = $dev->encaminharPlanoAcao($acaoAtom, [], $acoes, $fake, 1);
$vincAtom = $dev->planoDaAcao($acaoAtom);
if ($rAtom['ok'] || $vincAtom !== null || !in_array($fake->created, $fake->deleted, true)) {
    failFast('Falha no vínculo deveria compensar (remover o Plano recém-criado) e não deixar vínculo parcial: ' . json_encode($rAtom));
}
ok('Atomicidade: falha ao registrar o vínculo compensa (Plano removido) e não deixa vínculo parcial');

// ===================== TREINAMENTO EXISTENTE =====================
$t1 = $dev->vincularTreinamento($acaoA, $A['trein'], $acoes, $treins, 1);
if (!$t1['ok'] || $t1['already_existed']) { failFast('Vincular treinamento existente deveria funcionar: ' . json_encode($t1)); }
$roster = (int)$pdo->query("SELECT COUNT(*) FROM treinamento_colaboradores WHERE treinamento_id={$A['trein']} AND colaborador_id={$colA}")->fetchColumn();
if ($roster !== 1) { failFast('Colaborador deveria constar na lista do treinamento (sem agenda fictícia)'); }
$agendas = (int)$pdo->query("SELECT COUNT(*) FROM treinamentos_agenda WHERE treinamento_id={$A['trein']}")->fetchColumn();
if ($agendas !== 0) { failFast('Nenhuma agenda deveria ter sido inventada'); }
$lista = $dev->treinamentosDaAcao($acaoA, $colA);
if (count($lista) !== 1 || $lista[0]['situacao']['label'] !== 'Aguardando agendamento') { failFast('Situação esperada: Aguardando agendamento, obtido ' . json_encode($lista)); }
ok('Treinamento vinculado: colaborador na lista, sem agenda inventada, situação "Aguardando agendamento"');

$t2 = $dev->vincularTreinamento($acaoA, $A['trein'], $acoes, $treins, 1);
$totVinc = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_acao_treinamentos WHERE acao_melhoria_id={$acaoA}")->fetchColumn();
if (!$t2['already_existed'] || $totVinc !== 1) { failFast('Segundo POST não deveria duplicar o vínculo'); }
ok('Idempotência do vínculo de treinamento');

// Treinamento externo bloqueado
$tx = $dev->vincularTreinamento($acaoA, $B['trein'], $acoes, $treins, 1);
$totalRosterB = (int)$pdo->query("SELECT COUNT(*) FROM treinamento_colaboradores WHERE treinamento_id={$B['trein']} AND colaborador_id={$colA}")->fetchColumn();
if ($tx['ok'] || $totalRosterB !== 0) { failFast('Treinamento de B não pode ser vinculado à Ação de A'); }
$_SESSION['user'] = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $A['eid'], 'allowed_client_ids' => [$A['eid']]];
$ty = $dev->vincularTreinamento($acaoB, $A['trein'], $acoes, $treins, 501);
$disp = array_column($dev->treinamentosDisponiveis($A['eid']), 'id');
$dispB = $dev->treinamentosDisponiveis($B['eid']);
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
if ($ty['ok'] || in_array($B['trein'], array_map('intval', $disp), true) || !empty($dispB)) { failFast('Cross-tenant em treinamento não bloqueado'); }
ok('Tenant: treinamento externo, Ação externa e listagem de outra empresa bloqueados');

// Nada alterou status automaticamente
if ($acoes->find($acaoA)['status'] !== 'pendente' || $gaps->find($gapA)['status'] !== 'aberto') { failFast('Vincular treinamento não deveria alterar Ação/GAP'); }
ok('Vincular treinamento não altera status da Ação nem do GAP');

// ===================== NECESSIDADE DE TREINAMENTO =====================
$n1 = $dev->registrarNecessidade($acaoA, ['titulo' => 'Oratória', 'descricao' => 'Falar em público', 'prioridade' => 'alta'], $acoes, 1);
if (!$n1['ok'] || $n1['id'] <= 0) { failFast('Falha ao registrar necessidade'); }
$c['necs'][] = $n1['id'];
$n1b = $dev->registrarNecessidade($acaoA, ['titulo' => 'Oratória'], $acoes, 1);
if (!$n1b['already_existed'] || $n1b['id'] !== $n1['id']) { failFast('Necessidade pendente duplicada deveria ser idempotente'); }
if ($dev->findNecessidade($n1['id'])['status'] !== 'pendente') { failFast('Necessidade deveria nascer pendente'); }
ok('Necessidade registrada (pendente), idempotente contra duplicidade');

$nx = $dev->registrarNecessidade($acaoB, ['titulo' => 'x'], $acoes, 1); // instituto vê B; testa como A abaixo
$_SESSION['user'] = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $A['eid'], 'allowed_client_ids' => [$A['eid']]];
$nxA = $dev->registrarNecessidade($acaoB, ['titulo' => 'x externa'], $acoes, 501);
$atenderExterna = $dev->atenderNecessidade((int)$nx['id'], $A['trein'], $acoes, $treins, 501);
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
if (!empty($nx['id'])) { $c['necs'][] = $nx['id']; }
if ($nxA['ok'] || $atenderExterna['ok']) { failFast('Necessidade/atendimento cross-tenant deveria ser bloqueado'); }
ok('Tenant: necessidade de outra empresa não pode ser criada nem atendida por Cliente Admin de A');

// atender com treinamento de B => bloqueia e nada muda (transação)
$atB = $dev->atenderNecessidade($n1['id'], $B['trein'], $acoes, $treins, 1);
if ($atB['ok'] || $dev->findNecessidade($n1['id'])['status'] !== 'pendente') { failFast('Atender com treinamento externo deveria falhar mantendo pendente'); }
ok('Necessidade A + Treinamento B bloqueado; permanece pendente (transação)');

// segundo treinamento legítimo de A para atender
$pdo->prepare('INSERT INTO treinamentos (nome, cliente_id, departamento_id) VALUES (:n,:c,:d)')->execute(['n' => "Oratória {$suffix}", 'c' => $A['eid'], 'd' => $A['dep']]);
$t2id = (int)$pdo->lastInsertId(); $c['trein'][] = $t2id;
$at = $dev->atenderNecessidade($n1['id'], $t2id, $acoes, new TreinamentoModel(), 1);
$nAt = $dev->findNecessidade($n1['id']);
if (!$at['ok'] || $nAt['status'] !== 'atendida' || (int)$nAt['treinamento_id'] !== $t2id || empty($nAt['atendida_em'])) { failFast('Atender necessidade deveria marcá-la atendida com treinamento: ' . json_encode($at)); }
if ((int)$pdo->query("SELECT COUNT(*) FROM pessoas_acao_treinamentos WHERE acao_melhoria_id={$acaoA} AND treinamento_id={$t2id}")->fetchColumn() !== 1) { failFast('Atender deveria criar o vínculo Ação<->Treinamento'); }
if ($dev->atenderNecessidade($n1['id'], $t2id, $acoes, $treins, 1)['ok']) { failFast('Necessidade já atendida não pode ser atendida de novo'); }
ok('Necessidade atendida ao vincular treinamento (vínculo criado, histórico preservado, sem reatender)');

// ===================== HISTÓRICO / ESTADO DERIVADO =====================
$acaoRow = $acoes->find($acaoA);
$d = $dev->desenvolvimentoDaAcao($acaoRow);
if (empty($d['plano']) || count($d['treinamentos']) !== 2 || count($d['necessidades']) !== 1) { failFast('Histórico deveria consolidar plano + 2 treinamentos + 1 necessidade'); }
if ($d['estado'] !== 'Aguardando treinamento') { failFast('Estado derivado esperado "Aguardando treinamento", obtido: ' . $d['estado']); }
$semNada = $dev->desenvolvimentoDaAcao($acoes->find($acaoAtom));
if ($semNada['estado'] !== 'Sem encaminhamento') { failFast('Ação sem encaminhamento deveria derivar "Sem encaminhamento"'); }
ok('Desenvolvimento consolidado por Ação com estado derivado (sem coluna redundante)');

// Situação vinda do módulo de Treinamentos (concluído)
$pdo->exec("UPDATE treinamento_colaboradores SET status='concluido' WHERE treinamento_id={$A['trein']} AND colaborador_id={$colA}");
$sit = $dev->situacaoTreinamento($A['trein'], $colA);
if (!$sit['concluido'] || $sit['label'] !== 'Concluído') { failFast('Situação deveria refletir conclusão do módulo de Treinamentos'); }
if ($acoes->find($acaoA)['status'] === 'concluida' || $gaps->find($gapA)['status'] === 'resolvido') { failFast('Treinamento concluído não deveria concluir Ação/GAP automaticamente'); }
ok('Conclusão do treinamento aparece a partir do módulo de Treinamentos, sem concluir Ação/GAP');

// ===================== RBAC =====================
foreach (['reader' => 'reader', 'cliente' => 'cliente', 'consultor' => 'consultor'] as $tipo) {
    $u = ['id' => 900, 'tipo_acesso' => $tipo, 'allowed_client_ids' => [$A['eid']]];
    foreach (['pessoas/acaoShow', 'planoacao/store', 'treinamentos/add_participante_extra'] as $route) {
        if (AccessControl::canAccessRoute($route, 'POST', $u)) { failFast("RBAC: perfil $tipo não deveria acessar $route"); }
    }
}
$admin = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $A['eid'], 'allowed_client_ids' => [$A['eid']]];
if (!AccessControl::canAccessRoute('pessoas/acaoShow', 'GET', $admin) || !AccessControl::canAccessRoute('planoacao/store', 'POST', $admin)) { failFast('RBAC: Cliente Admin deveria acessar Pessoas e Plano de Ação'); }
ok('RBAC: perfis sem acesso continuam bloqueados em Pessoas e nos módulos de destino; Cliente Admin/Instituto liberados');

echo "pessoas_desenvolvimento_integration_test passed.\n";
