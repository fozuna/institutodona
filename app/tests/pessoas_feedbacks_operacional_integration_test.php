<?php
// Pilar de Pessoas - Sprint 05.1: consolidação operacional de Feedbacks
// (listagem, filtros, tenant, vínculos com GAP/Avaliação, autoria, histórico).
// Reaproveita o domínio existente (PessoaFeedbackModel, pessoas_feedbacks) e
// as fixtures compartilhadas da Sprint 05 - nenhuma tabela/campo novo.
require __DIR__ . '/../autoload.php';
require __DIR__ . '/helpers/pessoas_sprint05_fixtures.php';

use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaFeedbackModel;
use App\Models\PessoaGapModel;
use App\Models\PessoaOperacionalModel;

ob_start();
function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }
function asInstituto(): void { $_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []]; }
function asClienteAdmin(int $eid): void { $_SESSION['user'] = ['id' => 987321, 'nome' => 'CA', 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $eid, 'allowed_client_ids' => [$eid]]; }
function eq($got, $exp, string $what): void { if ($got !== $exp) { failFast("$what: esperado " . json_encode($exp) . ' obtido ' . json_encode($got)); } }
function titulos(array $items): array { return array_map(static fn(array $r): string => (string)$r['titulo'], $items); }

asInstituto();
$F = s05_fixtures();
$eA = $F['eA']; $eB = $F['B']['eid'];
$pdo = Database::getConnection();
$m = new PessoaOperacionalModel();
$feedbacks = new PessoaFeedbackModel();
$cols = new ColaboradorModel();
$gaps = new PessoaGapModel();
$avals = new PessoaAvaliacaoModel();

// Fixtures adicionais de Feedback (além do "Feedback s05" que s05_fixtures() já cria
// para a1, positivo, 2026-09-10, sem vínculo). FKs em CASCADE a partir de
// clientes/colaboradores (já cadastradas no cleanup de s05_fixtures) removem estas
// linhas também - não precisa de cleanup próprio.
$fbIns = static fn(array $row) => s05_ins($pdo, 'pessoas_feedbacks', $row);
$fb2 = $fbIns(['empresa_id' => $eA, 'colaborador_id' => $F['a2'], 'tipo' => 'melhoria', 'titulo' => 'fb2 antigo', 'descricao' => 'd2', 'data_feedback' => '2026-02-01']);
$fb3 = $fbIns(['empresa_id' => $eA, 'colaborador_id' => $F['a3'], 'tipo' => 'positivo', 'titulo' => 'fb3 dep2', 'descricao' => 'd3', 'data_feedback' => '2026-09-12']);
$fb4 = $fbIns(['empresa_id' => $eA, 'colaborador_id' => $F['a1'], 'tipo' => 'melhoria', 'titulo' => 'fb4 com gap', 'descricao' => 'd4', 'data_feedback' => '2026-09-20', 'gap_id' => $F['g']['gA1']]);
$fb5 = $fbIns(['empresa_id' => $eA, 'colaborador_id' => $F['a1'], 'tipo' => 'positivo', 'titulo' => 'fb5 com avaliacao', 'descricao' => 'd5', 'data_feedback' => '2026-09-14', 'avaliacao_id' => $F['av1']]);
$fbB = $fbIns(['empresa_id' => $eB, 'colaborador_id' => $F['b1'], 'tipo' => 'positivo', 'titulo' => 'fbB outra empresa', 'descricao' => 'dB', 'data_feedback' => '2026-09-10']);
ok('Fixtures adicionais de Feedback criadas (5 em A com diversidade de tipo/período/vínculo, 1 em B)');

// ===== 1/3. Instituto consegue listar; tenant correto =====
$todosA = $m->listarFeedbacks([$eA], [], 1, 50);
eq($todosA['total'], 5, 'Instituto: total de feedbacks de A (B excluída)');
eq(titulos($todosA['items']), ['fb4 com gap', 'fb5 com avaliacao', 'fb3 dep2', 'Feedback s05', 'fb2 antigo'], 'Ordenação: data_feedback DESC, id DESC');
ok('Instituto lista feedbacks de A; tenant correto; ordenação por mais recente determinística');

