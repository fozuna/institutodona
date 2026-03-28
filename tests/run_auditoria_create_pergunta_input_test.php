<?php

$file = __DIR__ . '/../app/views/auditorias/create.php';
$content = file_get_contents($file);
if ($content === false) {
    fwrite(STDERR, "Could not read create.php\n");
    exit(1);
}

$passed = 0;
$total = 0;

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

echo "Running Auditoria Create Pergunta Input Tests...\n";
echo "================================================\n";

assertTest(strpos($content, 'data-pergunta-count="${index}"') !== false, "Pergunta counter marker exists");
assertTest(strpos($content, 'maxlength="1000"') !== false, "Pergunta textarea has maxlength 1000");

$hasBadRerender = (strpos($content, "querySelectorAll('[data-pergunta]')") !== false)
    && (strpos($content, 'renderQuestoes();') !== false)
    && (strpos($content, "querySelectorAll('[data-pergunta]')") < strpos($content, 'renderQuestoes();'));
assertTest($hasBadRerender === false, "Pergunta input does not force full rerender");

echo "================================================\n";
echo "Tests Completed: $passed/$total passed.\n";
exit($passed === $total ? 0 : 1);

