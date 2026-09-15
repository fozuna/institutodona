<?php
// Pilar de Pessoas - Sprint 01: fundação funcional de Avaliações de
// Desempenho. Teste de integração único cobrindo o fluxo real completo
// (modelo -> grupos -> perguntas -> ciclo -> participantes -> avaliação ->
// snapshot -> respostas -> finalização -> resultado), tenant/RBAC e
// imutabilidade histórica, em vez de dezenas de testes microscópicos.
require __DIR__ . '/../autoload.php';

use App\Core\AccessControl;
use App\Core\PessoasAvaliacaoScale;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\PessoaAvaliacaoModel;
use App\Models\PessoaCicloAvaliacaoModel;
use App\Models\PessoaModeloAvaliacaoModel;
use App\Models\SetorModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'avaliacao_ids' => [], 'ciclo_ids' => [], 'pergunta_ids' => [], 'grupo_ids' => [], 'modelo_ids' => [],
    'colaborador_ids' => [], 'funcao_ids' => [], 'setor_ids' => [], 'departamento_ids' => [], 'cliente_ids' => [],
    'usuario_ids' => [],
];
register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
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
        foreach ($cleanup['usuario_ids'] as $id) { $pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {
    }
});

function makeEmpresaCompleta(PDO $pdo, string $tag, string $suffix, array &$cleanup): array
{
    $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n, :c, :t)');
    $stmt->execute(['n' => "Empresa Pessoas {$tag} {$suffix}", 'c' => '11.111.1' . substr($tag, 0, 1) . '/0001-' . substr($suffix, 0, 2), 't' => 'Teste']);
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

function makeColaborador(ColaboradorModel $model, int $clienteId, int $funcaoId, string $nome, array &$cleanup): int
{
    $id = $model->create(['nome' => $nome, 'funcao_id' => $funcaoId, 'cliente_id' => $clienteId, 'ativo' => 1]);
    if ($id > 0) {
        $cleanup['colaborador_ids'][] = $id;
    }
    return $id;
}

// ===================== FIXTURES =====================
$empresaA = makeEmpresaCompleta($pdo, 'A', $suffix, $cleanup);
$empresaB = makeEmpresaCompleta($pdo, 'B', $suffix, $cleanup);

$colaboradores = new ColaboradorModel();
$col1 = makeColaborador($colaboradores, $empresaA['cliente_id'], $empresaA['funcao_id'], "Colaborador 1 {$suffix}", $cleanup);
$col2 = makeColaborador($colaboradores, $empresaA['cliente_id'], $empresaA['funcao_id'], "Colaborador 2 {$suffix}", $cleanup);
$colB = makeColaborador($colaboradores, $empresaB['cliente_id'], $empresaB['funcao_id'], "Colaborador B {$suffix}", $cleanup);
if ($col1 <= 0 || $col2 <= 0 || $colB <= 0) {
    failFast('Fixtures: falha ao criar colaboradores');
}
ok('Fixtures: Empresa A (2 colaboradores) + Empresa B (1 colaborador, fora do tenant de A)');

// ===================== ESTRUTURA: MODELO / GRUPOS / PERGUNTAS =====================
$modelos = new PessoaModeloAvaliacaoModel();
$modeloId = $modelos->create(['empresa_id' => $empresaA['cliente_id'], 'nome' => "Modelo {$suffix}", 'descricao' => 'Teste', 'created_by' => 1]);
if ($modeloId <= 0) {
    failFast('Falha ao criar modelo');
}
$cleanup['modelo_ids'][] = $modeloId;

$grupoComunicacao = $modelos->createGroup($modeloId, ['nome' => 'Comunicação', 'descricao' => '']);
$cleanup['grupo_ids'][] = $grupoComunicacao;
$grupoProdutividade = $modelos->createGroup($modeloId, ['nome' => 'Produtividade', 'descricao' => '']);
$cleanup['grupo_ids'][] = $grupoProdutividade;

