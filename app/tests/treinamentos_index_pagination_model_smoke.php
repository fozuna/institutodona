<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\TreinamentoAgendaModel;
use App\Models\TreinamentoModel;

function ok(string $message): void
{
    echo "OK: {$message}\n";
}

function failFast(string $message): void
{
    echo "FAIL: {$message}\n";
    exit(1);
}

$pdo = Database::getConnection();
$suffix = 'train_idx_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$treinamentoIds = [];
$agendaIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)')
        ->execute([
            'nome' => 'Cliente Index A ' . $suffix,
            'cnpj' => '77.777.777/0001-' . random_int(10, 99),
            'contato' => 'Smoke',
        ]);
    $clienteAId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteAId;

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)')
        ->execute([
            'nome' => 'Cliente Index B ' . $suffix,
            'cnpj' => '88.888.888/0001-' . random_int(10, 99),
            'contato' => 'Smoke',
        ]);
    $clienteBId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteBId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cliente_id)')
        ->execute(['nome' => 'Departamento Index A ' . $suffix, 'cliente_id' => $clienteAId]);
    $departamentoAId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoAId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cliente_id)')
        ->execute(['nome' => 'Departamento Index B ' . $suffix, 'cliente_id' => $clienteBId]);
    $departamentoBId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoBId;

    Auth::login([
        'id' => 9901,
        'nome' => 'Instituto Smoke',
        'email' => 'instituto.' . $suffix . '@test.local',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ]);

    $model = new TreinamentoModel();
    $agendaModel = new TreinamentoAgendaModel();

    for ($i = 1; $i <= 7; $i++) {
        $treinamentoIds[] = $model->create([
            'nome' => sprintf('Treinamento Lista %02d %s', $i, $suffix),
            'objetivo' => 'Validar paginação da listagem principal',
            'publico' => 'Equipe',
            'carga_horaria' => '4',
            'cliente_id' => $clienteAId,
            'departamento_id' => $departamentoAId,
            'periodicidade' => 'anual',
            'fornecedor' => 'Fornecedor A',
            'tipo_treinamento' => 'Interno',
            'template_certificado' => '',
            'assinatura_responsavel' => 'Gestor A',
            'setor_ids' => [],
            'funcao_ids' => [],
        ]);
    }

    $treinamentoBId = $model->create([
        'nome' => 'Treinamento Lista Externo ' . $suffix,
        'objetivo' => 'Não deve aparecer para o cliente A',
        'publico' => 'Equipe B',
        'carga_horaria' => '4',
        'cliente_id' => $clienteBId,
        'departamento_id' => $departamentoBId,
        'periodicidade' => 'anual',
        'fornecedor' => 'Fornecedor B',
        'tipo_treinamento' => 'Interno',
        'template_certificado' => '',
        'assinatura_responsavel' => 'Gestor B',
        'setor_ids' => [],
        'funcao_ids' => [],
    ]);
    $treinamentoIds[] = $treinamentoBId;

    $agendaIds[] = $agendaModel->create([
        'treinamento_id' => $treinamentoIds[0],
        'data' => '2030-01-15 08:00:00',
        'data_fim' => '2030-01-15 12:00:00',
        'unidade_id' => $clienteAId,
        'responsavel_id' => null,
        'instrutor' => 'Instrutor',
        'local' => 'Sala 1',
        'observacoes' => 'Agenda futura',
    ]);

    Auth::login([
        'id' => 9902,
        'nome' => 'Cliente A Smoke',
        'email' => 'cliente.a.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $clienteAId,
        'allowed_client_ids' => [$clienteAId],
    ]);

    $totalA = $model->countIndex(['cliente_id' => $clienteAId]);
    if ($totalA !== 7) {
        failFast('Listagem deve contar apenas os treinamentos do cliente A');
    }
    ok('Contagem index respeita escopo do cliente');

    $pageOne = $model->paginateIndex(['cliente_id' => $clienteAId], 1, 3);
    $pageThree = $model->paginateIndex(['cliente_id' => $clienteAId], 3, 3);
    if (count($pageOne) !== 3 || count($pageThree) !== 1) {
        failFast('Paginação deve respeitar limite e última página parcial');
    }
    ok('Paginação index divide os resultados corretamente');

    $agendado = $model->paginateIndex(['cliente_id' => $clienteAId, 'q' => sprintf('Treinamento Lista %02d %s', 1, $suffix)], 1, 5);
    $row = $agendado[0] ?? null;
    if (!$row) {
        failFast('Busca textual deve localizar o treinamento esperado');
    }
    if (($row['status_resumo'] ?? '') !== 'Agendado') {
        failFast('Treinamento com agenda futura deve receber status resumido Agendado');
    }
    if (($row['data_referencia_rotulo'] ?? '') !== 'Próxima data' || empty($row['data_referencia'])) {
        failFast('Treinamento com agenda futura deve expor a próxima data na listagem');
    }
    ok('Listagem index expõe status resumido e data relevante');
} finally {
    Auth::logout();
    foreach ($agendaIds as $agendaId) {
        $pdo->prepare('DELETE FROM treinamentos_agenda WHERE id = :id')->execute(['id' => $agendaId]);
    }
    foreach ($treinamentoIds as $treinamentoId) {
        $pdo->prepare('DELETE FROM treinamentos WHERE id = :id')->execute(['id' => $treinamentoId]);
    }
    foreach ($departamentoIds as $departamentoId) {
        $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $departamentoId]);
    }
    foreach ($clienteIds as $clienteId) {
        $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);
    }
}

echo "treinamentos_index_pagination_model_smoke passed.\n";
