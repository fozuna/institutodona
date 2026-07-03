<?php
require_once __DIR__ . '/../autoload.php';

$file = __DIR__ . '/../views/dashboard/kanban.php';
$source = file_get_contents($file);

function dashboard_assert(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

dashboard_assert($source !== false, 'Carrega a view principal do dashboard');
dashboard_assert(str_contains($source, 'id="dashboardPremiumRoot"'), 'Dashboard principal possui raiz premium dedicada');
dashboard_assert(str_contains($source, 'data-release="dashboard-manual-sync-20260703"'), 'Dashboard principal expõe marcador de release manual para verificação em produção');
dashboard_assert(str_contains($source, 'Dashboard Operacional'), 'Cabeçalho executivo do dashboard foi renderizado');
dashboard_assert(str_contains($source, 'id="dashboardFiltersForm"'), 'Card premium de filtros está presente');
dashboard_assert(str_contains($source, 'dash-card dash-card--filters'), 'Card de filtros possui camada visual dedicada para dropdown');
dashboard_assert(str_contains($source, 'id="dashboardEmpresasDetails"'), 'Seletor de empresas possui container identificável para controle de UX');
dashboard_assert(str_contains($source, 'id="dashCronRing"'), 'Painel executivo de cronograma mantém progress ring');
dashboard_assert(str_contains($source, 'id="dashPlanoChart"'), 'Painel de plano de ação expõe área de gráfico moderna');
dashboard_assert(str_contains($source, 'id="dashboardMetricTableBody"'), 'Tabela de detalhamento moderna está presente');
dashboard_assert(str_contains($source, 'function renderCronograma(cron)'), 'View registra renderer dinâmico do cronograma');
dashboard_assert(str_contains($source, 'function renderTable()'), 'View registra paginação e ordenação da tabela');
dashboard_assert(str_contains($source, 'function escapeHtml(value)'), 'Renderização dinâmica aplica sanitização de HTML');
dashboard_assert(str_contains($source, 'dashboardEmpresasAll'), 'Ações rápidas de seleção de empresas permanecem acessíveis');
dashboard_assert(str_contains($source, "if (event.key === 'Escape' && companyDetails?.open)"), 'Dropdown de empresas fecha com tecla Escape');

echo "dashboard_main_redesign_layout_smoke passed.\n";
