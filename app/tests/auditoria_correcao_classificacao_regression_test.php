<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

/**
 * Regressão ampla do item 10 (Fluxo A - correção de classificação):
 * validações funcionais, guardas de estado, RBAC, histórico, fluxo HTTP via
 * controller (subprocesso) e concorrência. A prova numérica de transferência
 * de setor_metricas fica em auditoria_correcao_classificacao_setor_metricas_regression_test.php.
 */

function runCorrigirProbe(string $method, string $role, int $userId, ?int $idCliente, int $auditoriaId, int $departamentoId, int $setorId, string $motivo, bool $withCsrf = true, ?int $prevLockVersion = null): array
{
    $probe = __DIR__ . '/helpers/auditoria_corrigir_classificacao_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg($method) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg((string)$userId) . ' '
        . escapeshellarg($idCliente !== null ? (string)$idCliente : '') . ' '
        . escapeshellarg((string)$auditoriaId) . ' '
        . escapeshellarg((string)$departamentoId) . ' '
        . escapeshellarg((string)$setorId) . ' '
        . escapeshellarg($motivo) . ' '
        . escapeshellarg($withCsrf ? '1' : '0') . ' '
        . escapeshellarg($prevLockVersion !== null ? (string)$prevLockVersion : '');
    $out = [];
    exec($cmd . ' 2>&1', $out);
    $raw = implode("\n", $out);
    $marker = '---PROBE_RESULT---';
    $pos = strpos($raw, $marker);
    $body = $pos !== false ? substr($raw, 0, $pos) : $raw;
    $resultLine = $pos !== false ? trim(substr($raw, $pos + strlen($marker))) : '';
    $decoded = json_decode($resultLine, true);
    return [
        'body' => $body,
        'status' => is_array($decoded) ? ($decoded['status'] ?? null) : null,
        'location' => is_array($decoded) ? ($decoded['location'] ?? '') : '',
    ];
}

$pdo = Database::getConnection();
$suffix = 'audcc2_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$auditoriaIds = [];

