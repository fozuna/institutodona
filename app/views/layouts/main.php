<?php
$cfg = __DIR__ . '/../../../config/config.php';
if (!file_exists($cfg)) {
    $cfg = __DIR__ . '/../../../config/config.example.php';
}
$config = require $cfg;
$brandName = \App\Core\AppBrand::displayName();
$brandTagline = \App\Core\AppBrand::TAGLINE;
$brandFooter = \App\Core\AppBrand::FOOTER_LABEL;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($brandName) ?></title>
    <?php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(dirname($scriptName), '/\\'); // e.g., /institutodona/public_html
        if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
        $assetsUrl = $baseUrl . '/assets';
        $themeCssPath = __DIR__ . '/../../../public_html/assets/css/theme.css';
        $appJsPath = __DIR__ . '/../../../public_html/assets/js/app.js';
        $themeCssVersion = is_file($themeCssPath) ? (string)filemtime($themeCssPath) : '1';
        $appJsVersion = is_file($appJsPath) ? (string)filemtime($appJsPath) : '1';
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?= $assetsUrl ?>/css/theme.css?v=<?= urlencode($themeCssVersion) ?>" />
</head>
<body class="bg-brand-gray-50 text-brand-black">
    <?php $user = $_SESSION['user'] ?? null; ?>
    <?php $isReader = ($user['tipo_acesso'] ?? null) === 'reader'; ?>
    <?php $rbac = \App\Core\AccessControl::frontendMatrix($user); ?>
    <div class="app-shell flex min-h-screen" data-app-shell data-sidebar-collapsed="false" data-sidebar-open="true" data-sidebar-viewport="desktop">
        <?php if ($user): ?>
            <button type="button"
                    class="app-sidebar-overlay"
                    data-sidebar-overlay
                    aria-label="Fechar menu lateral"></button>
            <aside id="appSidebar" class="app-sidebar shrink-0 bg-brand-brown text-white fixed h-screen desktop:relative desktop:block flex flex-col" aria-label="Menu lateral principal">
                <div class="px-4 py-3 border-b border-brand-brown">
                    <?php // Cabeçalho da marca sem toggle duplicado: mantém logo + nome empilhados para preservar leitura e alinhamento. ?>
                    <div class="sidebar-brand">
                        <img src="<?= $assetsUrl ?>/img/logobco.png" alt="Logo" class="sidebar-brand-logo shrink-0" />
                        <div class="leading-tight min-w-0">
                            <div class="font-bold text-sm sidebar-label"><?= htmlspecialchars($brandName) ?></div>
                            <?php if (!empty($brandTagline) && $brandTagline !== $brandName): ?>
                                <div class="text-[11px] opacity-80 sidebar-label"><?= htmlspecialchars($brandTagline) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                    $r = $_GET['route'] ?? '';
                    // Itens do menu ocultos por padrão: Reuniões, Coaching e Processos só aparecem quando o nome do usuário contém "Ozuna".
                    $showOzunaOnlyMenu = stripos((string)($user['nome'] ?? ''), 'Ozuna') !== false;
                    $ozunaOnlyAttr = $showOzunaOnlyMenu ? '' : 'style="display:none" aria-hidden="true" tabindex="-1"';
                    $isPessoasActive = strpos($r, 'treinamentos/') === 0 || strpos($r, 'pessoas/') === 0;
                    $isProcessosActive = strpos($r, 'manuais/') === 0
                        || strpos($r, 'auditorias/') === 0
                        || strpos($r, 'reunioes/') === 0
                        || strpos($r, 'coaching/') === 0
                        || strpos($r, 'processos/') === 0;
                    $isResultadosActive = strpos($r, 'indicadores/') === 0;
                    $isCadastrosActive = strpos($r, 'clientes/') === 0
                        || strpos($r, 'departamentos/') === 0
                        || strpos($r, 'setores/') === 0
                        || strpos($r, 'funcoes/') === 0
                        || strpos($r, 'colaboradores/') === 0
                        || strpos($r, 'usuarios/') === 0
                        || strpos($r, 'consultores/') === 0
                        || strpos($r, 'pilares/') === 0;
                    $isSobreManualActive = strpos($r, 'about/') === 0;
                    $canDashboard = $user && \App\Core\AccessControl::canAccessRoute('dashboard/index', 'GET', $user);
                    $canTreinamentos = $user && \App\Core\AccessControl::canAccessRoute('treinamentos/index', 'GET', $user);
                    $canBiblioteca = $user && \App\Core\AccessControl::canAccessRoute('manuais/index', 'GET', $user);
                    $canAuditorias = $user && \App\Core\AccessControl::canAccessRoute('auditorias/index', 'GET', $user);
                    $canReunioes = $user && \App\Core\AccessControl::canAccessRoute('reunioes/index', 'GET', $user);
                    $canCoaching = $user && \App\Core\AccessControl::canAccessRoute('coaching/index', 'GET', $user);
                    $canProcessos = $user && \App\Core\AccessControl::canAccessRoute('processos/index', 'GET', $user);
                    $canIndicadores = $user && \App\Core\AccessControl::canAccessRoute('indicadores/index', 'GET', $user);
                    $canAgenda = $user && \App\Core\AccessControl::canAccessRoute('agenda/index', 'GET', $user);
                    $canPlanoAcao = $user && \App\Core\AccessControl::canAccessRoute('planoacao/index', 'GET', $user);
                    $canTarefas = $user && \App\Core\AccessControl::canAccessRoute('tarefas/index', 'GET', $user);
                    $canCronograma = $user && \App\Core\AccessControl::canAccessRoute('cronograma/index', 'GET', $user);
                    $canAvaliacoes = $user && \App\Core\AccessControl::canAccessRoute('avaliacoes/index', 'GET', $user);
                    $canClientes = $user && \App\Core\AccessControl::canAccessRoute('clientes/index', 'GET', $user);
                    $canUsuarios = $user && \App\Core\AccessControl::canAccessRoute('usuarios/index', 'GET', $user);
                    $canConsultores = $user && \App\Core\AccessControl::canAccessRoute('consultores/index', 'GET', $user);
                    $canPilares = $user && \App\Core\AccessControl::canAccessRoute('pilares/index', 'GET', $user);
                    $canDepartamentos = $user && \App\Core\AccessControl::canAccessRoute('departamentos/index', 'GET', $user);
                    $canSetores = $user && \App\Core\AccessControl::canAccessRoute('setores/index', 'GET', $user);
                    $canFuncoes = $user && \App\Core\AccessControl::canAccessRoute('funcoes/index', 'GET', $user);
                    $canColaboradores = $user && \App\Core\AccessControl::canAccessRoute('colaboradores/index', 'GET', $user);
                    $canPessoasAvaliacoes = $user && \App\Core\AccessControl::canAccessRoute('pessoas/index', 'GET', $user);
                    $hasPessoasMenu = $canTreinamentos || $canPessoasAvaliacoes;
                    $hasProcessosMenu = $canBiblioteca || $canAuditorias || $canReunioes || $canCoaching || $canProcessos;
                    $hasResultadosMenu = $canIndicadores;
                    $hasCadastrosMenu = $canClientes || $canUsuarios || $canConsultores || $canPilares || $canDepartamentos || $canSetores || $canFuncoes || $canColaboradores;

                    // Sprint de normalizacao do menu lateral: dois helpers garantem que TODO item
                    // (simples ou pai de submenu) compartilhe exatamente a mesma estrutura interna
                    // (.nav-link-inner > icone + label [+ chevron]), eliminando a divergencia de
                    // alinhamento entre <a> e <button> que existia antes. Nao alteram RBAC/rotas -
                    // apenas centralizam a marcacao que ja era montada manualmente em ~20 lugares.
                    $navLink = static function (string $route, string $icon, string $label, bool $active, string $extraClass = '', string $rawAttrs = '') use ($r): string {
                        $cls = trim('nav-link ' . $extraClass . ($active ? ' is-active' : ''));
                        return '<a class="' . htmlspecialchars($cls) . '" href="index.php?route=' . htmlspecialchars($route) . '" title="' . htmlspecialchars($label) . '"' . ($rawAttrs !== '' ? ' ' . $rawAttrs : '') . '>'
                            . '<span class="nav-link-inner"><span data-feather="' . htmlspecialchars($icon) . '" class="nav-link-icon shrink-0"></span><span class="sidebar-label">' . htmlspecialchars($label) . '</span></span>'
                            . '</a>';
                    };
                    $submenuTrigger = static function (string $key, string $icon, string $label, bool $hasActiveChild) : string {
                        $cls = 'w-full nav-link submenu-trigger' . ($hasActiveChild ? ' has-active-descendant' : '');
                        return '<button type="button" class="' . htmlspecialchars($cls) . '" data-submenu-trigger="' . htmlspecialchars($key) . '" data-default-open="' . ($hasActiveChild ? 'true' : 'false') . '" aria-expanded="' . ($hasActiveChild ? 'true' : 'false') . '" title="' . htmlspecialchars($label) . '">'
                            . '<span class="nav-link-inner"><span data-feather="' . htmlspecialchars($icon) . '" class="nav-link-icon shrink-0"></span><span class="flex-1 text-left sidebar-label">' . htmlspecialchars($label) . '</span><span data-feather="chevron-down" class="submenu-chevron"></span></span>'
                            . '</button>';
                    };
                ?>
                <nav class="px-4 py-4 space-y-1">
                    <?php if ($canDashboard): ?>
                    <?= $navLink('dashboard/index', 'home', 'Dashboard', strpos($r,'dashboard/')===0) ?>
                    <?php endif; ?>
                    <?php if ($canAvaliacoes): ?>
                    <?= $navLink('avaliacoes/index', 'check-square', 'Avaliações', strpos($r,'avaliacoes/')===0) ?>
                    <?php endif; ?>
                    <?php if ($canCronograma): ?>
                    <?= $navLink('cronograma/index', 'calendar', 'Cronograma', strpos($r,'cronograma/')===0) ?>
                    <?php endif; ?>
                    <?php if ($hasProcessosMenu): ?>
                    <div class="submenu-group">
                        <?= $submenuTrigger('processos', 'layers', 'Processos', $isProcessosActive) ?>
                        <div class="submenu-panel <?= $isProcessosActive ? '' : 'hidden' ?>" data-submenu-panel="processos">
                            <?php if ($canBiblioteca): ?>
                            <?= $navLink('manuais/index', 'book-open', 'Biblioteca', strpos($r,'manuais/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canAuditorias): ?>
                            <?= $navLink('auditorias/index', 'clipboard', 'Auditorias', strpos($r,'auditorias/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canReunioes): ?>
                            <?= $navLink('reunioes/index', 'users', 'Reuniões', strpos($r,'reunioes/')===0, 'submenu-link', $ozunaOnlyAttr) ?>
                            <?php endif; ?>
                            <?php if ($canCoaching): ?>
                            <?= $navLink('coaching/index', 'target', 'Coaching', strpos($r,'coaching/')===0, 'submenu-link', $ozunaOnlyAttr) ?>
                            <?php endif; ?>
                            <?php if ($canProcessos): ?>
                            <?= $navLink('processos/index', 'git-branch', 'Processos', strpos($r,'processos/')===0, 'submenu-link', $ozunaOnlyAttr) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasResultadosMenu): ?>
                    <div class="submenu-group">
                        <?= $submenuTrigger('resultados', 'bar-chart-2', 'Resultados', $isResultadosActive) ?>
                        <div class="submenu-panel <?= $isResultadosActive ? '' : 'hidden' ?>" data-submenu-panel="resultados">
                            <?php if ($canIndicadores): ?>
                            <?= $navLink('indicadores/index', 'bar-chart-2', 'Indicadores', strpos($r,'indicadores/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasPessoasMenu): ?>
                    <div class="submenu-group">
                        <?= $submenuTrigger('pessoas', 'users', 'Pessoas', $isPessoasActive) ?>
                        <div class="submenu-panel <?= $isPessoasActive ? '' : 'hidden' ?>" data-submenu-panel="pessoas">
                            <?php
                                $isPessoasVisao = strpos($r, 'pessoas/visao') === 0;
                                $isPessoasPdi = strpos($r, 'pessoas/pdi') === 0;
                                // Sprint 05: telas operacionais (GAPs, Ações, Necessidades, Central do
                                // Colaborador) têm subnavegação própria; não realçam "Avaliações".
                                $isPessoasOperacional = (bool)preg_match('#^pessoas/(gap|acao|acoes|necessidade|feedback|colaboradorHistorico)#', (string)$r);
                            ?>
                            <?php if ($canPessoasAvaliacoes): ?>
                            <?= $navLink('pessoas/visaoGeral', 'bar-chart-2', 'Visão Geral', $isPessoasVisao, 'submenu-link') ?>
                            <?= $navLink('pessoas/index', 'trending-up', 'Avaliações de Desempenho', strpos($r,'pessoas/')===0 && !$isPessoasVisao && !$isPessoasPdi && !$isPessoasOperacional, 'submenu-link') ?>
                            <?= $navLink('pessoas/pdiIndex', 'target', 'PDI', $isPessoasPdi, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canTreinamentos): ?>
                            <?= $navLink('treinamentos/index', 'award', 'Treinamentos', strpos($r,'treinamentos/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($canPlanoAcao): ?>
                    <?= $navLink('planoacao/index', 'activity', 'Plano de Ação', strpos($r,'planoacao/')===0) ?>
                    <?php endif; ?>
                    <?php if ($canTarefas): ?>
                    <?= $navLink('tarefas/index', 'check-square', 'Tarefas', strpos($r,'tarefas/')===0) ?>
                    <?php endif; ?>
                    <?php if ($canAgenda): ?>
                    <?= $navLink('agenda/index', 'calendar', 'Agenda', strpos($r,'agenda/')===0) ?>
                    <?php endif; ?>
                    <?php if ($hasCadastrosMenu): ?>
                    <div class="submenu-group">
                        <?= $submenuTrigger('cadastros', 'folder', 'Cadastros', $isCadastrosActive) ?>
                        <div class="submenu-panel <?= $isCadastrosActive ? '' : 'hidden' ?>" data-submenu-panel="cadastros">
                            <?php if ($canClientes): ?>
                            <?= $navLink('clientes/index', 'briefcase', 'Clientes', strpos($r,'clientes/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canUsuarios): ?>
                            <?= $navLink('usuarios/index', 'user', 'Usuários', strpos($r,'usuarios/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canConsultores): ?>
                            <?= $navLink('consultores/index', 'users', 'Consultores', strpos($r,'consultores/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canPilares): ?>
                            <?= $navLink('pilares/index', 'grid', 'Pilares', strpos($r,'pilares/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canDepartamentos): ?>
                            <?= $navLink('departamentos/index', 'folder', 'Departamentos', strpos($r,'departamentos/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canSetores): ?>
                            <?= $navLink('setores/index', 'layers', 'Setores', strpos($r,'setores/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canFuncoes): ?>
                            <?= $navLink('funcoes/index', 'briefcase', 'Funções', strpos($r,'funcoes/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                            <?php if ($canColaboradores): ?>
                            <?= $navLink('colaboradores/index', 'users', 'Colaboradores', strpos($r,'colaboradores/')===0, 'submenu-link') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (($user['email'] ?? '') === 'admin@agencialester.com.br'): ?>
                    <?= $navLink('logs/index', 'file-text', 'Logs', strpos($r,'logs/')===0) ?>
                    <?php endif; ?>
                    <?= $navLink('about/index', 'book', 'Sobre e Manual de Uso', $isSobreManualActive) ?>
                </nav>
                <div class="mt-auto px-4 py-3 border-t border-brand-brown flex items-center justify-between">
                    <button id="themeToggle" class="text-sm flex items-center gap-2">
                        <span data-feather="moon"></span>
                        <span class="sidebar-label">Modo escuro</span>
                    </button>
                    <a href="index.php?route=auth/logout" class="text-sm flex items-center gap-2">
                        <span data-feather="log-out"></span>
                        <span class="sidebar-label">Sair</span>
                    </a>
                </div>
            </aside>
        <?php endif; ?>

        <!-- Container principal -->
        <main class="app-main flex-1 flex flex-col min-h-screen">
            <?php if ($user): ?>
            <div class="app-topbar px-4 md:px-6 py-3 flex items-center gap-3">
                <?php // Toggle único do layout: controla colapso em desktop e menu off-canvas em mobile sem disputar atenção com o título da tela. ?>
                <button type="button"
                        class="sidebar-toggle text-brand-brown"
                        data-sidebar-toggle
                        aria-controls="appSidebar"
                        aria-expanded="true"
                        aria-label="Recolher menu lateral"
                        title="Alternar menu lateral">
                    <span class="hamburger-box" aria-hidden="true">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </span>
                    <span class="sr-only">Alternar menu lateral</span>
                </button>
            </div>
            <?php endif; ?>
            <div class="flex-1 <?php echo $user ? 'p-6' : 'p-0'; ?>">
                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Sucesso!</strong>
                        <span class="block sm:inline"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
                        <?php unset($_SESSION['flash_success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['flash_error'])): ?>
                    <div class="mb-4 <?php echo $user ? 'bg-red-100 border border-red-400 text-red-700' : 'bg-[#e8eef8] border border-brand-brown text-brand-brown'; ?> px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Erro!</strong>
                        <span class="block sm:inline"><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
                        <?php unset($_SESSION['flash_error']); ?>
                    </div>
                <?php endif; ?>
                <?= $content ?>
                <?php if ($isReader): ?>
                <script>
                    (function(){
                        const writeRoutes = [
                            'store','update','delete','attach','upload','set_status',
                            'upsertMetric','addCheck','createAction','updateTask',
                            'updateAction','importRun','delete_update','storeFilial',
                            'updateAplicacao','deleteAplicacao','add_colaboradores',
                            'remove_colaborador','store_agenda','add_participantes',
                            'save_presence'
                        ];
                        document.querySelectorAll('form').forEach((form)=>{
                            const method = (form.getAttribute('method') || 'get').toLowerCase();
                            if (method !== 'get') {
                                form.querySelectorAll('input,select,textarea,button').forEach((el)=>{
                                    if (el.tagName === 'BUTTON') {
                                        el.disabled = true;
                                    } else if (el.name !== 'csrf') {
                                        el.readOnly = true;
                                        el.disabled = true;
                                    }
                                });
                            }
                        });
                        document.querySelectorAll('a[href*="index.php?route="]').forEach((a)=>{
                            const href = a.getAttribute('href') || '';
                            const hit = writeRoutes.some((s)=>href.includes('/' + s) || href.includes('=' + s) || href.includes('/' + s + '&'));
                            if (hit) {
                                a.style.display = 'none';
                            }
                        });
                    })();
                </script>
                <?php endif; ?>
                <?php if ($user): ?>
                <script>
                    (function(){
                        const rbac = <?= json_encode($rbac, JSON_UNESCAPED_UNICODE) ?>;
                        if (!rbac || rbac.unrestricted) return;
                        const publicRoutes = new Set(rbac.publicRoutes || []);
                        const prefixModules = rbac.prefixModules || {};
                        const writeActions = new Set(rbac.writeActions || []);
                        const deleteActions = new Set(rbac.deleteActions || []);
                        const allowedModules = new Set(rbac.allowedModules || []);
                        const role = String(rbac.role || '');

                        function routeFromUrl(url) {
                            try {
                                const full = new URL(url, window.location.href);
                                return String(full.searchParams.get('route') || '').toLowerCase();
                            } catch (e) {
                                return '';
                            }
                        }
                        function moduleForRoute(route) {
                            if (!route) return 'public';
                            if (publicRoutes.has(route)) return 'public';
                            const prefix = route.split('/')[0] || '';
                            return prefixModules[prefix] || prefix;
                        }
                        function actionForRoute(route) {
                            const action = (route.split('/')[1] || 'index').toLowerCase();
                            if (deleteActions.has(action)) return 'delete';
                            if (writeActions.has(action)) return 'write';
                            return 'view';
                        }
                        function canRoute(route) {
                            if (!route || publicRoutes.has(route)) return true;
                            const module = moduleForRoute(route);
                            if (!allowedModules.has(module) && !allowedModules.has('*')) return false;
                            const action = actionForRoute(route);
                            if (role === 'reader') return action === 'view';
                            if (role === 'cliente' && action === 'delete') return false;
                            return true;
                        }

                        document.querySelectorAll('a[href*="index.php?route="]').forEach((link) => {
                            if (!canRoute(routeFromUrl(link.href))) {
                                link.style.display = 'none';
                            }
                        });
                        document.querySelectorAll('form[action*="index.php?route="]').forEach((form) => {
                            const route = routeFromUrl(form.action);
                            if (!canRoute(route)) {
                                form.style.display = 'none';
                            }
                        });
                    })();
                </script>
                <?php endif; ?>
            </div>
            <?php if ($user): ?>
            <footer class="border-t bg-white text-xs text-gray-500 py-3 px-6">
                <div class="flex items-center justify-between">
                    
                    <!-- Espaço vazio para equilibrar o centro -->
                    <div class="w-1/3"></div>

                    <!-- Copy centralizada -->
                    <div class="w-1/3 text-center leading-relaxed">
                        <div>
                            &copy; <?php echo date('Y'); ?> <?= htmlspecialchars($brandFooter) ?>. Todos os direitos reservados.
                        </div>
                        <div>
                            Desenvolvido por
                            <a
                                href="https://traxter.com.br/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold text-brand-brown hover:text-brand-red transition-colors"
                            >TRAXTER</a>.
                        </div>
                    </div>

                    <!-- Versão alinhada à direita -->
                    <div class="w-1/3 text-right opacity-70">
                        <?php echo htmlspecialchars(\App\Core\AppVersion::get()); ?>
                    </div>

                </div>
            </footer>
            <?php endif; ?>
        </main>
    </div>
    <script src="<?= $assetsUrl ?>/js/app.js?v=<?= urlencode($appJsVersion) ?>"></script>
</body>
</html>
