<?php
require_once __DIR__ . '/../autoload.php';

use App\Models\ManualModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function assertOk(array $result, string $label): void
{
    if (empty($result['ok'])) {
        failFast($label . ' deveria ser ok. Retorno: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    ok($label . ' ok');
}

function assertFail(array $result, string $expectedError, string $label): void
{
    if (!empty($result['ok'])) {
        failFast($label . ' deveria falhar');
    }
    if (($result['error'] ?? null) !== $expectedError) {
        failFast($label . ' erro esperado ' . $expectedError . ' mas veio ' . json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    ok($label . ' falhou como esperado');
}

$size = 1024;
$max = 50 * 1024 * 1024;

assertOk(ManualModel::validateUpload('a.pdf', $size, 'application/pdf', $max), 'PDF');
assertOk(ManualModel::validateUpload('a.doc', $size, 'application/msword', $max), 'DOC');
assertOk(ManualModel::validateUpload('a.docx', $size, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $max), 'DOCX');
assertOk(ManualModel::validateUpload('a.txt', $size, 'text/plain', $max), 'TXT');
assertOk(ManualModel::validateUpload('a.rtf', $size, 'application/rtf', $max), 'RTF');
assertOk(ManualModel::validateUpload('a.xls', $size, 'application/vnd.ms-excel', $max), 'XLS');
assertOk(ManualModel::validateUpload('a.xlsx', $size, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $max), 'XLSX');
assertOk(ManualModel::validateUpload('a.ppt', $size, 'application/vnd.ms-powerpoint', $max), 'PPT');
assertOk(ManualModel::validateUpload('a.pptx', $size, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', $max), 'PPTX');
assertOk(ManualModel::validateUpload('a.jpg', $size, 'image/jpeg', $max), 'JPG');
assertOk(ManualModel::validateUpload('a.jpeg', $size, 'image/jpeg', $max), 'JPEG');
assertOk(ManualModel::validateUpload('a.png', $size, 'image/png', $max), 'PNG');
assertOk(ManualModel::validateUpload('a.gif', $size, 'image/gif', $max), 'GIF');
assertOk(ManualModel::validateUpload('a.bmp', $size, 'image/bmp', $max), 'BMP');
assertOk(ManualModel::validateUpload('a.svg', $size, 'image/svg+xml', $max), 'SVG');

if (ManualModel::categoryFromExtension('xlsx') !== 'planilhas') {
    failFast('Categoria xlsx deveria ser planilhas');
}
ok('Categoria por extensão');

assertFail(ManualModel::validateUpload('a.exe', $size, 'application/octet-stream', $max), 'ext_invalid', 'Ext inválida');
assertFail(ManualModel::validateUpload('a.pdf', 0, 'application/pdf', $max), 'size_invalid', 'Tamanho inválido');
assertFail(ManualModel::validateUpload('a.pdf', $max + 1, 'application/pdf', $max), 'size_exceeded', 'Tamanho excedido');
assertFail(ManualModel::validateUpload('a.pdf', $size, 'text/plain', $max), 'mime_invalid', 'MIME inválido');

echo "All manual upload validation unit tests passed.\n";

