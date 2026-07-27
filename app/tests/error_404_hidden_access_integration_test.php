<?php
require_once __DIR__ . '/../autoload.php';

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

function runProbe(string $mode, string $route, string $role, bool $ajax = false, int $clienteId = 1, int $foreignCliente = 0): array
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

// --- Cenário 1: rota inexistente ---
$r = runProbe('unknown', 'rota/completamente/inexistente', 'instituto');
assert_true($r['status'] === 404, 'Rota inexistente responde HTTP 404 (não mais 200 no Dashboard)');
assert_true(strpos($r['body'], 'Conteúdo não encontrado') !== false, 'Rota inexistente exibe a página personalizada de 404');
assert_true(strpos($r['body'], 'Voltar para a página anterior') !== false, 'Página 404 exibe o botão de retorno como ação principal');

$rAnon = runProbe('unknown', 'rota/fantasma', 'anon');
assert_true($rAnon['status'] === 404, 'Rota inexistente para usuário anônimo também responde 404');
assert_true(strpos($rAnon['body'], 'Conteúdo não encontrado') !== false, 'Usuário anônimo vê a mesma página personalizada de 404');
assert_true(strpos($rAnon['body'], 'app-sidebar') === false, 'Usuário anônimo não vê o menu interno na página 404');
assert_true(strpos($rAnon['body'], 'Ir para o login') !== false, 'Usuário anônimo vê "Ir para o login" como ação secundária');

// --- Cenário 2: RBAC nega (módulo não liberado para o perfil) ---
$rDenied = runProbe('denied', 'usuarios/index', 'cliente');
assert_true($rDenied['status'] === 404, 'RBAC negando acesso responde HTTP 404, não 403');
assert_true(strpos($rDenied['body'], 'Conteúdo não encontrado') !== false, 'RBAC negado exibe a página personalizada de 404 (oculta o motivo real)');
assert_true(strpos($rDenied['body'], 'Novo Usuário') === false, 'A tela real (Usuários) nunca chega a ser renderizada quando o acesso é negado');
assert_true(stripos($rDenied['body'], 'acesso restrito') === false, 'A mensagem interna real de negação não é exposta ao usuário');
assert_true(stripos($rDenied['body'], 'não pertence') === false, 'Nenhuma mensagem específica de motivo de negação é exposta ao usuário');

// Instituto/Cliente Admin continuam acessando normalmente (sem regressão de RBAC).
$rAllowed = runProbe('denied', 'usuarios/index', 'instituto');
assert_true($rAllowed['status'] !== 404, 'Instituto continua acessando usuarios/index normalmente (RBAC não foi enfraquecido)');
assert_true(strpos($rAllowed['body'], 'Novo Usuário') !== false, 'Instituto vê o conteúdo real da tela de Usuários');

// --- Cenário 2b: tenant nega (cliente fora do escopo) ---
$rTenant = runProbe('tenant', 'dashboard/index', 'cliente_admin', false, 1, 999);
assert_true($rTenant['status'] === 404, 'Tentar acessar ?cliente= fora do escopo do usuário responde 404');
assert_true(strpos($rTenant['body'], 'Conteúdo não encontrado') !== false, 'Tenant fora do escopo exibe a página personalizada de 404');

// --- APIs/AJAX: JSON, não HTML ---
$rAjax = runProbe('denied', 'usuarios/index', 'cliente', true);
assert_true($rAjax['status'] === 404, 'Requisição AJAX negada responde HTTP 404');
$jsonLine = trim(preg_replace('/---STATUS:\d*---/', '', $rAjax['body']));
$decoded = json_decode($jsonLine, true);
assert_true(is_array($decoded) && $decoded['success'] === false, 'Requisição AJAX negada retorna JSON válido com success=false');
assert_true(($decoded['message'] ?? '') === 'Recurso não encontrado.', 'Mensagem JSON é genérica, não revela o motivo real nem nomes internos');
assert_true(strpos($rAjax['body'], '<html') === false, 'Requisição AJAX negada não retorna HTML');

// --- Regressão: rota válida continua funcionando normalmente ---
$rValid = runProbe('valid', 'dashboard/index', 'instituto');
assert_true($rValid['status'] !== 404, 'Rota válida e autorizada não retorna 404');
assert_true(strpos($rValid['body'], 'Dashboard Operacional') !== false || strpos($rValid['body'], 'Indicadores de Progresso') !== false, 'Rota válida renderiza o conteúdo real esperado');

echo "Error 404 hidden access integration tests passed.\n";
