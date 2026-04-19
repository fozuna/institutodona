<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Services\AgendaEventService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'agenda_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$taskIds = [];
$actionIds = [];
$auditoriaIds = [];

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
    ok('Sincronização integrada de planos e auditorias');

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

    $grouped = AgendaEventService::groupByDate($events);
    if (count($grouped['2026-04-19'] ?? []) < 3) {
        failFast('Data clicável deveria concentrar todos os eventos do mesmo dia');
    }
    ok('Agrupamento diário para modal/listagem');

    echo "Agenda event service integration test passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if (!empty($auditoriaIds)) {
        $pdo->exec('DELETE FROM auditorias WHERE id IN (' . implode(',', array_map('intval', $auditoriaIds)) . ')');
    }
    if (!empty($actionIds)) {
        $pdo->exec('DELETE FROM pdca_actions WHERE id IN (' . implode(',', array_map('intval', $actionIds)) . ')');
    }
    if (!empty($taskIds)) {
        $pdo->exec('DELETE FROM pdca_tasks WHERE id IN (' . implode(',', array_map('intval', $taskIds)) . ')');
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
