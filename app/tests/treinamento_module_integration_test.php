<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;
use App\Services\AgendaEventService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'trein_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$treinamentoIds = [];
$agendaIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Treinamento ' . $suffix, 'c' => '11.111.111/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    Auth::login([
        'id' => 9010,
        'nome' => 'Teste Treinamento',
        'email' => 'treinamento.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $clienteId,
    ]);

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:c)')
        ->execute(['n' => 'Departamento Treinamento ' . $suffix, 'c' => $clienteId]);
    $departamentoId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor Treinamento ' . $suffix, 'd' => $departamentoId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao Treinamento ' . $suffix, 's' => $setorId]);
    $funcaoId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n,:e,:f,:c)')
        ->execute([
            'n' => 'Colaborador Treinamento ' . $suffix,
            'e' => 'colab.treinamento.' . $suffix . '@test.local',
            'f' => $funcaoId,
            'c' => $clienteId,
        ]);
    $colaboradorId = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorId;

    $treinamentoModel = new TreinamentoModel();
    $agendaModel = new TreinamentoAgendaModel();

    $treinamentoId = $treinamentoModel->create([
        'nome' => 'NR Integração ' . $suffix,
        'objetivo' => 'Validar fluxo completo do pilar',
        'publico' => 'Equipe interna',
        'carga_horaria' => '8',
        'departamento_id' => $departamentoId,
        'periodicidade' => 'anual',
        'fornecedor' => 'Fornecedor Teste',
        'setor_ids' => [$setorId],
        'funcao_ids' => [$funcaoId],
    ]);
    $treinamentoIds[] = $treinamentoId;

    $treinamento = $treinamentoModel->find($treinamentoId);
    if (!$treinamento || $treinamento['nome'] !== 'NR Integração ' . $suffix) {
        failFast('Treinamento deveria ser criado e encontrado');
    }
    if (count($treinamento['setor_ids'] ?? []) !== 1 || count($treinamento['funcao_ids'] ?? []) !== 1) {
        failFast('Relacionamentos N:N deveriam ser persistidos');
    }
    ok('CRUD e relacionamentos do treinamento');

    $treinamentoModel->syncColaboradores($treinamentoId, [$colaboradorId, $colaboradorId]);
    $linked = $treinamentoModel->linkedColaboradores($treinamentoId);
    if (count($linked) !== 1 || ($linked[0]['status'] ?? '') !== 'pendente') {
        failFast('Vínculo do colaborador deveria ser único e iniciar pendente');
    }
    ok('Vinculação sem duplicidade');

    $agendaId = $agendaModel->create([
        'treinamento_id' => $treinamentoId,
        'data' => '2026-04-21 09:30:00',
        'unidade_id' => $clienteId,
        'responsavel_id' => null,
        'instrutor' => 'Instrutor Interno',
        'local' => 'Sala 2',
        'observacoes' => 'Turma piloto',
    ]);
    $agendaIds[] = $agendaId;

    $pendentes = $agendaModel->pendingParticipantsForTreinamento($treinamentoId, $agendaId);
    if (count($pendentes) !== 1 || (int)($pendentes[0]['colaborador_id'] ?? 0) !== $colaboradorId) {
        failFast('Agendamento deveria sugerir apenas colaboradores pendentes');
    }

    $agendaModel->syncParticipants($agendaId, [$colaboradorId]);
    $participants = $agendaModel->participants($agendaId);
    if (count($participants) !== 1 || !empty($participants[0]['presenca'])) {
        failFast('Participante deveria ser incluído com presença zerada');
    }
    ok('Agendamento e seleção de participantes');

    $agendaModel->savePresence($agendaId, [$colaboradorId => 1], [$colaboradorId => 1]);
    $linkedAfterPresence = $treinamentoModel->linkedColaboradores($treinamentoId, 'concluido');
    if (count($linkedAfterPresence) !== 1) {
        failFast('Presença com certificado deveria concluir o vínculo');
    }
    ok('Presença e conclusão com certificado');

    $dashboard = $treinamentoModel->dashboard();
    if (empty($dashboard['concluidos'])) {
        failFast('Dashboard deveria listar concluídos após emissão do certificado');
    }
    ok('Dashboard de acompanhamento');

    $service = new AgendaEventService($pdo);
    $events = $service->eventsForRange('2026-04-01', '2026-04-30', 'treinamento');
    $titles = array_map(static fn(array $item): string => (string)($item['title'] ?? ''), $events);
    if (!in_array('NR Integração ' . $suffix, $titles, true)) {
        failFast('Agenda integrada deveria retornar o treinamento agendado');
    }
    ok('Integração com agenda');

    echo "Treinamento module integration test passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if (!empty($agendaIds)) {
        $pdo->exec('DELETE FROM treinamento_participantes WHERE agenda_id IN (' . implode(',', array_map('intval', $agendaIds)) . ')');
        $pdo->exec('DELETE FROM treinamentos_agenda WHERE id IN (' . implode(',', array_map('intval', $agendaIds)) . ')');
    }
    if (!empty($treinamentoIds)) {
        $pdo->exec('DELETE FROM treinamento_colaboradores WHERE treinamento_id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
        $pdo->exec('DELETE FROM treinamento_funcoes WHERE treinamento_id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
        $pdo->exec('DELETE FROM treinamento_setores WHERE treinamento_id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
        $pdo->exec('DELETE FROM treinamentos WHERE id IN (' . implode(',', array_map('intval', $treinamentoIds)) . ')');
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
