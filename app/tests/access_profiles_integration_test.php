<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function runProbe(string $route, string $method, string $role): string
{
    $root = realpath(__DIR__ . '/..');
    $probe = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'access_gate_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' ' . escapeshellarg($route) . ' ' . escapeshellarg($method) . ' ' . escapeshellarg($role);
    $out = [];
    @exec($cmd . ' 2>&1', $out);
    return trim(implode("\n", $out));
}

$r1 = runProbe('dashboard/index', 'GET', 'reader');
if (strpos($r1, 'ALLOWED') === false) {
    failFast('Reader deveria acessar rota GET de leitura');
}
ok('Reader acessa GET de leitura');

$r2 = runProbe('indicadores/store', 'POST', 'reader');
if (stripos($r2, 'somente leitura') === false) {
    failFast('Reader deveria ser bloqueado em POST de escrita');
}
ok('Reader bloqueado em POST de escrita');

$r3 = runProbe('usuarios/delete', 'GET', 'reader');
if (stripos($r3, 'somente leitura') === false) {
    failFast('Reader deveria ser bloqueado em rota de escrita via GET');
}
ok('Reader bloqueado em escrita via GET');

$r4 = runProbe('indicadores/store', 'POST', 'cliente_admin');
if (strpos($r4, 'ALLOWED') === false) {
    failFast('Cliente admin deveria acessar escrita em escopo');
}
ok('Cliente admin permite escrita');

echo "All access profile integration tests passed.\n";