// Cenário de cálculo conhecido (seção 33 do pedido): pergunta A nota 5 peso 2,
// pergunta B nota 3 peso 1 -> (5*2 + 3*1) / 3 = 4,3333... -> 4,33.
$perguntaA = $modelos->createQuestion($grupoComunicacao, ['pergunta' => "Comunica informações de forma clara e objetiva? {$suffix}", 'peso' => 2.0, 'obrigatoria' => true]);
$cleanup['pergunta_ids'][] = $perguntaA;
$perguntaB = $modelos->createQuestion($grupoProdutividade, ['pergunta' => "Entrega no prazo? {$suffix}", 'peso' => 1.0, 'obrigatoria' => true]);
$cleanup['pergunta_ids'][] = $perguntaB;
$perguntaOpcional = $modelos->createQuestion($grupoProdutividade, ['pergunta' => "Observações gerais (opcional)? {$suffix}", 'peso' => 1.0, 'obrigatoria' => false]);
$cleanup['pergunta_ids'][] = $perguntaOpcional;

$estrutura = $modelos->fullStructure($modeloId);
if (count($estrutura) !== 2 || count($estrutura[0]['perguntas']) !== 1 || count($estrutura[1]['perguntas']) !== 2) {
    failFast('Estrutura do modelo não bate com o esperado (2 grupos, 1 e 2 perguntas)');
}
ok('Estrutura: modelo com 2 grupos e 3 perguntas (pesos e ordenação corretos)');

$peso = (float)$modelos->findQuestion($perguntaA)['peso'];
if (abs($peso - 2.0) > 0.001) {
    failFast('Peso da pergunta A não foi persistido corretamente');
}
ok('Pesos decimais persistidos corretamente');

// ===================== TENANT: Cliente Admin A não acessa Empresa B =====================
$_SESSION['user'] = [
    'id' => 501,
    'nome' => 'Cliente Admin A',
    'email' => 'admin.a@example.com',
    'tipo_acesso' => 'cliente_admin',
    'id_cliente' => $empresaA['cliente_id'],
    'allowed_client_ids' => [$empresaA['cliente_id']],
];
$modeloExternoVisto = $modelos->find($modeloId + 1000000); // id inexistente/fora do escopo, sanity check
if ($modeloExternoVisto !== null) {
    failFast('Cenário tenant: id inexistente não deveria retornar modelo');
}
// Modelo criado pelo Instituto para a Empresa A: Cliente Admin de A consegue ver.
$modeloVistoPorAdminA = $modelos->find($modeloId);
if ($modeloVistoPorAdminA === null) {
    failFast('Cenário tenant: Cliente Admin de A deveria enxergar o próprio modelo');
}
// Um modelo fictício de B não pode ser encontrado/editado por Admin A.
$modeloB = null;
$_SESSION['user'] = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$modeloB = $modelos->create(['empresa_id' => $empresaB['cliente_id'], 'nome' => "Modelo B {$suffix}", 'created_by' => 1]);
$cleanup['modelo_ids'][] = $modeloB;
$_SESSION['user'] = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $empresaA['cliente_id'], 'allowed_client_ids' => [$empresaA['cliente_id']]];
if ($modelos->find($modeloB) !== null) {
    failFast('Cenário tenant: Cliente Admin A não pode enxergar modelo de B');
}
if ($modelos->findForEmpresa($modeloB, $empresaA['cliente_id']) !== null) {
    failFast('Cenário tenant: findForEmpresa não deve aceitar modelo de outra empresa');
}
// Tentar criar ciclo de A usando o modelo de B (manipulação de id) deve falhar.
$ciclosTmp = new PessoaCicloAvaliacaoModel();
$cicloAdulterado = $ciclosTmp->create(['empresa_id' => $empresaA['cliente_id'], 'modelo_id' => $modeloB, 'nome' => 'Ciclo adulterado'], $modelos);
if ($cicloAdulterado > 0) {
    failFast('Cenário tenant: não deveria ser possível criar ciclo de A usando modelo de B');
}
// Colaborador de B não pode ser adicionado como participante de ciclo de A (testado adiante).
ok('Tenant: Cliente Admin de A não acessa/usa modelo de outra empresa (find, findForEmpresa, criação de ciclo adulterada)');

$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

