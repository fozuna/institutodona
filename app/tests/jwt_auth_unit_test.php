<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\JwtService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$user = [
    'id' => 10,
    'nome' => 'Usuário JWT',
    'email' => 'jwt@test.local',
    'tipo_acesso' => 'instituto',
    'id_cliente' => null,
];

$token = JwtService::issue($user, 600);
if (!is_string($token) || strlen($token) < 40) {
    failFast('Token JWT inválido');
}
ok('Token JWT emitido');

$payload = JwtService::decode($token);
if (!$payload || (int)($payload['sub'] ?? 0) !== 10) {
    failFast('Payload JWT não validou corretamente');
}
ok('Token JWT validado');

$bad = JwtService::decode($token . 'x');
if ($bad !== null) {
    failFast('Token adulterado deveria falhar');
}
ok('Token adulterado bloqueado');

echo "All JWT auth unit tests passed.\n";
