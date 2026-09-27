<?php
/**
 * Fixtures controladas da Sprint 05 (Pilar de Pessoas - gestão operacional).
 * Empresa A (2 departamentos) com cenário completo; Empresa B com volume que
 * NUNCA pode aparecer para A. Cleanup registrado via register_shutdown_function
 * (FKs em CASCADE a partir de clientes/colaboradores removem o resto).
 *
 * Cenário de A (valores esperados documentados nos testes):
 *  GAPs:  gA1 a1 aberto sem ação | gA2 a1 aberto só com ação CANCELADA (= sem ação)
 *         gA3 a1 em_tratamento c/ ação pendente | gA4 a2 aberto c/ ação concluída
 *         gA5 a2 resolvido | gA6 a3 aberto sem ação (registrado em 2020)
 *  Ações: ac1 a1 pendente prazo passado (vencida, c/ Plano) | ac2 a1 cancelada prazo passado
 *         ac3 a2 concluída prazo passado | ac4 a2 em_andamento prazo passado (vencida, c/ Treinamento, responsável U)
 *         ac5 a3 pendente prazo futuro | ac6 a3 pendente sem prazo
 *  Necessidades: n1 a1 pendente alta (ac1) | n2 a1 atendida (ac1, treinamento T) | n3 a3 cancelada (ac5) | n4 a2 pendente baixa (ac4)
 *  PDI:   a1 ativo: 1 objetivo concluído + 1 pendente + 1 cancelado => 50%
 *  Avaliações: a1 finalizada 4,50 em 2026-09-15 (ciclo 1) + a1 em_andamento (ciclo 2)
 */

use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;

function s05_ins(PDO $pdo, string $table, array $row): int
{
    $cols = array_keys($row);
    $pdo->prepare("INSERT INTO $table (" . implode(',', $cols) . ') VALUES (:' . implode(',:', $cols) . ')')->execute($row);
    return (int)$pdo->lastInsertId();
}

