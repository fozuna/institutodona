<?php
namespace App\Controllers {
    // Intercepta header() apenas dentro do namespace do controller (resolução de função
    // por namespace do PHP), para capturar o "Location:" real emitido por
    // AuthController::doLogin() sem depender de headers_list() (não confiável em CLI).
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__captured_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require_once __DIR__ . '/../autoload.php';

    use App\Controllers\AuthController;
    use App\Controllers\DashboardController;
    use App\Core\Security;
    use App\Database\Database;
    use App\Models\ClienteModel;
    use App\Models\UsuarioModel;

    function ok(string $msg): void { echo "OK: {$msg}\n"; }
    function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

    /**
     * Reexecuta cenários que terminam em exit() (denyAccess() no bloqueio de tenant)
     * num processo separado - reaproveita o probe já existente no repositório
     * (helpers/error_404_probe.php), criado justamente para isolar esse tipo de
     * checagem sem matar o processo do teste principal.
     */
    function runAccessProbe(string $mode, string $route, string $role, bool $ajax = false, int $clienteId = 1, int $foreignCliente = 0): array
    {
        $probe = __DIR__ . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'error_404_probe.php';
        $cmd = 'php ' . escapeshellarg($probe) . ' '
            . escapeshellarg($mode) . ' '
            . escapeshellarg($route) . ' '
            . escapeshellarg($role) . ' '
            . escapeshellarg($ajax ? '1' : '0') . ' '
            . escapeshellarg((string)$clienteId) . ' '
            . escapeshellarg((string)$foreignCliente);
        $out = [];
        @exec($cmd . ' 2>&1', $out);
        $body = implode("\n", $out);
        $status = null;
        if (preg_match('/---STATUS:(\d*)---/', $body, $m)) {
            $status = $m[1] !== '' ? (int)$m[1] : null;
        }
        return ['body' => $body, 'status' => $status];
    }

    function resetRequest(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($GLOBALS['__captured_location']);
    }

    $pdo = Database::getConnection();
    $clientes = new ClienteModel();
    $suffix = substr(bin2hex(random_bytes(4)), 0, 8);

    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $clienteAId = $clientes->create(['nome_empresa' => 'Cliente Dashboard A ' . $suffix, 'CNPJ' => '88.999.0' . substr($suffix, 0, 2) . '/0001-88', 'contato' => 'Contato A']);
    $clienteBId = $clientes->create(['nome_empresa' => 'Cliente Dashboard B ' . $suffix, 'CNPJ' => '99.000.1' . substr($suffix, 0, 2) . '/0001-99', 'contato' => 'Contato B']);
    if ($clienteAId <= 0 || $clienteBId <= 0) { failFast('Falha ao criar clientes de teste'); }
    ok('Criou clientes A e B (tenants distintos) para o teste');

    $usuarios = new UsuarioModel();
    $email = 'cliente.admin.dash.' . $suffix . '@example.com';
    $senha = 'SenhaForte#' . $suffix;
    $usuarioId = $usuarios->create([
        'nome' => 'Cliente Admin Dashboard ' . $suffix,
        'email' => $email,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        'tipo_acesso' => 'cliente_admin',
        'id_cliente' => $clienteAId,
    ]);
    if ($usuarioId <= 0) { failFast('Falha ao criar usuário Cliente Admin de teste'); }
    ok('Criou usuário Cliente Admin vinculado à empresa A');

    // ===================== LOGIN =====================

    // 1/2/3) Login real do Cliente Admin -> redireciona para dashboard/index, não avaliacoes/index.
    resetRequest();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION = []; // limpa sessão de instituto antes do login real
    $_POST = [
        'email' => $email,
        'senha' => $senha,
        'csrf' => Security::csrfToken(),
    ];
    ob_start();
    (new AuthController())->doLogin();
    ob_end_clean();
    $location = (string)($GLOBALS['__captured_location'] ?? '');
    if (strpos($location, 'route=dashboard/index') === false) {
        failFast('Login do Cliente Admin não redirecionou para dashboard/index (obtido: ' . $location . ')');
    }
    if (strpos($location, 'avaliacoes') !== false) {
        failFast('Login do Cliente Admin ainda caiu em avaliacoes/index');
    }
    ok('Login do Cliente Admin redireciona direto para dashboard/index (não mais avaliacoes/index)');
    if (($_SESSION['user']['tipo_acesso'] ?? '') !== 'cliente_admin') {
        failFast('Sessão pós-login não corresponde ao usuário Cliente Admin esperado');
    }
    ok('Sessão autenticada corretamente como Cliente Admin da empresa A');

