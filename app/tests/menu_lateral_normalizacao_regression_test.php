<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AccessControl;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Sprint de UI/UX: normalização da hierarquia visual do menu lateral
 * (app/views/layouts/main.php + public_html/assets/css/theme.css). Cobre
 * apenas comportamento/marcação - nenhuma regra de RBAC, rota ou ordem de
 * módulos foi alterada (só a apresentação do que as variáveis can/has-Menu
 * já decidiam).
 */

function renderMenu(array $user, string $route): string
{
    $_SESSION['user'] = $user;
    $_GET['route'] = $route;
    $content = '<div>Painel de teste</div>';
    ob_start();
    require __DIR__ . '/../views/layouts/main.php';
    return (string)ob_get_clean();
}

function extractTag(string $html, string $needleAttr): ?string
{
    $pos = strpos($html, $needleAttr);
    if ($pos === false) { return null; }
    $tagStart = strrpos(substr($html, 0, $pos), '<');
    $tagEnd = strpos($html, '>', $pos);
    if ($tagStart === false || $tagEnd === false) { return null; }
    return substr($html, $tagStart, $tagEnd - $tagStart + 1);
}

function panelBlock(string $html, string $key): ?string
{
    $pos = strpos($html, 'data-submenu-panel="' . $key . '"');
    if ($pos === false) { return null; }
    $divStart = strrpos(substr($html, 0, $pos), '<div');
    // Panel de 1 nível não tem outro </div> antes do próximo submenu-group/fim - suficiente
    // para os grupos deste layout (sem nesting real hoje).
    $nextGroup = strpos($html, 'submenu-group', $pos);
    $end = $nextGroup !== false ? $nextGroup : strlen($html);
    return substr($html, $divStart, $end - $divStart);
}

