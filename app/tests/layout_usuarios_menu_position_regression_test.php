<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function renderMenu(array $user): string
{
    $_SESSION['user'] = $user;
    $_GET['route'] = 'dashboard/index';
    $content = '<div>Painel de teste</div>';
    ob_start();
    require __DIR__ . '/../views/layouts/main.php';
    return (string)ob_get_clean();
}

// 1) Usuário instituto (acesso amplo): "Usuários" deve aparecer em Cadastros, nunca em Pessoas.
$html = renderMenu([
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
]);

$countUsuariosLinks = substr_count($html, 'href="index.php?route=usuarios/index"');
if ($countUsuariosLinks !== 1) {
    failFast('Esperava exatamente 1 link para usuarios/index no menu, encontrou ' . $countUsuariosLinks);
}
ok('Link "Usuários" aparece uma única vez no menu (sem duplicidade)');

$posCadastros = strpos($html, 'data-submenu-panel="cadastros"');
$posPessoasPanel = strpos($html, 'data-submenu-panel="pessoas"');
$posPessoasPanelEnd = $posPessoasPanel !== false ? strpos($html, '</div>', strpos($html, '</div>', $posPessoasPanel) + 1) : false;
$posUsuariosLink = strpos($html, 'href="index.php?route=usuarios/index"');
if ($posCadastros === false || $posUsuariosLink === false || $posUsuariosLink < $posCadastros) {
    failFast('Link "Usuários" não está posicionado dentro do submenu "Cadastros"');
}
ok('Link "Usuários" está posicionado dentro do submenu "Cadastros"');

if ($posPessoasPanel !== false && $posPessoasPanelEnd !== false) {
    $pessoasPanelHtml = substr($html, $posPessoasPanel, $posPessoasPanelEnd - $posPessoasPanel);
    if (str_contains($pessoasPanelHtml, 'route=usuarios/index')) {
        failFast('Submenu "Pessoas" ainda contém o link de Usuários (duplicidade não removida)');
    }
}
ok('Submenu "Pessoas" não contém mais o link de Usuários');

// 2) RBAC: perfil "cliente_admin" tem acesso a Usuários mas NÃO a Treinamentos (módulo admin-only).
//    Antes da correção, isso faria o submenu "Pessoas" aparecer vazio (pois $hasPessoasMenu dependia de $canUsuarios).
$clienteAdminUser = [
    'id' => 2,
    'nome' => 'Cliente Admin Teste',
    'email' => 'cliente-admin@example.com',
    'tipo_acesso' => 'cliente_admin',
    'allowed_client_ids' => [1],
];
if (!\App\Core\AccessControl::canAccessRoute('usuarios/index', 'GET', $clienteAdminUser)) {
    failFast('Cenário de referência inválido: cliente_admin deveria ter acesso a usuarios/index');
}
if (\App\Core\AccessControl::canAccessRoute('treinamentos/index', 'GET', $clienteAdminUser)) {
    failFast('Cenário de referência inválido: cliente_admin não deveria ter acesso a treinamentos/index');
}
$html2 = renderMenu($clienteAdminUser);
if (str_contains($html2, 'data-submenu-trigger="pessoas"')) {
    failFast('Submenu "Pessoas" foi renderizado vazio para usuário sem acesso a Treinamentos (regressão de RBAC)');
}
if (!str_contains($html2, 'href="index.php?route=usuarios/index"')) {
    failFast('Usuário cliente_admin com permissão de Usuários deveria ver o link em Cadastros');
}
ok('Usuário sem acesso a Treinamentos não vê submenu "Pessoas" vazio, e ainda vê "Usuários" em Cadastros');

echo "layout_usuarios_menu_position_regression_test passed.\n";
