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

echo "Running Auditoria Percent Split Tests...\n";
echo "========================================\n";

$r = AuditoriaModel::percentSplit(0, 0);
assertTest($r['conforme'] === 0.00 && $r['nao_conforme'] === 0.00, "Zero/zero returns 0,0");

$r = AuditoriaModel::percentSplit(1, 0);
assertTest($r['conforme'] === 100.00 && $r['nao_conforme'] === 0.00, "All conforme is 100/0");

$r = AuditoriaModel::percentSplit(0, 1);
assertTest($r['conforme'] === 0.00 && $r['nao_conforme'] === 100.00, "All nao_conforme is 0/100");

$r = AuditoriaModel::percentSplit(2, 1);
assertTest(round($r['conforme'] + $r['nao_conforme'], 2) === 100.00, "Sum is 100.00 (2/1)");
assertTest($r['conforme'] === 66.67 && $r['nao_conforme'] === 33.33, "2/1 rounds to 66.67/33.33");

$r = AuditoriaModel::percentSplit(1, 2);
assertTest(round($r['conforme'] + $r['nao_conforme'], 2) === 100.00, "Sum is 100.00 (1/2)");
assertTest($r['conforme'] === 33.33 && $r['nao_conforme'] === 66.67, "1/2 rounds to 33.33/66.67");

$r = AuditoriaModel::percentSplit(1, 1);
assertTest(round($r['conforme'] + $r['nao_conforme'], 2) === 100.00, "Sum is 100.00 (1/1)");
assertTest($r['conforme'] === 50.00 && $r['nao_conforme'] === 50.00, "1/1 is 50/50");

echo "========================================\n";
echo "Tests Completed: $passed/$total passed.\n";
exit($passed === $total ? 0 : 1);

