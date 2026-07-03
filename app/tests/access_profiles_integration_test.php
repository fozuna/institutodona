<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function runProbe(string $route, string $method, string $role, string $allowedClients = '1', string $query = ''): string
{
    $root = realpath(__DIR__ . '/..');
    $probe = $root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'access_gate_probe.php';
    $cmd = 'php '
        . escapeshellarg($probe) . ' '
        . escapeshellarg($route) . ' '
        . escapeshellarg($method) . ' '
        . escapeshellarg($role) . ' '
        . escapeshellarg($allowedClients) . ' '
        . escapeshellarg($query);
    $out = [];
    @exec($cmd . ' 2>&1', $out);
    return trim(implode("\n", $out));
}

$r1 = runProbe('avaliacoes/show', 'GET', 'reader');
if (strpos($r1, 'ALLOWED') === false) {
    failFast('Cliente leitor deveria acessar rota de visualização permitida');
}
ok('Cliente leitor acessa visualização permitida');

$r2 = runProbe('avaliacoes/create', 'GET', 'reader');
if (stripos($r2, 'perfil não permite executar esta ação') === false) {
    failFast('Cliente leitor deveria ser bloqueado na tela de criação');
}
ok('Cliente leitor bloqueado na tela de criação');

$r3 = runProbe('avaliacoes/store', 'POST', 'reader');
if (stripos($r3, 'você não possui permissão para acessar este recurso') === false
    && stripos($r3, 'perfil não permite executar esta ação') === false) {
    failFast('Cliente leitor deveria ser bloqueado em POST de escrita');
}
ok('Cliente leitor bloqueado em POST de escrita');

$r4 = runProbe('departamentos/delete', 'GET', 'cliente');
if (stripos($r4, 'perfil não permite executar esta ação') === false) {
    failFast('Cliente editor não deveria conseguir excluir');
}
ok('Cliente editor bloqueado em exclusão');

$r5 = runProbe('departamentos/delete', 'GET', 'cliente_admin');
if (strpos($r5, 'ALLOWED') === false) {
    failFast('Cliente admin deveria conseguir excluir no módulo de cadastros');
}
ok('Cliente admin pode excluir no módulo de cadastros');

$r6 = runProbe('colaboradores/edit', 'GET', 'consultor');
if (strpos($r6, 'ALLOWED') === false) {
    failFast('Consultor deveria conseguir editar no módulo de cadastros permitido');
}
ok('Consultor acessa telas de edição permitidas');

$r7 = runProbe('indicadores/index', 'GET', 'consultor');
if (stripos($r7, 'você não possui permissão para acessar este recurso') === false) {
    failFast('Consultor deveria ser bloqueado fora dos módulos permitidos');
}
ok('Consultor bloqueado fora dos módulos permitidos');

$r8 = runProbe('dashboard/index', 'GET', 'instituto');
if (strpos($r8, 'ALLOWED') === false) {
    failFast('Instituto deveria acessar rotas administrativas');
}
ok('Instituto acessa rotas administrativas');

$r9 = runProbe('avaliacoes/show', 'GET', 'consultor', '1', 'cliente=2');
if (stripos($r9, 'este recurso não pertence à sua empresa') === false) {
    failFast('Manipulação de cliente fora do escopo deveria ser bloqueada');
}
ok('Manipulação de cliente fora do escopo é bloqueada');

$r10 = runProbe('indicadores/store', 'POST', 'cliente_admin');
if (strpos($r10, 'ALLOWED') === false) {
    failFast('Cliente admin deveria acessar o módulo de indicadores (operacional) no escopo permitido');
}
ok('Cliente admin acessa indicadores no escopo permitido');

$r11 = runProbe('avaliacoes/store', 'POST', 'cliente_admin');
if (strpos($r11, 'ALLOWED') === false) {
    failFast('Cliente admin deveria acessar escrita em escopo permitido');
}
ok('Cliente admin permite escrita em módulo permitido');

$r12 = runProbe('usuarios/delete', 'GET', 'instituto');
if (strpos($r12, 'ALLOWED') === false) {
    failFast('Instituto deveria conseguir gerenciar usuários');
}
ok('Instituto mantém acesso total');