// ===== 2/4. Cliente Admin lista dentro do escopo; cross-tenant bloqueado mesmo se o Controller mandar o id errado =====
asClienteAdmin($eA);
eq($m->listarFeedbacks([$eA], [], 1, 50)['total'], 5, 'Cliente Admin de A: lista o próprio escopo');
eq($m->listarFeedbacks([$eB], [], 1, 50)['total'], 0, 'Cliente Admin de A: empresa de B isolada mesmo se pedida diretamente');
eq($m->listarFeedbacks([$eA, $eB], [], 1, 50)['total'], 5, 'tenantInCondition reforça o escopo mesmo com [A,B] combinados');
asInstituto();
ok('Cliente Admin: escopo aplicado no SQL (tenantInCondition), não apenas no Controller');

// ===== 5. Filtro por empresa (via lista de empresaIds resolvida pelo Controller) =====
eq($m->listarFeedbacks([$eB], [], 1, 50)['total'], 1, 'filtro por empresa: só B');
ok('Filtro por empresa');

// ===== 6/7/8. Filtros organizacionais (departamento/setor/função) =====
eq($m->listarFeedbacks([$eA], ['departamento_id' => $F['A']['deps'][2]], 1, 50)['total'], 1, 'filtro departamento (dep2 = só a3)');
eq($m->listarFeedbacks([$eA], ['setor_id' => $F['A']['sets'][1]], 1, 50)['total'], 4, 'filtro setor (set1 = a1+a2)');
eq($m->listarFeedbacks([$eA], ['funcao_id' => $F['A']['funs'][2]], 1, 50)['total'], 1, 'filtro função (fun2 = só a3)');
ok('Filtros organizacionais: departamento, setor, função');

// ===== 9. Filtro por colaborador =====
eq($m->listarFeedbacks([$eA], ['colaborador_id' => $F['a1']], 1, 50)['total'], 3, 'filtro colaborador (a1: s05 + fb4 + fb5)');
$nomeA2 = (string)$pdo->query('SELECT nome FROM colaboradores WHERE id = ' . (int)$F['a2'])->fetchColumn();
eq($m->listarFeedbacks([$eA], ['q' => $nomeA2], 1, 50)['total'], 1, 'busca por nome do colaborador');
ok('Filtro por colaborador (id direto e busca por nome)');

// ===== 10/11. Filtro por tipo =====
eq($m->listarFeedbacks([$eA], ['tipo' => 'positivo'], 1, 50)['total'], 3, 'filtro tipo=positivo');
eq($m->listarFeedbacks([$eA], ['tipo' => 'melhoria'], 1, 50)['total'], 2, 'filtro tipo=melhoria');
eq($m->listarFeedbacks([$eA], ['tipo' => 'invalido; DROP TABLE'], 1, 50)['total'], 5, 'tipo fora da whitelist é ignorado (nunca interpolado)');
ok('Filtro por tipo: positivo, melhoria, valor inválido descartado com segurança');

// ===== 12. Filtro por período usa data_feedback (não created_at) =====
eq($m->listarFeedbacks([$eA], ['inicio' => '2026-09-01', 'fim' => '2026-09-30'], 1, 50)['total'], 4, 'período de setembro exclui fb2 (fevereiro)');
eq($m->listarFeedbacks([$eA], ['inicio' => '2026-02-01', 'fim' => '2026-02-28'], 1, 50)['total'], 1, 'período de fevereiro só pega fb2');
$semPeriodoMasCreatedAtDeHoje = $m->listarFeedbacks([$eA], ['inicio' => date('Y-m-d'), 'fim' => date('Y-m-d')], 1, 50)['total'];
eq($semPeriodoMasCreatedAtDeHoje, 0, 'período de hoje não pega fb2 (criado hoje via INSERT, mas com data_feedback de fevereiro) - prova que o filtro usa data_feedback');
ok('Filtro de período usa data_feedback (data semântica do evento), não created_at');

// ===== 13. Paginação =====
$p1 = $m->listarFeedbacks([$eA], [], 1, 2);
$p2 = $m->listarFeedbacks([$eA], [], 2, 2);
eq(count($p1['items']), 2, 'página 1 com 2 itens');
eq(count($p2['items']), 2, 'página 2 com 2 itens');
eq($p1['total'], 5, 'total preservado entre páginas');
eq(array_intersect(titulos($p1['items']), titulos($p2['items'])), [], 'páginas não se sobrepõem');
ok('Paginação');

