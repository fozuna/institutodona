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
                    $isPessoasActive = strpos($r, 'treinamentos/') === 0;
                    $isProcessosActive = strpos($r, 'manuais/') === 0
                        || strpos($r, 'auditorias/') === 0
                        || strpos($r, 'reunioes/') === 0
                        || strpos($r, 'coaching/') === 0
                        || strpos($r, 'processos/') === 0;
                    $isResultadosActive = strpos($r, 'indicadores/') === 0;
                    $isCadastrosActive = strpos($r, 'clientes/') === 0
                        || strpos($r, 'usuarios/') === 0
                        || strpos($r, 'consultores/') === 0
                        || strpos($r, 'pilares/') === 0;
                    $isSobreManualActive = strpos($r, 'about/') === 0;
                ?>
                <nav class="px-4 py-4 space-y-1">
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'dashboard/')===0?'is-active':'' ?>" href="index.php?route=dashboard/index" title="Dashboard"><span data-feather="home" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Dashboard</span></a>
                    <div class="submenu-group">
                        <button type="button"
                                class="w-full px-3 py-2 rounded nav-link submenu-trigger flex items-center <?= $isPessoasActive ? 'is-active' : '' ?>"
                                data-submenu-trigger="pessoas"
                                data-default-open="<?= $isPessoasActive ? 'true' : 'false' ?>"
                                aria-expanded="<?= $isPessoasActive ? 'true' : 'false' ?>"
                                title="Pessoas">
                            <span data-feather="users" class="inline-block mr-2 shrink-0"></span>
                            <span class="flex-1 text-left sidebar-label">Pessoas</span>
                            <span data-feather="chevron-down" class="submenu-chevron"></span>
                        </button>
                        <div class="submenu-panel <?= $isPessoasActive ? '' : 'hidden' ?>" data-submenu-panel="pessoas">
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'treinamentos/')===0?'is-active':'' ?>" href="index.php?route=treinamentos/index" title="Treinamentos"><span data-feather="award" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Treinamentos</span></a>
                        </div>
                    </div>
                    <div class="submenu-group">
                        <button type="button"
                                class="w-full px-3 py-2 rounded nav-link submenu-trigger flex items-center <?= $isProcessosActive ? 'is-active' : '' ?>"
                                data-submenu-trigger="processos"
                                data-default-open="<?= $isProcessosActive ? 'true' : 'false' ?>"
                                aria-expanded="<?= $isProcessosActive ? 'true' : 'false' ?>"
                                title="Processos">
                            <span data-feather="layers" class="inline-block mr-2 shrink-0"></span>
                            <span class="flex-1 text-left sidebar-label">Processos</span>
                            <span data-feather="chevron-down" class="submenu-chevron"></span>
                        </button>
                        <div class="submenu-panel <?= $isProcessosActive ? '' : 'hidden' ?>" data-submenu-panel="processos">
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'manuais/')===0?'is-active':'' ?>" href="index.php?route=manuais/index" title="Biblioteca"><span data-feather="book-open" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Biblioteca</span></a>
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'auditorias/')===0?'is-active':'' ?>" href="index.php?route=auditorias/index" title="Auditorias"><span data-feather="clipboard" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Auditorias</span></a>
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'reunioes/')===0?'is-active':'' ?>" href="index.php?route=reunioes/index" title="Reuniões" <?= $ozunaOnlyAttr ?>><span data-feather="users" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Reuniões</span></a>
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'coaching/')===0?'is-active':'' ?>" href="index.php?route=coaching/index" title="Coaching" <?= $ozunaOnlyAttr ?>><span data-feather="target" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Coaching</span></a>
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'processos/')===0?'is-active':'' ?>" href="index.php?route=processos/index" title="Processos" <?= $ozunaOnlyAttr ?>><span data-feather="git-branch" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Processos</span></a>
                        </div>
                    </div>
                    <div class="submenu-group">
                        <button type="button"
                                class="w-full px-3 py-2 rounded nav-link submenu-trigger flex items-center <?= $isResultadosActive ? 'is-active' : '' ?>"
                                data-submenu-trigger="resultados"
                                data-default-open="<?= $isResultadosActive ? 'true' : 'false' ?>"
                                aria-expanded="<?= $isResultadosActive ? 'true' : 'false' ?>"
                                title="Resultados">
                            <span data-feather="bar-chart-2" class="inline-block mr-2 shrink-0"></span>
                            <span class="flex-1 text-left sidebar-label">Resultados</span>
                            <span data-feather="chevron-down" class="submenu-chevron"></span>
                        </button>
                        <div class="submenu-panel <?= $isResultadosActive ? '' : 'hidden' ?>" data-submenu-panel="resultados">
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'indicadores/')===0?'is-active':'' ?>" href="index.php?route=indicadores/index" title="Indicadores"><span data-feather="bar-chart-2" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Indicadores</span></a>
                        </div>
                    </div>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'agenda/')===0?'is-active':'' ?>" href="index.php?route=agenda/index" title="Agenda"><span data-feather="calendar" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Agenda</span></a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'planoacao/')===0?'is-active':'' ?>" href="index.php?route=planoacao/index" title="Plano de Ação"><span data-feather="activity" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Plano de Ação</span></a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'tarefas/')===0?'is-active':'' ?>" href="index.php?route=tarefas/index" title="Tarefas"><span data-feather="check-square" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Tarefas</span></a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'cronograma/')===0?'is-active':'' ?>" href="index.php?route=cronograma/index" title="Cronograma"><span data-feather="calendar" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Cronograma</span></a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'avaliacoes/')===0?'is-active':'' ?>" href="index.php?route=avaliacoes/index" title="Avaliações"><span data-feather="check-square" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Avaliações</span></a>
                    <div class="submenu-group">
                        <button type="button"
                                class="w-full px-3 py-2 rounded nav-link submenu-trigger flex items-center <?= $isCadastrosActive ? 'is-active' : '' ?>"
                                data-submenu-trigger="cadastros"
                                data-default-open="<?= $isCadastrosActive ? 'true' : 'false' ?>"
                                aria-expanded="<?= $isCadastrosActive ? 'true' : 'false' ?>"
                                title="Cadastros">
                            <span data-feather="folder" class="inline-block mr-2 shrink-0"></span>
                            <span class="flex-1 text-left sidebar-label">Cadastros</span>
                            <span data-feather="chevron-down" class="submenu-chevron"></span>
                        </button>
                        <div class="submenu-panel <?= $isCadastrosActive ? '' : 'hidden' ?>" data-submenu-panel="cadastros">
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'clientes/')===0?'is-active':'' ?>" href="index.php?route=clientes/index" title="Clientes"><span data-feather="briefcase" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Clientes</span></a>
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'usuarios/')===0?'is-active':'' ?>" href="index.php?route=usuarios/index" title="Usuários"><span data-feather="user" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Usuários</span></a>
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'consultores/')===0?'is-active':'' ?>" href="index.php?route=consultores/index" title="Consultores"><span data-feather="users" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Consultores</span></a>
                            <a class="block px-3 py-2 rounded nav-link submenu-link <?= strpos($r,'pilares/')===0?'is-active':'' ?>" href="index.php?route=pilares/index" title="Pilares"><span data-feather="grid" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Pilares</span></a>
                        </div>
                    </div>
                    <?php if (($user['email'] ?? '') === 'admin@agencialester.com.br'): ?>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'logs/')===0?'is-active':'' ?>" href="index.php?route=logs/index" title="Logs"><span data-feather="file-text" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Logs</span></a>
                    <?php endif; ?>
                    <a class="block px-3 py-2 rounded nav-link <?= $isSobreManualActive ? 'is-active' : '' ?>" href="index.php?route=about/index" title="Sobre e Manual de Uso"><span data-feather="book" class="inline-block mr-2 shrink-0"></span><span class="sidebar-label">Sobre e Manual de Uso</span></a>
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
            </div>
            <?php if ($user): ?>
            <footer class="border-t bg-white text-xs text-gray-500 py-3 px-6">
                <div class="flex items-center justify-between">
                    
                    <!-- Espaço vazio para equilibrar o centro -->
                    <div class="w-1/3"></div>

                    <!-- Copy centralizada -->
                    <div class="w-1/3 text-center">
                        &copy; <?php echo date('Y'); ?> <?= htmlspecialchars($brandFooter) ?>. Todos os direitos reservados.
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