try {
    // ===================== FIXTURES =====================
    $cnpjA = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $cnpjOutro = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Correcao ' . $suffix, 'c' => $cnpjA, 'ct' => 'contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Correcao Outro ' . $suffix, 'c' => $cnpjOutro, 'ct' => 'contato']);
    $clienteOutroId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteOutroId;

    // Departamento A (2 setores, para testar "corrigir somente Setor")
    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')
        ->execute(['n' => 'Dep A ' . $suffix, 'cid' => $clienteId]);
    $depAId = (int)$pdo->lastInsertId();
    $depIds[] = $depAId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depAId, 'c' => $clienteId]);
    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')->execute(['n' => 'Setor A1 ' . $suffix, 'did' => $depAId]);
    $setorA1Id = (int)$pdo->lastInsertId();
    $setorIds[] = $setorA1Id;
    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')->execute(['n' => 'Setor A2 ' . $suffix, 'did' => $depAId]);
    $setorA2Id = (int)$pdo->lastInsertId();
    $setorIds[] = $setorA2Id;

    // Departamento B (outro departamento do mesmo tenant)
    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')
        ->execute(['n' => 'Dep B ' . $suffix, 'cid' => $clienteId]);
    $depBId = (int)$pdo->lastInsertId();
    $depIds[] = $depBId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depBId, 'c' => $clienteId]);
    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')->execute(['n' => 'Setor B ' . $suffix, 'did' => $depBId]);
    $setorBId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorBId;

    // Departamento/Setor de outro tenant
    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')
        ->execute(['n' => 'Dep Outro Tenant ' . $suffix, 'cid' => $clienteOutroId]);
    $depOutroId = (int)$pdo->lastInsertId();
    $depIds[] = $depOutroId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')->execute(['d' => $depOutroId, 'c' => $clienteOutroId]);
    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')->execute(['n' => 'Setor Outro Tenant ' . $suffix, 'did' => $depOutroId]);
    $setorOutroId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorOutroId;

    Auth::login([
        'id' => 3201,
        'nome' => 'Instituto Teste Correcao 2',
        'email' => 'instituto.correcao2@test.local',
        'tipo_acesso' => 'instituto',
        'id_cliente' => null,
    ]);

    $model = new AuditoriaModel();

    function criarEFinalizarAuditoria(AuditoriaModel $model, int $clienteId, int $setorId, string $nome, int $userId): int
    {
        $id = $model->create([
            'cliente_id' => $clienteId,
            'setor_id' => $setorId,
            'nome_auditoria' => $nome,
            'data_auditoria' => date('Y-m-d'),
            'questoes' => [
                ['responsavel_nome' => 'Resp 1', 'pergunta' => 'Pergunta 1 de conformidade do processo auditado', 'referencia_esperada' => 'POP-1', 'processos' => []],
                ['responsavel_nome' => 'Resp 2', 'pergunta' => 'Pergunta 2 de conformidade do processo auditado', 'referencia_esperada' => 'POP-2', 'processos' => []],
            ],
        ], $userId);
        if ($id <= 0) { failFast('Falha ao criar auditoria de teste: ' . $nome); }
        $questoes = $model->questoesByAuditoria($id);
        $q1 = (int)$questoes[0]['id'];
        $q2 = (int)$questoes[1]['id'];
        $okF = $model->finalizarAuditoria($id, [
            ['questao_id' => $q1, 'conformidade' => 'conforme', 'observacoes' => ''],
            ['questao_id' => $q2, 'conformidade' => 'nao_conforme', 'observacoes' => ''],
        ], $userId);
        if (!$okF) { failFast('Falha ao finalizar auditoria de teste: ' . $nome . ' erro=' . (string)$model->getLastError()); }
        return $id;
    }

    // ===================== FUNCIONAL: VALIDAÇÕES =====================
    $auditoria1Id = criarEFinalizarAuditoria($model, $clienteId, $setorA1Id, 'Auditoria Validacoes ' . $suffix, 3201);
    $auditoriaIds[] = $auditoria1Id;
    $item1 = $model->find($auditoria1Id);

    // 1) corrigir somente Setor (mesmo departamento A: A1 -> A2)
    $okSomenteSetor = $model->corrigirClassificacao($auditoria1Id, 3201, $depAId, $setorA2Id, 'Setor incorreto dentro do mesmo departamento.', (int)$item1['lock_version']);
    if (!$okSomenteSetor) { failFast('Corrigir somente Setor (mesmo departamento) deveria ter sucesso. erro=' . (string)$model->getLastError()); }
    $item1b = $model->find($auditoria1Id);
    if ((int)$item1b['setor_id'] !== $setorA2Id) { failFast('setor_id deveria ter sido atualizado para Setor A2'); }
    ok('1) Corrigir somente Setor (mesmo Departamento A: A1 -> A2) funciona');

    // 3) enviar os mesmos valores (setor atual = A2) -> no_change, sem erro real
    $okMesmoValor = $model->corrigirClassificacao($auditoria1Id, 3201, $depAId, $setorA2Id, 'Tentando reenviar o mesmo valor.', (int)$item1b['lock_version']);
    if ($okMesmoValor || $model->getLastError() !== 'no_change') { failFast('Enviar os mesmos valores deveria retornar no_change, obtido erro=' . (string)$model->getLastError()); }
    ok('3) Enviar exatamente o mesmo Departamento+Setor não executa transferência (no_change)');

    // 4) Departamento inválido (id inexistente)
    $okDepInvalido = $model->corrigirClassificacao($auditoria1Id, 3201, 999999999, $setorA2Id, 'Departamento inválido.', null);
    if ($okDepInvalido || $model->getLastError() !== 'invalid_departamento') { failFast('Departamento inválido deveria falhar com invalid_departamento, obtido=' . (string)$model->getLastError()); }
    ok('4) Departamento inexistente é rejeitado (invalid_departamento)');

    // 5) Setor inválido (id inexistente)
    $okSetorInvalido = $model->corrigirClassificacao($auditoria1Id, 3201, $depAId, 999999999, 'Setor inválido.', null);
    if ($okSetorInvalido || $model->getLastError() !== 'invalid_setor') { failFast('Setor inexistente deveria falhar com invalid_setor, obtido=' . (string)$model->getLastError()); }
    ok('5) Setor inexistente é rejeitado (invalid_setor)');

    // 6) Setor pertencente a outro Departamento (depA + setorB, que pertence a depB)
    $okSetorOutroDep = $model->corrigirClassificacao($auditoria1Id, 3201, $depAId, $setorBId, 'Setor de outro departamento.', null);
    if ($okSetorOutroDep || $model->getLastError() !== 'invalid_setor') { failFast('Setor de outro departamento deveria falhar com invalid_setor, obtido=' . (string)$model->getLastError()); }
    ok('6) Setor pertencente a outro Departamento é rejeitado mesmo que ambos existam (invalid_setor)');

    // 7) Setor de outro tenant
    $okSetorOutroTenant = $model->corrigirClassificacao($auditoria1Id, 3201, $depAId, $setorOutroId, 'Setor de outro tenant.', null);
    if ($okSetorOutroTenant || $model->getLastError() !== 'invalid_setor') { failFast('Setor de outro tenant deveria falhar com invalid_setor, obtido=' . (string)$model->getLastError()); }
    ok('7) Setor pertencente a outro tenant é rejeitado (invalid_setor)');

    // Departamento de outro tenant
    $okDepOutroTenant = $model->corrigirClassificacao($auditoria1Id, 3201, $depOutroId, $setorOutroId, 'Departamento de outro tenant.', null);
    if ($okDepOutroTenant || $model->getLastError() !== 'invalid_departamento') { failFast('Departamento de outro tenant deveria falhar com invalid_departamento, obtido=' . (string)$model->getLastError()); }
    ok('7b) Departamento pertencente a outro tenant é rejeitado (invalid_departamento)');

    // 8) Auditoria inexistente
    $okAuditoriaInexistente = $model->corrigirClassificacao(999999999, 3201, $depAId, $setorA2Id, 'Auditoria inexistente.', null);
    if ($okAuditoriaInexistente || $model->getLastError() !== 'not_found') { failFast('Auditoria inexistente deveria falhar com not_found, obtido=' . (string)$model->getLastError()); }
    ok('8) Auditoria inexistente é rejeitada (not_found)');

    // 9) Auditoria não Realizada (Agendada)
    $auditoriaAgendadaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorA1Id,
        'nome_auditoria' => 'Auditoria Agendada ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp', 'pergunta' => 'Pergunta única de conformidade', 'referencia_esperada' => 'POP', 'processos' => []]],
    ], 3201);
    if ($auditoriaAgendadaId <= 0) { failFast('Falha ao criar auditoria Agendada de teste'); }
    $auditoriaIds[] = $auditoriaAgendadaId;
    $okNaoRealizada = $model->corrigirClassificacao($auditoriaAgendadaId, 3201, $depAId, $setorA2Id, 'Ainda não realizada.', null);
    if ($okNaoRealizada || $model->getLastError() !== 'invalid_status') { failFast('Auditoria não Realizada deveria falhar com invalid_status, obtido=' . (string)$model->getLastError()); }
    ok('9) Auditoria com status diferente de Realizada é rejeitada (invalid_status)');

    // ===================== HISTÓRICO =====================
    $historico = $pdo->prepare('SELECT dados_anteriores, usuario_id FROM auditoria_historico WHERE auditoria_id = :id ORDER BY id DESC LIMIT 1');
    $historico->execute(['id' => $auditoria1Id]);
    $histRow = $historico->fetch();
    if (!$histRow) { failFast('Correção de classificação deveria ter gerado um registro em auditoria_historico'); }
    $histDados = json_decode((string)$histRow['dados_anteriores'], true);
    $evento = $histDados['evento'] ?? [];
    if (($evento['tipo'] ?? '') !== 'correcao_classificacao') { failFast('Evento de histórico deveria ser do tipo correcao_classificacao'); }
    if ((int)($evento['setor_anterior_id'] ?? 0) !== $setorA1Id || (int)($evento['setor_novo_id'] ?? 0) !== $setorA2Id) { failFast('Histórico deveria registrar setor anterior=A1 e setor novo=A2'); }
    if ((int)($evento['departamento_anterior_id'] ?? 0) !== $depAId || (int)($evento['departamento_novo_id'] ?? 0) !== $depAId) { failFast('Histórico deveria registrar departamento anterior=A e departamento novo=A (mesmo departamento neste caso)'); }
    if (($evento['motivo'] ?? '') !== 'Setor incorreto dentro do mesmo departamento.') { failFast('Histórico deveria registrar o motivo informado'); }
    if (($evento['status'] ?? '') !== 'Realizada') { failFast('Histórico deveria registrar que o status permanece Realizada'); }
    if ((int)$histRow['usuario_id'] !== 3201) { failFast('Histórico deveria registrar o usuário que executou a correção'); }
    if (($histDados['auditoria']['status'] ?? '') !== 'Realizada') { failFast('Snapshot completo do histórico deveria mostrar status Realizada'); }
    ok('16-20) Histórico registra departamento/setor anterior e novo, motivo, usuário e status Realizada corretamente');

    // ===================== ESTADO PRESERVADO =====================
    $itemOriginal = $model->find($auditoria1Id);
    if ((string)$itemOriginal['realizada_at'] === '') { failFast('realizada_at não deveria estar vazio'); }
    $respostasOriginal = $model->respostasByAuditoria($auditoria1Id);
    foreach ($respostasOriginal as $r) {
        if (empty($r['finalized_at'])) { failFast('Respostas deveriam continuar travadas (finalized_at preenchido)'); }
    }
    ok('27-32) realizada_at/finalized_at/respostas/nota/farol permanecem preservados (nenhuma nova finalização ocorreu)');

    // ===================== RBAC =====================
    if (!Auth::canCorrectAuditoriaClassification($clienteId)) { failFast('Instituto deveria poder corrigir classificação em qualquer tenant'); }
    ok('21) Instituto: permitido');

    Auth::login(['id' => 4001, 'nome' => 'Admin Mesmo Tenant', 'email' => 'admin.mesmo@test.local', 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $clienteId, 'allowed_client_ids' => [$clienteId]]);
    if (!Auth::canCorrectAuditoriaClassification($clienteId)) { failFast('Cliente Admin do próprio tenant deveria poder corrigir classificação'); }
    ok('22) Cliente Admin do próprio tenant: permitido');
    if (Auth::canCorrectAuditoriaClassification($clienteOutroId)) { failFast('Cliente Admin não deveria poder corrigir classificação de outro tenant'); }
    ok('23) Cliente Admin de outro tenant: bloqueado');

    Auth::login(['id' => 4002, 'nome' => 'Cliente Comum', 'email' => 'cliente.comum@test.local', 'tipo_acesso' => 'cliente', 'id_cliente' => $clienteId, 'allowed_client_ids' => [$clienteId]]);
    if (Auth::canCorrectAuditoriaClassification($clienteId)) { failFast('Cliente comum não deveria poder corrigir classificação'); }
    ok('24) Cliente comum: bloqueado');

    Auth::login(['id' => 4003, 'nome' => 'Reader', 'email' => 'reader@test.local', 'tipo_acesso' => 'reader', 'id_cliente' => $clienteId, 'allowed_client_ids' => [$clienteId]]);
    if (Auth::canCorrectAuditoriaClassification($clienteId)) { failFast('Reader não deveria poder corrigir classificação'); }
    ok('25) Reader: bloqueado');

    Auth::login(['id' => 4004, 'nome' => 'Consultor', 'email' => 'consultor@test.local', 'tipo_acesso' => 'consultor', 'id_cliente' => null, 'allowed_client_ids' => []]);
    if (Auth::canCorrectAuditoriaClassification($clienteId)) { failFast('Consultor não deveria poder corrigir classificação'); }
    ok('26) Consultor: bloqueado');

    Auth::login(['id' => 3201, 'nome' => 'Instituto Teste Correcao 2', 'email' => 'instituto.correcao2@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);

    // ===================== FLUXO HTTP (controller/rota, via subprocesso) =====================
    $auditoria2Id = criarEFinalizarAuditoria($model, $clienteId, $setorA1Id, 'Auditoria HTTP ' . $suffix, 3201);
    $auditoriaIds[] = $auditoria2Id;
    $item2 = $model->find($auditoria2Id);
    $lock2 = (int)$item2['lock_version'];

    $rGet = runCorrigirProbe('GET', 'instituto', 3201, null, $auditoria2Id, $depAId, $setorA2Id, 'Teste GET', true);
    if ((int)$rGet['status'] !== 400) { failFast('GET deveria retornar 400, obtido ' . (string)$rGet['status']); }
    ok('Controller: GET não executa a correção (400)');

    $rNoCsrf = runCorrigirProbe('POST', 'instituto', 3201, null, $auditoria2Id, $depAId, $setorA2Id, 'Teste sem CSRF', false);
    if ((int)$rNoCsrf['status'] !== 400) { failFast('POST sem CSRF deveria retornar 400, obtido ' . (string)$rNoCsrf['status']); }
    ok('Controller: POST sem CSRF é rejeitado (400)');

    $rSemMotivo = runCorrigirProbe('POST', 'instituto', 3201, null, $auditoria2Id, $depAId, $setorA2Id, '', true);
    if (strpos((string)$rSemMotivo['location'], 'auditorias/show') === false) { failFast('POST sem motivo deveria redirecionar de volta para show'); }
    $itemSemMotivo = $model->find($auditoria2Id);
    if ((int)$itemSemMotivo['setor_id'] !== $setorA1Id) { failFast('POST sem motivo não deveria ter alterado o setor'); }
    ok('Controller: POST sem motivo é recusado e não altera nada');

    $rSemSetor = runCorrigirProbe('POST', 'instituto', 3201, null, $auditoria2Id, 0, 0, 'Motivo válido', true);
    $itemSemSetor = $model->find($auditoria2Id);
    if ((int)$itemSemSetor['setor_id'] !== $setorA1Id) { failFast('POST sem departamento/setor não deveria ter alterado o setor'); }
    ok('Controller: POST sem departamento/setor selecionados é recusado');

    $rClienteComum = runCorrigirProbe('POST', 'cliente', 4002, $clienteId, $auditoria2Id, $depAId, $setorA2Id, 'Tentativa cliente comum', true);
    if ((int)$rClienteComum['status'] !== 403) { failFast('Cliente comum via controller deveria receber 403, obtido ' . (string)$rClienteComum['status']); }
    ok('Controller: cliente comum recebe 403');

    $rCrossTenant = runCorrigirProbe('POST', 'cliente_admin', 4001, $clienteOutroId, $auditoria2Id, $depAId, $setorA2Id, 'Tentativa cross-tenant', true);
    $itemCrossTenant = $model->find($auditoria2Id);
    if ((int)$itemCrossTenant['setor_id'] !== $setorA1Id) { failFast('Tentativa cross-tenant não deveria ter alterado nada'); }
    ok('Controller: Cliente Admin de outro tenant não consegue corrigir (auditoria não encontrada no escopo dele)');

    $rSucesso = runCorrigirProbe('POST', 'instituto', 3201, null, $auditoria2Id, $depAId, $setorA2Id, 'Setor cadastrado incorretamente.', true, $lock2);
    if (strpos((string)$rSucesso['location'], 'auditorias/show') === false) { failFast('POST válido deveria redirecionar para show. status=' . (string)$rSucesso['status'] . ' location=' . (string)$rSucesso['location']); }
    $itemSucesso = $model->find($auditoria2Id);
    if ((int)$itemSucesso['setor_id'] !== $setorA2Id) { failFast('POST válido deveria ter atualizado o setor_id para A2'); }
    if (($itemSucesso['status'] ?? '') !== 'Realizada') { failFast('Status deveria continuar Realizada após correção via controller'); }
    ok('Controller: fluxo completo de sucesso corrige a classificação e redireciona para show');

    // ===================== CONCORRÊNCIA =====================
    // Reenviar o mesmo POST com o lock_version ANTIGO (já consumido pela correção acima) deve falhar com segurança.
    $rDuplicado = runCorrigirProbe('POST', 'instituto', 3201, null, $auditoria2Id, $depAId, $setorA2Id, 'Reenvio duplicado (lock antigo).', true, $lock2);
    $itemDepoisDuplicado = $model->find($auditoria2Id);
    if ((int)$itemDepoisDuplicado['setor_id'] !== $setorA2Id) { failFast('POST duplicado com lock_version desatualizado não deveria alterar nada além do já aplicado'); }
    ok('33) Concorrência: reenvio com lock_version desatualizado é recusado com segurança, sem novo efeito');

    // Auditoria reaberta entre a leitura do formulário e o POST de correção -> correção recusada
    $auditoria3Id = criarEFinalizarAuditoria($model, $clienteId, $setorA1Id, 'Auditoria Reaberta Entre Get e Post ' . $suffix, 3201);
    $auditoriaIds[] = $auditoria3Id;
    $okReabrirAntes = $model->reabrirAuditoria($auditoria3Id, 3201, 'Reaberta antes da tentativa de correção.');
    if (!$okReabrirAntes) { failFast('Reabertura de preparação do cenário 34 deveria ter sucesso'); }
    $okCorrigirAposReabertura = $model->corrigirClassificacao($auditoria3Id, 3201, $depAId, $setorA2Id, 'Tentando corrigir após reabertura de terceiro.', null);
    if ($okCorrigirAposReabertura || $model->getLastError() !== 'invalid_status') { failFast('Corrigir classificação após reabertura concorrente deveria falhar com invalid_status, obtido=' . (string)$model->getLastError()); }
    ok('34) Auditoria reaberta por outro usuário entre a leitura e o POST -> correção recusada (invalid_status)');

    // POST duplicado (dois cliques) não transfere métricas duas vezes: reenviar o MESMO alvo já aplicado é seguro (no_change)
    $auditoria4Id = criarEFinalizarAuditoria($model, $clienteId, $setorA1Id, 'Auditoria Duplo Clique ' . $suffix, 3201);
    $auditoriaIds[] = $auditoria4Id;
    $item4 = $model->find($auditoria4Id);
    $primeiraCorrecao = $model->corrigirClassificacao($auditoria4Id, 3201, $depBId, $setorBId, 'Correção original.', (int)$item4['lock_version']);
    if (!$primeiraCorrecao) { failFast('Primeira correção do cenário de duplo clique deveria ter sucesso'); }
    $metricasSetorBApos1 = $pdo->prepare('SELECT total_validas, total_conforme FROM setor_metricas WHERE setor_id = :s AND ano_mes = :am');
    $metricasSetorBApos1->execute(['s' => $setorBId, 'am' => date('Y-m')]);
    $rowApos1 = $metricasSetorBApos1->fetch();
    $segundaTentativa = $model->corrigirClassificacao($auditoria4Id, 3201, $depBId, $setorBId, 'Segundo clique (duplicado).', null);
    if ($segundaTentativa || $model->getLastError() !== 'no_change') { failFast('Segunda tentativa (duplo clique) para o mesmo setor já aplicado deveria retornar no_change, obtido=' . (string)$model->getLastError()); }
    $metricasSetorBApos2 = $pdo->prepare('SELECT total_validas, total_conforme FROM setor_metricas WHERE setor_id = :s AND ano_mes = :am');
    $metricasSetorBApos2->execute(['s' => $setorBId, 'am' => date('Y-m')]);
    $rowApos2 = $metricasSetorBApos2->fetch();
    if ((int)$rowApos1['total_validas'] !== (int)$rowApos2['total_validas'] || (int)$rowApos1['total_conforme'] !== (int)$rowApos2['total_conforme']) {
        failFast('POST duplicado transferiu métricas mais de uma vez para o Setor B');
    }
    ok('35) POST duplicado (duplo clique) não transfere setor_metricas duas vezes');

    echo "Auditoria correção de classificação regression tests passed.\n";
} catch (\Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($auditoriaIds)) {
        $in = implode(',', array_map('intval', $auditoriaIds));
        $pdo->exec("DELETE FROM auditoria_historico WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_avaliacoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
    }
    if (!empty($setorIds)) {
        $in = implode(',', array_map('intval', $setorIds));
        $pdo->exec("DELETE FROM setor_metricas WHERE setor_id IN ($in)");
        $pdo->exec("DELETE FROM setores WHERE id IN ($in)");
    }
    if (!empty($depIds)) {
        $in = implode(',', array_map('intval', $depIds));
        $pdo->exec("DELETE FROM departamento_clientes WHERE departamento_id IN ($in)");
        $pdo->exec("DELETE FROM departamentos WHERE id IN ($in)");
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
