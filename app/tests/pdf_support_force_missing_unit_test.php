<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\PdfSupport;
use App\Services\TreinamentoDocumentService;

function assert_true($cond, $msg) {
    if (!$cond) { echo "FAIL: {$msg}\n"; exit(1); }
    echo "OK: {$msg}\n";
}

putenv('PDF_FORCE_MISSING=1');

assert_true(PdfSupport::isDompdfAvailable() === false, 'PDF_FORCE_MISSING desabilita Dompdf');

$svc = new TreinamentoDocumentService();
$thrown = false;
try {
    $ref = new ReflectionClass($svc);
    $m = $ref->getMethod('renderPdf');
    $m->setAccessible(true);
    $m->invoke($svc, '<html><body>test</body></html>', 'A4', 'portrait', false);
} catch (Throwable $e) {
    $thrown = true;
}
assert_true($thrown, 'renderPdf lança exceção quando Dompdf está indisponível');

putenv('PDF_FORCE_MISSING');

echo "pdf_support_force_missing_unit_test passed.\n";

