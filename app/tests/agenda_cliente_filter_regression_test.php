<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\AgendaController;
use App\Core\Auth;
use App\Database\Database;
use App\Models\DepartamentoModel;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;
use App\Services\AgendaEventService;

ob_start();

function agc_ok(string $msg): void { echo "OK: $msg\n"; }
function agc_failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'agendacli_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$taskIds = [];
$auditoriaIds = [];
$treinamentoIds = [];
$agendaTreinamentoIds = [];

function makeClienteFixture(PDO $pdo, string $suffix, string $tag, array &$clienteIds, array &$departamentoIds, array &$setorIds, array &$funcaoIds, array &$colaboradorIds, array &$taskIds, array &$auditoriaIds, array &$treinamentoIds, array &$agendaTreinamentoIds): int {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => "Cliente AgendaFiltro {$tag} {$suffix}", 'c' => '00.111.2' . random_int(10, 99) . '/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $departamentoId = (new DepartamentoModel())->create(['nome' => "Dep AgendaFiltro {$tag} {$suffix}", 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
    if ($departamentoId <= 0) {
        agc_failFast("Falha ao criar departamento de teste ({$tag})");
    }
    $departamentoIds[] = $departamentoId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => "Setor AgendaFiltro {$tag} {$suffix}", 'd' => $departamentoId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => "Funcao AgendaFiltro {$tag} {$suffix}", 's' => $setorId]);
    $funcaoId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n,:e,:f,:c)')
        ->execute(['n' => "Colab AgendaFiltro {$tag} {$suffix}", 'e' => "colab.agendafiltro.{$tag}.{$suffix}@test.local", 'f' => $funcaoId, 'c' => $clienteId]);
    $colaboradorIds[] = (int)$pdo->lastInsertId();

    $pdo->prepare('INSERT INTO pdca_tasks (id_cliente, titulo, descricao, meta_valor, meta_unidade, prazo, responsavel, fase, status, progresso) VALUES (:c,:t,:d,:m,:u,:p,:r,:f,:s,:g)')
        ->execute([
            'c' => $clienteId, 't' => "Plano AgendaFiltro {$tag} {$suffix}", 'd' => 'Descricao',
            'm' => 10, 'u' => 'itens', 'p' => '2026-05-19', 'r' => 'Resp', 'f' => 'DO', 's' => 'Em Andamento', 'g' => 10,
        ]);
    $taskIds[] = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO auditorias (cliente_id, setor_id, responsavel_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
        VALUES (:cliente,:setor,NULL,:data,:nome,:pergunta,:objetivo,:referencia,'Agendada',1,1)")
        ->execute([
            'cliente' => $clienteId, 'setor' => $setorId, 'data' => '2026-05-19',
            'nome' => "Auditoria AgendaFiltro {$tag} {$suffix}", 'pergunta' => 'Pergunta', 'objetivo' => 'Objetivo', 'referencia' => 'REF',
        ]);
    $auditoriaIds[] = (int)$pdo->lastInsertId();

    $treinamentoModel = new TreinamentoModel();
    $agendaModel = new TreinamentoAgendaModel();
    $treinamentoId = $treinamentoModel->create([
        'nome' => "Treinamento AgendaFiltro {$tag} {$suffix}",
        'objetivo' => 'Objetivo', 'publico' => 'Equipe', 'carga_horaria' => '4',
        'cliente_id' => $clienteId, 'departamento_id' => $departamentoId, 'periodicidade' => 'anual', 'fornecedor' => 'Fornecedor',
        'setor_ids' => [$setorId], 'funcao_ids' => [$funcaoId],
    ]);
    if ($treinamentoId <= 0) {
        agc_failFast("Falha ao criar treinamento de teste ({$tag})");
    }
    $treinamentoIds[] = $treinamentoId;
    $agendaTreinamentoIds[] = $agendaModel->create([
        'treinamento_id' => $treinamentoId, 'data' => '2026-05-19 09:00:00', 'unidade_id' => $clienteId,
        'responsavel_id' => null, 'instrutor' => 'Instrutor', 'local' => 'Sala 1', 'observacoes' => '',
    ]);

    return $clienteId;
}

