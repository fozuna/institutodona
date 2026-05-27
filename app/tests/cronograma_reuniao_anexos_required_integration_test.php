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

$suffix = 'crono_anexos_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteId = 0;
$cronogramaId = 0;
$eventIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Cronograma ' . $suffix, 'c' => '33.333.333/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();

    Auth::login([
        'id' => 9030,
        'nome' => 'Teste Cronograma Anexos',
        'email' => 'cronograma.anexos.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $clienteId,
    ]);

    $pdo->prepare('INSERT INTO cronogramas (id_cliente, nome, ano) VALUES (:c,:n,:a)')
        ->execute(['c' => $clienteId, 'n' => 'Cronograma ' . $suffix, 'a' => (int)date('Y')]);
    $cronogramaId = (int)$pdo->lastInsertId();

    $model = new CronogramaEventoModel();

    try {
        $model->create($cronogramaId, [
            'data' => date('Y') . '-05-10',
            'periodicidade' => 'unico',
            'tipo_evento' => 'Reunião',
            'topico' => 'Pilar ' . $suffix,
            'unidade' => 'Departamento',
            'atividade' => 'Reunião sem anexos',
            'responsavel' => 'Responsável',
            'modelo' => 'Presencial',
            'status' => 'Planejado',
        ]);
        failFast('Reunião sem anexos deveria ser bloqueada');
    } catch (Throwable $e) {
        ok('Bloqueia criação de reunião sem anexos');
    }

    $taskId = $model->create($cronogramaId, [
        'data' => date('Y') . '-05-11',
        'periodicidade' => 'unico',
        'tipo_evento' => 'Tarefa',
        'topico' => 'Pilar ' . $suffix,
        'unidade' => 'Departamento',
        'atividade' => 'Tarefa sem anexos',
        'responsavel' => 'Responsável',
        'modelo' => 'Presencial',
        'status' => 'Planejado',
    ]);
    if ($taskId <= 0) {
        failFast('Evento não-reunião deveria ser criado sem anexos');
    }
    $eventIds[] = $taskId;
    ok('Permite criação de evento não-reunião sem anexos');

    $meetingId = $model->create($cronogramaId, [
        'data' => date('Y') . '-05-12',
        'periodicidade' => 'unico',
        'tipo_evento' => 'Reunião',
        'topico' => 'Pilar ' . $suffix,
        'unidade' => 'Departamento',
        'atividade' => 'Reunião com anexos',
        'responsavel' => 'Responsável',
        'modelo' => 'Presencial',
        'status' => 'Planejado',
        'anexos_count' => 2,
    ]);
    if ($meetingId <= 0) {
        failFast('Reunião com anexos_count deveria ser criada');
    }
    $eventIds[] = $meetingId;
    ok('Permite criação de reunião com anexos_count');

    $pdo->prepare('INSERT INTO cronograma_evento_anexos (evento_id, path, original_name, mime, size, sha256) VALUES (:e,:p,:n,:m,:s,:h)')
        ->execute([
            'e' => $meetingId,
            'p' => '/tmp/' . $suffix . '_1.pdf',
            'n' => 'doc1.pdf',
            'm' => 'application/pdf',
            's' => 123,
            'h' => null,
        ]);
    $pdo->prepare('INSERT INTO cronograma_evento_anexos (evento_id, path, original_name, mime, size, sha256) VALUES (:e,:p,:n,:m,:s,:h)')
        ->execute([
            'e' => $meetingId,
            'p' => '/tmp/' . $suffix . '_2.docx',
            'n' => 'doc2.docx',
            'm' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            's' => 456,
            'h' => null,
        ]);

    $list = $model->anexosList($meetingId);
    if (count($list) !== 2) {
        failFast('Deveria listar 2 anexos vinculados à reunião');
    }
    ok('Lista múltiplos anexos para reunião');

    $meeting = $model->find($meetingId);
    if (!$meeting) {
        failFast('Reunião deveria existir para atualização');
    }
    $okUpdate = $model->update($meetingId, [
        'data' => (string)$meeting['data'],
        'topico' => (string)$meeting['topico'],
        'unidade' => (string)($meeting['unidade'] ?? ''),
        'atividade' => 'Reunião atualizada ' . $suffix,
        'responsavel' => (string)($meeting['responsavel'] ?? ''),
        'modelo' => $meeting['modelo'] ?? null,
        'status' => (string)$meeting['status'],
    ], 'evento');
    if (!$okUpdate) {
        failFast('Atualização de reunião com anexos deveria funcionar');
    }
    ok('Atualiza reunião quando há anexos');

    $pdo->exec('UPDATE cronograma_evento_anexos SET deleted_at = NOW() WHERE evento_id = ' . (int)$meetingId);
    $meeting2 = $model->find($meetingId);
    if (!$meeting2) {
        failFast('Reunião deveria existir após soft delete de anexos');
    }
    try {
        $model->update($meetingId, [
            'data' => (string)$meeting2['data'],
            'topico' => (string)$meeting2['topico'],
            'unidade' => (string)($meeting2['unidade'] ?? ''),
            'atividade' => 'Reunião sem docs ' . $suffix,
            'responsavel' => (string)($meeting2['responsavel'] ?? ''),
            'modelo' => $meeting2['modelo'] ?? null,
            'status' => (string)$meeting2['status'],
        ], 'evento');
        failFast('Atualização de reunião sem anexos deveria ser bloqueada');
    } catch (Throwable $e) {
        ok('Bloqueia atualização de reunião sem anexos');
    }

    echo "cronograma_reuniao_anexos_required_integration_test passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if ($clienteId > 0 && !empty($eventIds)) {
        $pdo->exec('DELETE FROM cronograma_evento_anexos WHERE evento_id IN (' . implode(',', array_map('intval', $eventIds)) . ')');
        $pdo->exec('DELETE FROM cronograma_eventos WHERE id IN (' . implode(',', array_map('intval', $eventIds)) . ') OR evento_pai_id IN (' . implode(',', array_map('intval', $eventIds)) . ')');
    }
    if ($cronogramaId > 0) {
        $pdo->exec('DELETE FROM cronogramas WHERE id = ' . (int)$cronogramaId);
    }
    if ($clienteId > 0) {
        $pdo->exec('DELETE FROM clientes WHERE id = ' . (int)$clienteId);
    }
}