// ===================== CICLO: criação, transições inválidas =====================
$ciclos = new PessoaCicloAvaliacaoModel();
$cicloId = $ciclos->create([
    'empresa_id' => $empresaA['cliente_id'],
    'modelo_id' => $modeloId,
    'nome' => "Ciclo {$suffix}",
    'data_inicio' => date('Y-m-d'),
    'data_fim' => date('Y-m-d', strtotime('+30 days')),
    'created_by' => 1,
], $modelos);
if ($cicloId <= 0) {
    failFast('Falha ao criar ciclo');
}
$cleanup['ciclo_ids'][] = $cicloId;
$cicloRow = $ciclos->find($cicloId);
if ($cicloRow['status'] !== 'rascunho') {
    failFast('Ciclo deveria nascer em rascunho');
}
ok('Ciclo criado em rascunho');

if ($ciclos->encerrar($cicloId, $empresaA['cliente_id'])) {
    failFast('Transição inválida: não deveria conseguir encerrar um ciclo em rascunho');
}
if ($ciclos->abrir($cicloId, $empresaA['cliente_id'])) {
    failFast('Transição inválida: não deveria conseguir abrir um ciclo sem participantes');
}
ok('Transições inválidas bloqueadas (encerrar rascunho; abrir sem participantes)');

// ===================== PARTICIPANTES =====================
$resultParticipantes = $ciclos->replaceParticipants($cicloId, $empresaA['cliente_id'], [$col1, $col2, $colB, 999999], $colaboradores);
if (!$resultParticipantes['ok'] || $resultParticipantes['added'] !== 2 || $resultParticipantes['ignored'] !== 2) {
    failFast('Participantes: esperado 2 adicionados (col1,col2) e 2 ignorados (colB de outra empresa + id inexistente). Obtido: ' . json_encode($resultParticipantes));
}
ok('Participantes: colaborador de outra empresa (colB) e id inexistente ignorados; só col1/col2 (da mesma empresa) entraram');

$avaliacoes = new PessoaAvaliacaoModel();
$avaliacoes->ensureForParticipants($cicloId, $empresaA['cliente_id'], [$col1, $col2]);
$participantesListados = $ciclos->listParticipants($cicloId);
if (count($participantesListados) !== 2) {
    failFast('listParticipants deveria retornar 2 participantes');
}
foreach ($participantesListados as $p) {
    $cleanup['avaliacao_ids'][] = (int)$p['avaliacao_id'];
    if ($p['avaliacao_status'] !== 'pendente') {
        failFast('Avaliação deveria nascer pendente para cada participante');
    }
}
ok('Cada participante ganhou automaticamente uma Avaliação individual (status pendente)');

// ===================== ABERTURA DO CICLO =====================
if (!$ciclos->abrir($cicloId, $empresaA['cliente_id'])) {
    failFast('Falha ao abrir o ciclo com participantes');
}
if ($ciclos->abrir($cicloId, $empresaA['cliente_id'])) {
    failFast('Transição inválida: não deveria conseguir abrir um ciclo já aberto de novo');
}
ok('Ciclo aberto com sucesso; reabertura indevida bloqueada');

// ===================== AVALIAÇÃO: início idempotente + snapshot =====================
$avaliacao1Id = null;
foreach ($ciclos->listParticipants($cicloId) as $p) {
    if ((int)$p['colaborador_id'] === $col1) {
        $avaliacao1Id = (int)$p['avaliacao_id'];
    }
}
if (!$avaliacao1Id) {
    failFast('Não encontrou a avaliação do colaborador 1');
}

$usuarioAvaliadorId = 777;
$iniciada1 = $avaliacoes->iniciar($avaliacao1Id, $empresaA['cliente_id'], $usuarioAvaliadorId, $modelos);
$iniciada2 = $avaliacoes->iniciar($avaliacao1Id, $empresaA['cliente_id'], $usuarioAvaliadorId, $modelos); // repetir: deve ser idempotente
if ($iniciada1['status'] !== 'em_andamento' || $iniciada2['status'] !== 'em_andamento') {
    failFast('Avaliação deveria estar em_andamento após iniciar');
}
$respostasSnapshot = $avaliacoes->listRespostas($avaliacao1Id);
if (count($respostasSnapshot) !== 3) {
    failFast('Idempotência do início: esperado 3 itens de snapshot (3 perguntas), obtido ' . count($respostasSnapshot) . ' - possível duplicação por chamar iniciar() duas vezes');
}
ok('Início da avaliação é idempotente (chamar duas vezes não duplica snapshot) e gera 3 itens (uma por pergunta ativa)');

