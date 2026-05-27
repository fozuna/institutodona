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
    ]);
    if ($rootId <= 0) {
        failFast('create() deve retornar id válido');
    }
    $eventIds[] = $rootId;
    ok('Criação: evento Reunião sem documentos (permitido)');

    $all = $model->byCronograma($cronogramaId);
    $found = array_values(array_filter($all, static fn(array $r): bool => (int)($r['id'] ?? 0) === $rootId));
    if (count($found) !== 1) {
        failFast('byCronograma() deve retornar o evento criado');
    }
    $row = $found[0];
    if (($row['tipo_evento'] ?? '') !== 'Reunião') {
        failFast('tipo_evento deve ser Reunião');
    }
    ok('Recuperação: tipo_evento persistido');

    $thrown = false;
    try {
        $model->setStatus($rootId, 'Finalizado');
    } catch (Throwable $e) {
        $thrown = true;
    }
    if (!$thrown) {
        failFast('Finalização de Reunião sem documentos deve falhar');
    }
    ok('Validação: finalizar Reunião sem documentos falha');

    $pdo->prepare('INSERT INTO cronograma_evento_anexos (evento_id, path, original_name, mime, size, sha256) VALUES (:e,:p,:n,:m,:s,:h)')
        ->execute([
            'e' => $rootId,
            'p' => '/tmp/' . $suffix . '_doc.pdf',
            'n' => 'doc.pdf',
            'm' => 'application/pdf',
            's' => 123,
            'h' => null,
        ]);
    ok('Anexo: documento persistido para o evento');

    $okStatus = $model->setStatus($rootId, 'Finalizado');
    if (!$okStatus) {
        failFast('Após anexar documento, finalização deve funcionar');
    }
    ok('Finalização: Reunião com documento pode ser finalizada');

    $view = file_get_contents(__DIR__ . '/../views/cronograma/add_evento.php');
    if ($view === false) {
        failFast('Não foi possível ler view add_evento.php');
    }
    if (strpos($view, 'id="cronogramaTipoEvento"') === false) {
        failFast('View deve conter seletor de tipo_evento');
    }
    if (strpos($view, 'name="anexos[]"') !== false) {
        failFast('View não deve exigir/anexar documentos na criação do evento');
    }
    ok('UI: criação não exige anexos para reunião');
} finally {
    try {
        if ($cronogramaId > 0) {
            $pdo->prepare('DELETE FROM cronograma_evento_anexos WHERE evento_id IN (SELECT id FROM cronograma_eventos WHERE id_cronograma = :id)')->execute(['id' => $cronogramaId]);
            $pdo->prepare('DELETE FROM cronograma_eventos WHERE id_cronograma = :id')->execute(['id' => $cronogramaId]);
            $pdo->prepare('DELETE FROM cronogramas WHERE id = :id')->execute(['id' => $cronogramaId]);
        }
        if ($clienteId > 0) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);
        }
    } catch (Throwable $e) {
    }
}