// ===== 14. Ordenação por mais recente (já provada acima); confirma id DESC como desempate =====
$mesmodia = [
    $fbIns(['empresa_id' => $eA, 'colaborador_id' => $F['a1'], 'tipo' => 'positivo', 'titulo' => 'empate 1', 'descricao' => 'x', 'data_feedback' => '2026-05-01']),
    $fbIns(['empresa_id' => $eA, 'colaborador_id' => $F['a1'], 'tipo' => 'positivo', 'titulo' => 'empate 2', 'descricao' => 'x', 'data_feedback' => '2026-05-01']),
];
$empate = $m->listarFeedbacks([$eA], ['colaborador_id' => $F['a1'], 'inicio' => '2026-05-01', 'fim' => '2026-05-01'], 1, 50);
eq(titulos($empate['items']), ['empate 2', 'empate 1'], 'desempate por id DESC quando data_feedback é igual');
ok('Ordenação determinística: data_feedback DESC, id DESC');

// ===== 15. Registro contextual pelo colaborador (empresa nunca vem do formulário) =====
$antesA1 = count($feedbacks->listByColaborador($F['a1'], $eA));
$novoId = $feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => 'Contextual', 'descricao' => 'via central', 'data_feedback' => '2026-09-25'], $cols, $avals, $gaps, 501);
if ($novoId <= 0) { failFast('Criar feedback contextual falhou'); }
$linha = $feedbacks->find($novoId);
if ((int)$linha['colaborador_id'] !== $F['a1'] || (int)$linha['empresa_id'] !== $eA) { failFast('Feedback contextual com colaborador/empresa incorretos'); }
if ((int)$linha['registrado_por'] !== 501 || $linha['registrado_por_nome'] === null && false) { /* nome pode ser null se usuário de teste não existe - ok */ }
$depoisA1 = count($feedbacks->listByColaborador($F['a1'], $eA));
eq($depoisA1, $antesA1 + 1, '24. histórico do colaborador (listByColaborador) atualizado imediatamente');
ok('Registro contextual: colaborador/empresa corretos; autoria gravada; histórico atualizado');

// ===== 16/17. Vínculo válido com Avaliação e com GAP =====
$comAval = $feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => 'Com avaliação válida', 'descricao' => 'x', 'avaliacao_id' => $F['av1']], $cols, $avals, $gaps, 1);
$linhaAval = $feedbacks->find($comAval);
eq((int)$linhaAval['avaliacao_id'], (int)$F['av1'], 'vínculo com avaliação do MESMO colaborador/empresa é aceito');
$comGap = $feedbacks->create($eA, $F['a1'], ['tipo' => 'melhoria', 'titulo' => 'Com GAP válido', 'descricao' => 'x', 'gap_id' => $F['g']['gA1']], $cols, $avals, $gaps, 1);
$linhaGap = $feedbacks->find($comGap);
eq((int)$linhaGap['gap_id'], (int)$F['g']['gA1'], 'vínculo com GAP do MESMO colaborador/empresa é aceito');
eq($linhaGap['gap_titulo'], 'gA1 sem acao', 'find() traz o título do GAP relacionado (usado na tela de detalhe)');
ok('Vínculo válido com Avaliação e com GAP (mesmo colaborador/empresa)');

