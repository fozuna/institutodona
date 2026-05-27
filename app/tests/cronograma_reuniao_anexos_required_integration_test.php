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

    $meetingNoDocsId = $model->create($cronogramaId, [
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
    if ($meetingNoDocsId <= 0) {
        failFast('Reunião sem anexos deveria ser criada (anexos só no encerramento)');
    }
    ok('Permite criação de reunião sem anexos');

    $thrown = false;
    try {
        $model->setStatus($meetingNoDocsId, 'Finalizado');
    } catch (Throwable $e) {
        $thrown = true;
    }
    if (!$thrown) {
        failFast('Encerramento de reunião sem anexos deveria ser bloqueado');
    }
    ok('Bloqueia encerramento de reunião sem anexos');

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
    ]);
    if ($meetingId <= 0) {
        failFast('Reunião deveria ser criada');
    }
    ok('Permite criação de reunião (múltiplos anexos no encerramento)');

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

    $okFinal = $model->setStatus($meetingId, 'Finalizado');
    if (!$okFinal) {
        failFast('Encerramento de reunião com anexos deveria funcionar');
    }
    ok('Permite encerrar reunião com anexos');

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
        $model->setStatus($meetingId, 'Pendente');
        $model->update($meetingId, [
            'data' => (string)$meeting2['data'],
            'topico' => (string)$meeting2['topico'],
            'unidade' => (string)($meeting2['unidade'] ?? ''),
            'atividade' => 'Reunião sem docs ' . $suffix,
            'responsavel' => (string)($meeting2['responsavel'] ?? ''),
            'modelo' => $meeting2['modelo'] ?? null,
            'status' => 'Finalizado',
        ], 'evento');
        failFast('Atualização de reunião sem anexos deveria ser bloqueada');
    } catch (Throwable $e) {
        ok('Bloqueia encerramento via update() quando não há anexos');
    }

    $seriesRoot = $model->create($cronogramaId, [
        'data' => date('Y') . '-06-01',
        'periodicidade' => 'mensal',
        'tipo_evento' => 'Reunião',
        'topico' => 'Pilar ' . $suffix,
        'unidade' => 'Departamento',
        'atividade' => 'Reunião recorrente',
        'responsavel' => 'Responsável',
        'modelo' => 'Online',
        'status' => 'Planejado',
    ]);
    if ($seriesRoot <= 0) {
        failFast('Reunião mensal deveria ser criada');
    }
    $members = $model->seriesMembers($seriesRoot);
    if (count($members) < 2) {
        failFast('Série mensal deveria gerar ao menos 2 ocorrências');
    }
    usort($members, static fn(array $a, array $b): int => strcmp((string)($a['data'] ?? ''), (string)($b['data'] ?? '')));
    $first = (int)($members[0]['id'] ?? 0);
    $second = (int)($members[1]['id'] ?? 0);
    if ($first <= 0 || $second <= 0) {
        failFast('Ocorrências da série deveriam ter IDs válidos');
    }
    $pdo->prepare('INSERT INTO cronograma_evento_anexos (evento_id, path, original_name, mime, size, sha256) VALUES (:e,:p,:n,:m,:s,:h)')
        ->execute([
            'e' => $first,
            'p' => '/tmp/' . $suffix . '_serie_1.pdf',
            'n' => 'serie_doc1.pdf',
            'm' => 'application/pdf',
            's' => 100,
            'h' => null,
        ]);
    $l1 = $model->anexosList($first);
    $l2 = $model->anexosList($second);
    if (count($l1) !== 1 || count($l2) !== 0) {
        failFast('Anexos devem permanecer isolados por ocorrência (sem replicação na série)');
    }
    ok('Isolamento: anexos não são replicados em eventos recorrentes');

    echo "cronograma_reuniao_anexos_required_integration_test passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if ($cronogramaId > 0) {
        $pdo->prepare('DELETE FROM cronograma_evento_anexos WHERE evento_id IN (SELECT id FROM cronograma_eventos WHERE id_cronograma = :id)')->execute(['id' => $cronogramaId]);
        $pdo->prepare('DELETE FROM cronograma_eventos WHERE id_cronograma = :id')->execute(['id' => $cronogramaId]);
        $pdo->prepare('DELETE FROM cronogramas WHERE id = :id')->execute(['id' => $cronogramaId]);
    }
    if ($clienteId > 0) {
        $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);
    }
}
