<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AuditoriaValidator;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$valid = [
    'cliente_id' => 1,
    'setor_id' => 2,
    'responsavel_id' => 3,
    'data_auditoria' => date('d/m/Y'),
    'pergunta' => 'Pergunta de auditoria com tamanho adequado',
    'objetivo' => 'Objetivo da auditoria com mais de vinte caracteres',
    'referencia_esperada' => 'POP-001',
];
$errors = AuditoriaValidator::validateCadastro($valid);
if (!empty($errors)) {
    failFast('Cadastro válido não deveria retornar erros');
}
ok('Validação de cadastro válido');

$future = $valid;
$future['data_auditoria'] = date('d/m/Y', strtotime('+30 days'));
$futureErrors = AuditoriaValidator::validateCadastro($future);
if (!empty($futureErrors['data_auditoria'])) {
    failFast('Cadastro com data futura deveria ser permitido');
}
ok('Validação de cadastro com data futura');

$invalid = $valid;
$invalid['pergunta'] = 'curta';
$invalid['objetivo'] = 'curto';
$invalid['data_auditoria'] = '32/15/2025';
$errors2 = AuditoriaValidator::validateCadastro($invalid);
if (empty($errors2['pergunta']) || empty($errors2['objetivo']) || empty($errors2['data_auditoria'])) {
    failFast('Cadastro inválido deveria retornar erros específicos');
}
ok('Validação de cadastro inválido');

$execOk = AuditoriaValidator::validateExecucao([
    'avaliacao' => str_repeat('A', 60),
    'obs' => 'Observação complementar',
]);
if (!empty($execOk)) {
    failFast('Execução válida não deveria retornar erros');
}
ok('Validação de execução válida');

$execInvalid = AuditoriaValidator::validateExecucao([
    'avaliacao' => 'muito curta',
    'obs' => str_repeat('B', 1001),
]);
if (empty($execInvalid['avaliacao']) || empty($execInvalid['obs'])) {
    failFast('Execução inválida deveria retornar erros');
}
ok('Validação de execução inválida');

echo "All auditoria validator unit tests passed.\n";
