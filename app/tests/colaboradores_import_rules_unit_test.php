<?php
require __DIR__ . '/../autoload.php';

use App\Services\ColaboradorImportService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$svc = new ColaboradorImportService();
$ref = new ReflectionClass($svc);

$mCpfCnpj = $ref->getMethod('isCpfOrCnpjValid');
$mCpfCnpj->setAccessible(true);

$mDate = $ref->getMethod('parseBrDateToDb');
$mDate->setAccessible(true);

if (!$mCpfCnpj->invoke($svc, '11144477735')) {
    failFast('CPF válido deveria passar');
}
ok('CPF válido');

if ($mCpfCnpj->invoke($svc, '11111111111')) {
    failFast('CPF inválido deveria falhar');
}
ok('CPF inválido');

if (!$mCpfCnpj->invoke($svc, '11222333000181')) {
    failFast('CNPJ válido deveria passar');
}
ok('CNPJ válido');

if ($mCpfCnpj->invoke($svc, '00000000000000')) {
    failFast('CNPJ inválido deveria falhar');
}
ok('CNPJ inválido');

$d = $mDate->invoke($svc, '31/12/2000');
if ($d !== '2000-12-31') {
    failFast('Data deveria converter para 2000-12-31 mas veio ' . var_export($d, true));
}
ok('Data válida');

$d2 = $mDate->invoke($svc, '31/13/2000');
if ($d2 !== null) {
    failFast('Data inválida deveria retornar null');
}
ok('Data inválida');

echo "All colaboradores import rules unit tests passed.\n";