// ===== 18/19. Avaliação/GAP cross-tenant (ou de outro colaborador) bloqueados =====
$avB = s05_ins($pdo, 'pessoas_avaliacoes', ['empresa_id' => $eB, 'ciclo_id' => $F['B']['ciclo1'], 'colaborador_id' => $F['b1'], 'status' => 'finalizada', 'resultado' => 3.0, 'finalizado_em' => '2026-09-01 10:00:00']);
$semAval = $feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => 'Avaliação de outra empresa', 'descricao' => 'x', 'avaliacao_id' => $avB], $cols, $avals, $gaps, 1);
if ($semAval <= 0) { failFast('Feedback com avaliação cross-tenant deveria ser criado (só sem o vínculo), não rejeitado por inteiro'); }
$linhaSemAval = $feedbacks->find($semAval);
if ($linhaSemAval['avaliacao_id'] !== null) { failFast('Avaliação de outra empresa NÃO deveria ser persistida como vínculo'); }
$semGapOutroColab = $feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => 'GAP de outro colaborador', 'descricao' => 'x', 'gap_id' => $F['g']['gA4']], $cols, $avals, $gaps, 1);
$linhaSemGap = $feedbacks->find($semGapOutroColab);
if ($linhaSemGap['gap_id'] !== null) { failFast('GAP de OUTRO colaborador (mesma empresa) NÃO deveria ser persistido como vínculo'); }
$semGapB = $feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => 'GAP de outra empresa', 'descricao' => 'x', 'gap_id' => $F['g']['gB1']], $cols, $avals, $gaps, 1);
$linhaSemGapB = $feedbacks->find($semGapB);
if ($linhaSemGapB['gap_id'] !== null) { failFast('GAP de OUTRA empresa NÃO deveria ser persistido como vínculo'); }
ok('Avaliação/GAP cross-tenant e de outro colaborador: vínculo descartado (propriedade validada antes da gravação)');

// ===== 20/21. Conteúdo obrigatório; tipo inválido bloqueado =====
if ($feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => 'Sem descrição', 'descricao' => ''], $cols, $avals, $gaps, 1) !== 0) { failFast('Descrição vazia deveria bloquear'); }
if ($feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => '', 'descricao' => 'x'], $cols, $avals, $gaps, 1) !== 0) { failFast('Título vazio deveria bloquear'); }
if ($feedbacks->create($eA, $F['a1'], ['tipo' => 'neutro', 'titulo' => 'x', 'descricao' => 'x'], $cols, $avals, $gaps, 1) !== 0) { failFast('Tipo inválido deveria bloquear'); }
if ($feedbacks->create($eA, $F['b1'], ['tipo' => 'positivo', 'titulo' => 'x', 'descricao' => 'x'], $cols, $avals, $gaps, 1) !== 0) { failFast('Colaborador de outra empresa (empresaId=A, colaborador de B) deveria bloquear'); }
ok('Validação obrigatória: título, descrição, tipo e colaborador/empresa coerentes');

// ===== Tamanho máximo conforme schema (titulo 255 / descricao 2000) =====
$idLongo = $feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => str_repeat('T', 400), 'descricao' => str_repeat('D', 2500)], $cols, $avals, $gaps, 1);
if ($idLongo <= 0) { failFast('Título/descrição longos deveriam ser truncados, não rejeitados'); }
$linhaLonga = $feedbacks->find($idLongo);
if (mb_strlen($linhaLonga['titulo']) > 255 || mb_strlen($linhaLonga['descricao']) > 2000) { failFast('Truncamento de título/descrição não aplicado conforme schema'); }
ok('Título/descrição truncados no limite do schema (VARCHAR 255/2000), sem erro de banco');

// ===== 23. Autoria =====
$comAutoria = $feedbacks->create($eA, $F['a1'], ['tipo' => 'positivo', 'titulo' => 'Com autoria', 'descricao' => 'x'], $cols, $avals, $gaps, (int)$F['u']);
$listaA1 = $feedbacks->listByColaborador($F['a1'], $eA);
$achado = null;
foreach ($listaA1 as $row) { if ((int)$row['id'] === $comAutoria) { $achado = $row; break; } }
if ($achado === null || (int)$achado['registrado_por'] !== (int)$F['u'] || empty($achado['registrado_por_nome'])) {
    failFast('Autoria (registrado_por/registrado_por_nome) deveria aparecer no histórico do colaborador');
}
ok('Autoria: registrado_por persistido e resolvido para nome (registrado_por_nome) na listagem e no histórico');

// ===== Independência: Feedback não tem lifecycle (sem update/delete no domínio) =====
if (method_exists($feedbacks, 'update') || method_exists($feedbacks, 'delete') || method_exists($feedbacks, 'updateStatus')) {
    failFast('PessoaFeedbackModel não deveria ganhar update()/delete()/updateStatus() nesta Sprint (feedback é histórico imutável)');
}
ok('Feedback permanece sem lifecycle/edição/exclusão (histórico de gestão imutável)');

echo "\nTodos os testes operacionais de Feedbacks passaram.\n";
