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
assert_true((bool)preg_match('/^[0-9a-f]{10}$/', PdfSupport::newErrorId()), 'newErrorId gera codigo hex de 10 chars');
$diag = PdfSupport::dompdfDiagnostics();
assert_true(is_array($diag) && array_key_exists('vendor_autoload_exists', $diag) && array_key_exists('tmp_writable', $diag), 'dompdfDiagnostics retorna chaves basicas');

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
