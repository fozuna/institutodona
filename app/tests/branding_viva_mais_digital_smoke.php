<?php
require_once __DIR__ . '/../autoload.php';

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }
function ok(string $msg): void { echo "OK: {$msg}\n"; }

$checks = [
    __DIR__ . '/../views/layouts/main.php' => 'AppBrand::displayName',
    __DIR__ . '/../views/auth/login.php' => 'AppBrand::displayName',
    __DIR__ . '/../core/AppBrand.php' => "public const NAME = 'VIVA+ DIGITAL'",
    __DIR__ . '/../core/XlsxExport.php' => 'AppBrand::EXPORT_APP',
    __DIR__ . '/../core/SimplePdfReport.php' => 'AppBrand::PRODUCER',
    __DIR__ . '/../services/PublicAvaliacaoContextResolver.php' => 'AppBrand::displayName',
];

foreach ($checks as $path => $needle) {
    $content = @file_get_contents($path);
    if ($content === false || !str_contains($content, $needle)) {
        failFast('Branding VIVA+ DIGITAL ausente/desatualizado em: ' . basename($path));
    }
}

$staleNeedle = 'SIS+';
$staleFiles = [
    __DIR__ . '/../core/AppBrand.php',
    __DIR__ . '/../core/SimplePdfReport.php',
    __DIR__ . '/../services/PublicAvaliacaoContextResolver.php',
];
foreach ($staleFiles as $path) {
    $content = @file_get_contents($path);
    if ($content !== false && str_contains($content, $staleNeedle)) {
        failFast('Referência antiga "SIS+" ainda presente em: ' . basename($path));
    }
}

ok('Branding VIVA+ DIGITAL aplicado nos pontos centrais, sem resíduos de "SIS+"');
echo "branding_viva_mais_digital_smoke passed.\n";
