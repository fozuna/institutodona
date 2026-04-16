<?php
require __DIR__ . '/../autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$dompdf = new Dompdf($options);

echo json_encode([
    'options_class_exists' => class_exists(Options::class),
    'dompdf_class_exists' => class_exists(Dompdf::class),
    'instance_created' => $dompdf instanceof Dompdf,
], JSON_UNESCAPED_UNICODE);

if (!class_exists(Options::class) || !class_exists(Dompdf::class) || !($dompdf instanceof Dompdf)) {
    exit(1);
}
