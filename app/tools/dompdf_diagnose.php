<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\PdfSupport;

$root = dirname(__DIR__, 2);
$composerJson = $root . '/composer.json';
$composerLock = $root . '/composer.lock';
$vendorAutoload = $root . '/vendor/autoload.php';
$vendorDir = $root . '/vendor';

echo "Dompdf diagnose\n";
echo "root={$root}\n\n";

echo "1) composer.json\n";
if (!is_file($composerJson)) {
    echo "  MISSING: {$composerJson}\n";
} else {
    $json = json_decode((string)file_get_contents($composerJson), true);
    $req = $json['require']['dompdf/dompdf'] ?? null;
    echo "  dompdf/dompdf requirement=" . ($req !== null ? (string)$req : 'NOT_DECLARED') . "\n";
}

echo "\n2) composer.lock\n";
if (!is_file($composerLock)) {
    echo "  MISSING: {$composerLock}\n";
} else {
    $lock = json_decode((string)file_get_contents($composerLock), true);
    $pkgs = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);
    $found = null;
    foreach ($pkgs as $p) {
        if (($p['name'] ?? '') === 'dompdf/dompdf') {
            $found = $p;
            break;
        }
    }
    echo "  dompdf/dompdf installed_version=" . ($found['version'] ?? 'NOT_FOUND') . "\n";
}

echo "\n3) vendor/autoload.php\n";
echo "  exists=" . (is_file($vendorAutoload) ? 'yes' : 'no') . " path={$vendorAutoload}\n";
if (is_file($vendorAutoload)) {
    echo "  readable=" . (is_readable($vendorAutoload) ? 'yes' : 'no') . "\n";
}

echo "\n4) Dompdf classes\n";
echo "  PdfSupport::isDompdfAvailable()=" . (PdfSupport::isDompdfAvailable() ? 'true' : 'false') . "\n";
echo "  class_exists(Dompdf\\\\Options)=" . (class_exists(\Dompdf\Options::class) ? 'true' : 'false') . "\n";
echo "  class_exists(Dompdf\\\\Dompdf)=" . (class_exists(\Dompdf\Dompdf::class) ? 'true' : 'false') . "\n";

echo "\n5) Ambiente e recursos\n";
echo "  PHP_VERSION=" . PHP_VERSION . "\n";
echo "  PHP_SAPI=" . PHP_SAPI . "\n";
echo "  memory_limit=" . (string)ini_get('memory_limit') . "\n";
echo "  max_execution_time=" . (string)ini_get('max_execution_time') . "\n";
echo "  PDF_FORCE_MISSING=" . (string)getenv('PDF_FORCE_MISSING') . "\n";
echo "  sys_get_temp_dir=" . (string)sys_get_temp_dir() . "\n";
echo "  tmp_writable=" . (@is_writable((string)sys_get_temp_dir()) ? 'yes' : 'no') . "\n";
echo "  storage_writable=" . (@is_writable($root . '/storage') ? 'yes' : 'no') . "\n";
echo "  ext_mbstring=" . (extension_loaded('mbstring') ? 'yes' : 'no') . "\n";
echo "  ext_gd=" . (extension_loaded('gd') ? 'yes' : 'no') . "\n";
echo "  ext_iconv=" . (extension_loaded('iconv') ? 'yes' : 'no') . "\n";
echo "  ext_zlib=" . (extension_loaded('zlib') ? 'yes' : 'no') . "\n";

echo "\n6) vendor permissions (best-effort)\n";
if (!is_dir($vendorDir)) {
    echo "  MISSING: {$vendorDir}\n";
} else {
    echo "  readable=" . (is_readable($vendorDir) ? 'yes' : 'no') . "\n";
    $perms = @fileperms($vendorDir);
    echo "  perms=" . ($perms !== false ? substr(sprintf('%o', $perms), -4) : 'unknown') . "\n";
}

echo "\n7) PdfSupport::dompdfDiagnostics()\n";
echo json_encode(PdfSupport::dompdfDiagnostics(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\nDone.\n";
