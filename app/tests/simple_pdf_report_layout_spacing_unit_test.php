<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\SimplePdfReport;

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

function find_text_y(string $pdf, string $needle): ?float
{
    $pattern = '/BT \/F\d+ [\d.]+ Tf 1 0 0 1 [\d.]+ ([\d.]+) Tm \((.*?)\) Tj ET/s';
    if (!preg_match_all($pattern, $pdf, $matches, PREG_SET_ORDER)) {
        return null;
    }

    foreach ($matches as $match) {
        $text = stripcslashes($match[2]);
        $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        if ($text === $needle) {
            return (float)$match[1];
        }
    }

    return null;
}

$pdf = SimplePdfReport::fromAudit([
    'header_title' => 'Relatório de Auditoria',
    'report_title' => 'Teste de implementação de testes de implementações',
    'header_subtitle' => 'DINEX ENGENHARIA MINERAL LTDA · FINANCEIRO',
    'show_logo' => false,
    'generated_at' => '23/06/2026 19:58',
    'summary_sections' => [
        [
            'columns' => 4,
            'items' => [
                ['label' => 'Data agendada', 'value' => '25/04/2026'],
                ['label' => 'Status', 'value' => 'Realizada'],
                ['label' => 'Realizada em', 'value' => '16/04/2026 19:08'],
                ['label' => 'Total de questões', 'value' => '1'],
            ],
        ],
        [
            'columns' => 4,
            'items' => [
                ['label' => 'Conforme', 'value' => '0 (0,00%)'],
                ['label' => 'Não conforme', 'value' => '0 (0,00%)'],
                ['label' => 'Não se aplica', 'value' => '1'],
                [
                    'label' => 'Semáforo',
                    'value' => 'Vermelho',
                    'badge' => 'VERMELHO',
                    'badge_fill' => [254, 226, 226],
                    'badge_text' => [153, 27, 27],
                ],
            ],
        ],
    ],
    'detail_rows' => [
        ['label' => 'Responsáveis', 'value' => '-'],
        ['label' => 'Auditor responsável', 'value' => 'Admin'],
        ['label' => 'Conformidade geral', 'value' => '0,00%'],
        ['label' => 'Resumo final', 'value' => 'Conforme: 0 | Não conforme: 0 | N/A: 1'],
    ],
    'questions' => [
        [
            'title' => 'quer auditar algo auditavel?',
            'rows' => [
                'Responsável' => 'jose da silva, pedro sampaio',
                'Referência esperada' => 'Não eu não quero',
                'Processos' => 'Nenhum',
                'Conformidade' => 'Não se aplica',
            ],
            'images' => [],
        ],
    ],
]);

$eyebrowY = find_text_y($pdf, 'Relatório de Auditoria');
$titleY = find_text_y($pdf, 'Teste de implementação de testes de implementações');
$subtitleY = find_text_y($pdf, 'DINEX ENGENHARIA MINERAL LTDA · FINANCEIRO');
$detailValueY = find_text_y($pdf, 'Conforme: 0 | Não conforme: 0 | N/A: 1');
$sectionY = find_text_y($pdf, 'Respostas da Auditoria');

assert_true($eyebrowY !== null, 'Encontra a linha superior do cabecalho');
assert_true($titleY !== null, 'Encontra o titulo principal do relatorio');
assert_true($subtitleY !== null, 'Encontra o subtitulo do relatorio');
assert_true($detailValueY !== null, 'Encontra o valor da ultima linha do card de detalhes');
assert_true($sectionY !== null, 'Encontra o titulo da secao de respostas');
assert_true($titleY < ($eyebrowY - 10.0), 'Titulo principal fica abaixo do titulo superior sem sobreposicao');
assert_true($subtitleY < ($titleY - 12.0), 'Subtitulo fica abaixo do titulo principal');
assert_true($sectionY < ($detailValueY - 18.0), 'Secao de respostas inicia abaixo do card de detalhes');

echo "simple_pdf_report_layout_spacing_unit_test passed.\n";
