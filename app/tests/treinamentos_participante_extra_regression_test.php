<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\TreinamentosController;
use App\Core\Auth;
use App\Core\Security;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;

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
$suffix = 'extra_' . substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'colaborador_ids' => [], 'setor_ids' => [], 'funcao_ids' => [], 'departamento_ids' => [],
    'treinamento_id' => 0, 'agenda_id' => 0, 'cliente_ids' => [],
];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['agenda_id'])) {
            $pdo->prepare('DELETE FROM treinamento_participantes WHERE agenda_id = :id')->execute(['id' => $cleanup['agenda_id']]);
            $pdo->prepare('DELETE FROM treinamentos_agenda WHERE id = :id')->execute(['id' => $cleanup['agenda_id']]);
        }
        if (!empty($cleanup['treinamento_id'])) {
            $pdo->prepare('DELETE FROM treinamento_colaboradores WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamento_setores WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamento_funcoes WHERE treinamento_id = :id')->execute(['id' => $cleanup['treinamento_id']]);
            $pdo->prepare('DELETE FROM treinamentos WHERE id = :id')->execute(['id' => $cleanup['treinamento_id']]);
        }
        foreach ($cleanup['colaborador_ids'] as $id) { $pdo->prepare('DELETE FROM colaboradores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['funcao_ids'] as $id) { $pdo->prepare('DELETE FROM funcoes WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$funcoes = new FuncaoModel();
$model = new TreinamentoModel();
$agendaModel = new TreinamentoAgendaModel();

// Cliente A: empresa do treinamento. Cliente C: outro tenant (para teste de rejeição).
$clienteId = $clientes->create(['nome_empresa' => 'Cliente Extra ' . $suffix, 'CNPJ' => '44.333.2' . substr($suffix, 0, 2) . '/0001-44', 'contato' => 'Teste']);
$outroClienteId = $clientes->create(['nome_empresa' => 'Cliente Extra Outro ' . $suffix, 'CNPJ' => '44.333.3' . substr($suffix, 0, 2) . '/0001-44', 'contato' => 'Teste']);
if ($clienteId <= 0 || $outroClienteId <= 0) failFast('Falha ao criar clientes de teste');
$cleanup['cliente_ids'] = [$clienteId, $outroClienteId];

// Departamento/Setor/Função A: público-alvo do treinamento.
$depA = $departamentos->create(['nome' => 'Dep A Extra ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$setorA = $setores->create(['nome' => 'Setor A Extra ' . $suffix, 'departamento_id' => $depA]);
$funcaoA = $funcoes->create(['nome' => 'Funcao A Extra ' . $suffix, 'setor_id' => $setorA]);

// Departamento/Setor/Função B: OUTRO setor da mesma empresa (não faz parte do público-alvo).
$depB = $departamentos->create(['nome' => 'Dep B Extra ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$setorB = $setores->create(['nome' => 'Setor B Extra ' . $suffix, 'departamento_id' => $depB]);
$funcaoB = $funcoes->create(['nome' => 'Funcao B Extra ' . $suffix, 'setor_id' => $setorB]);

// Departamento/Setor/Função C: outra empresa (para teste de rejeição de tenant).
$depC = $departamentos->create(['nome' => 'Dep C Extra ' . $suffix, 'cliente_id' => $outroClienteId, 'cliente_ids' => [$outroClienteId]]);
$setorC = $setores->create(['nome' => 'Setor C Extra ' . $suffix, 'departamento_id' => $depC]);
$funcaoC = $funcoes->create(['nome' => 'Funcao C Extra ' . $suffix, 'setor_id' => $setorC]);

if (!$depA || !$setorA || !$funcaoA || !$depB || !$setorB || !$funcaoB || !$depC || !$setorC || !$funcaoC) {
    failFast('Falha ao criar estrutura de departamento/setor/função de teste');
}
$cleanup['departamento_ids'] = [$depA, $depB, $depC];
$cleanup['setor_ids'] = [$setorA, $setorB, $setorC];
$cleanup['funcao_ids'] = [$funcaoA, $funcaoB, $funcaoC];

$stmt = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n, :e, :f, :c)');
$stmt->execute(['n' => 'Colaborador A ' . $suffix, 'e' => 'colabA.' . $suffix . '@test.local', 'f' => $funcaoA, 'c' => $clienteId]);
$colaboradorA = (int)$pdo->lastInsertId();
$stmt->execute(['n' => 'Colaborador B ' . $suffix, 'e' => 'colabB.' . $suffix . '@test.local', 'f' => $funcaoB, 'c' => $clienteId]);
$colaboradorB = (int)$pdo->lastInsertId();
$stmt->execute(['n' => 'Colaborador C ' . $suffix, 'e' => 'colabC.' . $suffix . '@test.local', 'f' => $funcaoC, 'c' => $outroClienteId]);
$colaboradorC = (int)$pdo->lastInsertId();
$cleanup['colaborador_ids'] = [$colaboradorA, $colaboradorB, $colaboradorC];
ok('Criou colaborador A (público-alvo), B (outro setor, mesma empresa) e C (outra empresa)');

$treinamentoId = $model->create([
    'nome' => 'Treinamento Extra ' . $suffix,
    'objetivo' => 'Objetivo', 'publico' => 'Equipe', 'carga_horaria' => '4',
    'cliente_id' => $clienteId, 'departamento_id' => $depA, 'periodicidade' => 'anual', 'fornecedor' => 'Fornecedor',
    'setor_ids' => [$setorA], 'funcao_ids' => [$funcaoA],
]);
if ($treinamentoId <= 0) failFast('Falha ao criar treinamento de teste');
$cleanup['treinamento_id'] = $treinamentoId;
ok('Criou treinamento com público-alvo restrito ao Setor A / Função A');

// Pré-cadastro do público principal: só o colaborador A (do setor/função configurados).
$model->syncSelectedColaboradores($treinamentoId, [$colaboradorA]);
$linked = $model->linkedColaboradores($treinamentoId);
if (count($linked) !== 1 || (int)$linked[0]['colaborador_id'] !== $colaboradorA) {
    failFast('Pré-cadastro do público principal deveria conter apenas o colaborador A');
}
if (($linked[0]['origem'] ?? '') !== 'publico_alvo') {
    failFast('Colaborador do público principal deveria ter origem "publico_alvo"');
}
ok('Pré-cadastro do público principal persiste com origem "publico_alvo"');

// 1) Busca de participante extra NÃO deve ser travada pelo setor/função do treinamento.
$searchResults = $model->searchColaboradoresParaExtra($clienteId, 'Colaborador B', $treinamentoId);
$foundIds = array_map(static fn(array $r): int => (int)$r['id'], $searchResults);
if (!in_array($colaboradorB, $foundIds, true)) {
    failFast('Busca de participante extra deveria encontrar o colaborador B, mesmo sendo de outro setor');
}
ok('Busca de participante extra encontra colaborador de outro setor/departamento da mesma empresa');

// 2) Adicionar colaborador B como extra.
$resultB = $model->addParticipanteExtra($treinamentoId, $colaboradorB);
if (!$resultB['ok']) {
    failFast('Falha ao adicionar colaborador B como participante extra: ' . ($resultB['error'] ?? ''));
}
$linkedAfterExtra = $model->linkedColaboradores($treinamentoId);
$extraRow = null;
foreach ($linkedAfterExtra as $row) {
    if ((int)$row['colaborador_id'] === $colaboradorB) { $extraRow = $row; break; }
}
if (!$extraRow || ($extraRow['origem'] ?? '') !== 'extra') {
    failFast('Colaborador B deveria estar vinculado com origem "extra"');
}
ok('Colaborador B adicionado como participante extra, com origem "extra" persistida');

// 3) Duplicidade: adicionar o mesmo colaborador de novo deve falhar com mensagem clara.
$dup = $model->addParticipanteExtra($treinamentoId, $colaboradorB);
if ($dup['ok']) {
    failFast('Adicionar o mesmo participante extra duas vezes deveria ser bloqueado (duplicidade)');
}
ok('Bloqueia duplicidade ao tentar adicionar o mesmo participante extra novamente');

// 4) Isolamento multiempresa: colaborador de outra empresa deve ser rejeitado.
$rejected = $model->addParticipanteExtra($treinamentoId, $colaboradorC);
if ($rejected['ok']) {
    failFast('Colaborador de outra empresa não deveria poder ser adicionado como participante extra');
}
ok('Rejeita colaborador de outra empresa/tenant como participante extra');

// 5) Salvar novamente o pré-cadastro do público principal (só colaborador A) NÃO deve remover o extra.
$model->syncSelectedColaboradores($treinamentoId, [$colaboradorA]);
$linkedAfterResync = $model->linkedColaboradores($treinamentoId);
$idsAfterResync = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $linkedAfterResync);
if (!in_array($colaboradorB, $idsAfterResync, true)) {
    failFast('Regressão: salvar o pré-cadastro do público principal removeu o participante extra');
}
if (count($linkedAfterResync) !== 2) {
    failFast('Deveria haver exatamente 2 vínculos após o resync: público principal (A) + extra (B)');
}
ok('Atualizar o pré-cadastro do público principal preserva o participante extra (não duplica, não remove)');

// 6) O extra aparece como pendente para presença/certificado ao agendar uma sessão (mesmo mecanismo do público principal).
$agendaId = $agendaModel->create(['treinamento_id' => $treinamentoId, 'data' => date('Y-m-d H:i:s'), 'unidade_id' => $clienteId]);
if ($agendaId <= 0) failFast('Falha ao criar sessão de agenda para o treinamento');
$cleanup['agenda_id'] = $agendaId;
$pending = $agendaModel->pendingParticipantsForTreinamento($treinamentoId, $agendaId);
$pendingIds = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $pending);
if (!in_array($colaboradorB, $pendingIds, true)) {
    failFast('Participante extra deveria aparecer como pendente para presença/certificado na sessão agendada');
}
ok('Participante extra aparece como pendente para presença/certificado, mesmo mecanismo do público principal');

// 7) Remover o participante extra funciona, e não afeta o público principal.
$removed = $model->removeParticipanteExtra($treinamentoId, $colaboradorB);
if (!$removed) failFast('Falha ao remover o participante extra');
$notRemovable = $model->removeParticipanteExtra($treinamentoId, $colaboradorA);
if ($notRemovable) {
    failFast('removeParticipanteExtra não deveria remover um vínculo de origem "publico_alvo"');
}
$linkedFinal = $model->linkedColaboradores($treinamentoId);
if (count($linkedFinal) !== 1 || (int)$linkedFinal[0]['colaborador_id'] !== $colaboradorA) {
    failFast('Após remover o extra, deveria restar apenas o público principal (colaborador A)');
}
ok('Remove participante extra corretamente, sem afetar o público principal (proteção por origem)');

// 8) Controller end-to-end: endpoint de busca e de adicionar participante extra.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'treinamentos/extra_colaboradores_ajax', 'id' => (string)$treinamentoId, 'q' => 'Colaborador B'];
ob_start();
(new TreinamentosController())->extraColaboradoresAjax();
$searchPayload = json_decode((string)ob_get_clean(), true);
$searchIds = array_map(static fn(array $r): int => (int)$r['id'], $searchPayload['items'] ?? []);
if (!($searchPayload['ok'] ?? false) || !in_array($colaboradorB, $searchIds, true)) {
    failFast('Endpoint extraColaboradoresAjax deveria retornar o colaborador B');
}
ok('Endpoint AJAX de busca de participante extra funciona end-to-end');

$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['csrf' => $csrf, 'treinamento_id' => (string)$treinamentoId, 'colaborador_id' => (string)$colaboradorB];
ob_start();
(new TreinamentosController())->addParticipanteExtra();
$addPayload = json_decode((string)ob_get_clean(), true);
if (!($addPayload['ok'] ?? false)) {
    failFast('Endpoint addParticipanteExtra deveria adicionar o colaborador B com sucesso');
}
ok('Endpoint addParticipanteExtra funciona end-to-end (com validação de CSRF e empresa)');

// 9) Segurança: addParticipantes (participantes de sessão de agenda) agora valida empresa do colaborador.
// Executado em subprocesso porque addParticipantes() sempre chama redirect() (header + exit) ao final,
// o que encerraria este script de teste antes da limpeza dos fixtures.
$probe = __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'treinamentos_add_participantes_probe.php';
$cmd = 'php ' . escapeshellarg($probe) . ' ' . escapeshellarg((string)$agendaId) . ' ' . escapeshellarg((string)$colaboradorA) . ' ' . escapeshellarg((string)$colaboradorC);
@exec($cmd . ' 2>&1');
$participants = $agendaModel->participants($agendaId);
$participantIds = array_map(static fn(array $r): int => (int)$r['colaborador_id'], $participants);
if (in_array($colaboradorC, $participantIds, true)) {
    failFast('addParticipantes não deveria aceitar colaborador de outra empresa (falha de segurança corrigida)');
}
if (!in_array($colaboradorA, $participantIds, true)) {
    failFast('addParticipantes deveria aceitar o colaborador da mesma empresa normalmente');
}
ok('addParticipantes valida empresa do colaborador: aceita da mesma empresa, rejeita de outro tenant');

echo "Treinamentos participante extra regression tests passed.\n";
ob_end_flush();