// ===================== SALVAMENTO PARCIAL + RETOMADA =====================
$itemA = null; $itemB = null; $itemOpcional = null;
foreach ($respostasSnapshot as $item) {
    if ($item['pergunta_id'] == $perguntaA) { $itemA = $item; }
    if ($item['pergunta_id'] == $perguntaB) { $itemB = $item; }
    if ($item['pergunta_id'] == $perguntaOpcional) { $itemOpcional = $item; }
}
if (!$itemA || !$itemB || !$itemOpcional) {
    failFast('Snapshot não trouxe os 3 itens esperados');
}

$avaliacoes->saveAnswers($avaliacao1Id, $empresaA['cliente_id'], [
    $itemA['id'] => ['resposta' => 5, 'observacao' => 'Parcial'],
]);
$avaliacaoAposParcial = $avaliacoes->find($avaliacao1Id);
if ($avaliacaoAposParcial['status'] !== 'em_andamento') {
    failFast('Salvamento parcial não deveria mudar o status para além de em_andamento');
}
$respostasAposParcial = $avaliacoes->listRespostas($avaliacao1Id);
$itemASalvo = array_values(array_filter($respostasAposParcial, static fn($i) => $i['id'] == $itemA['id']))[0];
if ((int)$itemASalvo['resposta'] !== 5) {
    failFast('Resposta parcial da pergunta A não foi persistida');
}
ok('Salvamento parcial funciona sem exigir todas as perguntas; status permanece em_andamento');

// Simula "retomar depois": nota inválida (fora de 1-5) é ignorada sem quebrar o restante.
$avaliacoes->saveAnswers($avaliacao1Id, $empresaA['cliente_id'], [
    $itemA['id'] => ['resposta' => 99, 'observacao' => 'Tentativa inválida'],
]);
$itemAAposInvalida = $avaliacoes->listRespostas($avaliacao1Id);
$itemAAposInvalida = array_values(array_filter($itemAAposInvalida, static fn($i) => $i['id'] == $itemA['id']))[0];
if ((int)$itemAAposInvalida['resposta'] !== 5) {
    failFast('Nota fora de 1-5 (99) deveria ser rejeitada, mantendo o valor anterior válido (5)');
}
ok('Nota fora da faixa 1-5 é rejeitada no backend (não confia em validação client-side)');

// ===================== FINALIZAÇÃO: obrigatória sem resposta bloqueia =====================
$tentativaFinalizarSemObrigatoria = $avaliacoes->finalizar($avaliacao1Id, $empresaA['cliente_id']);
if ($tentativaFinalizarSemObrigatoria['ok']) {
    failFast('Finalização deveria falhar: pergunta B (obrigatória) ainda sem resposta');
}
ok('Finalização bloqueada quando pergunta obrigatória está sem resposta');

// Responde a obrigatória e finaliza para valer.
$avaliacoes->saveAnswers($avaliacao1Id, $empresaA['cliente_id'], [
    $itemB['id'] => ['resposta' => 3, 'observacao' => null],
]);
$resultFinal = $avaliacoes->finalizar($avaliacao1Id, $empresaA['cliente_id']);
if (!$resultFinal['ok']) {
    failFast('Finalização deveria ter sucesso agora. Erros: ' . implode(' | ', $resultFinal['errors']));
}
$avaliacaoFinal = $avaliacoes->find($avaliacao1Id);
if ($avaliacaoFinal['status'] !== 'finalizada' || empty($avaliacaoFinal['finalizado_em'])) {
    failFast('Avaliação deveria estar finalizada com finalizado_em preenchido');
}

