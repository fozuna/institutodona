<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\CronogramaEventoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Item 05A: o campo de upload da ata de uma Reunião ficava com a classe CSS
 * "hidden" fixa em evento_drawer.php, só removida via JS quando o <select>
 * de Status chegava a valer "Finalizado" - e o botão "Encerrar reunião"
 * setava esse valor e submetia o form no mesmo instante, sem o usuário
 * conseguir usar o upload antes da primeira tentativa (que falhava por
 * falta de anexo). Este teste comprova que a área de upload já vem visível
 * no primeiro carregamento do drawer, sem depender de um erro prévio.
 */

function runProbe(string $method, string $role, int $userId, ?int $idCliente, string $action, int $eventoId, int $idCronograma, string $escopo = 'evento', array $extra = [], bool $withCsrf = true): array
{
    $probe = __DIR__ . '/helpers/cronograma_evento_probe.php';
    $extraB64 = base64_encode(json_encode($extra, JSON_UNESCAPED_UNICODE));
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg($method) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg((string)$userId) . ' '
        . escapeshellarg($idCliente !== null ? (string)$idCliente : '') . ' '
        . escapeshellarg($action) . ' '
        . escapeshellarg((string)$eventoId) . ' '
        . escapeshellarg((string)$idCronograma) . ' '
        . escapeshellarg($escopo) . ' '
        . escapeshellarg($extraB64) . ' '
        . escapeshellarg($withCsrf ? '1' : '0');
    $out = [];
    exec($cmd . ' 2>&1', $out);
    $raw = implode("\n", $out);
    $marker = '---PROBE_RESULT---';
    $pos = strpos($raw, $marker);
    $body = $pos !== false ? substr($raw, 0, $pos) : $raw;
    $resultLine = $pos !== false ? trim(substr($raw, $pos + strlen($marker))) : '';
    $decoded = json_decode($resultLine, true);
    return [
        'body' => $body,
        'status' => is_array($decoded) ? ($decoded['status'] ?? null) : null,
        'location' => is_array($decoded) ? ($decoded['location'] ?? '') : '',
    ];
}

$pdo = Database::getConnection();
$suffix = 'crono_ata_ui_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$cronogramaIds = [];

