<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function runAudProbe(string $role, string $action = 'store'): string
{
    $root = realpath(__DIR__ . '/..');
    $probe = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auditorias_permission_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' ' . escapeshellarg($role) . ' ' . escapeshellarg($action);
    $out = [];
    @exec($cmd . ' 2>&1', $out);
    return trim(implode("\n", $out));
}

$consultor = runAudProbe('consultor', 'store');
if (stripos($consultor, 'Requisição inválida') === false) {
    failFast('Consultor deveria passar pela permissão de escrita em auditorias');
}
ok('Consultor com acesso de escrita em auditorias');

$instituto = runAudProbe('instituto', 'store');
if (stripos($instituto, 'Requisição inválida') === false) {
    failFast('Instituto deveria passar pela permissão de escrita em auditorias');
}
ok('Instituto com acesso de escrita em auditorias');

$empresa = runAudProbe('cliente', 'store');
if (stripos($empresa, 'Acesso negado') === false) {
    failFast('Perfil empresa deveria ser bloqueado para escrita em auditorias');
}
ok('Perfil empresa bloqueado para escrita em auditorias');

$reader = runAudProbe('reader', 'store');
if (stripos($reader, 'Acesso negado') === false) {
    failFast('Perfil reader deveria ser bloqueado para escrita em auditorias');
}
ok('Perfil reader bloqueado para escrita em auditorias');

echo "All auditorias permission integration tests passed.\n";
