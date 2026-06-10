<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow p-5">
        <h1 class="text-xl font-bold mb-2"><?= htmlspecialchars((string)($title ?? $pageTitle ?? 'Módulo')) ?></h1>
        <p class="text-sm text-gray-600">Este modulo esta em preparacao na versao PWA.</p>
        <div class="mt-4">
            <?php
                $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
                $baseUrl = rtrim(dirname($scriptName), '/\\');
                if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
            ?>
            <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown inline-block" href="<?= htmlspecialchars($baseUrl . '/pwa/dashboard') ?>">Voltar</a>
        </div>
    </div>
</div>
