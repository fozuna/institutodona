<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$root = realpath(__DIR__ . '/..');
$probe = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'access_gate_probe.php';
$logFile = __DIR__ . '/../../storage/logs/audit.log';

$before = is_file($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$cmd = 'php '
    . escapeshellarg($probe) . ' '
    . escapeshellarg('cronograma/index') . ' '
    . escapeshellarg('GET') . ' '
    . escapeshellarg('cliente') . ' '
    . escapeshellarg('1');
$out = [];
@exec($cmd . ' 2>&1', $out);
$response = trim(implode("\n", $out));

if (stripos($response, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('A negativa padronizada não foi retornada pelo probe');
}
ok('Negativa padronizada retornada pelo probe');

$after = is_file($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
if (count($after) <= count($before)) {
    failFast('Nenhuma linha nova foi registrada no audit.log');
}

$lastLine = (string)end($after);
$payload = json_decode($lastLine, true);
if (!is_array($payload)) {
    failFast('Última linha do audit.log não é um JSON válido');
}
if (($payload['action'] ?? '') !== 'access_denied') {
    failFast('Último evento do audit.log não registrou access_denied');
}
if (($payload['route'] ?? '') !== 'cronograma/index') {
    failFast('Rota registrada no log não corresponde ao acesso negado');
}
if (($payload['user_tipo'] ?? '') !== 'cliente') {
    failFast('Perfil registrado no log não corresponde ao usuário bloqueado');
}

ok('Log de segurança registra tentativa negada com rota e perfil');

echo "Cliente admin security log smoke passed.\n";
