<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\ManualModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Item 03: "Falha ao atualizar manual." ao editar SOMENTE os vínculos de
 * filial de um Manual existente, sem reenviar o documento. Causa raiz:
 * ManualModel::update() decidia sucesso por $stmt->rowCount() > 0 - um
 * UPDATE que casa a linha mas não muda nenhuma coluna (exatamente o cenário
 * "só mexi nas filiais") tem rowCount() = 0 no MySQL, mesmo sendo uma
 * operação válida. ManuaisController::update() dá exit() em TODO caminho de
 * sucesso (BaseController::redirect()), então os cenários de sucesso rodam
 * via subprocesso (helpers/manuais_update_probe.php - mesmo padrão de
 * cronograma_evento_probe.php/atas_probe.php/dashboard_probe.php).
 */

function runProbe(string $role, int $userId, array $allowed, int $id, int $empresaId, int $departamentoId, string $nome, string $descricao, string $filiaisCsv, bool $withFile = false, string $pdfPath = ''): array
{
    $probe = __DIR__ . '/helpers/manuais_update_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg((string)$userId) . ' '
        . escapeshellarg(implode(',', $allowed)) . ' '
        . escapeshellarg((string)$id) . ' '
        . escapeshellarg((string)$empresaId) . ' '
        . escapeshellarg((string)$departamentoId) . ' '
        . escapeshellarg($nome) . ' '
        . escapeshellarg($descricao) . ' '
        . escapeshellarg($filiaisCsv) . ' '
        . escapeshellarg($withFile ? '1' : '0') . ' '
        . escapeshellarg('1') . ' '
        . escapeshellarg($pdfPath);
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

function isSuccess(array $r): bool
{
    return strpos((string)$r['location'], 'manuais/index') !== false;
}

function linkedFiliais(\PDO $pdo, int $manualId): array
{
    $stmt = $pdo->prepare('SELECT filial_id FROM manual_filial_links WHERE manual_id = :mid ORDER BY filial_id');
    $stmt->execute(['mid' => $manualId]);
    return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
}

$pdo = Database::getConnection();
$suffix = 'manuallink_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$tmpFiles = [];

$makeCnpj = static function (): string {
    $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
};

try {
    $_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

    $clientes = new ClienteModel();
    $deps = new DepartamentoModel();
    $model = new ManualModel();

    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $insCli->execute(['n' => 'Matriz Link ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $matrizId = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Filial 1 Link ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 0, 'mid' => $matrizId]);
    $filial1Id = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Filial 2 Link ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 0, 'mid' => $matrizId]);
    $filial2Id = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Outra Matriz Fora ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $outraMatrizId = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Filial Nao Autorizada ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 0, 'mid' => $outraMatrizId]);
    $filialForaId = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Empresa Fora Tenant ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $empresaForaId = (int)$pdo->lastInsertId();
    $clienteIds = [$matrizId, $filial1Id, $filial2Id, $outraMatrizId, $filialForaId, $empresaForaId];

    $depId = $deps->create(['nome' => 'Dep Link ' . $suffix, 'cliente_id' => $matrizId]);
    $depForaId = $deps->create(['nome' => 'Dep Fora ' . $suffix, 'cliente_id' => $empresaForaId]);
    $depIds = [$depId, $depForaId];
    ok('Fixtures criadas: Matriz + 2 Filiais autorizadas + 1 Filial de outra matriz (não autorizada) + 1 Empresa totalmente fora do tenant');

    // Arquivo fisico real, para provar que ele nunca e tocado quando so os vinculos mudam.
    $dir = ManualModel::storageDirFor($matrizId, $depId, 'documentos');
    if (!is_dir($dir)) { mkdir($dir, 0775, true); }
    $originalContent = "%PDF-1.4\nCONTEUDO-ORIGINAL-" . $suffix . "\n%%EOF";
    $originalName = bin2hex(random_bytes(8)) . '.pdf';
    $originalAbsPath = $dir . '/' . $originalName;
    file_put_contents($originalAbsPath, $originalContent);
    $originalRelPath = 'storage/manuais/' . $matrizId . '/' . $depId . '/documentos/' . $originalName;
    $originalMtime = filemtime($originalAbsPath);

    $nomeOriginal = 'Manual Original ' . $suffix;
    $manualId = $model->create([
        'empresa_id' => $matrizId,
        'departamento_id' => $depId,
        'nome' => $nomeOriginal,
        'descricao' => 'Descrição original',
        'arquivo' => $originalRelPath,
        'tipo_arquivo' => 'pdf',
        'tamanho' => strlen($originalContent),
        'usuario_id' => 1,
    ]);
    if ($manualId <= 0) { failFast('Falha ao criar Manual de teste'); }

    $manualForaId = $model->create([
        'empresa_id' => $empresaForaId,
        'departamento_id' => $depForaId,
        'nome' => 'Manual Fora Tenant ' . $suffix,
        'descricao' => 'x',
        'arquivo' => 'storage/manuais/' . $empresaForaId . '/' . $depForaId . '/documentos/fora.pdf',
        'tipo_arquivo' => 'pdf',
        'tamanho' => 10,
        'usuario_id' => 1,
    ]);
    ok('Manual de teste criado (sem nenhuma filial vinculada) + Manual de outra empresa (fora do tenant do Cliente Admin de teste)');

    // ===================== CENÁRIO PRINCIPAL: só a filial muda, nenhum campo principal, sem novo upload =====================
    $r1 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, 'Descrição original', (string)$filial1Id);
    if (!isSuccess($r1)) {
        failFast('Cenário principal: editar SOMENTE a filial vinculada (sem mudar nome/descrição/empresa/departamento, sem novo upload) deveria ter sucesso. status=' . (string)$r1['status'] . ' location=' . $r1['location'] . ' body=' . $r1['body']);
    }
    if (linkedFiliais($pdo, $manualId) !== [$filial1Id]) {
        failFast('Cenário principal: Filial 1 deveria estar vinculada após o update');
    }
    if (!is_file($originalAbsPath) || file_get_contents($originalAbsPath) !== $originalContent) {
        failFast('Cenário principal: arquivo físico original não pode ser tocado quando não há novo upload');
    }
    if (filemtime($originalAbsPath) !== $originalMtime) {
        failFast('Cenário principal: arquivo físico não deveria ter sido regravado (mtime mudou)');
    }
    $row = $model->find($manualId);
    if ((string)$row['arquivo'] !== $originalRelPath || (string)$row['tipo_arquivo'] !== 'pdf' || (int)$row['tamanho'] !== strlen($originalContent)) {
        failFast('Cenário principal: arquivo/tipo_arquivo/tamanho deveriam permanecer exatamente os mesmos no banco');
    }
    ok('Cenário PRINCIPAL (bug reproduzido e corrigido): alterar somente a filial vinculada, sem tocar em nenhum campo principal e sem novo upload, tem sucesso; arquivo físico e metadados permanecem intactos');

    // ===================== Cenário 2: editar somente Descrição, sem novo upload =====================
    $novaDescricao = 'Descrição atualizada ' . $suffix;
    $r2 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, (string)$filial1Id);
    if (!isSuccess($r2)) { failFast('Cenário 2: editar somente a Descrição deveria ter sucesso'); }
    $row = $model->find($manualId);
    if ((string)$row['descricao'] !== $novaDescricao) { failFast('Cenário 2: descrição não foi persistida'); }
    ok('Cenário 2: editar somente a Descrição (sem novo upload) tem sucesso e persiste');

    // ===================== Cenário 3: remover todas as filiais =====================
    $r3 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, '');
    if (!isSuccess($r3)) { failFast('Cenário 3: remover todas as filiais deveria ter sucesso'); }
    if (linkedFiliais($pdo, $manualId) !== []) { failFast('Cenário 3: nenhuma filial deveria continuar vinculada'); }
    ok('Cenário 3: remover todas as filiais vinculadas funciona corretamente (sem falso erro)');

    // ===================== Cenário 4: adicionar uma filial (sem alterar campos principais) =====================
    $r4 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, (string)$filial1Id);
    if (!isSuccess($r4) || linkedFiliais($pdo, $manualId) !== [$filial1Id]) {
        failFast('Cenário 4: adicionar Filial 1 deveria ter sucesso e vinculá-la');
    }
    ok('Cenário 4: adicionar uma filial (sem alteração de campos principais) funciona');

    // ===================== Cenário 5: trocar Filial 1 por Filial 2 =====================
    $r5 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, (string)$filial2Id);
    if (!isSuccess($r5) || linkedFiliais($pdo, $manualId) !== [$filial2Id]) {
        failFast('Cenário 5: trocar Filial 1 por Filial 2 deveria resultar em somente a Filial 2 vinculada');
    }
    if ($model->isLinkedToFilial($manualId, $filial1Id)) { failFast('Cenário 5/20: Filial 1 não deveria mais estar vinculada'); }
    if (!$model->isLinkedToFilial($manualId, $filial2Id)) { failFast('Cenário 5/20: Filial 2 deveria estar vinculada'); }
    ok('Cenário 5/20: trocar de filial funciona e isLinkedToFilial() reflete corretamente quem passa a acessar o Manual');

    // ===================== Cenário 6: adicionar múltiplas filiais =====================
    $r6 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, $filial1Id . ',' . $filial2Id);
    if (!isSuccess($r6) || linkedFiliais($pdo, $manualId) !== [min($filial1Id, $filial2Id), max($filial1Id, $filial2Id)]) {
        failFast('Cenário 6: vincular as duas filiais de uma vez deveria funcionar');
    }
    ok('Cenário 6: adicionar múltiplas filiais de uma vez funciona');

    // ===================== Cenário 7: salvar sem nenhuma alteração (nem campos, nem filiais) =====================
    $r7 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, $filial1Id . ',' . $filial2Id);
    if (!isSuccess($r7)) { failFast('Cenário 7: salvar sem nenhuma alteração não pode gerar falso erro'); }
    ok('Cenário 7: salvar sem nenhuma alteração (nome/descrição/filiais idênticos) não gera falso erro');

    // ===================== Cenário 8: filial não autorizada é ignorada =====================
    $r8 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, $filial1Id . ',' . $filialForaId);
    if (!isSuccess($r8)) { failFast('Cenário 8: request com filial não autorizada misturada não pode falhar por completo'); }
    $linked = linkedFiliais($pdo, $manualId);
    if (in_array($filialForaId, $linked, true)) { failFast('Cenário 8: filial de outra matriz não pode ser vinculada por manipulação de POST'); }
    if (!in_array($filial1Id, $linked, true)) { failFast('Cenário 8: Filial 1 (válida, enviada junto) deveria continuar vinculada'); }
    ok('Cenário 8/18: filial não autorizada (de outra matriz) é ignorada; filial válida enviada junto continua sendo aplicada');

    // ===================== Cenário 9: novo upload continua funcionando =====================
    $newContent = "%PDF-1.4\nCONTEUDO-NOVO-" . $suffix . "\n%%EOF";
    $newTmp = sys_get_temp_dir() . '/' . uniqid('manual_probe_', true) . '.pdf';
    file_put_contents($newTmp, $newContent);
    $tmpFiles[] = $newTmp;
    $r9 = runProbe('instituto', 1, [], $manualId, $matrizId, $depId, $nomeOriginal, $novaDescricao, $filial1Id . ',' . $filial2Id, true, $newTmp);
    if (!isSuccess($r9)) { failFast('Cenário 9/13: novo upload deveria continuar funcionando. status=' . (string)$r9['status'] . ' body=' . $r9['body']); }
    $row = $model->find($manualId);
    $newAbsPath = dirname(__DIR__, 2) . '/' . ltrim((string)$row['arquivo'], '/');
    if ((string)$row['arquivo'] === $originalRelPath) { failFast('Cenário 9/13: caminho do arquivo deveria mudar após novo upload'); }
    if (!is_file($newAbsPath) || file_get_contents($newAbsPath) !== $newContent) { failFast('Cenário 9/13: novo arquivo deveria estar salvo com o novo conteúdo'); }
    if (is_file($originalAbsPath)) { failFast('Cenário 9: arquivo antigo deveria ter sido removido após a substituição bem-sucedida'); }
    ok('Cenário 9/13/19: novo upload continua funcionando - arquivo antigo removido só após sucesso confirmado, novo arquivo íntegro e acessível (equivalente a um download funcional)');

    // ===================== Cenário 10: Manual inexistente =====================
    $r10 = runProbe('instituto', 1, [], 999999999, $matrizId, $depId, 'x', 'x', '');
    if ((int)$r10['status'] !== 404) { failFast('Cenário 10: Manual inexistente deveria falhar com 404. status=' . (string)$r10['status']); }
    ok('Cenário 10: Manual inexistente falha com segurança (404), sem alterar nada');

    // ===================== Cenário 11: Manual de outro tenant (Cliente Admin sem acesso) =====================
    $r11 = runProbe('cliente_admin', 501, [$matrizId, $filial1Id, $filial2Id], $manualForaId, $empresaForaId, $depForaId, 'Tentativa', 'x', '');
    if ((int)$r11['status'] !== 404) { failFast('Cenário 11: Cliente Admin não deveria conseguir editar Manual de empresa fora do seu tenant. status=' . (string)$r11['status']); }
    $rowFora = $model->find($manualForaId); // como Instituto (sessão atual), então este find() enxerga
    ok('Cenário 11/17: Manual de outro tenant é bloqueado (404 oculto) para Cliente Admin sem acesso a essa empresa');

    // ===================== Cenários 14/15: erro durante os vínculos → rollback completo (Model, subclasse de teste) =====================
    $beforeRollbackRow = $model->find($manualId);
    $beforeRollbackLinks = linkedFiliais($pdo, $manualId);
    $failingModel = new class extends ManualModel {
        public function replaceFilialLinks(int $manualId, array $filialIds): void
        {
            // Simula uma falha DEPOIS de já ter apagado os vínculos antigos -
            // exatamente o ponto que a transação precisa desfazer por completo.
            $this->db->prepare('DELETE FROM manual_filial_links WHERE manual_id = :mid')->execute(['mid' => $manualId]);
            throw new \RuntimeException('Falha proposital nos vínculos (teste de rollback)');
        }
    };
    $rollbackOk = $failingModel->updateWithFilialLinks($manualId, [
        'empresa_id' => $matrizId,
        'departamento_id' => $depId,
        'nome' => 'NOME_QUE_NAO_PODE_PERSISTIR_' . $suffix,
        'descricao' => 'NAO_PODE_PERSISTIR',
        'arquivo' => (string)$beforeRollbackRow['arquivo'],
        'tipo_arquivo' => (string)$beforeRollbackRow['tipo_arquivo'],
        'tamanho' => (int)$beforeRollbackRow['tamanho'],
    ], [$filial1Id]);
    if ($rollbackOk !== false) { failFast('Cenário 14: updateWithFilialLinks() deveria retornar false quando os vínculos falham no meio do caminho'); }
    $afterRollbackRow = $model->find($manualId);
    if ((string)$afterRollbackRow['nome'] !== (string)$beforeRollbackRow['nome']) {
        failFast('Cenário 14: nome do Manual não pode ter sido alterado após rollback (a alteração do UPDATE também deveria ter sido desfeita)');
    }
    if (linkedFiliais($pdo, $manualId) !== $beforeRollbackLinks) {
        failFast('Cenário 15: vínculos anteriores deveriam continuar exatamente os mesmos após o rollback');
    }
    ok('Cenário 14/15: falha proposital durante a atualização dos vínculos desfaz TUDO (Manual e vínculos) via transação - nada fica parcialmente aplicado');

    echo "manuais_vincular_filiais_sem_reenvio_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
} finally {
    if (!empty($manualId)) {
        $manualRow = $model->find($manualId) ?? [];
        $absPath = !empty($manualRow['arquivo']) ? dirname(__DIR__, 2) . '/' . ltrim((string)$manualRow['arquivo'], '/') : null;
        $pdo->exec('DELETE FROM manual_filial_links WHERE manual_id = ' . (int)$manualId);
        $pdo->exec('DELETE FROM manuais WHERE id = ' . (int)$manualId);
        if ($absPath && is_file($absPath)) { @unlink($absPath); }
    }
    if (!empty($manualForaId)) {
        $pdo->exec('DELETE FROM manuais WHERE id = ' . (int)$manualForaId);
    }
    if (is_file($originalAbsPath ?? '')) { @unlink($originalAbsPath); }
    foreach ($tmpFiles as $f) { if (is_file($f)) { @unlink($f); } }
    if (!empty($depIds)) {
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', $depIds)) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
    unset($_SESSION['user']);
}
