<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

session_start();

$canManage = static function (): bool {
    return Auth::isConsultor() || Auth::isInstituto();
};

Auth::login([
    'id' => 301,
    'nome' => 'Consultor',
    'email' => 'cons@test.local',
    'tipo_acesso' => 'consultor',
    'id_cliente' => 1,
]);
if (!$canManage()) {
    failFast('Consultor deveria ter permissão de escrita em auditorias');
}
ok('Consultor com escrita');

Auth::login([
    'id' => 302,
    'nome' => 'Instituto',
    'email' => 'inst@test.local',
    'tipo_acesso' => 'instituto',
    'id_cliente' => null,
]);
if (!$canManage()) {
    failFast('Instituto deveria ter permissão de escrita em auditorias');
}
ok('Instituto com escrita');

Auth::login([
    'id' => 303,
    'nome' => 'Empresa',
    'email' => 'emp@test.local',
    'tipo_acesso' => 'cliente',
    'id_cliente' => 1,
]);
if ($canManage()) {
    failFast('Perfil empresa não deveria ter permissão de escrita em auditorias');
}
ok('Perfil empresa somente leitura');

Auth::login([
    'id' => 304,
    'nome' => 'Reader',
    'email' => 'reader@test.local',
    'tipo_acesso' => 'reader',
    'id_cliente' => 1,
]);
if ($canManage()) {
    failFast('Reader não deveria ter permissão de escrita em auditorias');
}
ok('Reader somente leitura');

echo "All auditorias permission unit tests passed.\n";
