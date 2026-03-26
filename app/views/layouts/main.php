<?php
$cfg = __DIR__ . '/../../../config/config.php';
if (!file_exists($cfg)) {
    $cfg = __DIR__ . '/../../../config/config.example.php';
}
$config = require $cfg;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Instituto Dona - Gestão de Processos</title>
    <?php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(dirname($scriptName), '/\\'); // e.g., /institutodona/public_html
        if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
        $assetsUrl = $baseUrl . '/assets';
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?= $assetsUrl ?>/css/theme.css" />
</head>
<body class="bg-brand-gray-50 text-brand-black">
    <?php $user = $_SESSION['user'] ?? null; ?>
    <?php $isReader = ($user['tipo_acesso'] ?? null) === 'reader'; ?>
    <div class="flex min-h-screen">
        <?php if ($user): ?>
            <!-- Sidebar fixa (apenas quando logado) -->
            <aside class="w-72 shrink-0 bg-brand-brown text-white fixed h-screen desktop:relative desktop:block flex flex-col">
                <div class="px-6 py-3 border-b border-brand-brown">
                    <div class="flex flex-col items-start gap-2">
                        <img src="<?= $assetsUrl ?>/img/logobco.png" alt="Logo" class="h-8 md:h-10 w-auto" />
                        <div class="leading-tight">
                            <div class="font-bold text-sm">Instituto Dona</div>
                            <div class="text-[11px] opacity-80">Gestão de Processos</div>
                        </div>
                    </div>
                </div>
                <?php $r = $_GET['route'] ?? ''; ?>
                <nav class="px-4 py-4 space-y-1">
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'dashboard/')===0?'is-active':'' ?>" href="index.php?route=dashboard/index"><span data-feather="home" class="inline-block mr-2"></span>Dashboard</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'clientes/')===0?'is-active':'' ?>" href="index.php?route=clientes/index"><span data-feather="users" class="inline-block mr-2"></span>Clientes</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'usuarios/')===0?'is-active':'' ?>" href="index.php?route=usuarios/index"><span data-feather="user" class="inline-block mr-2"></span>Usuários</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'pilares/')===0?'is-active':'' ?>" href="index.php?route=pilares/index"><span data-feather="layers" class="inline-block mr-2"></span>Pilares</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'agenda/')===0?'is-active':'' ?>" href="index.php?route=agenda/index"><span data-feather="calendar" class="inline-block mr-2"></span>Agenda</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'consultores/')===0?'is-active':'' ?>" href="index.php?route=consultores/index"><span data-feather="users" class="inline-block mr-2"></span>Consultores</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'cronograma/')===0?'is-active':'' ?>" href="index.php?route=cronograma/index"><span data-feather="calendar" class="inline-block mr-2"></span>Cronograma</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'avaliacoes/')===0?'is-active':'' ?>" href="index.php?route=avaliacoes/index"><span data-feather="check-square" class="inline-block mr-2"></span>Avaliações</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'planoacao/')===0?'is-active':'' ?>" href="index.php?route=planoacao/index"><span data-feather="activity" class="inline-block mr-2"></span>Plano de Ação</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'indicadores/')===0?'is-active':'' ?>" href="index.php?route=indicadores/index"><span data-feather="bar-chart-2" class="inline-block mr-2"></span>Indicadores</a>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'auditorias/')===0?'is-active':'' ?>" href="index.php?route=auditorias/index"><span data-feather="clipboard" class="inline-block mr-2"></span>Auditorias</a>
                    <?php if (($user['email'] ?? '') === 'admin@agencialester.com.br'): ?>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'logs/')===0?'is-active':'' ?>" href="index.php?route=logs/index"><span data-feather="file-text" class="inline-block mr-2"></span>Logs</a>
                    <?php endif; ?>
                    <a class="block px-3 py-2 rounded nav-link <?= strpos($r,'about/')===0?'is-active':'' ?>" href="index.php?route=about/index"><span data-feather="book" class="inline-block mr-2"></span>Sobre & Manual</a>
                </nav>
                <div class="mt-auto px-4 py-3 border-t border-brand-brown flex items-center justify-between">
                    <button id="themeToggle" class="text-sm flex items-center gap-2">
                        <span data-feather="moon"></span>
                        <span>Modo escuro</span>
                    </button>
                    <a href="index.php?route=auth/logout" class="text-sm flex items-center gap-2">
                        <span data-feather="log-out"></span>
                        <span>Sair</span>
                    </a>
                </div>
            </aside>
        <?php endif; ?>

        <!-- Container principal -->
        <main class="flex-1 <?php echo $user ? 'ml-72' : ''; ?> flex flex-col min-h-screen">
            <div class="flex-1 <?php echo $user ? 'p-6' : 'p-0'; ?>">
                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Sucesso!</strong>
                        <span class="block sm:inline"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
                        <?php unset($_SESSION['flash_success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['flash_error'])): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
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
                            'updateAplicacao','deleteAplicacao'
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
                        &copy; <?php echo date('Y'); ?> MENTORIA VIVA+. Todos os direitos reservados.
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
    <script src="<?= $assetsUrl ?>/js/app.js"></script>
</body>
</html>
