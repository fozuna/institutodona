<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Models\AuditoriaModel;

function assertTest($cond, $msg) {
    global $passed, $total;
    $total++;
    if ($cond) {
        $passed++;
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
    }
}

$passed = 0;
$total = 0;

echo "Running Auditoria Percent + Semaforo Tests...\n";
echo "=============================================\n";

$ref = new ReflectionClass(AuditoriaModel::class);
$m = $ref->getMethod('percentSplit');
$m->setAccessible(true);

$r = $m->invoke(null, 3, 0);
assertTest($r['conforme'] === 100.00 && $r['nao_conforme'] === 0.00, "3/0 -> 100/0");

$r = $m->invoke(null, 1, 3);
assertTest(round($r['conforme'] + $r['nao_conforme'], 2) === 100.00, "1/3 soma 100");

echo "=============================================\n";
echo "Tests Completed: $passed/$total passed.\n";
exit($passed === $total ? 0 : 1);