// Cálculo esperado: pergunta A nota 5 peso 2, pergunta B nota 3 peso 1 (pergunta opcional sem resposta, ignorada) -> (5*2+3*1)/3 = 4.3333 -> 4.33
$resultadoEsperado = round((5 * 2 + 3 * 1) / 3, 2);
if (abs((float)$avaliacaoFinal['resultado'] - $resultadoEsperado) > 0.001) {
    failFast("Cálculo ponderado incorreto: esperado {$resultadoEsperado}, obtido " . $avaliacaoFinal['resultado']);
}
if (abs($resultadoEsperado - 4.33) > 0.001) {
    failFast('Sanity check do próprio teste falhou (4.33 esperado)');
}
ok("Cálculo da média ponderada correto: (5×2 + 3×1) / 3 = {$avaliacaoFinal['resultado']} (esperado 4.33)");

$classificacao = PessoasAvaliacaoScale::classification((float)$avaliacaoFinal['resultado']);
if ($classificacao !== 'Acima do esperado') {
    failFast('Classificação de 4.33 deveria ser "Acima do esperado", obtido: ' . $classificacao);
}
ok('Classificação textual centralizada correta para 4.33 ("Acima do esperado")');

// ===================== RESULTADO POR GRUPO =====================
$porGrupo = $avaliacoes->resultadoPorGrupo($avaliacao1Id);
$porGrupoMap = [];
foreach ($porGrupo as $g) { $porGrupoMap[$g['grupo']] = $g['resultado']; }
if (abs(($porGrupoMap['Comunicação'] ?? -1) - 5.00) > 0.001) {
    failFast('Resultado do grupo Comunicação deveria ser 5.00 (só a pergunta A, nota 5)');
}
if (abs(($porGrupoMap['Produtividade'] ?? -1) - 3.00) > 0.001) {
    failFast('Resultado do grupo Produtividade deveria ser 3.00 (só a pergunta B respondida, nota 3 - opcional sem resposta não entra na média)');
}
ok('Resultado por grupo calculado corretamente (Comunicação=5.00, Produtividade=3.00)');

// ===================== BLOQUEIO DE EDIÇÃO APÓS FINALIZAÇÃO =====================
$tentativaSalvarFinalizada = $avaliacoes->saveAnswers($avaliacao1Id, $empresaA['cliente_id'], [$itemA['id'] => ['resposta' => 1]]);
if ($tentativaSalvarFinalizada) {
    failFast('Não deveria ser possível salvar respostas em avaliação já finalizada');
}
$tentativaFinalizarDeNovo = $avaliacoes->finalizar($avaliacao1Id, $empresaA['cliente_id']);
if ($tentativaFinalizarDeNovo['ok']) {
    failFast('Não deveria ser possível finalizar uma avaliação já finalizada de novo');
}
$respostaAindaCinco = $avaliacoes->listRespostas($avaliacao1Id);
$itemAFinal = array_values(array_filter($respostaAindaCinco, static fn($i) => $i['id'] == $itemA['id']))[0];
if ((int)$itemAFinal['resposta'] !== 5) {
    failFast('Resposta não deveria ter sido alterada pela tentativa de edição pós-finalização');
}
ok('Edição de respostas bloqueada após finalização (backend, não só UI)');

// ===================== IMUTABILIDADE: editar pergunta original não afeta histórico =====================
$modelos->updateQuestion($perguntaA, ['pergunta' => 'TEXTO COMPLETAMENTE DIFERENTE - EDITADO DEPOIS', 'peso' => 99.99, 'ordem' => 0, 'obrigatoria' => true]);
$respostaHistoricaPosEdicao = $avaliacoes->listRespostas($avaliacao1Id);
$itemAHistorico = array_values(array_filter($respostaHistoricaPosEdicao, static fn($i) => $i['id'] == $itemA['id']))[0];
if (strpos((string)$itemAHistorico['pergunta_snapshot'], 'Comunica informações') === false) {
    failFast('Snapshot histórico deveria manter o texto ORIGINAL da pergunta, não o editado depois');
}
if (abs((float)$itemAHistorico['peso_snapshot'] - 2.0) > 0.001) {
    failFast('Snapshot histórico deveria manter o PESO original (2.0), não o editado depois (99.99)');
}
$resultadoAindaCorreto = $avaliacoes->find($avaliacao1Id)['resultado'];
if (abs((float)$resultadoAindaCorreto - $resultadoEsperado) > 0.001) {
    failFast('Editar a pergunta original alterou o resultado histórico já calculado - não deveria');
}
ok('Imutabilidade confirmada: editar a pergunta original depois NÃO altera pergunta/peso/resultado da avaliação já finalizada');