$r13 = runProbe('usuarios/delete', 'GET', 'cliente_admin');
if (strpos($r13, 'ALLOWED') === false) {
    failFast('Cliente admin deveria conseguir gerenciar usuários vinculados ao cliente');
}
ok('Cliente admin acessa gerenciamento de usuários do cliente');

$r14 = runProbe('departamentos/index', 'GET', 'consultor', '1', 'cliente=2');
if (stripos($r14, 'este recurso não pertence à sua empresa') === false) {
    failFast('Acesso cruzado entre empresas deveria ser negado');
}
ok('Acesso cruzado entre empresas é negado');

$r15 = runProbe('avaliacoes/create', 'GET', 'cliente_admin');
if (strpos($r15, 'ALLOWED') === false) {
    failFast('Cliente admin deveria acessar a tela de criação');
}
ok('Cliente admin acessa tela de criação');

$r16 = runProbe('avaliacoes/create', 'GET', 'reader');
if (stripos($r16, 'perfil não permite executar esta ação') === false) {
    failFast('Cliente leitor não deveria acessar a tela de criação');
}
ok('Cliente leitor permanece somente leitura');

$r17 = runProbe('avaliacoes/index', 'GET', 'cliente_admin', '1', 'cliente=2');
if (stripos($r17, 'este recurso não pertence à sua empresa') === false) {
    failFast('Acesso direto por URL com cliente externo deveria ser bloqueado');
}
ok('Acesso direto por URL com cliente externo é bloqueado');

$r18 = runProbe('avaliacoes/show', 'GET', 'instituto', '1', 'cliente=999');
if (strpos($r18, 'ALLOWED') === false) {
    failFast('Instituto deveria ignorar restrições de escopo');
}
ok('Instituto ignora restrições de escopo');

$r19 = runProbe('avaliacoes/delete-ajax', 'POST', 'consultor');
if (strpos($r19, 'ALLOWED') === false) {
    failFast('Consultor deveria conseguir excluir em módulos permitidos');
}
ok('Consultor possui CRUD completo nos módulos permitidos');

$r20 = runProbe('avaliacoes/delete-ajax', 'POST', 'cliente');
if (stripos($r20, 'perfil não permite executar esta ação') === false) {
    failFast('Cliente editor deveria ser bloqueado em exclusão via API/POST');
}
ok('Cliente editor bloqueado em exclusão via API/POST');

$r21 = runProbe('cronograma/index', 'GET', 'cliente');
if (stripos($r21, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('Cliente editor deveria ser bloqueado no cronograma');
}
ok('Cliente editor bloqueado no cronograma');

$r22 = runProbe('planoacao/index', 'GET', 'reader');
if (stripos($r22, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('Cliente leitor deveria ser bloqueado em plano de ação');
}
ok('Cliente leitor bloqueado em plano de ação');

$r23 = runProbe('tarefas/index', 'GET', 'cliente');
if (stripos($r23, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('Cliente editor deveria ser bloqueado em tarefas');
}
ok('Cliente editor bloqueado em tarefas');

$r24 = runProbe('agenda/index', 'GET', 'reader');
if (stripos($r24, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('Cliente leitor deveria ser bloqueado em agenda');
}
ok('Cliente leitor bloqueado em agenda');

$r25 = runProbe('manuais/index', 'GET', 'cliente');
if (stripos($r25, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('Cliente editor deveria ser bloqueado na biblioteca de manuais');
}
ok('Cliente editor bloqueado na biblioteca de manuais');

$r26 = runProbe('reunioes/index', 'GET', 'reader');
if (stripos($r26, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('Cliente leitor deveria ser bloqueado em reuniões');
}
ok('Cliente leitor bloqueado em reuniões');

$r27 = runProbe('usuarios/index', 'GET', 'cliente');
if (stripos($r27, 'acesso restrito ao perfil cliente admin da empresa') === false) {
    failFast('Cliente editor deveria ser bloqueado no gerenciamento de usuários');
}
ok('Cliente editor bloqueado no gerenciamento de usuários');

echo "All access profile integration tests passed.\n";
