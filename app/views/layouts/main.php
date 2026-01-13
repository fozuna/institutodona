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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="assets/css/theme.css" />
</head>
<body class="bg-brand-gray-50 text-brand-black">
    <?php $user = $_SESSION['user'] ?? null; ?>
    <div class="flex min-h-screen">
        <?php if ($user): ?>
            <!-- Sidebar fixa (apenas quando logado) -->
            <aside class="w-72 shrink-0 bg-brand-brown text-white fixed h-screen desktop:relative desktop:block flex flex-col">
                <div class="px-6 py-3 border-b border-brand-brown">
                    <div class="flex flex-col items-start gap-2">
                        <img src="../public/assets/img/logobco.png" alt="Logo" class="h-8 md:h-10 w-auto" />
                        <div class="leading-tight">
                            <div class="font-bold text-sm">Instituto Dona</div>
                            <div class="text-[11px] opacity-80">Gestão de Processos</div>
                        </div>
                    </div>
                </div>
                <nav class="px-4 py-4 space-y-1">
                    <a class="block px-3 py-2 rounded nav-link" href="index.php?route=dashboard/index"><span data-feather="home" class="inline-block mr-2"></span>Dashboard</a>
                    <a class="block px-3 py-2 rounded nav-link" href="index.php?route=metodologias/index"><span data-feather="check-square" class="inline-block mr-2"></span>Tarefas</a>
                    <a class="block px-3 py-2 rounded nav-link" href="index.php?route=clientes/index"><span data-feather="users" class="inline-block mr-2"></span>Clientes</a>
                    <a class="block px-3 py-2 rounded nav-link" href="index.php?route=pilares/index"><span data-feather="layers" class="inline-block mr-2"></span>Pilares</a>
                    <a class="block px-3 py-2 rounded nav-link" href="index.php?route=agenda/index"><span data-feather="calendar" class="inline-block mr-2"></span>Agenda</a>
                    <a class="block px-3 py-2 rounded nav-link" href="index.php?route=consultores/index"><span data-feather="users" class="inline-block mr-2"></span>Consultores</a>
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
                <?= $content ?>
            </div>
            <?php if ($user): ?>
            <footer class="border-t bg-white text-center text-xs text-gray-500 py-3 px-6">
                &copy; <?php echo date('Y'); ?> MENTORIA VIVA+. Todos os direitos reservados. <span class="opacity-70">v1.23.25</span>
            </footer>
            <?php endif; ?>
        </main>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
