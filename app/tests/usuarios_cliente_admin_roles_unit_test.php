<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\UsuariosController;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$clienteAdminRoles = UsuariosController::allowedManageRoles([
    'tipo_acesso' => 'cliente_admin',
]);

if (array_keys($clienteAdminRoles) !== ['cliente_admin', 'cliente', 'reader']) {
    failFast('Cliente Admin deve gerenciar apenas perfis vinculados ao cliente');
}
ok('Cliente Admin gerencia apenas perfis do cliente');

if (UsuariosController::canManageConsultorLinks(['tipo_acesso' => 'cliente_admin'])) {
    failFast('Cliente Admin não deveria vincular consultores');
}
ok('Cliente Admin não vincula consultores');

$institutoRoles = UsuariosController::allowedManageRoles([
    'tipo_acesso' => 'instituto',
]);

if (!isset($institutoRoles['instituto'], $institutoRoles['consultor'])) {
    failFast('Instituto deve manter capacidade de gerenciar perfis globais');
}
ok('Instituto mantém gerenciamento de perfis globais');

if (!UsuariosController::canManageConsultorLinks(['tipo_acesso' => 'instituto'])) {
    failFast('Instituto deveria continuar podendo vincular consultores');
}
ok('Instituto continua vinculando consultores');

echo "All usuarios cliente admin role unit tests passed.\n";