    // 4) Dashboard realmente renderiza (200/sem erro) para esse usuário logado.
    resetRequest();
    $_GET['route'] = 'dashboard/index';
    ob_start();
    (new DashboardController())->index();
    $dashboardHtml = (string)ob_get_clean();
    if (strpos($dashboardHtml, 'Dashboard Operacional') === false && strpos($dashboardHtml, 'Indicadores de Progresso') === false) {
        failFast('Dashboard não renderizou o conteúdo esperado para o Cliente Admin');
    }
    ok('Dashboard retorna e renderiza normalmente para o Cliente Admin (regressão do antigo 404 oculto)');

    // ===================== TENANT =====================

    // 5) Sem filtro explícito, o dashboard já cai automaticamente no escopo da própria
    // empresa (scopeClienteIds() usa Auth::allowedClientIds() como fallback).
    if (strpos($dashboardHtml, 'Cliente Dashboard B ' . $suffix) !== false) {
        failFast('Dashboard do Cliente Admin expôs referência à empresa B (vazamento cross-tenant)');
    }
    ok('Dashboard não expõe a empresa B enquanto logado como Cliente Admin da empresa A');

    // 6/7/8) Tentativa de manipular ?cliente= para a empresa B é bloqueada (404 oculto) -
    // antes desta correção isso "passava" só porque o módulo inteiro já barrava Cliente
    // Admin; agora o bloqueio depende genuinamente do gate de tenant (routeClienteCandidate()
    // + Auth::canAccessCliente()), não mais do módulo.
    $rTenant = runAccessProbe('tenant', 'dashboard/index', 'cliente_admin', false, $clienteAId, $clienteBId);
    if ($rTenant['status'] !== 404) {
        failFast('Cliente Admin manipulando ?cliente= para outra empresa deveria receber 404 oculto (status obtido: ' . $rTenant['status'] . ')');
    }
    if (strpos($rTenant['body'], 'Conteúdo não encontrado') === false) {
        failFast('Bloqueio de tenant no Dashboard não retornou a página personalizada de 404');
    }
    ok('Cliente Admin tentando ver outra empresa via ?cliente= no Dashboard é bloqueado com 404 oculto (gate de tenant, não mais só de módulo)');

    // Regressão: acesso válido (própria empresa, sem manipulação) continua funcionando via probe isolado.
    $rValid = runAccessProbe('valid', 'dashboard/index', 'cliente_admin', false, $clienteAId, 0);
    if ($rValid['status'] === 404) {
        failFast('Acesso legítimo do Cliente Admin ao Dashboard da própria empresa foi bloqueado indevidamente');
    }
    ok('Acesso legítimo do Cliente Admin ao Dashboard (sem manipulação) continua funcionando');

    // Endpoint AJAX (apiDepartamentos) também respeita o tenant: pedir departamentos da
    // empresa B não deve retornar nada (scopeClienteIds() filtra antes da consulta).
    resetRequest();
    $_SESSION['user'] = [
        'id' => 999999998,
        'nome' => 'Probe AJAX',
        'email' => 'probe.ajax@example.com',
        'tipo_acesso' => 'cliente_admin',
        'id_cliente' => $clienteAId,
        'allowed_client_ids' => [$clienteAId],
    ];
    $_GET['route'] = 'dashboard/apiDepartamentos';
    $_GET['clientes'] = [(string)$clienteBId];
    ob_start();
    (new DashboardController())->apiDepartamentos();
    $ajaxOut = (string)ob_get_clean();
    $ajaxPayload = json_decode($ajaxOut, true);
    if (!is_array($ajaxPayload) || !empty($ajaxPayload['departamentos'])) {
        failFast('Endpoint AJAX dashboard/apiDepartamentos retornou dados ao pedir a empresa B fora do tenant: ' . $ajaxOut);
    }
    ok('Endpoint AJAX dashboard/apiDepartamentos respeita o tenant (empresa fora do escopo não retorna dados)');

