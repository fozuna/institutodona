<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\CronogramaEventoModel;
use App\Models\CronogramaModel;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;
use App\Services\AgendaEventService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'agenda_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$taskIds = [];
$actionIds = [];
$auditoriaIds = [];
$cronogramaIds = [];
$cronogramaEventoRootIds = [];
$treinamentoIds = [];
$agendaTreinamentoIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Agenda ' . $suffix, 'c' => '00.000.000/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    Auth::login([
        'id' => 9002,
        'nome' => 'Teste Agenda',
        'email' => 'agenda.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $clienteId,
    ]);

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:c)')
        ->execute(['n' => 'Departamento Agenda ' . $suffix, 'c' => $clienteId]);
    $departamentoId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor Agenda ' . $suffix, 'd' => $departamentoId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao Agenda ' . $suffix, 's' => $setorId]);
    $funcaoId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n,:e,:f,:c)')
        ->execute([
            'n' => 'Colaborador Agenda ' . $suffix,
            'e' => 'colab.agenda.' . $suffix . '@test.local',
            'f' => $funcaoId,
            'c' => $clienteId,
        ]);
    $colaboradorId = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorId;

    $pdo->prepare('INSERT INTO pdca_tasks (id_cliente, titulo, descricao, meta_valor, meta_unidade, prazo, responsavel, fase, status, progresso) VALUES (:c,:t,:d,:m,:u,:p,:r,:f,:s,:g)')
        ->execute([
            'c' => $clienteId,
            't' => 'Plano Agenda ' . $suffix,
            'd' => 'Descricao do plano',
            'm' => 10,
            'u' => 'itens',
            'p' => '2026-04-19',
            'r' => 'Responsavel Plano',
            'f' => 'DO',
            's' => 'Em Andamento',
            'g' => 45,
        ]);
    $taskId = (int)$pdo->lastInsertId();
    $taskIds[] = $taskId;

    $pdo->prepare('INSERT INTO pdca_actions (task_id, titulo, owner, due_date, status) VALUES (:t,:n,:o,:d,:s)')
        ->execute([
            't' => $taskId,
            'n' => 'Acao Agenda ' . $suffix,
            'o' => 'Owner Agenda',
            'd' => '2026-04-19',
            's' => 'Planejado',
        ]);
    $actionIds[] = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO auditorias (cliente_id, setor_id, responsavel_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
        VALUES (:cliente,:setor,NULL,:data,:nome,:pergunta,:objetivo,:referencia,'Agendada',1,1)")
        ->execute([
            'cliente' => $clienteId,
            'setor' => $setorId,
            'data' => '2026-04-19',
            'nome' => 'Auditoria Agenda ' . $suffix,
            'pergunta' => 'Pergunta base da auditoria',
            'objetivo' => 'Objetivo da auditoria agenda',
            'referencia' => 'REF-AGENDA',
        ]);
    $auditoriaId = (int)$pdo->lastInsertId();
    $auditoriaIds[] = $auditoriaId;

    $treinamentoModel = new TreinamentoModel();
    $agendaModel = new TreinamentoAgendaModel();
    $treinamentoId = $treinamentoModel->create([
        'nome' => 'Treinamento Agenda ' . $suffix,
        'objetivo' => 'Objetivo treinamento agenda',
        'publico' => 'Equipe operacional',
        'carga_horaria' => '4',
        'departamento_id' => $departamentoId,
        'periodicidade' => 'anual',
        'fornecedor' => 'Fornecedor Agenda',
        'setor_ids' => [$setorId],
        'funcao_ids' => [$funcaoId],
    ]);
    $treinamentoIds[] = $treinamentoId;
    $treinamentoModel->syncColaboradores($treinamentoId, [$colaboradorId]);
    $agendaTreinamentoId = $agendaModel->create([
        'treinamento_id' => $treinamentoId,
        'data' => '2026-04-19 09:00:00',
        'unidade_id' => $clienteId,
        'responsavel_id' => null,
        'instrutor' => 'Instrutor Agenda',
        'local' => 'Sala 1',
        'observacoes' => 'Turma da manhã',
    ]);
    $agendaTreinamentoIds[] = $agendaTreinamentoId;
    $agendaModel->syncParticipants($agendaTreinamentoId, [$colaboradorId]);

    $cronogramaModel = new CronogramaModel();
    $cronogramaEventoModel = new CronogramaEventoModel();
    $cronogramaId = $cronogramaModel->create([
        'id_cliente' => $clienteId,
        'nome' => 'Cronograma Agenda ' . $suffix,
        'ano' => 2026,
    ]);
    $cronogramaIds[] = $cronogramaId;
    $cronogramaEventoRootId = $cronogramaEventoModel->create($cronogramaId, [
        'data' => '2026-04-19',
        'periodicidade' => 'unico',
        'topico' => 'Pilar Agenda',
        'unidade' => 'Departamento Agenda',
        'atividade' => 'Evento Cronograma ' . $suffix,
        'responsavel' => 'Responsavel Cronograma',
        'modelo' => 'Presencial',
        'status' => 'Planejado',
    ]);
    $cronogramaEventoRootIds[] = $cronogramaEventoRootId;

    $service = new AgendaEventService($pdo);
    $events = $service->eventsForRange('2026-04-01', '2026-04-30', 'all');
    $titles = array_map(static fn(array $item): string => (string)($item['title'] ?? ''), $events);
    if (!in_array('Plano Agenda ' . $suffix, $titles, true)) {
        failFast('Agenda deveria sincronizar o plano de acao');
    }
    if (!in_array('[Acao] Acao Agenda ' . $suffix, $titles, true)) {
        failFast('Agenda deveria sincronizar as acoes do plano');
    }
    if (!in_array('Auditoria Agenda ' . $suffix, $titles, true)) {
        failFast('Agenda deveria sincronizar a auditoria');
    }
    if (!in_array('Treinamento Agenda ' . $suffix, $titles, true)) {
        failFast('Agenda deveria sincronizar o treinamento agendado');
    }
    if (!in_array('Evento Cronograma ' . $suffix, $titles, true)) {
        failFast('Agenda deveria sincronizar o evento de cronograma');
    }
    ok('Sincronização integrada de planos, auditorias, treinamentos e cronograma');

    $planOnly = $service->eventsForRange('2026-04-01', '2026-04-30', 'planoacao');
    if (count(array_filter($planOnly, static fn(array $item): bool => ($item['type'] ?? '') === 'auditoria')) > 0) {
        failFast('Filtro de plano de acao nao deveria retornar auditorias');
    }
    ok('Filtro apenas planos de acao');

    $auditOnly = $service->eventsForRange('2026-04-01', '2026-04-30', 'auditoria');
    if (count($auditOnly) !== 1 || ($auditOnly[0]['title'] ?? '') !== 'Auditoria Agenda ' . $suffix) {
        failFast('Filtro de auditoria deveria retornar apenas a auditoria do período');
    }
    ok('Filtro apenas auditorias');

    $trainingOnly = $service->eventsForRange('2026-04-01', '2026-04-30', 'treinamento');
    if (count($trainingOnly) !== 1 || ($trainingOnly[0]['title'] ?? '') !== 'Treinamento Agenda ' . $suffix) {
        failFast('Filtro de treinamento deveria retornar apenas o agendamento do período');
    }
    ok('Filtro apenas treinamentos');

    $cronogramaOnly = $service->eventsForRange('2026-04-01', '2026-04-30', 'cronograma');
    if (count($cronogramaOnly) !== 1 || ($cronogramaOnly[0]['title'] ?? '') !== 'Evento Cronograma ' . $suffix) {
        failFast('Filtro de cronograma deveria retornar apenas o evento do período');
    }
    ok('Filtro apenas cronograma');

    $grouped = AgendaEventService::groupByDate($events);
    if (count($grouped['2026-04-19'] ?? []) < 5) {
        failFast('Data clicável deveria concentrar todos os eventos do mesmo dia');
    }
    ok('Agrupamento diário para modal/listagem');

    echo "Agenda event service integration test passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if (!empty($cronogramaEventoRootIds)) {
        $pdo->exec('DELETE FROM cronograma_eventos WHERE id IN (' . implode(',', array_map('intval', $cronogramaEventoRootIds)) . ') OR evento_pai_id IN (' . implode(',', array_map('intval', $cronogramaEventoRootIds)) . ')');
    }
    if (!empty($cronogramaIds)) {
        $pdo->exec('DELETE FROM cronogramas WHERE id IN (' . implode(',', array_map('intval', $cronogramaIds)) . ')');
    }
    if (!empty($agendaTreinamentoIds)) {
        $pdo->exec('DELETE FROM treinamento_participantes WHERE agenda_id IN (' . implode(',', array_map('intval', $agendaTreinamentoIds)) . ')');
        $pdo->exec('DELETE FROM treinamentos_agenda WHERE id IN (' . implode(',', array_map('intval', $agendaTreinamentoIds)) . ')');
    }
    if (!empty($treinamentoIds)) {
        $pdo->exec('DELETE FROM treinamento_colaboradores WHERE treinamento_id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
        $pdo->exec('DELETE FROM treinamento_funcoes WHERE treinamento_id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
        $pdo->exec('DELETE FROM treinamento_setores WHERE treinamento_id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
        $pdo->exec('DELETE FROM treinamentos WHERE id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
    }
    if (!empty($auditoriaIds)) {
        $pdo->exec('DELETE FROM auditorias WHERE id IN (' . implode(',', array_map('intval', $auditoriaIds)) . ')');
    }
    if (!empty($actionIds)) {
        $pdo->exec('DELETE FROM pdca_actions WHERE id IN (' . implode(',', array_map('intval', $actionIds)) . ')');
    }
    if (!empty($taskIds)) {
        $pdo->exec('DELETE FROM pdca_tasks WHERE id IN (' . implode(',', array_map('intval', $taskIds)) . ')');
    }
    if (!empty($colaboradorIds)) {
        $pdo->exec('DELETE FROM colaboradores WHERE id IN (' . implode(',', array_map('intval', $colaboradorIds)) . ')');
    }
    if (!empty($funcaoIds)) {
        $pdo->exec('DELETE FROM funcoes WHERE id IN (' . implode(',', array_map('intval', $funcaoIds)) . ')');
    }
    if (!empty($setorIds)) {
        $pdo->exec('DELETE FROM setores WHERE id IN (' . implode(',', array_map('intval', $setorIds)) . ')');
    }
    if (!empty($departamentoIds)) {
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', $departamentoIds)) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
    Auth::logout();
}
