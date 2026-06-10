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

$instituto = userRole('instituto');
if (!AccessControl::canAccessRoute('dashboard/index', 'GET', $instituto)
    || !AccessControl::canAccessRoute('usuarios/delete', 'GET', $instituto)
    || !AccessControl::canAccessRoute('auditorias/api_delete', 'POST', $instituto)) {
    failFast('Instituto deveria possuir acesso total');
}
ok('Instituto possui acesso total');

$consultor = userRole('consultor');
if (!AccessControl::canAccessRoute('departamentos/index', 'GET', $consultor)
    || !AccessControl::canAccessRoute('colaboradores/edit', 'GET', $consultor)
    || !AccessControl::canAccessRoute('avaliacoes/delete-ajax', 'POST', $consultor)) {
    failFast('Consultor deveria possuir CRUD completo em cadastros e avaliações');
}
if (AccessControl::canAccessRoute('indicadores/index', 'GET', $consultor)
    || AccessControl::canAccessRoute('usuarios/index', 'GET', $consultor)
    || AccessControl::canAccessRoute('auditorias/index', 'GET', $consultor)) {
    failFast('Consultor não deveria acessar módulos administrativos fora do RBAC');
}
ok('Consultor respeita escopo de módulos permitidos');

$clienteAdmin = userRole('cliente_admin');
if (!AccessControl::canAccessRoute('departamentos/create', 'GET', $clienteAdmin)
    || !AccessControl::canAccessRoute('departamentos/delete', 'POST', $clienteAdmin)
    || !AccessControl::canAccessRoute('avaliacoes/store', 'POST', $clienteAdmin)) {
    failFast('Cliente admin deveria possuir CRUD completo nos módulos permitidos');
}
if (AccessControl::canAccessRoute('clientes/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('dashboard/index', 'GET', $clienteAdmin)) {
    failFast('Cliente admin não deveria acessar módulos restritos ao Instituto');
}
ok('Cliente admin respeita limites administrativos');

$clienteEditor = userRole('cliente');
if (!AccessControl::canAccessRoute('departamentos/edit', 'GET', $clienteEditor)
    || !AccessControl::canAccessRoute('avaliacoes/create', 'GET', $clienteEditor)
    || !AccessControl::canAccessRoute('avaliacoes/store', 'POST', $clienteEditor)) {
    failFast('Cliente editor deveria acessar leitura e escrita sem exclusão');
}
if (AccessControl::canAccessRoute('departamentos/delete', 'POST', $clienteEditor)
    || AccessControl::canDeleteRoute('avaliacoes/delete-ajax', 'POST', $clienteEditor)) {
    failFast('Cliente editor não deveria possuir exclusão');
}
ok('Cliente editor bloqueia exclusão');

$reader = userRole('reader');
if (!AccessControl::canAccessRoute('avaliacoes/index', 'GET', $reader)
    || !AccessControl::canAccessRoute('departamentos/index', 'GET', $reader)) {
    failFast('Cliente leitor deveria acessar somente leitura');
}
if (AccessControl::canAccessRoute('avaliacoes/create', 'GET', $reader)
    || AccessControl::canAccessRoute('departamentos/edit', 'GET', $reader)
    || AccessControl::canAccessRoute('avaliacoes/store', 'POST', $reader)
    || AccessControl::canAccessRoute('avaliacoes/delete-ajax', 'POST', $reader)) {
    failFast('Cliente leitor não deveria acessar ações de escrita');
}
ok('Cliente leitor permanece somente leitura');

if (AccessControl::defaultHomeRoute($instituto) !== 'dashboard/index') {
    failFast('Home do Instituto deveria ser o dashboard');
}
if (AccessControl::defaultHomeRoute($consultor) !== 'avaliacoes/index') {
    failFast('Home do consultor deveria apontar para um módulo permitido');
}
ok('Rotas iniciais respeitam o perfil');

echo "All RBAC route matrix unit tests passed.\n";
