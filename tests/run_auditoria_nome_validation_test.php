<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Models\AuditoriaModel;

function assertTest($cond, $msg) {
    global $passed, $total;
    $total++;
    if ($cond) { $passed++; echo "[PASS] $msg\n"; }
    else { echo "[FAIL] $msg\n"; }
}

$passed = 0;
$total = 0;

echo "Running Auditoria Nome Validation Tests...\n";
echo "=========================================\n";

$m = (new ReflectionClass(AuditoriaModel::class))->newInstanceWithoutConstructor();

$valid = [
    'João da Silva',
    'Maria José',
    'Antônio Carlos',
    'Ana Paula',
    'Validações Financeiras',
    'Projeto Ágil - Fase 1',
    'Controle_Interno 2026',
];
foreach ($valid as $nome) {
    $err = $m->validarNomeAuditoria($nome);
    assertTest($err === null, "Valid name accepted: {$nome}");
}

$invalid = [
    '', // vazio
    str_repeat('A', 101), // >100
    "Invalido@Nome", // caractere proibido
    "Nome#Teste", // caractere proibido
];
foreach ($invalid as $nome) {
    $err = $m->validarNomeAuditoria($nome);
    assertTest($err !== null, "Invalid name rejected: " . ($nome === '' ? '(empty)' : $nome));
}

echo "=========================================\n";
echo "Tests Completed: $passed/$total passed.\n";
exit($passed === $total ? 0 : 1);

