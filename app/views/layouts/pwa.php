<?php
$cfg = __DIR__ . '/../../../config/config.php';
if (!file_exists($cfg)) {
    $cfg = __DIR__ . '/../../../config/config.example.php';
}
$config = require $cfg;
$brandName = \App\Core\AppBrand::displayName();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title><?= htmlspecialchars($brandName) ?></title>
    <?php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(dirname($scriptName), '/\\');
        if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
        $assetsUrl = $baseUrl . '/assets';
        $themeCssPath = __DIR__ . '/../../../public_html/assets/css/theme.css';
        $themeCssVersion = is_file($themeCssPath) ? (string)filemtime($themeCssPath) : '1';
        $pwaCssPath = __DIR__ . '/../../../public_html/pwa/pwa.css';
        $pwaCssVersion = is_file($pwaCssPath) ? (string)filemtime($pwaCssPath) : '1';
        $pwaJsPath = __DIR__ . '/../../../public_html/pwa/pwa.js';
        $pwaJsVersion = is_file($pwaJsPath) ? (string)filemtime($pwaJsPath) : '1';
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="manifest" href="<?= $baseUrl ?>/pwa/manifest.webmanifest" />
    <meta name="theme-color" content="#5A3D2B" />
    <link rel="apple-touch-icon" href="<?= $assetsUrl ?>/img/logobco.png" />
    <link rel="stylesheet" href="<?= $assetsUrl ?>/css/theme.css?v=<?= urlencode($themeCssVersion) ?>" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/pwa/pwa.css?v=<?= urlencode($pwaCssVersion) ?>" />
</head>
<body class="bg-brand-gray-50 text-brand-black">
    <?php $user = $_SESSION['user'] ?? null; ?>
    <?php $r = (string)($_GET['route'] ?? 'pwa/dashboard'); ?>
    <?php
        $nav = [
            ['route' => 'pwa/dashboard', 'label' => 'Início', 'icon' => 'home'],
            ['route' => 'pwa/indicadores', 'label' => 'Indicadores', 'icon' => 'bar-chart-2'],
            ['route' => 'pwa/tarefas', 'label' => 'Tarefas', 'icon' => 'check-square'],
            ['route' => 'pwa/cronogramas', 'label' => 'Cronograma', 'icon' => 'calendar'],
            ['route' => 'pwa/auditorias', 'label' => 'Auditorias', 'icon' => 'clipboard'],
        ];
    ?>
    <div class="pwa-shell min-h-screen pb-20">
        <header class="pwa-topbar bg-brand-brown text-white sticky top-0 z-20">
            <div class="px-4 py-3 flex items-center gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <img src="<?= $assetsUrl ?>/img/logobco.png" alt="Logo" class="h-7 w-7 rounded" />
                    <div class="min-w-0">
                        <div class="font-semibold text-sm leading-tight truncate"><?= htmlspecialchars($brandName) ?></div>
                        <div class="text-xs opacity-80 leading-tight truncate" id="pwaStatusLabel"></div>
                    </div>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <?php if ($user): ?>
                        <a class="pwa-icon-btn" href="<?= $baseUrl ?>/pwa/logout" aria-label="Sair" title="Sair"><span data-feather="log-out"></span></a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="px-4 py-4">
            <?= $content ?>
        </main>

        <?php if ($user): ?>
        <nav class="pwa-bottom-nav bg-white border-t border-gray-200 fixed bottom-0 left-0 right-0 z-30">
            <div class="max-w-md mx-auto grid grid-cols-5">
                <?php foreach ($nav as $item): ?>
                    <?php
                        $active = strpos($r, $item['route']) === 0;
                        $href = $baseUrl . '/' . $item['route'];
                    ?>
                    <a href="<?= htmlspecialchars($href) ?>"
                       class="pwa-nav-item <?= $active ? 'is-active' : '' ?>"
                       aria-label="<?= htmlspecialchars($item['label']) ?>">
                        <span data-feather="<?= htmlspecialchars($item['icon']) ?>"></span>
                        <span class="pwa-nav-label"><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
        <?php endif; ?>
    </div>

    <script src="<?= $baseUrl ?>/pwa/pwa.js?v=<?= urlencode($pwaJsVersion) ?>"></script>
    <script>
      try { feather.replace(); } catch (e) {}
    </script>
</body>
</html>
