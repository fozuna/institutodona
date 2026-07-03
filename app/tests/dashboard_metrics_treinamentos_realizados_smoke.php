<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\DashboardController;
use App\Core\Auth;
use App\Database\Database;
use App\Database\MigrationRunner;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;

function dash_assert(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
}

try {
    $pdo = Database::getConnection();
} catch (\Throwable $e) {
    echo "SKIP: Sem conexão com DB no ambiente atual.\n";
    exit(0);
}

(new MigrationRunner())->applyAll();

$suffix = 'dash_tr_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$treinamentoIds = [];
$agendaIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Dashboard Treinamento ' . $suffix, 'c' => '33.333.333/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:c)')
        ->execute(['n' => 'Departamento Dashboard ' . $suffix, 'c' => $clienteId]);
    $departamentoId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $departamentoId, 'c' => $clienteId]);

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor Dashboard ' . $suffix, 'd' => $departamentoId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao Dashboard ' . $suffix, 's' => $setorId]);
    $funcaoId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id) VALUES (:n,:e,:f,:c)')
        ->execute([
            'n' => 'Colaborador Dashboard ' . $suffix,
            'e' => 'dash.tr.' . $suffix . '@test.local',
            'f' => $funcaoId,
            'c' => $clienteId,
        ]);
    $colaboradorId = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorId;

    Auth::login([
        'id' => 9903,
        'nome' => 'Instituto Dashboard',
        'email' => 'instituto.dashboard.' . $suffix . '@test.local',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ]);

    $treinamentoModel = new TreinamentoModel();
    $agendaModel = new TreinamentoAgendaModel();

    $treinamentoId = $treinamentoModel->create([
        'nome' => 'Treinamento Dashboard ' . $suffix,
        'objetivo' => 'Validar métrica de planejados e realizados',
        'publico' => 'Equipe',
        'carga_horaria' => '4',
        'cliente_id' => $clienteId,
        'departamento_id' => $departamentoId,
        'periodicidade' => 'anual',
        'fornecedor' => 'Fornecedor',
        'tipo_treinamento' => 'Interno',
        'template_certificado' => '',
        'assinatura_responsavel' => 'Gestor',
        'setor_ids' => [$setorId],
        'funcao_ids' => [$funcaoId],
    ]);
    $treinamentoIds[] = $treinamentoId;
    dash_assert($treinamentoId > 0, 'Cria treinamento para a métrica do dashboard');

    $treinamentoModel->syncColaboradores($treinamentoId, [$colaboradorId]);

    $agendaRealizadaId = $agendaModel->create([
        'treinamento_id' => $treinamentoId,
        'data' => '2026-07-20 08:00:00',
        'data_fim' => '2026-07-20 12:00:00',
        'unidade_id' => $clienteId,
        'responsavel_id' => null,
        'instrutor' => 'Instrutor Dashboard',
        'local' => 'Sala 1',
        'observacoes' => 'Agenda com presença confirmada',
    ]);
    $agendaIds[] = $agendaRealizadaId;

    $agendaPlanejadaId = $agendaModel->create([
        'treinamento_id' => $treinamentoId,
        'data' => '2026-07-28 08:00:00',
        'data_fim' => '2026-07-28 12:00:00',
        'unidade_id' => $clienteId,
        'responsavel_id' => null,
        'instrutor' => 'Instrutor Dashboard',
        'local' => 'Sala 2',
        'observacoes' => 'Agenda ainda planejada',
    ]);
    $agendaIds[] = $agendaPlanejadaId;

    $agendaModel->syncParticipants($agendaRealizadaId, [$colaboradorId]);
    $agendaModel->savePresence($agendaRealizadaId, [$colaboradorId => 1], [$colaboradorId => '08:00'], [$colaboradorId => '12:00'], [$colaboradorId => 'Presente']);

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [
        'route' => 'dashboard/metrics',
        'month_start' => '2026-07',
        'month_end' => '2026-07',
        'clientes' => [$clienteId],
    ];

    ob_start();
    (new DashboardController())->metrics();
    $json = json_decode(trim((string)ob_get_clean()), true);

    dash_assert(is_array($json) && ($json['ok'] ?? false) === true, 'Endpoint dashboard/metrics responde com sucesso');
    $treinamentos = (array)($json['treinamentos'] ?? []);
    dash_assert((int)($treinamentos['planejados'] ?? 0) === 2, 'Métrica de treinamentos planejados considera todas as agendas do período');
    dash_assert((int)($treinamentos['realizados'] ?? 0) === 1, 'Métrica de treinamentos realizados reconhece evidência real de execução no período');
    dash_assert((int)($treinamentos['total_sessoes'] ?? 0) === 2, 'Total de sessões permanece consistente com as agendas filtradas');
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

echo "dashboard_metrics_treinamentos_realizados_smoke passed.\n";
