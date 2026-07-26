<?php
$clientes = is_array($clientes ?? null) ? $clientes : [];
$filters = is_array($filters ?? null) ? $filters : [];
$kanbanData = is_array($kanbanData ?? null) ? $kanbanData : [];
$stats = is_array($stats ?? null) ? $stats : [];
$totalsByStatus = is_array($totalsByStatus ?? null) ? $totalsByStatus : [];
$departamentos = is_array($departamentos ?? null) ? $departamentos : [];
$departamentoId = (int)($filters['departamento_id'] ?? 0);

$monthStart = (string)($filters['month_start'] ?? '');
$monthEnd = (string)($filters['month_end'] ?? '');

$dashMonthNames = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
];
$dashSplitMonth = static function (string $value) use (&$dashMonthNames): array {
    if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m) && isset($dashMonthNames[$m[2]])) {
        return ['year' => $m[1], 'month' => $m[2]];
    }
    return ['year' => date('Y'), 'month' => date('m')];
};
$dashMonthStartParts = $dashSplitMonth($monthStart);
$dashMonthEndParts = $dashSplitMonth($monthEnd);
$dashYearMin = min((int)date('Y') - 5, (int)$dashMonthStartParts['year'], (int)$dashMonthEndParts['year']);
$dashYearMax = max((int)date('Y') + 1, (int)$dashMonthStartParts['year'], (int)$dashMonthEndParts['year']);

$clienteIds = is_array($filters['cliente_ids'] ?? null) ? array_values(array_map('intval', $filters['cliente_ids'])) : [];
$selectedCount = $clienteIds ? count($clienteIds) : count($clientes);
$selectedLabel = $clienteIds ? (count($clienteIds) . ' empresa(s) selecionada(s)') : 'Todas as empresas';
$pdfUrl = 'index.php?' . http_build_query([
    'route' => 'dashboard/pdf',
    'month_start' => $monthStart,
    'month_end' => $monthEnd,
    'clientes' => $clienteIds,
]);
$resumoMesUrl = 'index.php?' . http_build_query([
    'route' => 'dashboard/resumo_mes',
    'month_start' => $monthStart,
    'month_end' => $monthEnd,
    'clientes' => $clienteIds,
]);

if (!function_exists('dash_icon')) {
    function dash_icon(string $name): string
    {
        $icons = [
            'spark' => 'zap',
            'calendar' => 'calendar',
            'company' => 'briefcase',
            'refresh' => 'refresh-cw',
            'filter' => 'filter',
            'download' => 'download',
            'cronograma' => 'clock',
            'biblioteca' => 'book-open',
            'auditoria' => 'check-circle',
            'indicadores' => 'bar-chart-2',
            'plano' => 'activity',
            'treinamentos' => 'award',
            'table' => 'columns',
            'search' => 'search',
            'empty' => 'inbox',
        ];
        $slug = $icons[$name] ?? $icons['spark'];
        return '<span data-feather="' . htmlspecialchars($slug, ENT_QUOTES) . '" aria-hidden="true"></span>';
    }
}

if (!function_exists('dash_month')) {
    function dash_month(string $value): string
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
            return 'Período não definido';
        }
        $date = \DateTimeImmutable::createFromFormat('Y-m', $value);
        if (!$date) {
            return 'Período não definido';
        }
        $monthMap = [
            '01' => 'janeiro',
            '02' => 'fevereiro',
            '03' => 'março',
            '04' => 'abril',
            '05' => 'maio',
            '06' => 'junho',
            '07' => 'julho',
            '08' => 'agosto',
            '09' => 'setembro',
            '10' => 'outubro',
            '11' => 'novembro',
            '12' => 'dezembro',
        ];
        $month = $monthMap[$date->format('m')] ?? null;
        return $month ? ucfirst($month . ' de ' . $date->format('Y')) : 'Período não definido';
    }
}

if (!function_exists('dash_date')) {
    function dash_date(?string $value): string
    {
        if (!$value) {
            return 'Sem data';
        }
        $time = strtotime($value);
        return $time ? date('d/m/Y', $time) : 'Sem data';
    }
}

$periodLabel = dash_month($monthStart) . ' até ' . dash_month($monthEnd);
$portfolio = [];
foreach ($stats as $row) {
    $pilar = (string)($row['pilar'] ?? 'Pilar');
    if (!isset($portfolio[$pilar])) {
        $portfolio[$pilar] = ['Planejado' => 0, 'Em Andamento' => 0, 'Concluído' => 0, 'Pendente' => 0, 'total' => 0];
    }
    $status = (string)($row['status'] ?? 'Planejado');
    $total = (int)($row['total'] ?? 0);
    if (!isset($portfolio[$pilar][$status])) {
        $portfolio[$pilar][$status] = 0;
    }
    $portfolio[$pilar][$status] += $total;
    $portfolio[$pilar]['total'] += $total;
}
ksort($portfolio);

$latestItems = [];
foreach ($kanbanData as $status => $items) {
    foreach ((array)$items as $item) {
        $reference = !empty($item['data_conclusao']) ? (string)$item['data_conclusao'] : (string)($item['data_prevista'] ?? '');
        $latestItems[] = [
            'status' => (string)($item['status'] ?? $status),
            'pilar' => (string)($item['pilar_nome'] ?? 'Pilar'),
            'item' => (string)($item['item_pilar'] ?? 'Item sem descrição'),
            'cliente' => (string)($item['cliente_nome'] ?? 'Empresa'),
            'consultor' => (string)($item['consultor_nome'] ?? 'Não atribuído'),
            'label' => !empty($item['data_conclusao']) ? 'Conclusão' : 'Previsão',
            'date' => $reference,
            'stamp' => $reference !== '' ? ((int)(strtotime($reference) ?: 0)) : 0,
        ];
    }
}
usort($latestItems, static function (array $a, array $b): int {
    return ($b['stamp'] ?? 0) <=> ($a['stamp'] ?? 0);
});
$latestItems = array_slice($latestItems, 0, 8);
?>