try {
    Auth::login(['id' => 9101, 'nome' => 'Instituto AgendaFiltro', 'email' => 'inst.agendafiltro.' . $suffix . '@test.local', 'tipo_acesso' => 'instituto']);

    $clienteA = makeClienteFixture($pdo, $suffix, 'A', $clienteIds, $departamentoIds, $setorIds, $funcaoIds, $colaboradorIds, $taskIds, $auditoriaIds, $treinamentoIds, $agendaTreinamentoIds);
    $clienteB = makeClienteFixture($pdo, $suffix, 'B', $clienteIds, $departamentoIds, $setorIds, $funcaoIds, $colaboradorIds, $taskIds, $auditoriaIds, $treinamentoIds, $agendaTreinamentoIds);

    $service = new AgendaEventService($pdo);

    $allEvents = $service->eventsForRange('2026-05-01', '2026-05-31', 'all');
    $titlesAll = array_map(static fn(array $e): string => (string)($e['title'] ?? ''), $allEvents);
    if (!in_array('Plano AgendaFiltro A ' . $suffix, $titlesAll, true) || !in_array('Plano AgendaFiltro B ' . $suffix, $titlesAll, true)) {
        agc_failFast('Sem filtro de cliente, deveria consolidar eventos dos dois clientes');
    }
    agc_ok('Sem cliente selecionado, a agenda consolida eventos de todos os clientes permitidos');

    $eventsA = $service->eventsForRange('2026-05-01', '2026-05-31', 'all', $clienteA);
    $titlesA = array_map(static fn(array $e): string => (string)($e['title'] ?? ''), $eventsA);
    if (!in_array('Plano AgendaFiltro A ' . $suffix, $titlesA, true) || !in_array('Auditoria AgendaFiltro A ' . $suffix, $titlesA, true) || !in_array('Treinamento AgendaFiltro A ' . $suffix, $titlesA, true)) {
        agc_failFast('Filtro pelo cliente A deveria incluir os eventos de plano, auditoria e treinamento do cliente A');
    }
    if (in_array('Plano AgendaFiltro B ' . $suffix, $titlesA, true) || in_array('Auditoria AgendaFiltro B ' . $suffix, $titlesA, true) || in_array('Treinamento AgendaFiltro B ' . $suffix, $titlesA, true)) {
        agc_failFast('Filtro pelo cliente A vazou eventos do cliente B');
    }
    agc_ok('Filtro por cliente restringe corretamente plano de ação, auditoria e treinamento ao cliente selecionado');

    $eventsB = $service->eventsForRange('2026-05-01', '2026-05-31', 'all', $clienteB);
    $titlesB = array_map(static fn(array $e): string => (string)($e['title'] ?? ''), $eventsB);
    if (in_array('Plano AgendaFiltro A ' . $suffix, $titlesB, true)) {
        agc_failFast('Filtro pelo cliente B vazou eventos do cliente A');
    }
    agc_ok('Filtro por cliente B não vaza eventos do cliente A (isolamento nos dois sentidos)');

    Auth::logout();

    // Usuário escopado só ao cliente A (perfil "Cliente Admin", o único com acesso ao
    // módulo Agenda per AccessControl): tentar filtrar por cliente B deve ser rejeitado.
    Auth::login([
        'id' => 9102,
        'nome' => 'Cliente A AgendaFiltro',
        'email' => 'clienteA.agendafiltro.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente_admin',
        'id_cliente' => $clienteA,
    ]);

    // Observação: solicitar ?cliente=<id de outro tenant> já é bloqueado ANTES de chegar
    // em AgendaController::apiEvents(), pelo guard genérico e pré-existente
    // BaseController::authorizeRoute()/routeClienteCandidate() (verifica qualquer
    // parâmetro "cliente"/"cliente_id"/"id_cliente"/"empresa_id" contra
    // Auth::canAccessCliente() e responde 403 + exit). Esse comportamento já é
    // coberto genericamente por app/tests/access_profiles_integration_test.php;
    // não é retestado aqui porque o exit() do guard encerraria o processo PHP
    // antes da limpeza (finally) dos fixtures deste teste.

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['route' => 'agenda/api_events', 'start' => '2026-05-01', 'end' => '2026-05-31', 'type' => 'all', 'cliente' => (string)$clienteA];
    ob_start();
    (new AgendaController())->apiEvents();
    $payloadOwn = json_decode((string)ob_get_clean(), true);
    $titlesOwn = array_map(static fn(array $e): string => (string)($e['title'] ?? ''), $payloadOwn['items'] ?? []);
    if (!in_array('Plano AgendaFiltro A ' . $suffix, $titlesOwn, true)) {
        agc_failFast('Selecionar explicitamente o próprio cliente deveria continuar funcionando normalmente');
    }
    agc_ok('Selecionar explicitamente o próprio cliente (dentro do escopo permitido) funciona normalmente');

    echo "Agenda cliente filter regression test passed.\n";
} catch (Throwable $e) {
    agc_failFast('Excecao: ' . $e->getMessage());
} finally {
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
ob_end_flush();
