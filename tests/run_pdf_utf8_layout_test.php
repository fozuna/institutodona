<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Core\SimplePdfReport;

function assertTest($cond, $msg){ global $p,$t; $t++; echo ($cond ? "[PASS] " : "[FAIL] ") . $msg . "\n"; if($cond)$p++; }
$p=0; $t=0;

$pdf = SimplePdfReport::fromAudit([
    'report_title' => 'Relatório de Auditoria – Português',
    'generated_at' => '29/03/2026 10:30',
    'version' => 'v2.0',
    'header' => [
        'Empresa' => 'Instituto Doná',
        'Setor' => 'Operações – Ação',
        'Auditor responsável' => 'João da Silva',
    ],
    'questions' => [[
        'title' => 'Questão com acentuação: ç ã õ à á é í ó ú ü',
        'rows' => [
            'Conformidade' => 'não_conforme',
            'Observações' => 'ação, inspeção, produção e utilização',
        ],
        'images' => [],
    ]],
]);

assertTest(is_string($pdf) && str_starts_with($pdf, '%PDF-1.4'), 'PDF gerado com cabeçalho válido');
assertTest(strpos($pdf, '/Helvetica') !== false, 'Fontes configuradas');
assertTest(strpos($pdf, 'P') !== false, 'Conteúdo textual renderizado');
assertTest(strpos($pdf, "Ã") === false, 'Sem mojibake comum de UTF-8');
assertTest(strpos($pdf, 'startxref') !== false, 'Estrutura xref presente');

$pngPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf_test_png_' . uniqid() . '.png';
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lk2unQAAAABJRU5ErkJggg==');
file_put_contents($pngPath, $pngData);
$pdfWithPng = SimplePdfReport::fromAudit([
    'report_title' => 'Relatório com PNG',
    'generated_at' => '29/03/2026 10:30',
    'version' => 'v2.0',
    'header' => ['Empresa' => 'Instituto Doná'],
    'questions' => [[
        'title' => 'Questão com anexo PNG',
        'rows' => ['Conformidade' => 'conforme'],
        'images' => [$pngPath],
    ]],
]);
assertTest(str_starts_with($pdfWithPng, '%PDF-1.4'), 'PDF com PNG gerado sem fatal');
if (extension_loaded('gd')) {
    assertTest(strpos($pdfWithPng, '/XObject') !== false, 'Com GD ativo, PNG incorporado no PDF');
} else {
    assertTest(strpos($pdfWithPng, 'startxref') !== false, 'Sem GD, PDF segue gerado com fallback');
}
@unlink($pngPath);

echo "Tests Completed: $p/$t passed.\n";
exit($p===$t?0:1);