<style>
  .dash-view{--dash-primary:var(--brand-brown,#102040);--dash-primary-soft:rgba(16,32,64,.08);--dash-primary-border:rgba(16,32,64,.14);--dash-accent:var(--brand-red,#c83104);--dash-accent-soft:rgba(200,49,4,.08);--dash-warning:var(--brand-orange,#ea580c);--dash-warning-soft:rgba(234,88,12,.10);--dash-success:#15803d;--dash-success-soft:rgba(21,128,61,.10);--dash-danger:#b91c1c;--dash-danger-soft:rgba(185,28,28,.10);--dash-neutral:#475569;--dash-neutral-soft:#eef2f7;background:radial-gradient(circle at top left,rgba(16,32,64,.06),transparent 24rem),linear-gradient(180deg,#f8fafc 0%,#f3f6fa 100%);border-radius:28px;padding:24px;color:#0f172a;isolation:isolate}
  .dash-stack{display:grid;gap:20px}
  .dash-card{position:relative;background:#fff;border:1px solid #e2e8f0;border-radius:24px;box-shadow:0 12px 30px rgba(15,23,42,.06)}
  .dash-card-inner{padding:22px}
  .dash-card--filters{z-index:40;overflow:visible}
  .dash-hero{background:radial-gradient(circle at top right,rgba(255,255,255,.18),transparent 18rem),linear-gradient(135deg,#0f172a 0%,#102040 58%,#17315a 100%);color:#fff;border:0;overflow:hidden}
  .dash-hero-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(300px,1fr);gap:20px}
  .dash-title{font-size:clamp(32px,4vw,46px);line-height:1.02;font-weight:800;letter-spacing:-.04em}
  .dash-subtitle{margin-top:10px;color:rgba(255,255,255,.78);max-width:760px;line-height:1.7}
  .dash-chip-row{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
  .dash-chip,.dash-badge{display:inline-flex;align-items:center;gap:8px;border-radius:999px}
  .dash-chip{padding:8px 12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);color:#fff;font-size:12px;font-weight:600}
  .dash-chip svg,.dash-btn svg,.dash-btn-ghost svg,.dash-muted svg,.dash-search svg,.dash-empty-icon svg{width:16px;height:16px;display:block;flex-shrink:0}
  .dash-mini{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.12);border-radius:22px;padding:20px;display:grid;gap:12px}
  .dash-mini h2{font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.7)}
  .dash-mini-row{display:grid;gap:6px}
  .dash-mini-track{height:8px;border-radius:999px;background:rgba(255,255,255,.14);overflow:hidden}
  .dash-mini-fill{height:100%;border-radius:inherit;background:linear-gradient(90deg,rgba(255,255,255,.35),rgba(255,255,255,.95))}
  .dash-mini-row small{color:rgba(255,255,255,.74)}
  .dash-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}
  .dash-section-title{display:flex;align-items:center;gap:14px}
  .dash-section-title h2,.dash-kpi-title{font-size:18px;font-weight:700;letter-spacing:-.01em}
  .dash-section-title p,.dash-muted{color:#64748b;font-size:13px;line-height:1.55}
  .dash-kpi-sub{color:#64748b;font-size:14px;font-weight:600;line-height:1.4}
  .dash-icon{width:38px;height:38px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:var(--dash-primary-soft);color:var(--dash-primary);border:1px solid var(--dash-primary-border);flex-shrink:0}
  .dash-icon svg{width:19px;height:19px;display:block}
  .dash-icon.success{background:var(--dash-success-soft);border-color:rgba(21,128,61,.14);color:var(--dash-success)}
  .dash-icon.warning{background:var(--dash-warning-soft);border-color:rgba(234,88,12,.16);color:var(--dash-warning)}
  .dash-icon.danger{background:var(--dash-accent-soft);border-color:rgba(200,49,4,.16);color:var(--dash-accent)}
  .dash-filter-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px;align-items:end}
  .dash-field{display:grid;gap:6px;position:relative}
  .dash-field label{font-size:12px;font-weight:700;color:#64748b}
  .dash-month-picker{display:flex;gap:8px;flex-wrap:wrap}
  .dash-month-picker select{flex:1;min-width:96px}
  .dash-input,.dash-multi-summary{width:100%;min-height:52px;border:1px solid #dbe3ee;border-radius:16px;background:#fff;padding:14px 16px;font-size:15px;color:#0f172a;transition:border-color .18s ease,box-shadow .18s ease}
  .dash-input:focus,.dash-multi[open] .dash-multi-summary{outline:none;border-color:rgba(37,99,235,.42);box-shadow:0 0 0 4px rgba(37,99,235,.08)}
  .dash-multi{position:relative;z-index:20}
  .dash-multi[open]{z-index:80}
  .dash-multi-summary{display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;list-style:none}
  .dash-multi-summary::-webkit-details-marker{display:none}
  .dash-multi-panel{position:absolute;inset:calc(100% + 10px) 0 auto 0;background:#fff;border:1px solid #dbe3ee;border-radius:18px;box-shadow:0 28px 48px rgba(15,23,42,.18);padding:14px;z-index:90;animation:dash-pop .16s ease-out}
  .dash-multi-list{max-height:240px;overflow:auto;display:grid;gap:8px}
  .dash-check{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:14px;cursor:pointer;transition:background-color .18s ease,color .18s ease}
  .dash-check:hover{background:#f8fafc}
  .dash-actions{display:flex;flex-wrap:wrap;gap:10px}
  .dash-btn,.dash-btn-ghost{display:inline-flex;align-items:center;justify-content:center;gap:10px;border-radius:16px;min-height:52px;padding:14px 18px;font-weight:700;font-size:14px;text-decoration:none;transition:transform .18s ease,box-shadow .18s ease;background:#fff;border:1px solid #dbe3ee;color:#0f172a;cursor:pointer}
  .dash-btn{border:0;color:#fff;background:linear-gradient(135deg,#1d4ed8 0%,#2563eb 50%,#3b82f6 100%);box-shadow:0 16px 28px rgba(37,99,235,.24)}
  .dash-btn:hover,.dash-btn-ghost:hover,.dash-card:hover,.dash-bar-card:hover,.dash-quick-card:hover,.dash-portfolio-card:hover{transform:translateY(-1px)}
  .dash-btn:disabled{opacity:.85;cursor:wait}
  .dash-error{display:none;align-items:center;gap:10px;margin-top:14px;padding:12px 14px;border-radius:16px;border:1px solid #fecdd3;background:#fff1f2;color:#b91c1c}
  .dash-error.is-visible{display:flex}
  .dash-loading{display:none;align-items:center;gap:10px;padding:14px 16px;border-radius:18px;border:1px solid rgba(37,99,235,.14);background:linear-gradient(90deg,rgba(37,99,235,.08),rgba(255,255,255,.96));color:#1d4ed8;font-weight:700}
  .dash-view.is-loading .dash-loading{display:flex}
  .dash-spinner{width:16px;height:16px;border-radius:999px;border:2px solid rgba(37,99,235,.2);border-top-color:#2563eb;animation:dash-spin .8s linear infinite}
  .dash-grid-4,.dash-grid-2{display:grid;gap:20px}
  .dash-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}
  .dash-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
  .dash-kpi{min-height:232px;position:relative}
  .dash-kpi .dash-card-inner{display:flex;flex-direction:column;gap:14px;height:100%;padding:20px 22px}
  .dash-kpi-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
  .dash-kpi-top > div:first-child{display:flex;flex-direction:column;min-width:0;flex:1}
  .dash-kpi-value{font-size:36px;line-height:1.05;font-weight:700;letter-spacing:-.03em;margin-top:8px}
  .dash-kpi-top .dash-muted{margin-top:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;max-width:34ch}
  .dash-kpi-footer{display:flex;flex-wrap:wrap;gap:6px;margin-top:auto;padding-top:2px}
  .dash-badge{padding:5px 10px;font-size:11.5px;font-weight:600;line-height:1.2;border:1px solid transparent}
  .dash-badge.neutral{background:var(--dash-neutral-soft);border-color:#dde4ee;color:var(--dash-neutral)}
  .dash-badge.primary{background:var(--dash-primary-soft);border-color:var(--dash-primary-border);color:var(--dash-primary)}
  .dash-badge.success{background:var(--dash-success-soft);border-color:rgba(21,128,61,.14);color:var(--dash-success)}
  .dash-badge.warning{background:var(--dash-warning-soft);border-color:rgba(234,88,12,.16);color:var(--dash-warning)}
  .dash-badge.danger{background:var(--dash-danger-soft);border-color:rgba(185,28,28,.16);color:var(--dash-danger)}
  .dash-progress{width:100%;height:6px;border-radius:999px;background:#e2e8f0;overflow:hidden}
  .dash-progress > span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--dash-primary),#2b4b7c);width:0;transition:width .4s ease}
  .dash-empty{display:grid;place-items:center;gap:8px;min-height:180px;text-align:center;padding:18px;border-radius:20px;border:1px dashed #dbe3ee;background:linear-gradient(180deg,#f8fafc 0%,#fff 100%)}
  .dash-empty.hidden{display:none}
  .dash-empty-icon{width:40px;height:40px;border-radius:12px;background:var(--dash-primary-soft);color:var(--dash-primary);border:1px solid var(--dash-primary-border);display:inline-flex;align-items:center;justify-content:center}
  .dash-ring-wrap{display:grid;grid-template-columns:210px minmax(0,1fr);gap:20px;align-items:center}
  .dash-ring{width:192px;height:192px;border-radius:999px;display:grid;place-items:center;background:conic-gradient(var(--ring-color,#2563eb) calc(var(--ring-value,0) * 1%), #e2e8f0 0);position:relative}
  .dash-ring::before{content:'';position:absolute;inset:14px;border-radius:inherit;background:#fff;box-shadow:inset 0 0 0 1px rgba(226,232,240,.9)}
  .dash-ring-center{position:relative;z-index:1;text-align:center}
  .dash-ring-center strong{display:block;font-size:34px;line-height:1;font-weight:700;letter-spacing:-.03em}
  .dash-ring-center span{display:block;margin-top:6px;font-size:13px;color:#64748b}
  .dash-mini-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  .dash-mini-card{padding:14px 16px;border-radius:16px;background:#f8fafc;border:1px solid #e2e8f0}
  .dash-mini-card span{display:block;font-size:12px;color:#64748b;margin-bottom:4px}
  .dash-mini-card strong{font-size:17px;font-weight:700}
  .dash-bar-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;align-items:end;min-height:240px}
  .dash-bar-card{display:grid;gap:12px;padding:16px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;min-height:210px}
  .dash-bar-top{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:13px;font-weight:700}
  .dash-bar-stage{flex:1;display:flex;align-items:flex-end;justify-content:center}
  .dash-bar{width:100%;max-width:56px;min-height:8px;border-radius:999px 999px 14px 14px;box-shadow:inset 0 1px 0 rgba(255,255,255,.3);transition:height .4s ease}
  .dash-tooltip{position:relative}
  .dash-tooltip::after{content:attr(data-tooltip);position:absolute;left:50%;bottom:calc(100% + 10px);transform:translate(-50%,6px);opacity:0;pointer-events:none;white-space:nowrap;padding:8px 10px;border-radius:12px;background:rgba(15,23,42,.96);color:#fff;font-size:12px;box-shadow:0 12px 24px rgba(15,23,42,.18);transition:opacity .18s ease,transform .18s ease}
  .dash-tooltip:hover::after{opacity:1;transform:translate(-50%,0)}
  .dash-hbars{display:grid;gap:14px}
  .dash-hrow{display:grid;gap:6px}
  .dash-hhead{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:13px}
  .dash-hhead strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .dash-htrack{height:6px;border-radius:999px;background:#e2e8f0;overflow:hidden}
  .dash-hfill{height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--dash-primary),#2b4b7c);width:0;transition:width .4s ease}
  .dash-quick-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
  .dash-quick-card,.dash-portfolio-card,.dash-event{border:1px solid #e2e8f0;border-radius:20px;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%)}
  .dash-quick-card{padding:18px}
  .dash-quick-card strong{display:block;font-size:20px;margin-top:6px}
  .dash-search{position:relative;min-width:min(100%,320px)}
  .dash-search svg{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#64748b}
  .dash-search input{padding-left:44px}
  .dash-pill-row{display:flex;flex-wrap:wrap;gap:8px}
  .dash-pill{border:1px solid #dbe3ee;background:#fff;color:#64748b;border-radius:999px;padding:10px 14px;font-weight:700;font-size:12px;cursor:pointer;transition:all .18s ease}
  .dash-pill.is-active,.dash-pill:hover{background:#dbeafe;border-color:rgba(37,99,235,.2);color:#2563eb}
  .dash-table-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:20px}
  .dash-table{width:100%;min-width:760px;border-collapse:separate;border-spacing:0}
  .dash-table thead th{position:sticky;top:0;background:rgba(248,250,252,.96);backdrop-filter:blur(8px);padding:14px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#64748b;border-bottom:1px solid #e2e8f0}
  .dash-table thead button{border:0;background:transparent;padding:0;font:inherit;color:inherit;cursor:pointer}
  .dash-table tbody tr:nth-child(odd){background:rgba(248,250,252,.58)}
  .dash-table tbody tr:hover{background:rgba(219,234,254,.28)}
  .dash-table td{padding:14px 16px;border-bottom:1px solid rgba(226,232,240,.75);vertical-align:top;font-size:14px}
  .dash-table small{display:block;color:#64748b;margin-top:4px;line-height:1.5}
  .dash-pagination{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-top:14px}
  .dash-pages{display:inline-flex;gap:8px}
  .dash-page{border:1px solid #dbe3ee;background:#fff;color:#0f172a;border-radius:14px;min-width:40px;min-height:40px;padding:8px 12px;font-weight:700;cursor:pointer;transition:all .18s ease}
  .dash-page.is-active,.dash-page:hover{background:#dbeafe;border-color:rgba(37,99,235,.2);color:#2563eb}
  .dash-portfolio-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
  .dash-portfolio-card{padding:18px}
  .dash-portfolio-card header{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
  .dash-event-list{display:grid;gap:12px}
  .dash-event{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:14px;align-items:center;padding:16px}
  .dash-event strong{display:block;font-size:15px}
  .dash-event small{display:block;margin-top:4px;color:#64748b;line-height:1.55}
  .dash-event-date{text-align:right;font-size:12px;color:#64748b}
  @keyframes dash-spin{to{transform:rotate(360deg)}}
  @keyframes dash-pop{from{opacity:0;transform:translateY(-4px) scale(.985)}to{opacity:1;transform:translateY(0) scale(1)}}
  @media (max-width:1280px){.dash-grid-4,.dash-quick-grid,.dash-portfolio-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media (max-width:980px){
    .dash-view{padding:16px;border-radius:22px}
    .dash-hero-grid,.dash-grid-4,.dash-grid-2,.dash-quick-grid,.dash-portfolio-grid,.dash-ring-wrap{grid-template-columns:1fr}
    .dash-filter-grid{grid-template-columns:1fr}
    .dash-filter-grid > *{grid-column:auto!important}
    .dash-bar-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .dash-kpi-value{font-size:28px}
  }
  @media (max-width:640px){
    .dash-bar-grid{grid-template-columns:1fr}
    .dash-event{grid-template-columns:1fr}
    .dash-event-date{text-align:left}
    .dash-card-inner{padding:18px}
    .dash-kpi .dash-card-inner{padding:18px}
    .dash-kpi-value{font-size:24px}
    .dash-badge{padding:5px 9px;font-size:11px}
  }
  @media (max-width:1280px) and (min-width:981px){.dash-kpi-value{font-size:32px}}
</style>

<div class="dash-view" id="dashboardPremiumRoot" data-total-companies="<?= (int)count($clientes) ?>" data-release="dashboard-manual-sync-20260703">
  <div class="dash-stack">
    <section class="dash-card dash-hero">
      <div class="dash-card-inner">
        <div class="dash-hero-grid">
          <div>
            <div class="dash-chip"><?= dash_icon('spark') ?><span>Dashboard Operacional</span></div>
            <h1 class="dash-title">Indicadores de Progresso </h1>
            <p class="dash-subtitle">Acompanhe os principais indicadores operacionais do período.</p>
            <div class="dash-chip-row">
              <div class="dash-chip"><?= dash_icon('calendar') ?><span>Período</span><strong id="dashboardPeriodLabel"><?= htmlspecialchars($periodLabel) ?></strong></div>
              <div class="dash-chip"><?= dash_icon('company') ?><span>Empresas</span><strong id="dashboardEmpresaCount"><?= (int)$selectedCount ?></strong></div>
              <div class="dash-chip"><?= dash_icon('refresh') ?><span>Última atualização</span><strong id="dashboardLastUpdatedAt">Carregando...</strong></div>
            </div>
          </div>
          <aside class="dash-mini">
            <h2>Carteira atual</h2>
            <?php $totalCarteira = max(1, array_sum(array_map('intval', $totalsByStatus))); ?>
            <?php foreach (['Planejado', 'Em Andamento', 'Concluído', 'Pendente'] as $statusLabel): ?>
              <?php $count = (int)($totalsByStatus[$statusLabel] ?? 0); ?>
              <div class="dash-mini-row">
                <div class="flex items-center justify-between gap-3">
                  <strong><?= htmlspecialchars($statusLabel) ?></strong>
                  <small><?= $count ?> item(ns)</small>
                </div>
                <div class="dash-mini-track"><div class="dash-mini-fill" style="width: <?= max(0, min(100, ($count / $totalCarteira) * 100)) ?>%;"></div></div>
              </div>
            <?php endforeach; ?>
          </aside>
        </div>
      </div>
    </section>

    <section class="dash-card dash-card--filters">
      <div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title">
            <span class="dash-icon"><?= dash_icon('filter') ?></span>
            <div>
              <h2>Base de Filtros</h2>
              
            </div>
          </div>
          <button type="button" class="dash-btn-ghost" id="dashboardRefreshBtn"><?= dash_icon('refresh') ?><span>Atualizar agora</span></button>
        </div>

        <form method="get" action="index.php" id="dashboardFiltersForm" class="dash-filter-grid">
          <input type="hidden" name="route" value="dashboard/index" />

          <div class="dash-field" style="grid-column: span 2;">
            <label for="dashboardMonthStartMonth">Mês inicial</label>
            <div class="dash-month-picker">
              <select id="dashboardMonthStartMonth" class="dash-input" aria-label="Mês inicial - mês">
                <?php foreach ($dashMonthNames as $num => $label): ?>
                  <option value="<?= $num ?>" <?= $num === $dashMonthStartParts['month'] ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
              <select id="dashboardMonthStartYear" class="dash-input" aria-label="Mês inicial - ano">
                <?php for ($y = $dashYearMin; $y <= $dashYearMax; $y++): ?>
                  <option value="<?= $y ?>" <?= (string)$y === $dashMonthStartParts['year'] ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <input type="hidden" id="dashboardMonthStart" name="month_start" value="<?= htmlspecialchars($monthStart) ?>">
          </div>

          <div class="dash-field" style="grid-column: span 2;">
            <label for="dashboardMonthEndMonth">Mês final</label>
            <div class="dash-month-picker">
              <select id="dashboardMonthEndMonth" class="dash-input" aria-label="Mês final - mês">
                <?php foreach ($dashMonthNames as $num => $label): ?>
                  <option value="<?= $num ?>" <?= $num === $dashMonthEndParts['month'] ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
              <select id="dashboardMonthEndYear" class="dash-input" aria-label="Mês final - ano">
                <?php for ($y = $dashYearMin; $y <= $dashYearMax; $y++): ?>
                  <option value="<?= $y ?>" <?= (string)$y === $dashMonthEndParts['year'] ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <input type="hidden" id="dashboardMonthEnd" name="month_end" value="<?= htmlspecialchars($monthEnd) ?>">
          </div>

          <div class="dash-field" style="grid-column: span 3;">
            <label>Empresas</label>
            <details class="dash-multi" id="dashboardEmpresasDetails">
              <summary class="dash-multi-summary">
                <span id="dashboardEmpresasSummary"><?= htmlspecialchars($selectedLabel) ?></span>
                <span class="dash-muted"><?= dash_icon('company') ?></span>
              </summary>
              <div class="dash-multi-panel">
                <div class="dash-multi-list">
                  <?php foreach ($clientes as $cliente): ?>
                    <?php $id = (int)($cliente['id'] ?? 0); ?>
                    <label class="dash-check">
                      <input type="checkbox" name="clientes[]" value="<?= $id ?>" <?= in_array($id, $clienteIds, true) ? 'checked' : '' ?>>
                      <span><?= htmlspecialchars((string)($cliente['nome_empresa'] ?? '')) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <div class="dash-actions">
                  <button type="button" class="dash-btn-ghost" id="dashboardEmpresasAll">Selecionar todas</button>
                  <button type="button" class="dash-btn-ghost" id="dashboardEmpresasNone">Limpar seleção</button>
                </div>
              </div>
            </details>
          </div>

          <div class="dash-field" style="grid-column: span 2;">
            <label for="dashboardDepartamentoSelect">Departamento</label>
            <select name="departamento_id" id="dashboardDepartamentoSelect" class="dash-input">
              <option value="">Todos os departamentos</option>
              <?php foreach ($departamentos as $dep): ?>
                <option value="<?= (int)$dep['id'] ?>" <?= (int)$dep['id'] === $departamentoId ? 'selected' : '' ?>><?= htmlspecialchars((string)($dep['nome'] ?? '')) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="dash-muted">Não afeta Cronograma, Plano de Ação e Tarefas.</small>
          </div>

          <div class="dash-actions" style="grid-column: span 3;">
            <button class="dash-btn" type="submit" id="dashboardApplyBtn"><?= dash_icon('filter') ?><span id="dashboardApplyLabel">Aplicar filtros</span></button>
            <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" rel="noopener" id="dashboardPdfLink" class="dash-btn-ghost"><?= dash_icon('download') ?><span>Exportar PDF</span></a>
            <a href="<?= htmlspecialchars($resumoMesUrl) ?>" id="dashboardResumoMesLink" class="dash-btn-ghost"><?= dash_icon('calendar') ?><span>Resumo do Mês</span></a>
          </div>
        </form>

        <div class="dash-error" id="dashboardFilterError"></div>
      </div>
    </section>

    <div class="dash-loading" id="dashboardLoading"><span class="dash-spinner"></span><span>Atualizando indicadores do dashboard...</span></div>

    <section class="dash-grid-4" id="dashboardMetricsSurface">
      <article class="dash-card dash-kpi"><div class="dash-card-inner">
        <div class="dash-kpi-top">
          <div><div class="dash-kpi-sub">Cumprimento do Cronograma</div><div class="dash-kpi-value"><span id="dashCronPct">—</span>%</div><div class="dash-muted">Percentual de execução das entregas do período selecionado.</div></div>
          <span class="dash-icon"><?= dash_icon('cronograma') ?></span>
        </div>
        <div class="dash-kpi-footer"><span class="dash-badge neutral" id="dashCronStatus">Aguardando dados</span><span class="dash-badge neutral" id="dashCronComplement">Sem comparativo anterior</span></div>
      </div></article>

      <article class="dash-card dash-kpi"><div class="dash-card-inner">
        <div class="dash-kpi-top">
          <div><div class="dash-kpi-sub">Biblioteca</div><div class="dash-kpi-value" id="dashBibTotal">—</div><div class="dash-muted">Documentos disponíveis no recorte atual da plataforma.</div></div>
          <span class="dash-icon warning"><?= dash_icon('biblioteca') ?></span>
        </div>
        <div class="dash-kpi-footer"><span class="dash-badge neutral" id="dashBibBadge">Catálogo monitorado</span></div>
        <div class="dash-empty hidden" id="dashBibEmpty"><span class="dash-empty-icon"><?= dash_icon('empty') ?></span><strong>Nenhum documento encontrado</strong><p>Não há itens cadastrados no período atual. Ajuste os filtros ou publique novos documentos.</p></div>
      </div></article>

      <article class="dash-card dash-kpi"><div class="dash-card-inner">
        <div class="dash-kpi-top">
          <div><div class="dash-kpi-sub">Auditorias</div><div class="dash-kpi-value"><span id="dashAudMedia">—</span>%</div><div class="dash-muted">Nota média consolidada das auditorias realizadas no período.</div></div>
          <span class="dash-icon success"><?= dash_icon('auditoria') ?></span>
        </div>
        <div class="dash-kpi-footer"><span class="dash-badge neutral" id="dashAudStatus">Aguardando dados</span><span class="dash-badge neutral"><span id="dashAudTotal">—</span> realizada(s)</span></div>
      </div></article>

      <article class="dash-card dash-kpi"><div class="dash-card-inner">
        <div class="dash-kpi-top">
          <div><div class="dash-kpi-sub">Indicadores Estratégicos</div><div class="dash-kpi-value"><span id="dashIndMedia">—</span>%</div><div class="dash-muted">Atingimento médio das metas registradas no período selecionado.</div></div>
          <span class="dash-icon"><?= dash_icon('indicadores') ?></span>
        </div>
        <div class="dash-progress" style="margin-top:16px"><span id="dashIndBar"></span></div>
        <div class="dash-kpi-footer"><span class="dash-badge neutral" id="dashIndStatus">Aguardando dados</span><span class="dash-badge neutral"><span id="dashIndTotal">—</span> evento(s)</span></div>
        <div class="dash-empty hidden" id="dashIndEmpty"><span class="dash-empty-icon"><?= dash_icon('indicadores') ?></span><strong>Nenhum indicador estratégico</strong><p>Cadastre indicadores para exibir atingimento, tendência e leitura executiva no painel.</p><a href="index.php?route=indicadores/create" class="dash-btn">Cadastrar indicador</a></div>
      </div></article>
    </section>

    <section class="dash-grid-2">
      <article class="dash-card"><div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title"><span class="dash-icon"><?= dash_icon('cronograma') ?></span><div><h2>Cumprimento do Cronograma</h2>
          <p>Informativo dos eventos do período.</p></div></div>
        </div>
        <div class="dash-ring-wrap">
          <div class="dash-ring" id="dashCronRing"><div class="dash-ring-center"><strong id="dashCronRingValue">0%</strong><span id="dashCronRingStatus">Sem dados</span></div></div>
          <div>
            <div class="dash-mini-grid">
              <div class="dash-mini-card"><span>Concluído</span><strong id="dashCronDone">—</strong></div>
              <div class="dash-mini-card"><span>Pendente</span><strong id="dashCronPending">—</strong></div>
              <div class="dash-mini-card"><span>Total</span><strong id="dashCronTotal">—</strong></div>
              <div class="dash-mini-card"><span>Status</span><strong id="dashCronPanelStatus">Aguardando</strong></div>
            </div>
            <div class="dash-muted" id="dashCronLegend" style="margin-top:16px">Distribuição por status carregando...</div>
          </div>
        </div>
      </div></article>

      <article class="dash-card"><div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title"><span class="dash-icon warning"><?= dash_icon('plano') ?></span><div><h2>Plano de Ação</h2>
          <p>Resumo textual e leitura rápida por status.</p></div></div>
          <span class="dash-badge neutral"><span id="dashPlanoTotal">—</span> plano(s)</span>
        </div>
        <div id="dashPlanoChart" class="dash-bar-grid"></div>
        <div class="dash-muted" id="dashPlanoSummary" style="margin-top:16px">Aguardando dados do plano de ação...</div>
      </div></article>
    </section>

    <section class="dash-grid-2">
      <article class="dash-card"><div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title"><span class="dash-icon danger"><?= dash_icon('treinamentos') ?></span><div><h2>Treinamentos</h2><p>Painel executivo com planejados, realizados, participação e taxa de conclusão.</p></div></div>
        </div>
        <div class="dash-mini-grid">
          <div class="dash-mini-card"><span>Planejados</span><strong id="dashTrePlan">—</strong></div>
          <div class="dash-mini-card"><span>Realizados</span><strong id="dashTreReal">—</strong></div>
          <div class="dash-mini-card"><span>Participação</span><strong id="dashTrePct">—</strong></div>
          <div class="dash-mini-card"><span>Taxa de conclusão</span><strong id="dashTreConclusion">—</strong></div>
        </div>
        <div class="dash-kpi-footer" style="margin-top:16px"><span class="dash-badge neutral" id="dashTreStatus">Aguardando dados</span><span class="dash-badge neutral" id="dashTreComplement">Sem comparativo anterior</span></div>
        <div id="dashTreChart" class="dash-hbars" style="margin-top:18px"></div>
        <div class="dash-empty hidden" id="dashTreEmpty"><span class="dash-empty-icon"><?= dash_icon('treinamentos') ?></span><strong>Nenhum treinamento no período</strong><p>Não há treinamentos planejados ou realizados no recorte atual.</p></div>
      </div></article>

      <article class="dash-card"><div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title"><span class="dash-icon success"><?= dash_icon('spark') ?></span><div><h2>Indicadores rápidos</h2><p>Status resumidos da carteira atual para leitura imediata.</p></div></div>
        </div>
        <div class="dash-quick-grid">
          <?php foreach (['Planejado', 'Em Andamento', 'Concluído', 'Pendente'] as $statusLabel): ?>
            <?php
            $count = (int)($totalsByStatus[$statusLabel] ?? 0);
            $class = 'neutral';
            if ($statusLabel === 'Concluído') { $class = 'success'; }
            elseif ($statusLabel === 'Em Andamento') { $class = 'primary'; }
            elseif ($statusLabel === 'Pendente') { $class = 'danger'; }
            elseif ($statusLabel === 'Planejado') { $class = 'warning'; }
            ?>
            <div class="dash-quick-card">
              <span class="dash-badge <?= $class ?>"><?= htmlspecialchars($statusLabel) ?></span>
              <strong><?= $count ?></strong>
              <div class="dash-muted">itens monitorados na carteira atual</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div></article>
    </section>

    <section class="dash-card">
      <div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title"><span class="dash-icon"><?= dash_icon('table') ?></span><div><h2>Tabela de Detalhamento</h2><p>Busca, ordenação, paginação e filtros rápidos para leitura estruturada dos módulos do dashboard.</p></div></div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4" style="margin-bottom:16px">
          <div class="dash-search">
            <?= dash_icon('search') ?>
            <input type="text" id="dashboardMetricSearch" class="dash-input" placeholder="Buscar módulo, descrição ou insight">
          </div>
          <div class="dash-pill-row" id="dashboardQuickFilters">
            <button type="button" class="dash-pill is-active" data-filter="all">Todos</button>
            <button type="button" class="dash-pill" data-filter="success">Saudáveis</button>
            <button type="button" class="dash-pill" data-filter="warning">Atenção</button>
            <button type="button" class="dash-pill" data-filter="danger">Críticos</button>
          </div>
        </div>
        <div class="dash-table-wrap">
          <table class="dash-table">
            <thead>
              <tr>
                <th><button type="button" data-sort="module">Módulo</button></th>
                <th><button type="button" data-sort="value">Valor principal</button></th>
                <th><button type="button" data-sort="description">Descrição</button></th>
                <th><button type="button" data-sort="status">Status</button></th>
                <th><button type="button" data-sort="insight">Indicador complementar</button></th>
              </tr>
            </thead>
            <tbody id="dashboardMetricTableBody">
              <tr><td colspan="5" class="dash-muted">Carregando detalhamento executivo...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="dash-pagination">
          <div class="dash-muted" id="dashboardMetricSummary">Aguardando dados...</div>
          <div class="dash-pages" id="dashboardMetricPages"></div>
        </div>
      </div>
    </section>

    <section class="dash-grid-2">
      <article class="dash-card"><div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title"><span class="dash-icon"><?= dash_icon('spark') ?></span><div><h2>Panorama por Pilar</h2>
          <p>Revisão por Pilar (do período).</p></div></div>
        </div>
        <?php if (empty($portfolio)): ?>
          <div class="dash-empty"><span class="dash-empty-icon"><?= dash_icon('empty') ?></span><strong>Nenhum pilar monitorado</strong>
          <p>O dashboard não recebeu estatísticas de pilar para a seleção atual.</p></div>
        <?php else: ?>
          <div class="dash-portfolio-grid">
            <?php foreach ($portfolio as $pilarName => $bucket): ?>
              <div class="dash-portfolio-card">
                <header>
                  <strong><?= htmlspecialchars($pilarName) ?></strong>
                  <span class="dash-badge neutral"><?= (int)($bucket['total'] ?? 0) ?> total</span>
                </header>
                <div class="dash-mini-grid">
                  <div class="dash-mini-card"><span>Planejado</span><strong><?= (int)($bucket['Planejado'] ?? 0) ?></strong></div>
                  <div class="dash-mini-card"><span>Em andamento</span><strong><?= (int)($bucket['Em Andamento'] ?? 0) ?></strong></div>
                  <div class="dash-mini-card"><span>Concluído</span><strong><?= (int)($bucket['Concluído'] ?? 0) ?></strong></div>
                  <div class="dash-mini-card"><span>Pendente</span><strong><?= (int)($bucket['Pendente'] ?? 0) ?></strong></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div></article>

      <article class="dash-card"><div class="dash-card-inner">
        <div class="dash-section-head">
          <div class="dash-section-title"><span class="dash-icon warning"><?= dash_icon('calendar') ?></span><div><h2>Últimos Eventos do Sistema</h2>
          <p>Visão geral dos últimos eventos do sistema.</p></div></div>
        </div>
        <?php if (empty($latestItems)): ?>
          <div class="dash-empty"><span class="dash-empty-icon"><?= dash_icon('empty') ?></span><strong>Nenhum evento encontrado</strong><p>Não há aplicações recentes para exibir neste momento.</p></div>
        <?php else: ?>
          <div class="dash-event-list">
            <?php foreach ($latestItems as $item): ?>
              <?php
              $class = 'neutral';
              if ($item['status'] === 'Concluído') { $class = 'success'; }
              elseif ($item['status'] === 'Em Andamento') { $class = 'primary'; }
              elseif ($item['status'] === 'Pendente') { $class = 'danger'; }
              elseif ($item['status'] === 'Planejado') { $class = 'warning'; }
              ?>
              <div class="dash-event">
                <span class="dash-badge <?= $class ?>"><?= htmlspecialchars($item['status']) ?></span>
                <div class="min-w-0">
                  <strong><?= htmlspecialchars($item['item']) ?></strong>
                  <small><?= htmlspecialchars($item['pilar']) ?> • <?= htmlspecialchars($item['cliente']) ?><br>Consultor: <?= htmlspecialchars($item['consultor']) ?></small>
                </div>
                <div class="dash-event-date">
                  <div><?= htmlspecialchars($item['label']) ?></div>
                  <strong><?= htmlspecialchars(dash_date($item['date'])) ?></strong>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div></article>
    </section>
  </div>
</div>

<script>
  (function () {
    const root = document.getElementById('dashboardPremiumRoot');
    const form = document.getElementById('dashboardFiltersForm');
    const monthStart = document.getElementById('dashboardMonthStart');
    const monthEnd = document.getElementById('dashboardMonthEnd');
    const monthStartMonthSel = document.getElementById('dashboardMonthStartMonth');
    const monthStartYearSel = document.getElementById('dashboardMonthStartYear');
    const monthEndMonthSel = document.getElementById('dashboardMonthEndMonth');
    const monthEndYearSel = document.getElementById('dashboardMonthEndYear');
    const errorBox = document.getElementById('dashboardFilterError');
    const loading = document.getElementById('dashboardLoading');
    const summary = document.getElementById('dashboardEmpresasSummary');
    const periodLabel = document.getElementById('dashboardPeriodLabel');
    const empresaCountLabel = document.getElementById('dashboardEmpresaCount');
    const lastUpdatedAt = document.getElementById('dashboardLastUpdatedAt');
    const pdfLink = document.getElementById('dashboardPdfLink');
    const refreshBtn = document.getElementById('dashboardRefreshBtn');
    const applyBtn = document.getElementById('dashboardApplyBtn');
    const applyLabel = document.getElementById('dashboardApplyLabel');
    const totalCompanies = Number(root?.dataset.totalCompanies || 0);
    const btnAll = document.getElementById('dashboardEmpresasAll');
    const btnNone = document.getElementById('dashboardEmpresasNone');
    const companyDetails = document.getElementById('dashboardEmpresasDetails');
    const departamentoSelect = document.getElementById('dashboardDepartamentoSelect');

    const dash = {
      cronPct: document.getElementById('dashCronPct'),
      cronStatus: document.getElementById('dashCronStatus'),
      cronComplement: document.getElementById('dashCronComplement'),
      cronRing: document.getElementById('dashCronRing'),
      cronRingValue: document.getElementById('dashCronRingValue'),
      cronRingStatus: document.getElementById('dashCronRingStatus'),
      cronDone: document.getElementById('dashCronDone'),
      cronPending: document.getElementById('dashCronPending'),
      cronTotal: document.getElementById('dashCronTotal'),
      cronPanelStatus: document.getElementById('dashCronPanelStatus'),
      cronLegend: document.getElementById('dashCronLegend'),
      bibTotal: document.getElementById('dashBibTotal'),
      bibBadge: document.getElementById('dashBibBadge'),
      bibEmpty: document.getElementById('dashBibEmpty'),
      audMedia: document.getElementById('dashAudMedia'),
      audTotal: document.getElementById('dashAudTotal'),
      audStatus: document.getElementById('dashAudStatus'),
      indMedia: document.getElementById('dashIndMedia'),
      indTotal: document.getElementById('dashIndTotal'),
      indBar: document.getElementById('dashIndBar'),
      indStatus: document.getElementById('dashIndStatus'),
      indEmpty: document.getElementById('dashIndEmpty'),
      planoTotal: document.getElementById('dashPlanoTotal'),
      planoChart: document.getElementById('dashPlanoChart'),
      planoSummary: document.getElementById('dashPlanoSummary'),
      trePlan: document.getElementById('dashTrePlan'),
      treReal: document.getElementById('dashTreReal'),
      trePct: document.getElementById('dashTrePct'),
      treConclusion: document.getElementById('dashTreConclusion'),
      treStatus: document.getElementById('dashTreStatus'),
      treComplement: document.getElementById('dashTreComplement'),
      treChart: document.getElementById('dashTreChart'),
      treEmpty: document.getElementById('dashTreEmpty'),
      tableBody: document.getElementById('dashboardMetricTableBody'),
      tableSummary: document.getElementById('dashboardMetricSummary'),
      tablePages: document.getElementById('dashboardMetricPages'),
      search: document.getElementById('dashboardMetricSearch'),
      quickFilters: document.getElementById('dashboardQuickFilters')
    };

    const tableState = { rows: [], query: '', filter: 'all', sort: 'value', dir: 'desc', page: 1, perPage: 4 };

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function setError(message) {
      if (!errorBox) return;
      if (!message) {
        errorBox.classList.remove('is-visible');
        errorBox.textContent = '';
        return;
      }
      errorBox.classList.add('is-visible');
      errorBox.textContent = String(message);
    }

    function setLoadingState(isLoading) {
      root?.classList.toggle('is-loading', !!isLoading);
      if (loading) loading.style.display = isLoading ? 'flex' : '';
      if (applyBtn) applyBtn.disabled = !!isLoading;
      if (refreshBtn) refreshBtn.disabled = !!isLoading;
      if (applyLabel) applyLabel.textContent = isLoading ? 'Atualizando...' : 'Aplicar filtros';
    }

    function selectedCount() {
      return form ? Array.from(form.querySelectorAll('input[name="clientes[]"]:checked')).length : 0;
    }

    function effectiveCompanyCount() {
      const count = selectedCount();
      return count > 0 ? count : totalCompanies;
    }

    function updateSelectedLabels() {
      if (summary) {
        summary.textContent = selectedCount() > 0 ? (selectedCount() + ' empresa(s) selecionada(s)') : 'Todas as empresas';
      }
      if (empresaCountLabel) {
        empresaCountLabel.textContent = String(effectiveCompanyCount());
      }
      updatePdfLink();
    }

    function updatePdfLink() {
      if (!pdfLink || !form) return;
      const params = new URLSearchParams();
      params.set('route', 'dashboard/pdf');
      params.set('month_start', String(monthStart?.value || ''));
      params.set('month_end', String(monthEnd?.value || ''));
      const departamentoValue = String(departamentoSelect?.value || '').trim();
      if (departamentoValue) params.set('departamento_id', departamentoValue);
      Array.from(form.querySelectorAll('input[name="clientes[]"]:checked')).forEach((el) => {
        const value = String(el.value || '').trim();
        if (value) params.append('clientes[]', value);
      });
      pdfLink.href = 'index.php?' + params.toString();
    }

    async function loadDepartamentos() {
      if (!departamentoSelect || !form) return;
      const params = new URLSearchParams();
      params.set('route', 'dashboard/apiDepartamentos');
      Array.from(form.querySelectorAll('input[name="clientes[]"]:checked')).forEach((el) => {
        const value = String(el.value || '').trim();
        if (value) params.append('clientes[]', value);
      });
      const currentValue = departamentoSelect.value;
      try {
        const response = await fetch(`index.php?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await response.json().catch(() => null);
        const list = (json && json.ok && Array.isArray(json.departamentos)) ? json.departamentos : [];
        const options = ['<option value="">Todos os departamentos</option>'].concat(
          list.map((d) => `<option value="${escapeHtml(String(d.id))}">${escapeHtml(String(d.nome || ''))}</option>`)
        );
        departamentoSelect.innerHTML = options.join('');
        departamentoSelect.value = list.some((d) => String(d.id) === currentValue) ? currentValue : '';
      } catch (error) {
        /* mantém a lista atual em caso de falha de rede */
      }
    }

    function validateMonths() {
      const start = String(monthStart?.value || '').trim();
      const end = String(monthEnd?.value || '').trim();
      if (!start || !end) {
        setError('Selecione o mês inicial e o mês final.');
        return false;
      }
      if (end < start) {
        setError('O mês final não pode ser anterior ao mês inicial.');
        return false;
      }
      setError('');
      return true;
    }

    function formatPct(value, fallback = '—') {
      if (value === null || value === undefined || value === '') return fallback;
      return String(Number(value).toFixed(2)).replace('.', ',');
    }

    function formatInt(value) {
      return String(Math.round(Number(value || 0)));
    }

    function nowLabel() {
      return new Date().toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }

    function periodText(startDate, endDate) {
      return startDate && endDate ? `${startDate} até ${endDate}` : 'Período não definido';
    }

    function statusConfig(pct) {
      const value = Number(pct || 0);
      if (value >= 90) return { text: 'Excelente', badge: 'success', color: '#16a34a' };
      if (value >= 75) return { text: 'Bom', badge: 'primary', color: '#2563eb' };
      if (value >= 50) return { text: 'Atenção', badge: 'warning', color: '#d97706' };
      return { text: 'Crítico', badge: 'danger', color: '#dc2626' };
    }

    function renderBadge(element, label, kind) {
      if (!element) return;
      element.className = `dash-badge ${kind || 'neutral'}`;
      element.textContent = label;
    }

    function renderCronograma(cron) {
      const pct = Number(cron?.pct || 0);
      const total = Number(cron?.total || 0);
      const done = Number(cron?.finalizados || 0);
      const pending = Math.max(0, total - done);
      const status = statusConfig(pct);
      if (dash.cronPct) dash.cronPct.textContent = formatPct(pct, '0,00');
      if (dash.cronDone) dash.cronDone.textContent = formatInt(done);
      if (dash.cronPending) dash.cronPending.textContent = formatInt(pending);
      if (dash.cronTotal) dash.cronTotal.textContent = formatInt(total);
      if (dash.cronRingValue) dash.cronRingValue.textContent = `${formatPct(pct, '0,00')}%`;
      if (dash.cronRingStatus) dash.cronRingStatus.textContent = status.text;
      if (dash.cronPanelStatus) dash.cronPanelStatus.textContent = status.text;
      if (dash.cronRing) {
        dash.cronRing.style.setProperty('--ring-value', String(Math.max(0, Math.min(100, pct))));
        dash.cronRing.style.setProperty('--ring-color', status.color);
      }
      renderBadge(dash.cronStatus, status.text, status.badge);
      renderBadge(dash.cronComplement, `${formatInt(done)} concluído(s) • ${formatInt(pending)} pendente(s)`, 'neutral');
      const order = ['Finalizado', 'Andamento', 'Pendente', 'Adiado', 'Planejado'];
      const items = order.filter((key) => Object.prototype.hasOwnProperty.call(cron?.by_status || {}, key)).map((key) => `${key}: ${formatInt(cron.by_status[key])}`);
      if (dash.cronLegend) {
        dash.cronLegend.textContent = items.length ? items.join(' • ') : 'Nenhum evento de cronograma no período.';
      }
    }

    function renderBiblioteca(biblioteca) {
      const total = Number(biblioteca?.total_itens || 0);
      if (dash.bibTotal) dash.bibTotal.textContent = formatInt(total);
      renderBadge(dash.bibBadge, total > 0 ? `${formatInt(total)} documento(s) visível(is)` : 'Catálogo vazio no período', total > 0 ? 'warning' : 'neutral');
      dash.bibEmpty?.classList.toggle('hidden', total > 0);
    }

    function renderAuditorias(auditorias) {
      const media = auditorias?.media_conformidade_pct;
      const total = Number(auditorias?.total_realizadas || 0);
      const pct = media === null || media === undefined ? 0 : Number(media);
      const status = statusConfig(pct);
      if (dash.audMedia) dash.audMedia.textContent = media === null || media === undefined ? '—' : formatPct(pct);
      if (dash.audTotal) dash.audTotal.textContent = formatInt(total);
      renderBadge(dash.audStatus, total > 0 ? status.text : 'Sem auditorias', total > 0 ? status.badge : 'neutral');
    }

    function renderIndicadores(indicadores) {
      const media = indicadores?.media_atingimento_pct;
      const total = Number(indicadores?.total_eventos || 0);
      const pct = media === null || media === undefined ? 0 : Number(media);
      const status = statusConfig(pct);
      if (dash.indMedia) dash.indMedia.textContent = media === null || media === undefined ? '—' : formatPct(pct);
      if (dash.indTotal) dash.indTotal.textContent = formatInt(total);
      if (dash.indBar) dash.indBar.style.width = `${Math.max(0, Math.min(100, pct))}%`;
      renderBadge(dash.indStatus, total > 0 ? status.text : 'Sem indicadores', total > 0 ? status.badge : 'neutral');
      dash.indEmpty?.classList.toggle('hidden', total > 0);
    }

    function renderPlano(plano) {
      const statusOrder = [
        { label: 'Planejado', color: '#2563eb' },
        { label: 'Em Andamento', color: '#d97706' },
        { label: 'Pendente', color: '#dc2626' },
        { label: 'Concluído', color: '#16a34a' }
      ];
      const rows = Array.isArray(plano?.by_status) ? plano.by_status : [];
      const total = Number(plano?.total || 0);
      if (dash.planoTotal) dash.planoTotal.textContent = formatInt(total);
      const map = {};
      rows.forEach((row) => { map[String(row.status || '')] = Number(row.total || 0); });
      const max = Math.max(1, ...statusOrder.map((item) => map[item.label] || 0));
      if (dash.planoChart) {
        dash.planoChart.innerHTML = statusOrder.map((item) => {
          const value = map[item.label] || 0;
          const height = Math.max(8, (value / max) * 130);
          return `
            <div class="dash-bar-card">
              <div class="dash-bar-top"><span>${item.label}</span><strong>${formatInt(value)}</strong></div>
              <div class="dash-bar-stage">
                <div class="dash-bar dash-tooltip" data-tooltip="${item.label}: ${formatInt(value)} item(ns)" style="height:${height}px;background:${item.color};"></div>
              </div>
              <small class="dash-muted">${total > 0 ? formatPct((value / total) * 100, '0,00') : '0,00'}% do total</small>
            </div>
          `;
        }).join('');
      }
      if (dash.planoSummary) {
        dash.planoSummary.textContent = total > 0
          ? `${formatInt(map['Concluído'] || 0)} concluído(s), ${formatInt(map['Em Andamento'] || 0)} em andamento e ${formatInt(map['Pendente'] || 0)} pendente(s) no período.`
          : 'Nenhum plano de ação encontrado no período atual.';
      }
    }

    function renderTreinamentos(treinamentos) {
      const planejados = Number(treinamentos?.planejados || 0);
      const realizados = Number(treinamentos?.realizados || 0);
      const participacao = Number(treinamentos?.participacao_pct || 0);
      const conclusao = planejados > 0 ? (realizados / planejados) * 100 : 0;
      const chartRows = Array.isArray(treinamentos?.por_treinamento) ? treinamentos.por_treinamento.slice(0, 5) : [];
      const status = statusConfig(conclusao);
      if (dash.trePlan) dash.trePlan.textContent = formatInt(planejados);
      if (dash.treReal) dash.treReal.textContent = formatInt(realizados);
      if (dash.trePct) dash.trePct.textContent = `${formatPct(participacao, '0,00')}%`;
      if (dash.treConclusion) dash.treConclusion.textContent = `${formatPct(conclusao, '0,00')}%`;
      renderBadge(dash.treStatus, planejados > 0 ? status.text : 'Sem treinamentos', planejados > 0 ? status.badge : 'neutral');
      renderBadge(dash.treComplement, `${formatInt(realizados)} realizado(s) de ${formatInt(planejados)} planejado(s)`, 'neutral');
      if (dash.treChart) {
        dash.treChart.innerHTML = chartRows.map((row) => {
          const pct = Number(row.participacao_pct || 0);
          const trainingName = escapeHtml(String(row.treinamento_nome || 'Treinamento'));
          return `
            <div class="dash-hrow">
              <div class="dash-hhead">
                <strong>${trainingName}</strong>
                <span>${formatPct(pct, '0,00')}%</span>
              </div>
              <div class="dash-htrack dash-tooltip" data-tooltip="${trainingName}: ${formatInt(row.total_presentes || 0)} presentes / ${formatInt(row.total_inscritos || 0)} inscritos">
                <div class="dash-hfill" style="width:${Math.max(0, Math.min(100, pct))}%;"></div>
              </div>
            </div>
          `;
        }).join('');
      }
      dash.treEmpty?.classList.toggle('hidden', planejados > 0 || realizados > 0 || chartRows.length > 0);
    }

    function buildRows(payload) {
      const cron = payload.cronograma || {};
      const bib = payload.biblioteca || {};
      const aud = payload.auditorias || {};
      const ind = payload.indicadores || {};
      const plano = payload.planoacao || {};
      const tre = payload.treinamentos || {};
      const cronPct = Number(cron.pct || 0);
      const audPct = aud.media_conformidade_pct === null || aud.media_conformidade_pct === undefined ? 0 : Number(aud.media_conformidade_pct);
      const indPct = ind.media_atingimento_pct === null || ind.media_atingimento_pct === undefined ? 0 : Number(ind.media_atingimento_pct);
      const treConclusion = Number(tre.planejados || 0) > 0 ? (Number(tre.realizados || 0) / Number(tre.planejados || 0)) * 100 : 0;

      return [
        {
          module: 'Cronograma',
          valueLabel: `${formatPct(cronPct, '0,00')}%`,
          value: cronPct,
          description: `${formatInt(cron.finalizados || 0)} finalizado(s) de ${formatInt(cron.total || 0)} no período`,
          status: statusConfig(cronPct),
          insight: `Pendentes estimados: ${formatInt(Math.max(0, Number(cron.total || 0) - Number(cron.finalizados || 0)))}`,
        },
        {
          module: 'Biblioteca',
          valueLabel: formatInt(bib.total_itens || 0),
          value: Number(bib.total_itens || 0),
          description: 'Documentos disponíveis no recorte atual',
          status: { text: Number(bib.total_itens || 0) > 0 ? 'Disponível' : 'Sem dados', badge: Number(bib.total_itens || 0) > 0 ? 'warning' : 'neutral' },
          insight: Number(bib.total_itens || 0) > 0 ? 'Catálogo ativo' : 'Publicar novos documentos',
        },
        {
          module: 'Auditorias',
          valueLabel: aud.media_conformidade_pct === null || aud.media_conformidade_pct === undefined ? '—' : `${formatPct(audPct)}%`,
          value: audPct,
          description: `${formatInt(aud.total_realizadas || 0)} auditoria(s) realizada(s)`,
          status: { text: Number(aud.total_realizadas || 0) > 0 ? statusConfig(audPct).text : 'Sem dados', badge: Number(aud.total_realizadas || 0) > 0 ? statusConfig(audPct).badge : 'neutral' },
          insight: 'Meta comparativa não disponível no payload atual',
        },
        {
          module: 'Indicadores',
          valueLabel: ind.media_atingimento_pct === null || ind.media_atingimento_pct === undefined ? '—' : `${formatPct(indPct)}%`,
          value: indPct,
          description: `${formatInt(ind.total_eventos || 0)} evento(s) no período`,
          status: { text: Number(ind.total_eventos || 0) > 0 ? statusConfig(indPct).text : 'Sem dados', badge: Number(ind.total_eventos || 0) > 0 ? statusConfig(indPct).badge : 'neutral' },
          insight: Number(ind.total_eventos || 0) > 0 ? 'Painel estratégico ativo' : 'Cadastrar indicador estratégico',
        },
        {
          module: 'Plano de Ação',
          valueLabel: formatInt(plano.total || 0),
          value: Number(plano.total || 0),
          description: 'Planos distribuídos por status operacional',
          status: { text: Number(plano.total || 0) > 0 ? 'Acompanhamento ativo' : 'Sem dados', badge: Number(plano.total || 0) > 0 ? 'warning' : 'neutral' },
          insight: `Concluídos: ${formatInt((Array.isArray(plano.by_status) ? plano.by_status.find((row) => row.status === 'Concluído')?.total : 0) || 0)}`,
        },
        {
          module: 'Treinamentos',
          valueLabel: `${formatPct(treConclusion, '0,00')}%`,
          value: treConclusion,
          description: `${formatInt(tre.realizados || 0)} realizado(s) de ${formatInt(tre.planejados || 0)} planejado(s)`,
          status: { text: Number(tre.planejados || 0) > 0 ? statusConfig(treConclusion).text : 'Sem dados', badge: Number(tre.planejados || 0) > 0 ? statusConfig(treConclusion).badge : 'neutral' },
          insight: `Participação média: ${formatPct(tre.participacao_pct || 0, '0,00')}%`,
        }
      ];
    }

    function filteredRows() {
      const query = tableState.query.toLowerCase();
      const filtered = tableState.rows.filter((row) => {
        const text = `${row.module} ${row.description} ${row.insight} ${row.status.text}`.toLowerCase();
        const byQuery = query === '' || text.includes(query);
        const byFilter = tableState.filter === 'all' || row.status.badge === tableState.filter;
        return byQuery && byFilter;
      });
      filtered.sort((a, b) => {
        const key = tableState.sort;
        const direction = tableState.dir === 'asc' ? 1 : -1;
        let left;
        let right;
        if (key === 'module' || key === 'description' || key === 'insight' || key === 'status') {
          left = key === 'status' ? a.status.text : a[key];
          right = key === 'status' ? b.status.text : b[key];
          return String(left).localeCompare(String(right), 'pt-BR') * direction;
        }
        return (Number(a.value || 0) - Number(b.value || 0)) * direction;
      });
      return filtered;
    }

    function renderTable() {
      const rows = filteredRows();
      const totalPages = Math.max(1, Math.ceil(rows.length / tableState.perPage));
      if (tableState.page > totalPages) tableState.page = totalPages;
      const start = (tableState.page - 1) * tableState.perPage;
      const pageRows = rows.slice(start, start + tableState.perPage);
      if (dash.tableBody) {
        dash.tableBody.innerHTML = pageRows.length ? pageRows.map((row) => `
          <tr>
            <td><strong>${escapeHtml(row.module)}</strong><small>Leitura executiva do módulo</small></td>
            <td><strong>${escapeHtml(row.valueLabel)}</strong><small>Valor principal do período</small></td>
            <td>${escapeHtml(row.description)}</td>
            <td><span class="dash-badge ${escapeHtml(row.status.badge)}">${escapeHtml(row.status.text)}</span></td>
            <td>${escapeHtml(row.insight)}</td>
          </tr>
        `).join('') : '<tr><td colspan="5" class="dash-muted">Nenhum resultado encontrado para os filtros aplicados.</td></tr>';
      }
      if (dash.tableSummary) {
        const from = rows.length ? start + 1 : 0;
        const to = Math.min(rows.length, start + tableState.perPage);
        dash.tableSummary.textContent = `Exibindo ${from}-${to} de ${rows.length} linha(s) do detalhamento.`;
      }
      if (dash.tablePages) {
        let html = '';
        for (let page = 1; page <= totalPages; page++) {
          html += `<button type="button" class="dash-page ${page === tableState.page ? 'is-active' : ''}" data-page="${page}">${page}</button>`;
        }
        dash.tablePages.innerHTML = html;
        dash.tablePages.querySelectorAll('[data-page]').forEach((button) => {
          button.addEventListener('click', () => {
            tableState.page = Number(button.getAttribute('data-page') || 1);
            renderTable();
          });
        });
      }
    }

    async function loadMetrics() {
      if (!validateMonths() || !form) return;
      setLoadingState(true);
      updateSelectedLabels();
      try {
        const params = new URLSearchParams(new FormData(form));
        params.set('route', 'dashboard/metrics');
        const response = await fetch(`index.php?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await response.json().catch(() => null);
        if (!response.ok || !json || !json.ok) {
          setError(json && json.message ? json.message : 'Não foi possível carregar os dados do dashboard.');
          return;
        }
        const filters = json.filters || {};
        if (periodLabel) periodLabel.textContent = periodText(filters.start_date, filters.end_date);
        if (lastUpdatedAt) lastUpdatedAt.textContent = nowLabel();

        renderCronograma(json.cronograma || {});
        renderBiblioteca(json.biblioteca || {});
        renderAuditorias(json.auditorias || {});
        renderIndicadores(json.indicadores || {});
        renderPlano(json.planoacao || {});
        renderTreinamentos(json.treinamentos || {});

        tableState.rows = buildRows(json);
        tableState.page = 1;
        renderTable();
      } catch (error) {
        setError('Não foi possível carregar os dados do dashboard.');
      } finally {
        setLoadingState(false);
      }
    }

    btnAll?.addEventListener('click', () => {
      form?.querySelectorAll('input[name="clientes[]"]').forEach((input) => { input.checked = true; });
      updateSelectedLabels();
      loadDepartamentos();
    });

    btnNone?.addEventListener('click', () => {
      form?.querySelectorAll('input[name="clientes[]"]').forEach((input) => { input.checked = false; });
      updateSelectedLabels();
      loadDepartamentos();
    });

    form?.querySelectorAll('input[name="clientes[]"]').forEach((input) => {
      input.addEventListener('change', () => {
        updateSelectedLabels();
        loadDepartamentos();
      });
    });
    departamentoSelect?.addEventListener('change', updatePdfLink);
    function syncMonthField(hiddenInput, monthSel, yearSel) {
      if (!hiddenInput || !monthSel || !yearSel) return;
      const value = `${yearSel.value}-${monthSel.value}`;
      if (hiddenInput.value !== value) {
        hiddenInput.value = value;
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
    monthStartMonthSel?.addEventListener('change', () => syncMonthField(monthStart, monthStartMonthSel, monthStartYearSel));
    monthStartYearSel?.addEventListener('change', () => syncMonthField(monthStart, monthStartMonthSel, monthStartYearSel));
    monthEndMonthSel?.addEventListener('change', () => syncMonthField(monthEnd, monthEndMonthSel, monthEndYearSel));
    monthEndYearSel?.addEventListener('change', () => syncMonthField(monthEnd, monthEndMonthSel, monthEndYearSel));

    monthStart?.addEventListener('change', updatePdfLink);
    monthEnd?.addEventListener('change', updatePdfLink);

    document.addEventListener('click', (event) => {
      if (!companyDetails?.open) return;
      if (companyDetails.contains(event.target)) return;
      companyDetails.open = false;
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && companyDetails?.open) {
        companyDetails.open = false;
      }
    });

    form?.addEventListener('submit', (event) => {
      if (!validateMonths()) {
        event.preventDefault();
        return;
      }
      setLoadingState(true);
    });

    refreshBtn?.addEventListener('click', loadMetrics);

    dash.search?.addEventListener('input', () => {
      tableState.query = String(dash.search?.value || '').trim();
      tableState.page = 1;
      renderTable();
    });

    dash.quickFilters?.querySelectorAll('[data-filter]').forEach((button) => {
      button.addEventListener('click', () => {
        dash.quickFilters?.querySelectorAll('[data-filter]').forEach((chip) => chip.classList.remove('is-active'));
        button.classList.add('is-active');
        tableState.filter = String(button.getAttribute('data-filter') || 'all');
        tableState.page = 1;
        renderTable();
      });
    });

    document.querySelectorAll('.dash-table thead [data-sort]').forEach((button) => {
      button.addEventListener('click', () => {
        const sort = String(button.getAttribute('data-sort') || 'value');
        if (tableState.sort === sort) {
          tableState.dir = tableState.dir === 'asc' ? 'desc' : 'asc';
        } else {
          tableState.sort = sort;
          tableState.dir = sort === 'value' ? 'desc' : 'asc';
        }
        renderTable();
      });
    });

    updateSelectedLabels();
    updatePdfLink();
    loadMetrics();
  })();
</script>