function s05_fixtures(string $nomeA1 = ''): array
{
    $pdo = Database::getConnection();
    $sfx = substr(bin2hex(random_bytes(4)), 0, 8);
    $c = ['clientes' => [], 'deps' => [], 'setores' => [], 'funcoes' => [], 'colabs' => [], 'tasks' => [], 'trein' => [], 'users' => []];
    $GLOBALS['__s05_cleanup'] = &$c;
    register_shutdown_function(static function () use ($pdo, &$c) {
        try {
            foreach ($c['tasks'] as $id) { $pdo->exec('DELETE FROM pdca_tasks WHERE id=' . (int)$id); }
            foreach ($c['trein'] as $id) { $pdo->exec('DELETE FROM treinamentos WHERE id=' . (int)$id); }
            foreach ($c['clientes'] as $id) {
                foreach (['pessoas_ciclos_avaliacao', 'pessoas_modelos_avaliacao'] as $t) { $pdo->exec("DELETE FROM $t WHERE empresa_id=" . (int)$id); }
            }
            foreach ($c['colabs'] as $id) { $pdo->exec('DELETE FROM colaboradores WHERE id=' . (int)$id); }
            foreach ($c['funcoes'] as $id) { $pdo->exec('DELETE FROM funcoes WHERE id=' . (int)$id); }
            foreach ($c['setores'] as $id) { $pdo->exec('DELETE FROM setores WHERE id=' . (int)$id); }
            foreach ($c['deps'] as $id) { $pdo->exec('DELETE FROM departamentos WHERE id=' . (int)$id); }
            foreach ($c['users'] as $id) { $pdo->exec('DELETE FROM usuarios WHERE id=' . (int)$id); }
            foreach ($c['clientes'] as $id) { $pdo->exec('DELETE FROM clientes WHERE id=' . (int)$id); }
        } catch (\Throwable $e) {
        }
    });

    $empresa = static function (string $tag) use ($pdo, $sfx, &$c): array {
        $eid = s05_ins($pdo, 'clientes', ['nome_empresa' => "Emp S05 {$tag} {$sfx}", 'CNPJ' => '44.' . random_int(100, 999) . '.' . random_int(100, 999) . '/0001-' . random_int(10, 99), 'contato' => 'T']);
        $c['clientes'][] = $eid;
        $out = ['eid' => $eid, 'deps' => [], 'sets' => [], 'funs' => []];
        foreach ([1, 2] as $n) {
            $dep = (new DepartamentoModel())->create(['nome' => "Dep{$n} {$tag} {$sfx}", 'cliente_id' => $eid]); $c['deps'][] = $dep;
            $set = (new SetorModel())->create(['nome' => "Set{$n} {$tag} {$sfx}", 'departamento_id' => $dep]); $c['setores'][] = $set;
            $fun = (new FuncaoModel())->create(['nome' => "Fun{$n} {$tag} {$sfx}", 'setor_id' => $set]); $c['funcoes'][] = $fun;
            $out['deps'][$n] = $dep; $out['sets'][$n] = $set; $out['funs'][$n] = $fun;
        }
        $modelo = s05_ins($pdo, 'pessoas_modelos_avaliacao', ['empresa_id' => $eid, 'nome' => "Modelo {$tag} {$sfx}"]);
        $out['ciclo1'] = s05_ins($pdo, 'pessoas_ciclos_avaliacao', ['empresa_id' => $eid, 'modelo_id' => $modelo, 'nome' => "Ciclo1 {$tag} {$sfx}", 'status' => 'aberto']);
        $out['ciclo2'] = s05_ins($pdo, 'pessoas_ciclos_avaliacao', ['empresa_id' => $eid, 'modelo_id' => $modelo, 'nome' => "Ciclo2 {$tag} {$sfx}", 'status' => 'aberto']);
        return $out;
    };
    $A = $empresa('A');
    $B = $empresa('B');
    $cols = new ColaboradorModel();
    $colab = static function (string $nome, array $E, int $dep) use ($cols, &$c): int {
        $id = $cols->create(['nome' => $nome, 'funcao_id' => $E['funs'][$dep], 'cliente_id' => $E['eid'], 'ativo' => 1]);
        $c['colabs'][] = $id;
        return $id;
    };
    $eA = $A['eid'];
    $a1 = $colab($nomeA1 !== '' ? $nomeA1 : "A1 {$sfx}", $A, 1);
    $a2 = $colab("A2 {$sfx}", $A, 1);
    $a3 = $colab("A3 {$sfx}", $A, 2);
    $b1 = $colab("B1 {$sfx}", $B, 1);

    $past = '2020-01-01';
    $future = '2099-12-31';
    $u = s05_ins($pdo, 'usuarios', ['nome' => "Resp S05 {$sfx}", 'email' => "resp.s05.{$sfx}@test.local", 'senha_hash' => password_hash('x', PASSWORD_DEFAULT), 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $eA]);
    $c['users'][] = $u;

    // Avaliações
    $av1 = s05_ins($pdo, 'pessoas_avaliacoes', ['empresa_id' => $eA, 'ciclo_id' => $A['ciclo1'], 'colaborador_id' => $a1, 'status' => 'finalizada', 'resultado' => 4.50, 'finalizado_em' => '2026-09-15 10:00:00']);
    s05_ins($pdo, 'pessoas_avaliacoes', ['empresa_id' => $eA, 'ciclo_id' => $A['ciclo2'], 'colaborador_id' => $a1, 'status' => 'em_andamento']);

    // GAPs
    $gap = static fn(int $col, string $st, string $t, ?string $res = null, ?string $created = null, ?int $av = null) => s05_ins($pdo, 'pessoas_gaps', array_filter([
        'empresa_id' => $eA, 'colaborador_id' => $col, 'titulo' => $t, 'status' => $st, 'origem' => $av ? 'avaliacao' : 'manual',
        'avaliacao_id' => $av, 'resolvido_em' => $res, 'created_at' => $created,
    ], static fn($v) => $v !== null));
    $g = [];
    $g['gA1'] = $gap($a1, 'aberto', 'gA1 sem acao', null, '2026-09-16 09:00:00', $av1);
    $g['gA2'] = $gap($a1, 'aberto', 'gA2 so cancelada', null, '2026-09-16 10:00:00');
    $g['gA3'] = $gap($a1, 'em_tratamento', 'gA3 com acao', null, '2026-09-16 11:00:00');
    $g['gA4'] = $gap($a2, 'aberto', 'gA4 acao concluida', null, '2026-09-16 12:00:00');
    $g['gA5'] = $gap($a2, 'resolvido', 'gA5 resolvido', '2026-09-20 10:00:00', '2026-09-16 13:00:00');
    $g['gA6'] = $gap($a3, 'aberto', 'gA6 antigo', null, '2020-06-01 10:00:00');
    $g['gB1'] = s05_ins($pdo, 'pessoas_gaps', ['empresa_id' => $B['eid'], 'colaborador_id' => $b1, 'titulo' => 'gB1', 'status' => 'aberto']);

    // Ações
    $acao = static fn(int $col, string $st, ?string $prazo, ?int $gapId, string $t, ?int $resp = null, ?string $conc = null) => s05_ins($pdo, 'pessoas_acoes_melhoria', array_filter([
        'empresa_id' => $eA, 'colaborador_id' => $col, 'gap_id' => $gapId, 'titulo' => $t, 'status' => $st, 'prazo' => $prazo,
        'responsavel_usuario_id' => $resp, 'concluido_em' => $conc, 'created_at' => '2026-09-17 10:00:00',
    ], static fn($v) => $v !== null));
    $ac = [];
    $ac['ac1'] = $acao($a1, 'pendente', $past, $g['gA3'], 'ac1 vencida plano');
    $ac['ac2'] = $acao($a1, 'cancelada', $past, $g['gA2'], 'ac2 cancelada');
    $ac['ac3'] = $acao($a2, 'concluida', $past, $g['gA4'], 'ac3 concluida', null, '2026-09-18 10:00:00');
    $ac['ac4'] = $acao($a2, 'em_andamento', $past, null, 'ac4 vencida treinamento', $u);
    $ac['ac5'] = $acao($a3, 'pendente', $future, null, 'ac5 futura');
    $ac['ac6'] = $acao($a3, 'pendente', null, null, 'ac6 sem prazo');
    $ac['acB'] = s05_ins($pdo, 'pessoas_acoes_melhoria', ['empresa_id' => $B['eid'], 'colaborador_id' => $b1, 'titulo' => 'acB', 'status' => 'pendente', 'prazo' => $past]);

    // Encaminhamentos
    $task = s05_ins($pdo, 'pdca_tasks', ['id_cliente' => $eA, 'titulo' => "Plano S05 {$sfx}", 'status' => 'Em Andamento']); $c['tasks'][] = $task;
    $trein = s05_ins($pdo, 'treinamentos', ['nome' => "Trein S05 {$sfx}", 'cliente_id' => $eA, 'departamento_id' => $A['deps'][1]]); $c['trein'][] = $trein;
    s05_ins($pdo, 'pessoas_acao_planos', ['empresa_id' => $eA, 'acao_melhoria_id' => $ac['ac1'], 'plano_task_id' => $task, 'created_at' => '2026-09-18 11:00:00']);
    s05_ins($pdo, 'pessoas_acao_treinamentos', ['empresa_id' => $eA, 'acao_melhoria_id' => $ac['ac4'], 'treinamento_id' => $trein, 'created_at' => '2026-09-18 12:00:00']);
    s05_ins($pdo, 'treinamento_colaboradores', ['treinamento_id' => $trein, 'colaborador_id' => $a1, 'status' => 'pendente']);
    s05_ins($pdo, 'treinamento_colaboradores', ['treinamento_id' => $trein, 'colaborador_id' => $a2, 'status' => 'concluido']);

    // Necessidades
    $nec = static fn(int $col, int $acaoId, string $st, string $prio, string $t, ?int $tid = null) => s05_ins($pdo, 'pessoas_necessidades_treinamento', array_filter([
        'empresa_id' => $eA, 'colaborador_id' => $col, 'acao_melhoria_id' => $acaoId, 'titulo' => $t, 'status' => $st, 'prioridade' => $prio,
        'treinamento_id' => $tid, 'atendida_em' => $tid ? '2026-09-19 10:00:00' : null,
    ], static fn($v) => $v !== null));
    $n = [];
    $n['n1'] = $nec($a1, $ac['ac1'], 'pendente', 'alta', 'n1 pendente alta');
    $n['n2'] = $nec($a1, $ac['ac1'], 'atendida', 'media', 'n2 atendida', $trein);
    $n['n3'] = $nec($a3, $ac['ac5'], 'cancelada', 'media', 'n3 cancelada');
    $n['n4'] = $nec($a2, $ac['ac4'], 'pendente', 'baixa', 'n4 pendente baixa');
    $n['nB'] = s05_ins($pdo, 'pessoas_necessidades_treinamento', ['empresa_id' => $B['eid'], 'colaborador_id' => $b1, 'acao_melhoria_id' => $ac['acB'], 'titulo' => 'nB', 'status' => 'pendente']);

    // PDI ativo de a1: 1 concluído + 1 pendente + 1 cancelado => 50%
    $pdi = s05_ins($pdo, 'pessoas_pdis', ['empresa_id' => $eA, 'colaborador_id' => $a1, 'titulo' => "PDI a1 {$sfx}", 'status' => 'ativo', 'data_inicio' => '2026-09-01', 'created_at' => '2026-09-19 12:00:00']);
    foreach (['concluido', 'pendente', 'cancelado'] as $st) {
        s05_ins($pdo, 'pessoas_pdi_objetivos', ['pdi_id' => $pdi, 'titulo' => "obj {$st}", 'status' => $st]);
    }

    // Feedback
    s05_ins($pdo, 'pessoas_feedbacks', ['empresa_id' => $eA, 'colaborador_id' => $a1, 'tipo' => 'positivo', 'titulo' => 'Feedback s05', 'descricao' => 'bom trabalho', 'data_feedback' => '2026-09-10']);

    return compact('sfx', 'A', 'B', 'eA', 'a1', 'a2', 'a3', 'b1', 'u', 'g', 'ac', 'n', 'pdi', 'task', 'trein', 'av1');
}
