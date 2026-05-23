<?php
require_once __DIR__ . '/../autoload.php';

function assert_true($cond, $msg) {
    if (!$cond) { echo "FAIL: $msg\n"; exit(1); }
    echo "OK: $msg\n";
}

$modelPath = __DIR__ . '/../models/CronogramaEventoModel.php';
$code = file_get_contents($modelPath);
assert_true(is_string($code) && $code !== '', 'Leu CronogramaEventoModel.php');

assert_true(strpos($code, 'Illegal mix of collations') === false, 'Não há hardcode de erro de collation');
assert_true(strpos($code, "utf8mb4_uca1400_ai_ci") !== false, 'Existe fallback para collation utf8mb4_uca1400_ai_ci quando suportado');
assert_true(strpos($code, "utf8mb4_unicode_ci") !== false, 'Existe fallback para collation utf8mb4_unicode_ci');
assert_true(strpos($code, "information_schema.COLLATIONS") !== false, 'Seleciona collation suportada via information_schema.COLLATIONS');
assert_true(strpos($code, 'CONVERT(:topico USING utf8mb4)') !== false, 'Query converte parâmetro :topico para utf8mb4');
assert_true(strpos($code, 'COALESCE(unidade, \'\')') !== false, 'Query normaliza unidade via COALESCE');

echo "cronograma_collation_guard_smoke passed.\n";