try {
    $institutoOzuna = ['id' => 1, 'nome' => 'Instituto Ozuna', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto'];

    // ===================== CENÁRIO 1: item simples =====================
    $html = renderMenu($institutoOzuna, 'dashboard/index');
    $dashboardTag = extractTag($html, 'route=dashboard/index');
    if ($dashboardTag === null || strpos($dashboardTag, 'nav-link') === false || strpos($dashboardTag, 'is-active') === false) {
        failFast('Cenário 1: item simples "Dashboard" deveria ser <a class="nav-link is-active"> na rota ativa');
    }
    if (strpos($html, 'data-feather="home" class="nav-link-icon') === false) {
        failFast('Cenário 1: item simples deveria usar a mesma estrutura .nav-link-icon dos demais itens');
    }
    ok('Cenário 1: item simples renderiza com a mesma caixa/estrutura padronizada (.nav-link > .nav-link-inner)');

    // ===================== CENÁRIOS 2/3: pai fechado / pai aberto =====================
    $htmlFechado = renderMenu($institutoOzuna, 'dashboard/index'); // fora de Processos
    $triggerFechado = extractTag($htmlFechado, 'data-submenu-trigger="processos"');
    if ($triggerFechado === null || strpos($triggerFechado, 'aria-expanded="false"') === false) {
        failFast('Cenário 2: trigger "Processos" deveria estar aria-expanded=false quando a rota ativa não pertence ao grupo');
    }
    if (strpos($triggerFechado, 'has-active-descendant') !== false || strpos($triggerFechado, 'is-active') !== false) {
        failFast('Cenário 2: trigger "Processos" fechado não deveria ter nenhum destaque (nem has-active-descendant nem is-active)');
    }
    $panelFechado = panelBlock($htmlFechado, 'processos');
    if ($panelFechado === null || strpos($panelFechado, 'hidden') === false) {
        failFast('Cenário 2: painel "Processos" deveria estar com a classe hidden quando fechado');
    }
    ok('Cenário 2: pai fechado - aria-expanded=false, painel hidden, sem nenhum destaque visual');

    $htmlAberto = renderMenu($institutoOzuna, 'auditorias/index');
    $triggerAberto = extractTag($htmlAberto, 'data-submenu-trigger="processos"');
    if ($triggerAberto === null || strpos($triggerAberto, 'aria-expanded="true"') === false) {
        failFast('Cenário 3: trigger "Processos" deveria estar aria-expanded=true com uma rota filha ativa');
    }
    if (strpos($triggerAberto, 'has-active-descendant') === false) {
        failFast('Cenário 3: trigger "Processos" aberto deveria ter o indicador discreto has-active-descendant');
    }
    if (strpos($triggerAberto, ' is-active') !== false || preg_match('/class="[^"]*\bis-active\b[^"]*"/', $triggerAberto)) {
        failFast('Cenário 3: trigger "Processos" nunca deve receber a classe forte is-active (só o filho pode)');
    }
    $panelAberto = panelBlock($htmlAberto, 'processos');
    if ($panelAberto === null || preg_match('/data-submenu-panel="processos"[^>]*hidden/', $panelAberto)) {
        failFast('Cenário 3: painel "Processos" não deveria ter a classe hidden quando aberto');
    }
    ok('Cenário 3: pai aberto - aria-expanded=true, painel visível, apenas indicador discreto (has-active-descendant), nunca is-active');

    // ===================== CENÁRIO 4: filho ativo =====================
    $auditoriasTag = extractTag($htmlAberto, 'route=auditorias/index');
    if ($auditoriasTag === null || strpos($auditoriasTag, 'is-active') === false) {
        failFast('Cenário 4: link "Auditorias" deveria ter is-active na própria rota');
    }
    $bibliotecaTag = extractTag($htmlAberto, 'route=manuais/index');
    if ($bibliotecaTag === null || strpos($bibliotecaTag, 'is-active') !== false) {
        failFast('Cenário 4: link "Biblioteca" (irmão não ativo) não deveria ter is-active junto com "Auditorias"');
    }
    ok('Cenário 4: apenas o filho realmente ativo recebe o destaque forte (is-active); irmãos não ativos ficam neutros');

    // ===================== CENÁRIO 5: acesso direto a filho abre o pai (para cada grupo) =====================
    $groups = [
        'resultados' => 'indicadores/index',
        'pessoas' => 'treinamentos/index',
        'cadastros' => 'clientes/index',
    ];
    foreach ($groups as $key => $childRoute) {
        $h = renderMenu($institutoOzuna, $childRoute);
        $trigger = extractTag($h, 'data-submenu-trigger="' . $key . '"');
        if ($trigger === null || strpos($trigger, 'aria-expanded="true"') === false) {
            failFast("Cenário 5: acessar $childRoute diretamente deveria abrir automaticamente o grupo \"$key\"");
        }
    }
    ok('Cenário 5: acesso direto a uma rota filha abre automaticamente o respectivo grupo pai (Processos/Resultados/Pessoas/Cadastros)');

    // ===================== CENÁRIO 6: troca entre filhos do mesmo grupo =====================
    $htmlManuais = renderMenu($institutoOzuna, 'manuais/index');
    $triggerManuais = extractTag($htmlManuais, 'data-submenu-trigger="processos"');
    if ($triggerManuais === null || strpos($triggerManuais, 'aria-expanded="true"') === false) {
        failFast('Cenário 6: grupo "Processos" deveria continuar aberto ao navegar para outro filho (Biblioteca)');
    }
    $manuaisTag = extractTag($htmlManuais, 'route=manuais/index');
    $auditoriasTagOutro = extractTag($htmlManuais, 'route=auditorias/index');
    if ($manuaisTag === null || strpos($manuaisTag, 'is-active') === false) {
        failFast('Cenário 6: "Biblioteca" deveria ficar is-active ao ser a rota atual');
    }
    if ($auditoriasTagOutro !== null && strpos($auditoriasTagOutro, 'is-active') !== false) {
        failFast('Cenário 6: "Auditorias" não deveria mais estar is-active após trocar para "Biblioteca"');
    }
    ok('Cenário 6: trocar entre filhos do mesmo grupo mantém o grupo aberto e move o destaque para o novo item ativo');

    // ===================== CENÁRIO 7: múltiplos grupos com submenu não se interferem =====================
    $htmlCadastros = renderMenu($institutoOzuna, 'clientes/index');
    $trigProcessos = extractTag($htmlCadastros, 'data-submenu-trigger="processos"');
    $trigResultados = extractTag($htmlCadastros, 'data-submenu-trigger="resultados"');
    $trigPessoas = extractTag($htmlCadastros, 'data-submenu-trigger="pessoas"');
    $trigCadastros = extractTag($htmlCadastros, 'data-submenu-trigger="cadastros"');
    foreach (['processos' => $trigProcessos, 'resultados' => $trigResultados, 'pessoas' => $trigPessoas] as $k => $t) {
        if ($t !== null && strpos($t, 'aria-expanded="true"') !== false) {
            failFast("Cenário 7: grupo \"$k\" não deveria abrir quando a rota ativa é de Cadastros (clientes/index)");
        }
    }
    if ($trigCadastros === null || strpos($trigCadastros, 'aria-expanded="true"') === false) {
        failFast('Cenário 7: grupo "Cadastros" deveria ser o único aberto para a rota clientes/index');
    }
    ok('Cenário 7: múltiplos grupos com submenu coexistem sem interferência - só o grupo dono da rota ativa abre');

    // ===================== CENÁRIOS 8/9: Cliente Admin vê só o permitido =====================
    $clienteAdmin = ['id' => 2, 'nome' => 'Cliente Admin Ozuna', 'email' => 'admin@empresa.example', 'tipo_acesso' => 'cliente_admin', 'allowed_client_ids' => [1]];
    $htmlAdmin = renderMenu($clienteAdmin, 'colaboradores/index');
    foreach (['route=usuarios/index', 'route=consultores/index', 'route=pilares/index', 'route=departamentos/index', 'route=setores/index', 'route=funcoes/index', 'route=clientes/index'] as $forbidden) {
        if (strpos($htmlAdmin, $forbidden) !== false) {
            failFast("Cenário 8: Cliente Admin não deveria ver \"$forbidden\" no menu (RBAC bloqueado)");
        }
    }
    if (strpos($htmlAdmin, 'route=colaboradores/index') === false) {
        failFast('Cenário 9: Cliente Admin deveria continuar vendo "Colaboradores" (permitido)');
    }
    if (strpos($htmlAdmin, 'data-submenu-trigger="cadastros"') === false) {
        failFast('Cenário 9: grupo "Cadastros" deveria aparecer para Cliente Admin (tem ao menos Colaboradores)');
    }
    ok('Cenário 8/9: Cliente Admin não vê itens sem permissão (Usuários/Consultores/Pilares/Departamentos/Setores/Funções/Clientes) e continua vendo os permitidos (Colaboradores)');

    // ===================== CENÁRIO 10: Instituto mantém o menu completo =====================
    $htmlInstituto = renderMenu($institutoOzuna, 'dashboard/index');
    foreach ([
        'route=dashboard/index', 'route=avaliacoes/index', 'route=cronograma/index',
        'route=manuais/index', 'route=auditorias/index', 'route=reunioes/index', 'route=coaching/index', 'route=processos/index',
        'route=indicadores/index', 'route=treinamentos/index', 'route=planoacao/index', 'route=tarefas/index', 'route=agenda/index',
        'route=clientes/index', 'route=usuarios/index', 'route=consultores/index', 'route=pilares/index',
        'route=departamentos/index', 'route=setores/index', 'route=funcoes/index', 'route=colaboradores/index',
        'route=about/index',
    ] as $needle) {
        if (strpos($htmlInstituto, $needle) === false) {
            failFast("Cenário 10: Instituto deveria ver \"$needle\" no menu completo");
        }
    }
    ok('Cenário 10: Instituto mantém o menu completo, todos os módulos presentes');

    // ===================== CENÁRIO 11: mobile (CSS/estrutura, sem browser headless) =====================
    $css = file_get_contents(__DIR__ . '/../../public_html/assets/css/theme.css');
    if ($css === false || strpos($css, '@media (max-width: 900px)') === false) {
        failFast('Cenário 11: breakpoint mobile do sidebar deveria continuar existindo em theme.css');
    }
    if (!preg_match('/@media \(max-width: 900px\)[^}]*\{.*?\.submenu-panel\s*\{[^}]*margin-left/s', $css)) {
        failFast('Cenário 11: submenu deveria continuar com recuo ajustado em mobile');
    }
    if (strpos($css, 'padding: 0.5rem 0.75rem;') === false) {
        failFast('Cenário 11: itens do menu deveriam manter área de toque confortável (padding do .nav-link)');
    }
    ok('Cenário 11: breakpoint mobile e recuo de submenu preservados; área de toque dos itens mantida');

    // ===================== CENÁRIO 12: teclado/foco =====================
    if (strpos($triggerAberto, '<button type="button"') === false) {
        failFast('Cenário 12: item pai deve ser um <button> real (focável/acionável por teclado nativamente)');
    }
    if (strpos($triggerAberto, 'aria-expanded=') === false) {
        failFast('Cenário 12: item pai deve expor aria-expanded para leitores de tela');
    }
    if (strpos($css, '.nav-link:focus') === false) {
        failFast('Cenário 12: deveria existir estilo de foco visível para os itens do menu');
    }
    ok('Cenário 12: item pai é <button> nativamente focável/acionável, com aria-expanded e foco visível preservados');

    // ===================== CENÁRIO 13: nenhuma rota quebra (hrefs batem com RBAC do usuário) =====================
    preg_match_all('/href="index\.php\?route=([a-z0-9_\/\-]+)"/i', $htmlInstituto, $matches);
    $hrefRoutes = array_unique($matches[1] ?? []);
    if (count($hrefRoutes) < 15) {
        failFast('Cenário 13: menu do Instituto deveria conter um número razoável de rotas distintas (encontrado: ' . count($hrefRoutes) . ')');
    }
    foreach ($hrefRoutes as $route) {
        if (!AccessControl::canAccessRoute($route, 'GET', $institutoOzuna)) {
            failFast('Cenário 13: rota "' . $route . '" presente no menu do Instituto não é reconhecida como acessível pelo RBAC');
        }
    }
    ok('Cenário 13: todas as rotas renderizadas no menu continuam reconhecidas pelo RBAC (nenhum link quebrado/órfão)');

    echo "menu_lateral_normalizacao_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
}
