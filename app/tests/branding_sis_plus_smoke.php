<?php
require_once __DIR__ . '/../autoload.php';

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }
function ok(string $msg): void { echo "OK: {$msg}\n"; }

$checks = [
    __DIR__ . '/../views/layouts/main.php' => 'AppBrand::displayName',
    __DIR__ . '/../views/auth/login.php' => 'AppBrand::displayName',
    __DIR__ . '/../core/AppBrand.php' => "public const NAME = 'SIS+'",
    __DIR__ . '/../core/XlsxExport.php' => 'AppBrand::EXPORT_APP',
    __DIR__ . '/../core/SimplePdfReport.php' => 'SIS+ PDF UTF-8',
    __DIR__ . '/../services/PublicAvaliacaoContextResolver.php' => 'SIS+',
];

foreach ($checks as $path => $needle) {
    $content = @file_get_contents($path);
    if ($content === false || !str_contains($content, $needle)) {
        failFast('Branding SIS+ ausente em: ' . basename($path));
    }
}

ok('Branding SIS+ aplicado nos pontos centrais');
echo "branding_sis_plus_smoke passed.\n";
