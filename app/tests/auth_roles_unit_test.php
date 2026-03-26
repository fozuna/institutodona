<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

session_start();

Auth::login([
    'id' => 11,
    'nome' => 'Cliente Admin',
    'email' => 'ca@test.local',
    'tipo_acesso' => 'cliente_admin',
    'id_cliente' => 1,
]);
if (!Auth::isClienteAdmin() || !Auth::isCliente() || Auth::isReader()) {
    failFast('Perfil cliente_admin não foi identificado corretamente');
}
ok('Perfil cliente_admin reconhecido');

Auth::login([
    'id' => 12,
    'nome' => 'Reader',
    'email' => 'reader@test.local',
    'tipo_acesso' => 'reader',
    'id_cliente' => 1,
]);
if (!Auth::isReader() || Auth::isClienteAdmin()) {
    failFast('Perfil reader não foi identificado corretamente');
}
ok('Perfil reader reconhecido');

echo "All auth role unit tests passed.\n";
