<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Models\ManualModel;

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
if (!ManualModel::canManage() || !ManualModel::canDelete()) {
    failFast('Permissões de manuais para cliente_admin divergentes');
}
ok('Permissões de manuais para cliente_admin (editar/excluir ok)');

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
if (ManualModel::canManage() || ManualModel::canDelete()) {
    failFast('Permissões de manuais para reader divergentes');
}
ok('Permissões de manuais para reader (somente leitura)');

Auth::login([
    'id' => 13,
    'nome' => 'Instituto',
    'email' => 'inst@test.local',
    'tipo_acesso' => 'instituto',
    'id_cliente' => null,
]);
if (!Auth::isInstituto() || !ManualModel::canManage() || !ManualModel::canDelete()) {
    failFast('Permissões de manuais para instituto divergentes');
}
ok('Permissões de manuais para instituto (editar/excluir ok)');

echo "All auth role unit tests passed.\n";
