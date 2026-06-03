<?php
require __DIR__ . '/../autoload.php';

use App\Core\PdfSupport;
use App\Core\Security;
use App\Controllers\IndicadoresController;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

if (!PdfSupport::isDompdfAvailable()) {
    echo "SKIP: Dompdf indisponível no ambiente atual.\n";
    exit(0);
}
if (!extension_loaded('gd')) {
    echo "SKIP: Extensão GD indisponível no ambiente atual.\n";
    exit(0);
}

$csrf = Security::csrfToken();
$fakePng = base64_encode("\x89PNG\r\n\x1a\n" . str_repeat("0", 256));

function makeCharts(int $n, string $fakePng): array {
    $charts = [];
    for ($i = 1; $i <= $n; $i++) {
        $charts[] = [
            'title' => 'Indicador ' . $i,
            'trend' => 'Tendência: estável',
            'meta' => '10,00',
            'achieved' => '9,00',
            'image' => 'data:image/png;base64,' . $fakePng,
        ];
    }
    return $charts;
}

function execChartsPdf(string $csrf, array $charts): string {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf' => $csrf,
        'cliente_id' => 1,
        'cliente_nome' => 'Cliente',
        'charts' => json_encode($charts, JSON_UNESCAPED_UNICODE),
    ];
    $_GET = ['route' => 'indicadores/chartsPdf'];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

    header_remove();
    ob_start();
    (new IndicadoresController())->chartsPdf();
    return (string)ob_get_clean();
}

function countPages(string $pdf): int {
    if (preg_match_all('/\/Type\s*\/Page\b(?!s)/', $pdf, $m)) {
        return count($m[0] ?? []);
    }
    return 0;
}

$pdf3 = execChartsPdf($csrf, makeCharts(3, $fakePng));
assert_true(substr($pdf3, 0, 4) === '%PDF', 'chartsPdf gera PDF com menos de 12 gráficos');
assert_true(countPages($pdf3) >= 1, 'chartsPdf gera ao menos 1 página com 3 gráficos');

$pdf12 = execChartsPdf($csrf, makeCharts(12, $fakePng));
assert_true(substr($pdf12, 0, 4) === '%PDF', 'chartsPdf gera PDF com exatamente 12 gráficos');
assert_true(countPages($pdf12) >= 2, 'chartsPdf gera múltiplas páginas quando necessário (12 gráficos)');

$pdf13 = execChartsPdf($csrf, makeCharts(13, $fakePng));
assert_true(substr($pdf13, 0, 4) === '%PDF', 'chartsPdf gera PDF com mais de 12 gráficos');
assert_true(countPages($pdf13) >= 2, 'chartsPdf pagina automaticamente quando há mais de 12 gráficos');

echo "Indicadores chartsPdf pagination smoke tests passed.\n";
