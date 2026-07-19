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
if (!AccessControl::canAccessRoute('avaliacoes/store', 'POST', $clienteAdmin)
    || !AccessControl::canAccessRoute('manuais/index', 'GET', $clienteAdmin)
    || !AccessControl::canAccessRoute('cronograma/store', 'POST', $clienteAdmin)
    || !AccessControl::canAccessRoute('planoacao/updateTask', 'POST', $clienteAdmin)
    || !AccessControl::canAccessRoute('tarefas/delete', 'POST', $clienteAdmin)
    || !AccessControl::canAccessRoute('agenda/index', 'GET', $clienteAdmin)
    || !AccessControl::canAccessRoute('reunioes/store', 'POST', $clienteAdmin)
    || !AccessControl::canAccessRoute('indicadores/index', 'GET', $clienteAdmin)
    || !AccessControl::canAccessRoute('auditorias/index', 'GET', $clienteAdmin)
    || !AccessControl::canAccessRoute('colaboradores/create', 'GET', $clienteAdmin)
    || !AccessControl::canAccessRoute('colaboradores/delete', 'POST', $clienteAdmin)) {
    failFast('Cliente admin deveria possuir CRUD completo nos módulos operacionais/permitidos');
}
if (AccessControl::canAccessRoute('clientes/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('dashboard/index', 'GET', $clienteAdmin)) {
    failFast('Cliente admin não deveria acessar módulos restritos ao Instituto');
}
ok('Cliente admin respeita limites administrativos');

if (AccessControl::canAccessRoute('usuarios/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('usuarios/create', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('usuarios/store', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('usuarios/edit', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('usuarios/update', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('usuarios/delete', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('consultores/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('consultores/store', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('pilares/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('pilares/store', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('departamentos/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('departamentos/create', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('departamentos/store', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('departamentos/edit', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('departamentos/update', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('departamentos/delete', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('setores/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('setores/delete', 'POST', $clienteAdmin)
    || AccessControl::canAccessRoute('funcoes/index', 'GET', $clienteAdmin)
    || AccessControl::canAccessRoute('funcoes/delete', 'POST', $clienteAdmin)) {
    failFast('Cliente admin não deveria acessar cadastros estruturais do sistema (usuários, consultores, pilares, departamentos, setores, funções)');
}
ok('Cliente admin é bloqueado dos cadastros estruturais do sistema (usuários/consultores/pilares/departamentos/setores/funções)');

$clienteEditor = userRole('cliente');
if (!AccessControl::canAccessRoute('departamentos/edit', 'GET', $clienteEditor)
    || !AccessControl::canAccessRoute('avaliacoes/create', 'GET', $clienteEditor)
    || !AccessControl::canAccessRoute('avaliacoes/store', 'POST', $clienteEditor)) {
    failFast('Cliente editor deveria acessar leitura e escrita sem exclusão');
}
if (AccessControl::canAccessRoute('departamentos/delete', 'POST', $clienteEditor)
    || AccessControl::canAccessRoute('manuais/index', 'GET', $clienteEditor)
    || AccessControl::canAccessRoute('cronograma/index', 'GET', $clienteEditor)
    || AccessControl::canAccessRoute('planoacao/index', 'GET', $clienteEditor)
    || AccessControl::canAccessRoute('tarefas/index', 'GET', $clienteEditor)
    || AccessControl::canAccessRoute('agenda/index', 'GET', $clienteEditor)
    || AccessControl::canAccessRoute('reunioes/index', 'GET', $clienteEditor)
    || AccessControl::canAccessRoute('usuarios/index', 'GET', $clienteEditor)
    || AccessControl::canDeleteRoute('avaliacoes/delete-ajax', 'POST', $clienteEditor)) {
    failFast('Cliente editor não deveria possuir exclusão');
}
ok('Cliente editor bloqueia exclusão e módulos exclusivos de Cliente Admin');

$reader = userRole('reader');
if (!AccessControl::canAccessRoute('avaliacoes/index', 'GET', $reader)
    || !AccessControl::canAccessRoute('departamentos/index', 'GET', $reader)) {
    failFast('Cliente leitor deveria acessar somente leitura');
}
if (AccessControl::canAccessRoute('avaliacoes/create', 'GET', $reader)
    || AccessControl::canAccessRoute('departamentos/edit', 'GET', $reader)
    || AccessControl::canAccessRoute('manuais/index', 'GET', $reader)
    || AccessControl::canAccessRoute('cronograma/index', 'GET', $reader)
    || AccessControl::canAccessRoute('planoacao/index', 'GET', $reader)
    || AccessControl::canAccessRoute('tarefas/index', 'GET', $reader)
    || AccessControl::canAccessRoute('agenda/index', 'GET', $reader)
    || AccessControl::canAccessRoute('reunioes/index', 'GET', $reader)
    || AccessControl::canAccessRoute('usuarios/index', 'GET', $reader)
    || AccessControl::canAccessRoute('avaliacoes/store', 'POST', $reader)
    || AccessControl::canAccessRoute('avaliacoes/delete-ajax', 'POST', $reader)) {
    failFast('Cliente leitor não deveria acessar ações de escrita');
}
ok('Cliente leitor permanece somente leitura e sem acesso aos módulos de Cliente Admin');

if (AccessControl::defaultHomeRoute($instituto) !== 'dashboard/index') {
    failFast('Home do Instituto deveria ser o dashboard');
}
if (AccessControl::defaultHomeRoute($consultor) !== 'avaliacoes/index') {
    failFast('Home do consultor deveria apontar para um módulo permitido');
}
ok('Rotas iniciais respeitam o perfil');

echo "All RBAC route matrix unit tests passed.\n";
