<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Core\AuditoriaValidator;

function assertTest($condition, $message) {
    global $passed, $total;
    $total++;
    if ($condition) {
        echo "[PASS] $message\n";
        $passed++;
    } else {
        echo "[FAIL] $message\n";
    }
}

$passed = 0;
$total = 0;

echo "Running Auditoria Validator Execucao Tests...\n";
echo "=============================================\n";

$avaliacoes = [
    ['questao_id' => 1, 'conformidade' => 'conforme', 'observacoes' => ''],
    ['questao_id' => 2, 'conformidade' => 'nao_conforme', 'observacoes' => ''],
    ['questao_id' => 3, 'conformidade' => 'nao_aplica', 'observacoes' => ''],
];
$errors = AuditoriaValidator::validateExecucao($avaliacoes, 3);
assertTest(empty($errors), "Accepts conforme/nao_conforme/nao_aplica");

$avaliacoesBad = [
    ['questao_id' => 1, 'conformidade' => 'pendente', 'observacoes' => ''],
];
$errors = AuditoriaValidator::validateExecucao($avaliacoesBad, 1);
assertTest(!empty($errors), "Rejects invalid conformidade");

$avaliacoesObs = [
    ['questao_id' => 1, 'conformidade' => 'conforme', 'observacoes' => str_repeat('a', 2001)],
];
$errors = AuditoriaValidator::validateExecucao($avaliacoesObs, 1);
assertTest(isset($errors['avaliacao_obs_1']), "Rejects observacoes > 2000");

echo "=============================================\n";
echo "Tests Completed: $passed/$total passed.\n";
exit($passed === $total ? 0 : 1);

