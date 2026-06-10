<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AccessControl;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function userRole(string $role): array
{
    return [
        'id' => 1,
        'nome' => ucfirst($role),
        'email' => $role . '@test.local',
        'tipo_acesso' => $role,
        'id_cliente' => 1,
        'allowed_client_ids' => [1],
    ];
}

$admin = userRole('cliente_admin');
if (!AccessControl::canAccessRoute('pwa/indicadores', 'GET', $admin)) {
    failFast('PWA indicadores deveria respeitar mapeamento para modulo operacional e permitir cliente_admin');
}
ok('PWA indicadores permite cliente_admin');

$reader = userRole('reader');
if (!AccessControl::canAccessRoute('pwa/indicadores', 'GET', $reader)) {
    failFast('PWA indicadores deveria permitir leitura para reader');
}
if (AccessControl::canAccessRoute('pwa/indicadores/create', 'GET', $reader)) {
    failFast('Reader nao deveria ter escrita no PWA');
}
ok('PWA respeita somente leitura para reader');

if (AccessControl::moduleForRoute('pwa/dashboard') !== 'public') {
    failFast('PWA dashboard deveria ser tratado como publico para classificacao de modulo');
}
ok('PWA dashboard tratado como publico');

echo "All PWA route mapping unit tests passed.\n";

