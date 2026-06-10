<?php
use App\Core\AccessControl;
use App\Core\Auth;

$user = $_SESSION['user'] ?? null;
$cards = [
    ['route' => 'pwa/tratamentos', 'label' => 'Tratamentos', 'desc' => 'Plano e dashboard', 'icon' => 'activity', 'target' => 'processos/index'],
    ['route' => 'pwa/auditorias', 'label' => 'Auditorias', 'desc' => 'Execucao e anexos', 'icon' => 'clipboard', 'target' => 'auditorias/index'],
    ['route' => 'pwa/indicadores', 'label' => 'Indicadores', 'desc' => 'Lista e lancamentos', 'icon' => 'bar-chart-2', 'target' => 'indicadores/index'],
    ['route' => 'pwa/agenda', 'label' => 'Agenda', 'desc' => 'Calendario integrado', 'icon' => 'calendar', 'target' => 'agenda/index'],
    ['route' => 'pwa/planoacao', 'label' => 'Plano de Acao', 'desc' => 'Planos e atividades', 'icon' => 'layers', 'target' => 'planoacao/index'],
    ['route' => 'pwa/tarefas', 'label' => 'Tarefas', 'desc' => 'Operacao e prazos', 'icon' => 'check-square', 'target' => 'tarefas/index'],
    ['route' => 'pwa/cronogramas', 'label' => 'Cronogramas', 'desc' => 'Agenda mensal', 'icon' => 'grid', 'target' => 'cronograma/index'],
    ['route' => 'pwa/avaliacoes', 'label' => 'Avaliacoes', 'desc' => 'Listagem e links', 'icon' => 'file-text', 'target' => 'avaliacoes/index'],
    ['route' => 'pwa/balancos', 'label' => 'Balancos', 'desc' => 'Em desenvolvimento', 'icon' => 'pie-chart', 'target' => null],
    ['route' => 'pwa/oficinas', 'label' => 'Oficinas', 'desc' => 'Em desenvolvimento', 'icon' => 'tool', 'target' => null],
];
$scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = rtrim(dirname($scriptName), '/\\');
if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
?>

<div class="space-y-4">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Usuario</div>
        <div class="font-semibold"><?= htmlspecialchars((string)($user['nome'] ?? '')) ?></div>
        <div class="text-xs text-gray-500"><?= htmlspecialchars((string)($user['email'] ?? '')) ?></div>
        <div class="text-xs text-gray-500 mt-2">Perfil: <?= htmlspecialchars(AccessControl::roleLabel((string)($user['tipo_acesso'] ?? ''))) ?></div>
        <?php if (!Auth::isInstituto()): ?>
            <div class="text-xs text-gray-500">Empresas: <?= count(Auth::allowedClientIds()) ?></div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 gap-3">
        <?php foreach ($cards as $c): ?>
            <?php
                $enabled = true;
                if ($c['target']) {
                    $enabled = AccessControl::canAccessRoute($c['target'], 'GET', $user);
                }
            ?>
            <a href="<?= htmlspecialchars($baseUrl . '/' . $c['route']) ?>"
               class="pwa-card-link bg-white rounded-lg shadow p-4 flex items-center gap-3 <?= $enabled ? '' : 'opacity-50 pointer-events-none' ?>">
                <span class="pwa-card-icon text-brand-red"><span data-feather="<?= htmlspecialchars($c['icon']) ?>"></span></span>
                <span class="min-w-0">
                    <span class="block font-semibold truncate"><?= htmlspecialchars($c['label']) ?></span>
                    <span class="block text-xs text-gray-500 truncate"><?= htmlspecialchars($c['desc']) ?></span>
                </span>
                <span class="ml-auto text-gray-400"><span data-feather="chevron-right"></span></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
