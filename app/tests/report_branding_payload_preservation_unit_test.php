<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\ReportBranding;

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

$payload = [
    'report_title' => 'Relatorio Teste',
    'header_subtitle' => 'Subtitulo teste',
    'header' => [
        'Empresa' => 'Empresa XPTO',
    ],
    'questions' => [
        [
            'title' => 'Pergunta 1',
            'rows' => ['Conformidade' => 'Conforme'],
            'images' => [],
        ],
    ],
];

$documento = ReportBranding::aplicarBrandingRelatorio('pdf', $payload);

assert_true(($documento['report_title'] ?? '') === 'Relatorio Teste', 'Mantem o titulo informado');
assert_true(($documento['header_subtitle'] ?? '') === 'Subtitulo teste', 'Mantem o subtitulo informado');
assert_true(isset($documento['header']['Empresa']) && $documento['header']['Empresa'] === 'Empresa XPTO', 'Preserva o bloco de cabecalho do relatorio');
assert_true(isset($documento['questions'][0]['title']) && $documento['questions'][0]['title'] === 'Pergunta 1', 'Preserva as questoes do relatorio');
assert_true(array_key_exists('logo_path', $documento), 'Injeta configuracao de branding no payload');

echo "report_branding_payload_preservation_unit_test passed.\n";
