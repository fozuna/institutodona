<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\CronogramaEventoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = null;
try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "SKIP: sem conexão com o banco para testes de integração do cronograma.\n";
    exit(0);
}
$suffix = 'crono_evt_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteId = 0;
$cronogramaId = 0;
$eventIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Cronograma ' . $suffix, 'c' => '22.222.222/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();

    Auth::login([
        'id' => 9020,
        'nome' => 'Teste Cronograma',
        'email' => 'cronograma.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $clienteId,
    ]);

    $pdo->prepare('INSERT INTO cronogramas (id_cliente, nome, ano) VALUES (:c,:n,:a)')
        ->execute(['c' => $clienteId, 'n' => 'Cronograma ' . $suffix, 'a' => (int)date('Y')]);
    $cronogramaId = (int)$pdo->lastInsertId();

    $model = new CronogramaEventoModel();

    $bad = CronogramaEventoModel::validateAtaUpload('x.png', 10, 'image/png', 50 * 1024 * 1024);
    if (!empty($bad['ok'])) {
        failFast('Ata não deve permitir imagens');
    }
    ok('Validação: ata rejeita imagens');

    $thrown = false;
    try {
        $model->create($cronogramaId, [
            'data' => date('Y') . '-05-14',
            'periodicidade' => 'unico',
            'tipo_evento' => 'Reunião',
            'topico' => 'Pilar ' . $suffix,
            'unidade' => 'Depto',
            'atividade' => 'Atividade',
            'responsavel' => 'Resp',
            'modelo' => 'Online',
            'status' => 'Planejado',
        ]);
    } catch (Throwable $e) {
        $thrown = true;
    }
    if (!$thrown) {
        failFast('Reunião sem ata deve falhar');
    }
    ok('Validação: Reunião exige ata');

    $rootId = $model->create($cronogramaId, [
        'data' => date('Y') . '-05-15',
        'periodicidade' => 'unico',
        'tipo_evento' => 'Reunião',
        'topico' => 'Pilar ' . $suffix,
        'unidade' => 'Depto',
        'atividade' => 'Atividade',
        'responsavel' => 'Resp',
        'modelo' => 'Online',
        'status' => 'Planejado',
        'ata_path' => __DIR__ . '/dummy_ata_' . $suffix . '.pdf',
        'ata_original_name' => 'ata_' . $suffix . '.pdf',
        'ata_mime' => 'application/pdf',
        'ata_size' => 123,
        'ata_sha256' => str_repeat('a', 64),
    ]);
    if ($rootId <= 0) {
        failFast('create() deve retornar id válido');
    }
    $eventIds[] = $rootId;
    ok('Criação: evento Reunião com ata');

    $all = $model->byCronograma($cronogramaId);
    $found = array_values(array_filter($all, static fn(array $r): bool => (int)($r['id'] ?? 0) === $rootId));
    if (count($found) !== 1) {
        failFast('byCronograma() deve retornar o evento criado');
    }
    $row = $found[0];
    if (($row['tipo_evento'] ?? '') !== 'Reunião') {
        failFast('tipo_evento deve ser Reunião');
    }
    if (($row['ata_original_name'] ?? '') === '') {
        failFast('ata_original_name deve estar preenchido');
    }
    ok('Recuperação: tipo_evento e metadados da ata persistidos');

    $view = file_get_contents(__DIR__ . '/../views/cronograma/add_evento.php');
    if ($view === false) {
        failFast('Não foi possível ler view add_evento.php');
    }
    if (strpos($view, 'id="cronogramaTipoEvento"') === false) {
        failFast('View deve conter seletor de tipo_evento');
    }
    if (strpos($view, 'id="cronogramaAtaWrap"') === false) {
        failFast('View deve conter bloco condicional de ata');
    }
    ok('UI: view contém seletor de tipo e bloco de ata');
} finally {
    try {
        if ($cronogramaId > 0) {
            $pdo->prepare('DELETE FROM cronograma_eventos WHERE id_cronograma = :id')->execute(['id' => $cronogramaId]);
            $pdo->prepare('DELETE FROM cronogramas WHERE id = :id')->execute(['id' => $cronogramaId]);
        }
        if ($clienteId > 0) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);
        }
    } catch (Throwable $e) {
    }
}
