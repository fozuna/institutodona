<?php
require __DIR__ . '/../autoload.php';

use App\Core\AccessControl;
use App\Core\XlsxExport;
use App\Database\Database;
use App\Services\ColaboradorImportService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$readerUser = [
    'id' => 2,
    'nome' => 'Reader',
    'email' => 'reader@example.com',
    'tipo_acesso' => 'reader',
    'allowed_client_ids' => [],
];
if (!AccessControl::canAccessRoute('colaboradores/importTemplate', 'GET', $readerUser)) {
    failFast('RBAC: reader deveria conseguir baixar o modelo (GET colaboradores/importTemplate).');
}
ok('RBAC download liberado para reader');

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$empresaNome = 'Unidade Modelo ' . $suffix;

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute([
    'nome' => $empresaNome,
    'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
    'contato' => 'Modelo Import',
]);
$empresaId = (int)$pdo->lastInsertId();
if ($empresaId <= 0) {
    failFast('Falha ao criar cliente');
}
ok('Cliente criado');

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Cliente Admin',
    'email' => 'cliente_admin@example.com',
    'tipo_acesso' => 'cliente_admin',
    'allowed_client_ids' => [$empresaId],
];

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colab_template_' . $suffix;
@mkdir($tmpDir, 0775, true);

$emails = [];
$createdDept = '';
$createdSetor = '';
$createdFuncao = '';
$filesToDelete = [];

register_shutdown_function(function() use (&$pdo, &$empresaId, &$emails, &$createdFuncao, &$createdSetor, &$createdDept, &$filesToDelete, &$tmpDir) {
    try {
        if (!empty($emails)) {
            $inList = implode(',', array_fill(0, count($emails), '?'));
            $del = $pdo->prepare("DELETE FROM colaboradores WHERE email IN ($inList)");
            $del->execute($emails);
        }
        if ($createdFuncao !== '') { $pdo->prepare('DELETE FROM funcoes WHERE nome = :n')->execute(['n' => $createdFuncao]); }
        if ($createdSetor !== '') { $pdo->prepare('DELETE FROM setores WHERE nome = :n')->execute(['n' => $createdSetor]); }
        if ($createdDept !== '' && $empresaId > 0) { $pdo->prepare('DELETE FROM departamentos WHERE nome = :n AND cliente_id = :cid')->execute(['n' => $createdDept, 'cid' => $empresaId]); }
        if ($empresaId > 0) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $empresaId]); }
    } catch (\Throwable $e) {}
    foreach ($filesToDelete as $p) { @unlink($p); }
    if (!empty($tmpDir)) { @rmdir($tmpDir); }
});

$svc = new ColaboradorImportService($pdo);

$defCsv = $svc->templateDefinition(1);
$cols = (array)($defCsv['columns'] ?? []);
if (empty($cols['nome']) || empty($cols['documento']) || empty($cols['dn']) || empty($cols['celular']) || empty($cols['email']) || empty($cols['unidade']) || empty($cols['funcao']) || empty($cols['setor']) || empty($cols['departamento'])) {
    failFast('Template: colunas base esperadas não encontradas.');
}
$orientationRows = is_array($defCsv['orientation_rows'] ?? null) ? $defCsv['orientation_rows'] : [];
$requiredMap = [];
foreach ($orientationRows as $row) {
    $requiredMap[(string)($row['campo'] ?? '')] = mb_strtolower((string)($row['obrigatorio'] ?? ''));
}
if (($requiredMap['Documento'] ?? '') !== 'não' || ($requiredMap['Celular'] ?? '') !== 'não' || ($requiredMap['Email'] ?? '') !== 'não') {
    failFast('Template: Documento, Celular e Email deveriam constar como opcionais nas orientações.');
}
if (($requiredMap['Nome'] ?? '') !== 'sim' || ($requiredMap['DN'] ?? '') !== 'sim' || ($requiredMap['Unidade'] ?? '') !== 'sim') {
    failFast('Template: campos ainda obrigatórios não foram preservados nas orientações.');
}
ok('Template contém colunas e obrigatoriedades corretas');

$rowsCsv = is_array($defCsv['rows'] ?? null) ? $defCsv['rows'] : [];
if (count($rowsCsv) < 2) {
    failFast('Template: esperado pelo menos 2 linhas de exemplo.');
}
$createdDept = (string)($rowsCsv[0]['departamento'] ?? '');
$createdSetor = (string)($rowsCsv[0]['setor'] ?? '');
$createdFuncao = (string)($rowsCsv[0]['funcao'] ?? '');
foreach ($rowsCsv as $r) {
    $emails[] = (string)($r['email'] ?? '');
}

$csvPath = $tmpDir . DIRECTORY_SEPARATOR . 'modelo.csv';
$filesToDelete[] = $csvPath;
$fh = fopen($csvPath, 'wb');
if ($fh === false) {
    failFast('Falha ao criar arquivo CSV');
}
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, array_values($cols), ';', '"', '\\');
$keys = array_keys($cols);
foreach ($rowsCsv as $row) {
    $vals = [];
    foreach ($keys as $k) {
        $vals[] = isset($row[$k]) ? (string)$row[$k] : '';
    }
    fputcsv($fh, $vals, ';', '"', '\\');
}
fclose($fh);

$resCsv = $svc->import($csvPath, 'modelo.csv', 1);
if (empty($resCsv['ok']) || (int)($resCsv['inserted'] ?? 0) !== count($rowsCsv)) {
    failFast('Import do modelo CSV falhou: ' . json_encode($resCsv, JSON_UNESCAPED_UNICODE));
}
ok('Import do modelo CSV');

$defXlsx = $svc->templateDefinition(1);
$colsX = (array)($defXlsx['columns'] ?? []);
$rowsX = is_array($defXlsx['rows'] ?? null) ? $defXlsx['rows'] : [];
foreach ($rowsX as $r) {
    $emails[] = (string)($r['email'] ?? '');
}
$xlsxPath = XlsxExport::exportTemplateWorkbook([
    [
        'name' => 'Modelo',
        'columns' => $colsX,
        'rows' => $rowsX,
    ],
    [
        'name' => 'Orientações',
        'columns' => (array)($defXlsx['orientation_columns'] ?? []),
        'rows' => is_array($defXlsx['orientation_rows'] ?? null) ? $defXlsx['orientation_rows'] : [],
    ],
], 'modelo_' . $suffix . '.xlsx', ['report_title' => 'Modelo de Importação - Colaboradores']);
$filesToDelete[] = $xlsxPath;

$resX = $svc->import($xlsxPath, 'modelo.xlsx', 1);
if (empty($resX['ok']) || (int)($resX['inserted'] ?? 0) !== count($rowsX)) {
    failFast('Import do modelo XLSX falhou: ' . json_encode($resX, JSON_UNESCAPED_UNICODE));
}
ok('Import do modelo XLSX');

ok('Cleanup');
echo "All colaboradores import template integration tests passed.\n";