try {
    // ===================== PARTE 1: código-fonte (padrão auditoria_dropdown_unit_test.php) =====================
    $drawerSrc = file_get_contents(__DIR__ . '/../views/cronograma/evento_drawer.php');
    $showSrc = file_get_contents(__DIR__ . '/../views/cronograma/show.php');
    if ($drawerSrc === false || $showSrc === false) {
        failFast('Não foi possível ler os arquivos de origem do Cronograma');
    }

    $sectionPos = strpos($drawerSrc, 'data-cronograma-reuniao-anexos');
    if ($sectionPos === false) {
        failFast('Seção de anexos de Reunião não encontrada em evento_drawer.php');
    }
    $sectionTagStart = strrpos(substr($drawerSrc, 0, $sectionPos), '<section');
    $sectionTag = substr($drawerSrc, $sectionTagStart, $sectionPos - $sectionTagStart);
    if (strpos($sectionTag, 'hidden') !== false) {
        failFast('A seção de anexos ainda carrega a classe "hidden" fixa no HTML - causa raiz do bug não corrigida');
    }
    ok('evento_drawer.php: seção de anexos não nasce mais com "hidden" fixo no HTML');

    if (strpos($showSrc, 'syncReuniaoBlock') !== false) {
        failFast('JS ainda contém syncReuniaoBlock() (lógica de esconder o upload até status=Finalizado)');
    }
    ok('show.php: lógica que escondia o upload até o status virar "Finalizado" foi removida');

    // ===================== PARTE 2: fixtures =====================
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Cronograma Ata UI A ' . $suffix, 'c' => '44.444.4' . substr($suffix, -2) . '/0001-44', 't' => 'Contato']);
    $clienteAId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteAId;

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Cronograma Ata UI B ' . $suffix, 'c' => '55.555.5' . substr($suffix, -2) . '/0001-55', 't' => 'Contato']);
    $clienteBId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteBId;

    Auth::login(['id' => 9040, 'nome' => 'Instituto Cronograma UI', 'email' => 'instituto.cronogramaui@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);

    $pdo->prepare('INSERT INTO cronogramas (id_cliente, nome, ano) VALUES (:c,:n,:a)')
        ->execute(['c' => $clienteAId, 'n' => 'Cronograma A ' . $suffix, 'a' => (int)date('Y')]);
    $cronogramaAId = (int)$pdo->lastInsertId();
    $cronogramaIds[] = $cronogramaAId;

    $pdo->prepare('INSERT INTO cronogramas (id_cliente, nome, ano) VALUES (:c,:n,:a)')
        ->execute(['c' => $clienteBId, 'n' => 'Cronograma B ' . $suffix, 'a' => (int)date('Y')]);
    $cronogramaBId = (int)$pdo->lastInsertId();
    $cronogramaIds[] = $cronogramaBId;

    $model = new CronogramaEventoModel();
    $reuniaoAbertaId = $model->create($cronogramaAId, [
        'data' => date('Y') . '-05-15', 'periodicidade' => 'unico', 'tipo_evento' => 'Reunião',
        'topico' => 'Pilar ' . $suffix, 'unidade' => 'Departamento', 'atividade' => 'Reunião aberta ' . $suffix,
        'responsavel' => 'Responsável', 'modelo' => 'Presencial', 'status' => 'Planejado',
    ]);
    $tarefaId = $model->create($cronogramaAId, [
        'data' => date('Y') . '-05-16', 'periodicidade' => 'unico', 'tipo_evento' => 'Tarefa',
        'topico' => 'Pilar ' . $suffix, 'unidade' => 'Departamento', 'atividade' => 'Tarefa ' . $suffix,
        'responsavel' => 'Responsável', 'modelo' => 'Presencial', 'status' => 'Planejado',
    ]);
    $reuniaoFinalizadaId = $model->create($cronogramaAId, [
        'data' => date('Y') . '-05-17', 'periodicidade' => 'unico', 'tipo_evento' => 'Reunião',
        'topico' => 'Pilar ' . $suffix, 'unidade' => 'Departamento', 'atividade' => 'Reunião a finalizar ' . $suffix,
        'responsavel' => 'Responsável', 'modelo' => 'Presencial', 'status' => 'Planejado',
    ]);
    if ($reuniaoAbertaId <= 0 || $tarefaId <= 0 || $reuniaoFinalizadaId <= 0) {
        failFast('Falha ao criar eventos de teste');
    }
    $pdo->prepare('INSERT INTO cronograma_evento_anexos (evento_id, path, original_name, mime, size, sha256) VALUES (:e,:p,:n,:m,:s,:h)')
        ->execute(['e' => $reuniaoFinalizadaId, 'p' => '/tmp/' . $suffix . '.pdf', 'n' => 'ata.pdf', 'm' => 'application/pdf', 's' => 100, 'h' => null]);
    $okFinal = $model->setStatus($reuniaoFinalizadaId, 'Finalizado');
    if (!$okFinal) { failFast('Falha ao preparar reunião finalizada de teste'); }
    ok('Fixtures criadas: Reunião aberta, Tarefa, Reunião já finalizada (Cliente A) e Cronograma isolado do Cliente B');

    // ===================== CENÁRIOS 1/2: upload visível no primeiro carregamento (sem precisar de erro antes) =====================
    // eventoDrawer() so chama http_response_code() explicitamente nos caminhos de erro
    // (404/403/...); no caminho de sucesso o codigo fica "nao definido" (false no SAPI
    // CLI). Por isso o sucesso e verificado pela ausencia de um codigo de erro conhecido.
    $r1 = runProbe('GET', 'instituto', 9040, null, 'eventoDrawer', $reuniaoAbertaId, $cronogramaAId);
    if (in_array((int)$r1['status'], [400, 403, 404, 422], true)) {
        failFast('Cenário 1: drawer da reunião aberta deveria carregar normalmente. status=' . (string)$r1['status'] . ' body=' . $r1['body']);
    }
    if (strpos($r1['body'], 'data-cronograma-reuniao-anexos') === false) {
        failFast('Cenário 1: seção de anexos não foi renderizada para reunião aberta');
    }
    $sectionStart = strpos($r1['body'], '<section');
    $sectionStart = strpos($r1['body'], 'data-cronograma-reuniao-anexos');
    $tagStart = strrpos(substr($r1['body'], 0, $sectionStart), '<section');
    $tag = substr($r1['body'], $tagStart, $sectionStart - $tagStart);
    if (strpos($tag, 'hidden') !== false) {
        failFast('Cenário 1/2: seção de anexos ainda é renderizada com "hidden" - upload não está visível no primeiro carregamento');
    }
    if (strpos($r1['body'], 'name="anexos[]"') === false) {
        failFast('Cenário 1: campo de upload (anexos[]) não está presente/disponível no HTML');
    }
    ok('Cenário 1/2: ao abrir uma reunião ainda não finalizada, a área de upload já vem visível - sem precisar tentar finalizar antes');

    // ===================== CENÁRIO 10: reabrir o drawer mantém o mesmo comportamento =====================
    $r1b = runProbe('GET', 'instituto', 9040, null, 'eventoDrawer', $reuniaoAbertaId, $cronogramaAId);
    $sectionStart2 = strpos($r1b['body'], 'data-cronograma-reuniao-anexos');
    $tagStart2 = $sectionStart2 !== false ? strrpos(substr($r1b['body'], 0, $sectionStart2), '<section') : false;
    $tag2 = ($tagStart2 !== false) ? substr($r1b['body'], $tagStart2, $sectionStart2 - $tagStart2) : '';
    if ($sectionStart2 === false || strpos($tag2, 'hidden') !== false) {
        failFast('Cenário 10: reabrir o drawer da mesma reunião deveria manter a área de upload visível');
    }
    ok('Cenário 10: reabrir o drawer (nova requisição) mantém a área de upload visível de forma consistente');

    // ===================== CENÁRIO 9: outros tipos de evento não mostram a área de ata =====================
    $r2 = runProbe('GET', 'instituto', 9040, null, 'eventoDrawer', $tarefaId, $cronogramaAId);
    if (strpos($r2['body'], 'data-cronograma-reuniao-anexos') !== false) {
        failFast('Cenário 9: evento do tipo Tarefa não deveria renderizar a área de anexos de Reunião');
    }
    ok('Cenário 9: eventos que não são Reunião continuam sem a área de documentos (sem regressão)');

    // Regressão complementar: reunião já finalizada também não deve mais oferecer upload.
    $r3 = runProbe('GET', 'instituto', 9040, null, 'eventoDrawer', $reuniaoFinalizadaId, $cronogramaAId);
    if (strpos($r3['body'], 'data-cronograma-reuniao-anexos') !== false) {
        failFast('Reunião já finalizada não deveria mais oferecer a área de upload de anexos');
    }
    ok('Regressão: reunião já finalizada não oferece mais upload (condição !isFinalizado preservada)');

    // ===================== CENÁRIO 8: RBAC continua protegido =====================
    $r4 = runProbe('GET', 'cliente', 9041, $clienteAId, 'eventoDrawer', $reuniaoAbertaId, $cronogramaAId);
    if ((int)$r4['status'] !== 404) {
        failFast('Cenário 8: perfil "cliente" (sem requireClienteAdminAccess) deveria ser bloqueado com 404 oculto. status=' . (string)$r4['status']);
    }
    ok('Cenário 8: RBAC continua protegido - perfil sem permissão de gestão não acessa o drawer do evento');

    // ===================== CENÁRIO 7: tenant continua protegido =====================
    $r5 = runProbe('GET', 'cliente_admin', 9042, $clienteAId, 'eventoDrawer', $reuniaoAbertaId, $cronogramaAId);
    if ((int)$r5['status'] === 404) {
        failFast('Cenário 7 (controle): Cliente Admin do próprio tenant (Cliente A) deveria acessar normalmente. status=' . (string)$r5['status']);
    }
    $eventoClienteB = $model->create($cronogramaBId, [
        'data' => date('Y') . '-05-18', 'periodicidade' => 'unico', 'tipo_evento' => 'Reunião',
        'topico' => 'Pilar ' . $suffix, 'unidade' => 'Departamento', 'atividade' => 'Reunião Cliente B ' . $suffix,
        'responsavel' => 'Responsável', 'modelo' => 'Presencial', 'status' => 'Planejado',
    ]);
    $r6 = runProbe('GET', 'cliente_admin', 9042, $clienteAId, 'eventoDrawer', $eventoClienteB, $cronogramaBId);
    if ((int)$r6['status'] !== 404) {
        failFast('Cenário 7: Cliente Admin do Cliente A não deveria acessar evento do Cliente B. status=' . (string)$r6['status']);
    }
    ok('Cenário 7: tenant continua protegido - Cliente Admin de uma empresa não acessa evento de outra empresa');

    // ===================== CENÁRIO 6: documento inválido continua rejeitado (validação reaproveitada) =====================
    $invalidExt = CronogramaEventoModel::validateReuniaoDocumentoUpload('malware.exe', 100, 'application/octet-stream');
    if (!empty($invalidExt['ok'])) {
        failFast('Cenário 6: extensão não permitida (.exe) deveria ser rejeitada');
    }
    $tooLarge = CronogramaEventoModel::validateReuniaoDocumentoUpload('ata.pdf', 25 * 1024 * 1024, 'application/pdf');
    if (!empty($tooLarge['ok'])) {
        failFast('Cenário 6: arquivo acima de 20MB deveria ser rejeitado');
    }
    $wrongMime = CronogramaEventoModel::validateReuniaoDocumentoUpload('ata.pdf', 100, 'text/plain');
    if (!empty($wrongMime['ok'])) {
        failFast('Cenário 6: MIME incompatível com a extensão declarada deveria ser rejeitado (não confia só na extensão)');
    }
    $valid = CronogramaEventoModel::validateReuniaoDocumentoUpload('ata.pdf', 100, 'application/pdf');
    if (empty($valid['ok'])) {
        failFast('Cenário 6 (controle): arquivo PDF válido deveria ser aceito pela validação');
    }
    ok('Cenário 6: validação de documento continua rejeitando extensão inválida, tamanho excedido e MIME incompatível (não confia só na extensão)');

    echo "cronograma_reuniao_upload_visivel_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($cronogramaIds)) {
        $in = implode(',', array_map('intval', $cronogramaIds));
        $pdo->exec("DELETE FROM cronograma_evento_anexos WHERE evento_id IN (SELECT id FROM cronograma_eventos WHERE id_cronograma IN ($in))");
        $pdo->exec("DELETE FROM cronograma_eventos WHERE id_cronograma IN ($in)");
        $pdo->exec("DELETE FROM cronogramas WHERE id IN ($in)");
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
