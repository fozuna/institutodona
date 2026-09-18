<?php
// Pilar de Pessoas - Sprint 02: Resultado -> GAP -> Feedback -> Ação de
// Melhoria. Teste de integração único cobrindo o fluxo real completo a
// partir de uma avaliação finalizada da Sprint 01.
require __DIR__ . '/../autoload.php';

use App\Core\PessoasGestaoConfig;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\PessoaAcaoMelhoriaModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaCicloAvaliacaoModel;
use App\Models\PessoaFeedbackModel;
use App\Models\PessoaGapModel;
use App\Models\PessoaModeloAvaliacaoModel;
use App\Models\SetorModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'acao_ids' => [], 'feedback_ids' => [], 'gap_ids' => [],
    'avaliacao_ids' => [], 'ciclo_ids' => [], 'pergunta_ids' => [], 'grupo_ids' => [], 'modelo_ids' => [],
    'colaborador_ids' => [], 'funcao_ids' => [], 'setor_ids' => [], 'departamento_ids' => [], 'cliente_ids' => [],
];
register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        foreach ($cleanup['acao_ids'] as $id) { $pdo->prepare('DELETE FROM pessoas_acoes_melhoria WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['feedback_ids'] as $id) { $pdo->prepare('DELETE FROM pessoas_feedbacks WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['gap_ids'] as $id) { $pdo->prepare('DELETE FROM pessoas_gaps WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['avaliacao_ids'] as $id) {
            $pdo->prepare('DELETE FROM pessoas_avaliacao_respostas WHERE avaliacao_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM pessoas_avaliacoes WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['ciclo_ids'] as $id) {
            $pdo->prepare('DELETE FROM pessoas_ciclo_participantes WHERE ciclo_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM pessoas_avaliacoes WHERE ciclo_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM pessoas_ciclos_avaliacao WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['pergunta_ids'] as $id) { $pdo->prepare('DELETE FROM pessoas_modelos_perguntas WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['grupo_ids'] as $id) { $pdo->prepare('DELETE FROM pessoas_modelos_grupos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['modelo_ids'] as $id) { $pdo->prepare('DELETE FROM pessoas_modelos_avaliacao WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['colaborador_ids'] as $id) { $pdo->prepare('DELETE FROM colaboradores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['funcao_ids'] as $id) { $pdo->prepare('DELETE FROM funcoes WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {
    }
});

function makeEmpresaCompleta(PDO $pdo, string $tag, string $suffix, array &$cleanup): array
{
    $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n, :c, :t)');
    $stmt->execute(['n' => "Empresa GapFb {$tag} {$suffix}", 'c' => '33.333.3' . substr($tag, 0, 1) . '/0001-' . substr($suffix, 0, 2), 't' => 'Teste']);
    $clienteId = (int)$pdo->lastInsertId();
    $cleanup['cliente_ids'][] = $clienteId;
    $deps = new DepartamentoModel();
    $depId = $deps->create(['nome' => "Dep {$tag} {$suffix}", 'cliente_id' => $clienteId]);
    $cleanup['departamento_ids'][] = $depId;
    $setores = new SetorModel();
    $setorId = $setores->create(['nome' => "Setor {$tag} {$suffix}", 'departamento_id' => $depId]);
    $cleanup['setor_ids'][] = $setorId;
    $funcoes = new FuncaoModel();
    $funcaoId = $funcoes->create(['nome' => "Funcao {$tag} {$suffix}", 'setor_id' => $setorId]);
    $cleanup['funcao_ids'][] = $funcaoId;
    return ['cliente_id' => $clienteId, 'departamento_id' => $depId, 'setor_id' => $setorId, 'funcao_id' => $funcaoId];
}

// ===================== FIXTURES: avaliação finalizada (Sprint 01) =====================
$empresaA = makeEmpresaCompleta($pdo, 'A', $suffix, $cleanup);
$empresaB = makeEmpresaCompleta($pdo, 'B', $suffix, $cleanup);

$colaboradores = new ColaboradorModel();
$col1 = $colaboradores->create(['nome' => "Colaborador GapFb {$suffix}", 'funcao_id' => $empresaA['funcao_id'], 'cliente_id' => $empresaA['cliente_id'], 'ativo' => 1]);
$colB = $colaboradores->create(['nome' => "Colaborador B GapFb {$suffix}", 'funcao_id' => $empresaB['funcao_id'], 'cliente_id' => $empresaB['cliente_id'], 'ativo' => 1]);
if ($col1 <= 0 || $colB <= 0) { failFast('Falha ao criar colaboradores'); }
$cleanup['colaborador_ids'] = [$col1, $colB];

$modelos = new PessoaModeloAvaliacaoModel();
$modeloId = $modelos->create(['empresa_id' => $empresaA['cliente_id'], 'nome' => "Modelo GapFb {$suffix}", 'created_by' => 1]);
$cleanup['modelo_ids'][] = $modeloId;
$grupoId = $modelos->createGroup($modeloId, ['nome' => 'Comunicação']);
$cleanup['grupo_ids'][] = $grupoId;
$perguntaBaixa = $modelos->createQuestion($grupoId, ['pergunta' => "Comunica bem? {$suffix}", 'peso' => 1, 'obrigatoria' => true]);
$cleanup['pergunta_ids'][] = $perguntaBaixa;
$perguntaAlta = $modelos->createQuestion($grupoId, ['pergunta' => "Entrega no prazo? {$suffix}", 'peso' => 1, 'obrigatoria' => true]);
$cleanup['pergunta_ids'][] = $perguntaAlta;

$ciclos = new PessoaCicloAvaliacaoModel();
$cicloId = $ciclos->create(['empresa_id' => $empresaA['cliente_id'], 'modelo_id' => $modeloId, 'nome' => "Ciclo GapFb {$suffix}"], $modelos);
$cleanup['ciclo_ids'][] = $cicloId;
$ciclos->replaceParticipants($cicloId, $empresaA['cliente_id'], [$col1], $colaboradores);
$avaliacoes = new PessoaAvaliacaoModel();
$avaliacoes->ensureForParticipants($cicloId, $empresaA['cliente_id'], [$col1]);
$ciclos->abrir($cicloId, $empresaA['cliente_id']);
$participante = $ciclos->listParticipants($cicloId)[0];
$avaliacaoId = (int)$participante['avaliacao_id'];
$cleanup['avaliacao_ids'][] = $avaliacaoId;

$avaliacoes->iniciar($avaliacaoId, $empresaA['cliente_id'], 1, $modelos);
$itens = $avaliacoes->listRespostas($avaliacaoId);
$itemBaixo = null; $itemAlto = null;
foreach ($itens as $it) {
    if ($it['pergunta_id'] == $perguntaBaixa) { $itemBaixo = $it; }
    if ($it['pergunta_id'] == $perguntaAlta) { $itemAlto = $it; }
}
$avaliacoes->saveAnswers($avaliacaoId, $empresaA['cliente_id'], [
    $itemBaixo['id'] => ['resposta' => 2, 'observacao' => 'Precisa melhorar'],
    $itemAlto['id'] => ['resposta' => 5, 'observacao' => 'Excelente'],
]);
$resultFinal = $avaliacoes->finalizar($avaliacaoId, $empresaA['cliente_id']);
if (!$resultFinal['ok']) {
    failFast('Falha ao finalizar avaliação de fixture: ' . implode(' | ', $resultFinal['errors']));
}
ok('Fixture: avaliação finalizada com nota 2 (potencial GAP) e nota 5 (destaque positivo)');

// ===================== POTENCIAL GAP / DESTAQUE POSITIVO (limites centralizados) =====================
if (!PessoasGestaoConfig::isPotencialGap(2) || PessoasGestaoConfig::isPotencialGap(3)) {
    failFast('Limite de potencial GAP incorreto (esperado <=2)');
}
if (!PessoasGestaoConfig::isDestaquePositivo(4) || PessoasGestaoConfig::isDestaquePositivo(3)) {
    failFast('Limite de destaque positivo incorreto (esperado >=4)');
}
ok('Limites de potencial GAP (<=2) e destaque positivo (>=4) centralizados e corretos');

// ===================== REGISTRAR GAP A PARTIR DA RESPOSTA =====================
$gaps = new PessoaGapModel();
$r1 = $gaps->createFromResposta($empresaA['cliente_id'], $col1, $avaliacaoId, (int)$itemBaixo['id'], ['titulo' => 'Comunicação a desenvolver', 'descricao' => 'Contexto do feedback', 'prioridade' => 'alta'], $avaliacoes, 1);
if ($r1['id'] <= 0 || $r1['already_existed']) {
    failFast('GAP a partir da resposta deveria ter sido criado com sucesso na primeira vez');
}
$gapId = $r1['id'];
$cleanup['gap_ids'][] = $gapId;
$gapCriado = $gaps->find($gapId);
if ($gapCriado['origem'] !== 'avaliacao' || $gapCriado['status'] !== 'aberto' || (int)$gapCriado['resposta_id'] !== (int)$itemBaixo['id']) {
    failFast('GAP criado a partir de resposta com dados incorretos');
}
ok('GAP registrado a partir da resposta (origem=avaliacao, status=aberto)');

// Tentar de novo para a MESMA resposta -> idempotente, sem duplicar.
$r2 = $gaps->createFromResposta($empresaA['cliente_id'], $col1, $avaliacaoId, (int)$itemBaixo['id'], ['titulo' => 'Outro título'], $avaliacoes, 1);
if (!$r2['already_existed'] || $r2['id'] !== $gapId) {
    failFast('Registrar GAP de novo para a mesma resposta deveria retornar o GAP já existente, sem duplicar');
}
$totalGapsParaResposta = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_gaps WHERE resposta_id = {$itemBaixo['id']}")->fetchColumn();
if ($totalGapsParaResposta !== 1) {
    failFast('Deveria existir exatamente 1 GAP para a resposta, encontrado ' . $totalGapsParaResposta);
}
ok('Duplicidade de GAP para a mesma resposta bloqueada (idempotente, sem duplicar linha no banco)');

// ===================== TRANSIÇÕES DE STATUS DO GAP =====================
if (!$gaps->updateStatus($gapId, $empresaA['cliente_id'], 'em_tratamento')) {
    failFast('Deveria conseguir mover GAP de aberto para em_tratamento');
}
if ($gaps->updateStatus($gapId, $empresaA['cliente_id'], 'aberto')) {
    failFast('Transição inválida: não deveria conseguir voltar de em_tratamento para aberto');
}
ok('GAP movido para em_tratamento; transição inválida (voltar para aberto) bloqueada');

// ===================== FEEDBACK DE MELHORIA (relacionado ao GAP) =====================
$feedbacks = new PessoaFeedbackModel();
$feedbackMelhoriaId = $feedbacks->create($empresaA['cliente_id'], $col1, [
    'tipo' => 'melhoria', 'titulo' => 'Conversa sobre comunicação', 'descricao' => 'Conversamos sobre pontos de melhoria.',
    'data_feedback' => date('Y-m-d'), 'avaliacao_id' => $avaliacaoId, 'gap_id' => $gapId,
], $colaboradores, $avaliacoes, $gaps, 1);
if ($feedbackMelhoriaId <= 0) {
    failFast('Falha ao criar feedback de melhoria relacionado ao GAP');
}
$cleanup['feedback_ids'][] = $feedbackMelhoriaId;
ok('Feedback de Melhoria registrado, relacionado à avaliação e ao GAP');

// ===================== FEEDBACK POSITIVO INDEPENDENTE =====================
$feedbackPositivoId = $feedbacks->create($empresaA['cliente_id'], $col1, [
    'tipo' => 'positivo', 'titulo' => 'Excelente condução de reunião', 'descricao' => 'Excelente condução da reunião mensal com a equipe.',
    'data_feedback' => date('Y-m-d'),
], $colaboradores, $avaliacoes, $gaps, 1);
if ($feedbackPositivoId <= 0) {
    failFast('Falha ao criar feedback positivo independente');
}
$cleanup['feedback_ids'][] = $feedbackPositivoId;
$feedbackPositivo = $feedbacks->find($feedbackPositivoId);
if ($feedbackPositivo['avaliacao_id'] !== null || $feedbackPositivo['gap_id'] !== null) {
    failFast('Feedback independente não deveria ter avaliacao_id/gap_id');
}
ok('Feedback Positivo registrado de forma totalmente independente (sem avaliação nem GAP)');

// Nenhum GAP foi criado automaticamente pela nota alta.
$totalGapsAntes = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_gaps WHERE colaborador_id = {$col1}")->fetchColumn();
if ($totalGapsAntes !== 1) {
    failFast('Nota alta não deveria ter gerado GAP automaticamente (esperado ainda 1 GAP no total)');
}
ok('Nenhum GAP foi criado automaticamente a partir da nota alta (5)');

// ===================== AÇÃO DE MELHORIA =====================
$acoes = new PessoaAcaoMelhoriaModel();
$elegiveis = $acoes->usuariosResponsaveisDisponiveis($empresaA['cliente_id']);
if (empty($elegiveis)) {
    failFast('Deveria haver ao menos 1 usuário elegível (Instituto tem acesso global)');
}
$responsavelId = (int)$elegiveis[0]['id'];

$acaoId = $acoes->create($empresaA['cliente_id'], $col1, [
    'titulo' => 'Treinamento de comunicação', 'descricao' => 'Inscrever em curso de comunicação assertiva.',
    'responsavel_usuario_id' => $responsavelId, 'data_inicio' => date('Y-m-d'), 'prazo' => date('Y-m-d', strtotime('+30 days')),
    'gap_id' => $gapId, 'avaliacao_id' => $avaliacaoId,
], $colaboradores, $gaps, $avaliacoes, 1);
if ($acaoId <= 0) {
    failFast('Falha ao criar ação de melhoria');
}
$cleanup['acao_ids'][] = $acaoId;
$acaoCriada = $acoes->find($acaoId);
if ($acaoCriada['status'] !== 'pendente' || (int)$acaoCriada['responsavel_usuario_id'] !== $responsavelId || (int)$acaoCriada['gap_id'] !== $gapId) {
    failFast('Ação de melhoria criada com dados incorretos');
}
ok('Ação de Melhoria criada com responsável, prazo e vinculada ao GAP (status pendente)');

if (!$acoes->marcarEmAndamento($acaoId, $empresaA['cliente_id'])) {
    failFast('Falha ao marcar ação como em andamento');
}
$conclusaoTexto = 'Colaborador concluiu treinamento de comunicação e apresentou melhora nas reuniões de equipe.';
if (!$acoes->concluir($acaoId, $empresaA['cliente_id'], $conclusaoTexto)) {
    failFast('Falha ao concluir ação');
}
$acaoConcluida = $acoes->find($acaoId);
if ($acaoConcluida['status'] !== 'concluida' || $acaoConcluida['conclusao'] !== $conclusaoTexto || empty($acaoConcluida['concluido_em'])) {
    failFast('Ação concluída não gravou status/conclusão/concluido_em corretamente');
}
ok('Ação de Melhoria concluída com texto de conclusão e concluido_em preenchido');

// GAP continua independente: concluir a ação NÃO resolve o GAP automaticamente.
$gapAposAcaoConcluida = $gaps->find($gapId);
if ($gapAposAcaoConcluida['status'] !== 'em_tratamento') {
    failFast('Concluir a ação NÃO deveria alterar automaticamente o status do GAP');
}
ok('GAP permanece independente da Ação: concluir a ação não resolve o GAP automaticamente');

// Segunda ação, cancelada (preserva histórico, não hard-delete).
$acao2Id = $acoes->create($empresaA['cliente_id'], $col1, ['titulo' => 'Ação cancelada de teste'], $colaboradores, $gaps, $avaliacoes, 1);
$cleanup['acao_ids'][] = $acao2Id;
if (!$acoes->cancelar($acao2Id, $empresaA['cliente_id'])) {
    failFast('Falha ao cancelar ação');
}
$acao2 = $acoes->find($acao2Id);
if ($acao2['status'] !== 'cancelada') {
    failFast('Ação cancelada deveria ter status=cancelada e continuar no histórico (sem hard-delete)');
}
ok('Ação cancelada preserva o registro no histórico (sem hard-delete)');

// ===================== RESOLVER O GAP =====================
if (!$gaps->updateStatus($gapId, $empresaA['cliente_id'], 'resolvido')) {
    failFast('Falha ao resolver o GAP');
}
$gapResolvido = $gaps->find($gapId);
if ($gapResolvido['status'] !== 'resolvido' || empty($gapResolvido['resolvido_em'])) {
    failFast('GAP resolvido deveria ter status=resolvido e resolvido_em preenchido');
}
if ($gaps->updateStatus($gapId, $empresaA['cliente_id'], 'em_tratamento')) {
    failFast('GAP resolvido não deveria poder ser reaberto nesta Sprint');
}
ok('GAP resolvido (resolvido_em preenchido); reabertura bloqueada');

// ===================== GAP MANUAL =====================
$gapManualId = $gaps->createManual($empresaA['cliente_id'], $col1, ['titulo' => 'Dificuldade de liderança percebida', 'descricao' => 'Observação direta', 'prioridade' => 'baixa'], $colaboradores, 1);
if ($gapManualId <= 0) {
    failFast('Falha ao criar GAP manual');
}
$cleanup['gap_ids'][] = $gapManualId;
$gapManual = $gaps->find($gapManualId);
if ($gapManual['origem'] !== 'manual' || $gapManual['avaliacao_id'] !== null || $gapManual['resposta_id'] !== null) {
    failFast('GAP manual deveria ter origem=manual, avaliacao_id=NULL e resposta_id=NULL');
}
ok('GAP manual criado corretamente (origem=manual, sem avaliação/resposta associada)');

// ===================== HISTÓRICO DO COLABORADOR =====================
$avaliacoesColab = $pdo->prepare('SELECT id FROM pessoas_avaliacoes WHERE colaborador_id = :cid');
$avaliacoesColab->execute(['cid' => $col1]);
$gapsColab = $gaps->listByColaborador($col1, $empresaA['cliente_id']);
$feedbacksColab = $feedbacks->listByColaborador($col1, $empresaA['cliente_id']);
$acoesColab = $acoes->listByColaborador($col1, $empresaA['cliente_id']);
if (count($gapsColab) !== 2) {
    failFast('Histórico deveria mostrar 2 GAPs (da avaliação + manual), encontrado ' . count($gapsColab));
}
if (count($feedbacksColab) !== 2) {
    failFast('Histórico deveria mostrar 2 feedbacks (melhoria + positivo), encontrado ' . count($feedbacksColab));
}
if (count($acoesColab) !== 2) {
    failFast('Histórico deveria mostrar 2 ações (concluída + cancelada), encontrado ' . count($acoesColab));
}
// Mais recente primeiro.
if ((int)$gapsColab[0]['id'] !== $gapManualId) {
    failFast('Histórico de GAPs deveria vir mais recente primeiro (GAP manual, criado por último)');
}
ok('Histórico do colaborador consolida avaliação + 2 GAPs + 2 feedbacks + 2 ações, mais recentes primeiro');

// ===================== TENANT: Empresa A não pode usar dados da Empresa B =====================
if ($gaps->createManual($empresaA['cliente_id'], $colB, ['titulo' => 'GAP adulterado'], $colaboradores, 1) > 0) {
    failFast('Tenant: não deveria ser possível criar GAP manual para colaborador de outra empresa');
}
if ($feedbacks->create($empresaA['cliente_id'], $colB, ['tipo' => 'positivo', 'titulo' => 'x', 'descricao' => 'x', 'data_feedback' => date('Y-m-d')], $colaboradores, $avaliacoes, $gaps, 1) > 0) {
    failFast('Tenant: não deveria ser possível criar feedback para colaborador de outra empresa');
}
if ($acoes->create($empresaA['cliente_id'], $colB, ['titulo' => 'x'], $colaboradores, $gaps, $avaliacoes, 1) > 0) {
    failFast('Tenant: não deveria ser possível criar ação para colaborador de outra empresa');
}
// Criar um GAP legítimo em B para tentar usá-lo cross-tenant a partir de A.
$gapDeB = $gaps->createManual($empresaB['cliente_id'], $colB, ['titulo' => 'GAP legítimo de B'], $colaboradores, 1);
$cleanup['gap_ids'][] = $gapDeB;
if ($gaps->findForColaborador($gapDeB, $col1, $empresaA['cliente_id']) !== null) {
    failFast('Tenant: GAP de B não deveria ser acessível via colaborador de A');
}
$acaoComGapExterno = $acoes->create($empresaA['cliente_id'], $col1, ['titulo' => 'Ação com gap externo', 'gap_id' => $gapDeB], $colaboradores, $gaps, $avaliacoes, 1);
if ($acaoComGapExterno > 0) {
    $cleanup['acao_ids'][] = $acaoComGapExterno;
    $acaoVerificada = $acoes->find($acaoComGapExterno);
    if (!empty($acaoVerificada['gap_id'])) {
        failFast('Tenant: gap_id de outra empresa não deveria ter sido associado à ação (deveria ficar NULL)');
    }
}
// Avaliação/resposta de fora do colaborador não podem virar GAP.
$rAdulterado = $gaps->createFromResposta($empresaA['cliente_id'], $colB, $avaliacaoId, (int)$itemBaixo['id'], ['titulo' => 'x'], $avaliacoes, 1);
if ($rAdulterado['id'] > 0) {
    failFast('Tenant: não deveria ser possível criar GAP a partir de resposta de outro colaborador/avaliação');
}
ok('Tenant: colaborador/avaliação/resposta/GAP de outra empresa bloqueados em GAP, Feedback e Ação (gap_id externo ignorado com segurança, nunca vaza dado)');

echo "pessoas_gaps_feedbacks_acoes_integration_test passed.\n";
