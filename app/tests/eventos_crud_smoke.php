<?php
require_once __DIR__ . '/../autoload.php';

use App\Models\ClienteModel;
use App\Models\TarefaModel;
use App\Models\ReuniaoModel;
use App\Models\CoachingModel;
use App\Models\ProcessoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$clientes = new ClienteModel();
$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente CRUD Eventos ' . uniqid(),
    'CNPJ' => '00.000.000/0001-00',
    'contato' => 'Teste',
]);
if ($clienteId <= 0) {
    failFast('Nao criou cliente base para CRUD de eventos');
}
ok('Criou cliente base para CRUD de eventos');

$tarefaModel = new TarefaModel();
$tarefaId = $tarefaModel->create([
    'cliente_id' => $clienteId,
    'titulo' => 'Tarefa Smoke',
    'descricao' => 'Descricao',
    'data_inicio' => '2026-05-07 10:00:00',
    'prioridade' => 'media',
    'status' => 'Planejado',
]);
if ($tarefaId <= 0) {
    failFast('Nao criou tarefa');
}
ok('Criou tarefa');
if (!$tarefaModel->update($tarefaId, ['titulo' => 'Tarefa Smoke Edit', 'cliente_id' => $clienteId, 'data_inicio' => '2026-05-07 10:00:00', 'prioridade' => 'alta', 'status' => 'Andamento'])) {
    failFast('Nao atualizou tarefa');
}
ok('Atualizou tarefa');
if (!$tarefaModel->finalize($tarefaId, 1)) {
    failFast('Nao finalizou tarefa');
}
ok('Finalizou tarefa');
if (!$tarefaModel->delete($tarefaId)) {
    failFast('Nao excluiu tarefa');
}
ok('Excluiu tarefa');

$reuniaoModel = new ReuniaoModel();
$reuniaoId = $reuniaoModel->create([
    'cliente_id' => $clienteId,
    'titulo' => 'Reuniao Smoke',
    'local' => 'Sala',
    'pauta' => 'Pauta',
    'data_inicio' => '2026-05-07 11:00:00',
    'status' => 'Planejado',
]);
if ($reuniaoId <= 0) {
    failFast('Nao criou reuniao');
}
ok('Criou reuniao');
if (!$reuniaoModel->update($reuniaoId, ['cliente_id' => $clienteId, 'titulo' => 'Reuniao Smoke Edit', 'data_inicio' => '2026-05-07 11:00:00', 'status' => 'Adiado'])) {
    failFast('Nao atualizou reuniao');
}
ok('Atualizou reuniao');
if (!$reuniaoModel->finalize($reuniaoId, 1)) {
    failFast('Nao finalizou reuniao');
}
ok('Finalizou reuniao');
if (!$reuniaoModel->delete($reuniaoId)) {
    failFast('Nao excluiu reuniao');
}
ok('Excluiu reuniao');

$coachingModel = new CoachingModel();
$coachingId = $coachingModel->create([
    'cliente_id' => $clienteId,
    'titulo' => 'Coaching Smoke',
    'coach' => 'Coach',
    'observacoes' => 'Obs',
    'data_inicio' => '2026-05-07 12:00:00',
    'status' => 'Planejado',
]);
if ($coachingId <= 0) {
    failFast('Nao criou coaching');
}
ok('Criou coaching');
if (!$coachingModel->update($coachingId, ['cliente_id' => $clienteId, 'titulo' => 'Coaching Smoke Edit', 'data_inicio' => '2026-05-07 12:00:00', 'status' => 'Andamento'])) {
    failFast('Nao atualizou coaching');
}
ok('Atualizou coaching');
if (!$coachingModel->finalize($coachingId, 1)) {
    failFast('Nao finalizou coaching');
}
ok('Finalizou coaching');
if (!$coachingModel->delete($coachingId)) {
    failFast('Nao excluiu coaching');
}
ok('Excluiu coaching');

$processoModel = new ProcessoModel();
$processoId = $processoModel->create([
    'cliente_id' => $clienteId,
    'nome' => 'Processo Smoke',
    'descricao' => 'Descricao',
    'responsavel' => 'Resp',
    'data_inicio' => '2026-05-07',
    'status' => 'Planejado',
]);
if ($processoId <= 0) {
    failFast('Nao criou processo');
}
ok('Criou processo');
if (!$processoModel->update($processoId, ['cliente_id' => $clienteId, 'nome' => 'Processo Smoke Edit', 'data_inicio' => '2026-05-07', 'status' => 'Pendente'])) {
    failFast('Nao atualizou processo');
}
ok('Atualizou processo');
if (!$processoModel->finalize($processoId, 1)) {
    failFast('Nao finalizou processo');
}
ok('Finalizou processo');
if (!$processoModel->delete($processoId)) {
    failFast('Nao excluiu processo');
}
ok('Excluiu processo');

echo "eventos_crud_smoke passed.\n";

