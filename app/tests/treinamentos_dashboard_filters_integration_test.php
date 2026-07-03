<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;

function td_ok(string $message): void
{
    echo "OK: {$message}\n";
}

function td_fail(string $message): void
{
    echo "FAIL: {$message}\n";
    exit(1);
}

$pdo = Database::getConnection();
$suffix = 'dash_filters_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$treinamentoIds = [];
$agendaIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Dashboard Filtros ' . $suffix, 'c' => '44.444.444/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:c)')
        ->execute(['n' => 'Departamento Dashboard Filtros ' . $suffix, 'c' => $clienteId]);
    $departamentoId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $departamentoId, 'c' => $clienteId]);

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor Alice ' . $suffix, 'd' => $departamentoId]);
    $setorAliceId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorAliceId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor Bruno ' . $suffix, 'd' => $departamentoId]);
    $setorBrunoId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorBrunoId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao Alice ' . $suffix, 's' => $setorAliceId]);
    $funcaoAliceId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoAliceId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao Bruno ' . $suffix, 's' => $setorBrunoId]);
    $funcaoBrunoId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoBrunoId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n,:e,:f,:c)')
        ->execute([
            'n' => 'Colaborador Alice ' . $suffix,
            'e' => 'alice.' . $suffix . '@test.local',
            'f' => $funcaoAliceId,
            'c' => $clienteId,
        ]);
    $colaboradorAliceId = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorAliceId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n,:e,:f,:c)')
        ->execute([
            'n' => 'Colaborador Bruno ' . $suffix,
            'e' => 'bruno.' . $suffix . '@test.local',
            'f' => $funcaoBrunoId,
            'c' => $clienteId,
        ]);
    $colaboradorBrunoId = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorBrunoId;

    Auth::login([
        'id' => 9910,
        'nome' => 'Cliente Dashboard Filtros',
        'email' => 'cliente.dashboard.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $clienteId,
        'allowed_client_ids' => [$clienteId],
    ]);

    $treinamentoModel = new TreinamentoModel();
    $agendaModel = new TreinamentoAgendaModel();

    $treinamentoAliceId = $treinamentoModel->create([
        'nome' => 'Treinamento Alice ' . $suffix,
        'objetivo' => 'Validar filtros do dashboard',
        'publico' => 'Equipe Alice',
        'carga_horaria' => '4',
        'cliente_id' => $clienteId,
        'departamento_id' => $departamentoId,
        'periodicidade' => 'anual',
        'fornecedor' => 'Fornecedor',
        'tipo_treinamento' => 'Interno',
        'template_certificado' => '',
        'assinatura_responsavel' => 'Gestor',
        'setor_ids' => [$setorAliceId],
        'funcao_ids' => [$funcaoAliceId],
    ]);
    $treinamentoIds[] = $treinamentoAliceId;

    $treinamentoBrunoId = $treinamentoModel->create([
        'nome' => 'Treinamento Bruno ' . $suffix,
        'objetivo' => 'Validar filtros do dashboard',
        'publico' => 'Equipe Bruno',
        'carga_horaria' => '4',
        'cliente_id' => $clienteId,
        'departamento_id' => $departamentoId,
        'periodicidade' => 'anual',
        'fornecedor' => 'Fornecedor',
        'tipo_treinamento' => 'Externo',
        'template_certificado' => '',
        'assinatura_responsavel' => 'Gestor',
        'setor_ids' => [$setorBrunoId],
        'funcao_ids' => [$funcaoBrunoId],
    ]);
    $treinamentoIds[] = $treinamentoBrunoId;

    $treinamentoModel->syncColaboradores($treinamentoAliceId, [$colaboradorAliceId]);
    $treinamentoModel->syncColaboradores($treinamentoBrunoId, [$colaboradorBrunoId]);

    $agendaAliceId = $agendaModel->create([
        'treinamento_id' => $treinamentoAliceId,
        'data' => '2026-07-15 09:00:00',
        'data_fim' => '2026-07-15 12:00:00',
        'unidade_id' => $clienteId,
        'responsavel_id' => null,
        'instrutor' => 'Alice',
        'local' => 'Sala A',
        'observacoes' => 'Turma Alice',
    ]);
    $agendaIds[] = $agendaAliceId;

    $agendaBrunoId = $agendaModel->create([
        'treinamento_id' => $treinamentoBrunoId,
        'data' => '2026-07-16 09:00:00',
        'data_fim' => '2026-07-16 12:00:00',
        'unidade_id' => $clienteId,
        'responsavel_id' => null,
        'instrutor' => 'Bruno',
        'local' => 'Sala B',
        'observacoes' => 'Turma Bruno',
    ]);
    $agendaIds[] = $agendaBrunoId;

    $agendaModel->syncParticipants($agendaAliceId, [$colaboradorAliceId]);
    $agendaModel->syncParticipants($agendaBrunoId, [$colaboradorBrunoId]);

    $dashboard = $treinamentoModel->dashboard([
        'cliente_id' => $clienteId,
        'periodo_inicio' => '2026-07-01',
        'periodo_fim' => '2026-07-31',
        'setor_id' => $setorAliceId,
        'tipo_treinamento' => 'Interno',
        'instrutor' => 'Alice',
    ]);

    if (count((array)($dashboard['setores'] ?? [])) !== 1) {
        td_fail('Comparativo por setor deveria respeitar setor/tipo/instrutor filtrados');
    }
    $setorNome = (string)($dashboard['setores'][0]['setor_nome'] ?? '');
    if ($setorNome !== 'Setor Alice ' . $suffix) {
        td_fail('Comparativo por setor deveria retornar apenas o setor filtrado');
    }
    td_ok('Comparativo por setor respeita os filtros globais');

    $pendentes = (array)($dashboard['pendentes'] ?? []);
    if (count($pendentes) !== 1 || (string)($pendentes[0]['treinamento_nome'] ?? '') !== 'Treinamento Alice ' . $suffix) {
        td_fail('Pendentes deveria respeitar período, setor, tipo e instrutor');
    }
    td_ok('Pendentes respeita os filtros globais');

    $alertasSetor = (array)($dashboard['alertas_setor'] ?? []);
    if (count($alertasSetor) !== 1 || (string)($alertasSetor[0]['setor_nome'] ?? '') !== 'Setor Alice ' . $suffix) {
        td_fail('Alertas automáticos por setor deveria respeitar os filtros globais');
    }
    td_ok('Alertas automáticos por setor respeita os filtros globais');
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
        $pdo->exec('DELETE FROM departamento_clientes WHERE departamento_id IN (' . implode(',', array_map('intval', $departamentoIds)) . ')');
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', $departamentoIds)) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
    Auth::logout();
}

echo "treinamentos_dashboard_filters_integration_test passed.\n";