    // ===================== MENU =====================

    $_SESSION['user'] = [
        'id' => 999999997,
        'nome' => 'Probe Menu',
        'email' => 'probe.menu@example.com',
        'tipo_acesso' => 'cliente_admin',
        'id_cliente' => $clienteAId,
        'allowed_client_ids' => [$clienteAId],
    ];
    $_GET['route'] = 'dashboard/index';
    $content = '<div>Painel de teste</div>';
    ob_start();
    require __DIR__ . '/../views/layouts/main.php';
    $menuHtml = (string)ob_get_clean();

    if (!preg_match('/href="index\.php\?route=dashboard\/index"[^>]*>[^<]*<span[^>]*><\/span>\s*<span[^>]*>Dashboard/s', $menuHtml)
        && strpos($menuHtml, 'route=dashboard/index') === false) {
        failFast('Menu do Cliente Admin não exibe o link de Dashboard');
    }
    ok('Menu do Cliente Admin exibe o Dashboard (item 9)');

    // Avaliações é um módulo legítimo e deliberado para Cliente Admin (AVALIACOES_MODULE
    // já está na lista de módulos desse perfil) - continua no menu, só deixou de ser a
    // home pós-login.
    if (strpos($menuHtml, 'route=avaliacoes/index') === false) {
        failFast('Menu do Cliente Admin deveria continuar exibindo Avaliações (acesso legítimo, não removido)');
    }
    ok('Menu do Cliente Admin continua exibindo Avaliações (acesso legítimo e deliberado, não é mais só fallback de rota)');

    foreach (['usuarios/index', 'consultores/index', 'pilares/index', 'departamentos/index', 'setores/index', 'funcoes/index', 'clientes/index'] as $forbiddenRoute) {
        if (strpos($menuHtml, 'route=' . $forbiddenRoute) !== false) {
            failFast('Menu do Cliente Admin não deveria exibir link para ' . $forbiddenRoute . ' (cadastro estrutural/administrativo)');
        }
    }
    ok('Menu do Cliente Admin continua ocultando cadastros estruturais e módulos administrativos (item 11)');

    foreach (['treinamentos/index', 'auditorias/index', 'indicadores/index', 'cronograma/index', 'manuais/index', 'planoacao/index', 'tarefas/index'] as $operationalRoute) {
        if (strpos($menuHtml, 'route=' . $operationalRoute) === false) {
            failFast('Menu do Cliente Admin deveria continuar exibindo ' . $operationalRoute . ' (módulo operacional permitido)');
        }
    }
    ok('Menu do Cliente Admin continua exibindo todos os módulos operacionais permitidos (item 12)');

    // ===================== INSTITUTO E OUTROS PERFIS =====================

    resetRequest();
    $rInstitutoValid = runAccessProbe('valid', 'dashboard/index', 'instituto');
    if ($rInstitutoValid['status'] === 404) { failFast('Instituto perdeu acesso ao Dashboard'); }
    ok('Instituto preserva acesso completo ao Dashboard (item 13/14)');

    foreach (['cliente', 'reader', 'consultor'] as $role) {
        $rOther = runAccessProbe('valid', 'dashboard/index', $role, false, $clienteAId, 0);
        if ($rOther['status'] !== 404) {
            failFast('Perfil "' . $role . '" não deveria ganhar acesso ao Dashboard como efeito colateral desta correção');
        }
    }
    ok('Nenhum outro perfil (cliente, reader, consultor) ganhou acesso indevido ao Dashboard (item 16)');

    // Acesso direto de Cliente Admin a clientes/index continua bloqueado (item 19/regra ADMIN_MODULE intacta).
    $rClientesIndex = runAccessProbe('valid', 'clientes/index', 'cliente_admin', false, $clienteAId, 0);
    if ($rClientesIndex['status'] !== 404) {
        failFast('Cliente Admin não deveria ter ganho acesso a clientes/index');
    }
    ok('Cliente Admin continua sem acesso a clientes/index (ADMIN_MODULE intacto)');

    echo "Dashboard Cliente Admin home regression tests passed.\n";
}
