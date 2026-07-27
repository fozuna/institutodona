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

/**
 * Desde a página 404 oculta (que substitui as mensagens de negação
 * específicas por uma resposta genérica de "não encontrado"), o critério de
 * "bloqueado" deixou de ser o texto da mensagem antiga e passou a ser:
 * não retornou "ALLOWED" e retornou a página personalizada de 404.
 */
function assertBlocked(string $response, string $message): void
{
    if (strpos($response, 'ALLOWED') !== false) {
        failFast($message . ' (retornou ALLOWED, deveria ter sido bloqueado)');
    }
    if (strpos($response, 'Conteúdo não encontrado') === false) {
        failFast($message . ' (bloqueado, mas não retornou a página personalizada de 404)');
    }
}

$r1 = runProbe('avaliacoes/show', 'GET', 'reader');
if (strpos($r1, 'ALLOWED') === false) {
    failFast('Cliente leitor deveria acessar rota de visualização permitida');
}
ok('Cliente leitor acessa visualização permitida');

$r2 = runProbe('avaliacoes/create', 'GET', 'reader');
assertBlocked($r2, 'Cliente leitor deveria ser bloqueado na tela de criação');
ok('Cliente leitor bloqueado na tela de criação (oculto como 404)');

$r3 = runProbe('avaliacoes/store', 'POST', 'reader');
assertBlocked($r3, 'Cliente leitor deveria ser bloqueado em POST de escrita');
ok('Cliente leitor bloqueado em POST de escrita (oculto como 404)');

$r4 = runProbe('departamentos/delete', 'GET', 'cliente');
assertBlocked($r4, 'Cliente editor não deveria conseguir excluir');
ok('Cliente editor bloqueado em exclusão (oculto como 404)');

// Nota: departamentos é um cadastro estrutural do sistema, bloqueado para
// Cliente Admin via CLIENT_ADMIN_FORBIDDEN_PREFIXES (AccessControl.php) desde
// o commit e4c8bba, anterior a esta sessão - a asserção antiga aqui esperava
// o oposto (bug de teste desatualizado, não uma regressão desta entrega).
$r5 = runProbe('departamentos/delete', 'GET', 'cliente_admin');
assertBlocked($r5, 'Cliente admin não deveria excluir departamentos (cadastro estrutural do sistema)');
ok('Cliente admin bloqueado em departamentos/delete, cadastro estrutural (oculto como 404)');

$r6 = runProbe('colaboradores/edit', 'GET', 'consultor');
if (strpos($r6, 'ALLOWED') === false) {
    failFast('Consultor deveria conseguir editar no módulo de cadastros permitido');
}
ok('Consultor acessa telas de edição permitidas');

$r7 = runProbe('indicadores/index', 'GET', 'consultor');
assertBlocked($r7, 'Consultor deveria ser bloqueado fora dos módulos permitidos');
ok('Consultor bloqueado fora dos módulos permitidos (oculto como 404)');

$r8 = runProbe('dashboard/index', 'GET', 'instituto');
if (strpos($r8, 'ALLOWED') === false) {
    failFast('Instituto deveria acessar rotas administrativas');
}
ok('Instituto acessa rotas administrativas');

$r9 = runProbe('avaliacoes/show', 'GET', 'consultor', '1', 'cliente=2');
assertBlocked($r9, 'Manipulação de cliente fora do escopo deveria ser bloqueada');
ok('Manipulação de cliente fora do escopo é bloqueada (oculta como 404)');

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

// Nota: assim como departamentos, "usuarios" é cadastro estrutural do sistema,
// bloqueado para Cliente Admin via CLIENT_ADMIN_FORBIDDEN_PREFIXES desde o
// commit e4c8bba (anterior a esta sessão) - regra de negócio confirmada:
// Cliente Admin não cadastra usuários. Asserção antiga desatualizada.
$r13 = runProbe('usuarios/delete', 'GET', 'cliente_admin');
assertBlocked($r13, 'Cliente admin não deveria gerenciar usuários (cadastro estrutural do sistema)');
ok('Cliente admin bloqueado em usuarios/delete, cadastro estrutural (oculto como 404)');

$r14 = runProbe('departamentos/index', 'GET', 'consultor', '1', 'cliente=2');
assertBlocked($r14, 'Acesso cruzado entre empresas deveria ser negado');
ok('Acesso cruzado entre empresas é negado (oculto como 404)');

$r15 = runProbe('avaliacoes/create', 'GET', 'cliente_admin');
if (strpos($r15, 'ALLOWED') === false) {
    failFast('Cliente admin deveria acessar a tela de criação');
}
ok('Cliente admin acessa tela de criação');

$r16 = runProbe('avaliacoes/create', 'GET', 'reader');
assertBlocked($r16, 'Cliente leitor não deveria acessar a tela de criação');
ok('Cliente leitor permanece somente leitura (oculto como 404)');

$r17 = runProbe('avaliacoes/index', 'GET', 'cliente_admin', '1', 'cliente=2');
assertBlocked($r17, 'Acesso direto por URL com cliente externo deveria ser bloqueado');
ok('Acesso direto por URL com cliente externo é bloqueado (oculto como 404)');

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
assertBlocked($r20, 'Cliente editor deveria ser bloqueado em exclusão via API/POST');
ok('Cliente editor bloqueado em exclusão via API/POST (oculto como 404)');

$r21 = runProbe('cronograma/index', 'GET', 'cliente');
assertBlocked($r21, 'Cliente editor deveria ser bloqueado no cronograma');
ok('Cliente editor bloqueado no cronograma (oculto como 404)');

$r22 = runProbe('planoacao/index', 'GET', 'reader');
assertBlocked($r22, 'Cliente leitor deveria ser bloqueado em plano de ação');
ok('Cliente leitor bloqueado em plano de ação (oculto como 404)');

$r23 = runProbe('tarefas/index', 'GET', 'cliente');
assertBlocked($r23, 'Cliente editor deveria ser bloqueado em tarefas');
ok('Cliente editor bloqueado em tarefas (oculto como 404)');

$r24 = runProbe('agenda/index', 'GET', 'reader');
assertBlocked($r24, 'Cliente leitor deveria ser bloqueado em agenda');
ok('Cliente leitor bloqueado em agenda (oculto como 404)');

$r25 = runProbe('manuais/index', 'GET', 'cliente');
assertBlocked($r25, 'Cliente editor deveria ser bloqueado na biblioteca de manuais');
ok('Cliente editor bloqueado na biblioteca de manuais (oculto como 404)');

$r26 = runProbe('reunioes/index', 'GET', 'reader');
assertBlocked($r26, 'Cliente leitor deveria ser bloqueado em reuniões');
ok('Cliente leitor bloqueado em reuniões (oculto como 404)');

$r27 = runProbe('usuarios/index', 'GET', 'cliente');
assertBlocked($r27, 'Cliente editor deveria ser bloqueado no gerenciamento de usuários');
ok('Cliente editor bloqueado no gerenciamento de usuários (oculto como 404)');

echo "All access profile integration tests passed.\n";