// ===================== SEGUNDO PARTICIPANTE: fluxo ainda em_andamento continua acessível =====================
$avaliacao2Id = null;
foreach ($ciclos->listParticipants($cicloId) as $p) {
    if ((int)$p['colaborador_id'] === $col2) {
        $avaliacao2Id = (int)$p['avaliacao_id'];
    }
}
$avaliacoes->iniciar($avaliacao2Id, $empresaA['cliente_id'], $usuarioAvaliadorId, $modelos);
$snapshot2 = $avaliacoes->listRespostas($avaliacao2Id);
if (count($snapshot2) !== 3) {
    failFast('Segunda avaliação deveria ter seu próprio snapshot completo (3 itens)');
}
// Snapshot da avaliação 2 foi gerado DEPOIS da edição da pergunta A - reflete o texto/peso já editados (comportamento esperado: snapshot é tirado no momento do início, não retroativo).
$itemA2 = array_values(array_filter($snapshot2, static fn($i) => $i['pergunta_id'] == $perguntaA))[0];
if (strpos((string)$itemA2['pergunta_snapshot'], 'EDITADO DEPOIS') === false) {
    failFast('Snapshot da avaliação 2 (criado após a edição) deveria refletir o texto já atualizado da pergunta');
}
ok('Cada avaliação tira seu próprio snapshot no momento em que é iniciada (avaliação 2 reflete a pergunta já editada, sem afetar a avaliação 1)');

// ===================== ENCERRAMENTO DO CICLO =====================
if (!$ciclos->encerrar($cicloId, $empresaA['cliente_id'])) {
    failFast('Falha ao encerrar o ciclo aberto');
}
$cicloEncerrado = $ciclos->find($cicloId);
if ($cicloEncerrado['status'] !== 'encerrado') {
    failFast('Ciclo deveria estar encerrado');
}
ok('Ciclo encerrado com sucesso');

// ===================== RBAC: rota do módulo bloqueada para perfis não autorizados =====================
$readerUser = ['id' => 900, 'tipo_acesso' => 'reader', 'allowed_client_ids' => [$empresaA['cliente_id']]];
$clienteUser = ['id' => 901, 'tipo_acesso' => 'cliente', 'allowed_client_ids' => [$empresaA['cliente_id']]];
$consultorUser = ['id' => 902, 'tipo_acesso' => 'consultor', 'allowed_client_ids' => [$empresaA['cliente_id']]];
foreach (['reader' => $readerUser, 'cliente' => $clienteUser, 'consultor' => $consultorUser] as $label => $u) {
    if (AccessControl::canAccessRoute('pessoas/index', 'GET', $u)) {
        failFast("RBAC: perfil '$label' não deveria ter acesso a pessoas/index");
    }
}
$adminUser = ['id' => 501, 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $empresaA['cliente_id'], 'allowed_client_ids' => [$empresaA['cliente_id']]];
$institutoUser = ['id' => 1, 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
if (!AccessControl::canAccessRoute('pessoas/index', 'GET', $adminUser)) {
    failFast('RBAC: Cliente Admin deveria ter acesso a pessoas/index');
}
if (!AccessControl::canAccessRoute('pessoas/index', 'GET', $institutoUser)) {
    failFast('RBAC: Instituto deveria ter acesso a pessoas/index');
}
ok('RBAC: reader/cliente/consultor bloqueados de pessoas/*; Instituto e Cliente Admin liberados (nenhum papel novo criado)');

echo "pessoas_avaliacao_desempenho_integration_test passed.\n";
