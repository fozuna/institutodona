<?php
/** @var array $data */
$e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$formatValue = static function ($value, array $item): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $item['unidade_simbolo'] ?? '',
        'tipo' => $item['unidade_tipo'] ?? '',
    ]);
};
$branding = is_array($data['branding'] ?? null) ? $data['branding'] : [];
$logoSrc = (string)($branding['logo_uri'] ?? '');
$logoPos = (string)($branding['logo_position'] ?? 'left');
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : ['total' => 0, 'atingida' => 0, 'parcial' => 0, 'nao_atingida' => 0, 'pendente' => 0];
$periodoInicio = (string)($data['periodoInicio'] ?? '');
$periodoFim = (string)($data['periodoFim'] ?? '');
$indicadorNome = trim((string)($data['indicadorNome'] ?? ''));
$clienteNome = (string)($data['clienteNome'] ?? '');
$ano = (int)($data['ano'] ?? 0);
$badgeStyleByKey = static function (string $key): array {
    switch ($key) {
        case 'atingida':
            return ['bg' => '#dcfce7', 'fg' => '#166534', 'bd' => '#86efac'];
        case 'parcial':
            return ['bg' => '#fef3c7', 'fg' => '#92400e', 'bd' => '#fcd34d'];
        case 'nao_atingida':
            return ['bg' => '#fee2e2', 'fg' => '#991b1b', 'bd' => '#fca5a5'];
        default:
            return ['bg' => '#f3f4f6', 'fg' => '#374151', 'bd' => '#d1d5db'];
    }
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Painel de Indicadores</title>
  <style>
    @page {
      size: A4 landscape;
      margin: <?= (int)($branding['margins']['top'] ?? 14) ?>mm <?= (int)($branding['margins']['right'] ?? 12) ?>mm <?= (int)($branding['margins']['bottom'] ?? 14) ?>mm <?= (int)($branding['margins']['left'] ?? 12) ?>mm;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 10.5px; }
    .muted { color: #6b7280; }
    .header { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-bottom: 12px; }
    .header::after { content: ""; display: block; clear: both; }
    .logo-wrap { width: <?= (int)($branding['logo_width'] ?? 108) ?>px; }
    .logo-wrap.left { float: left; margin-right: 14px; }
    .logo-wrap.right { float: right; margin-left: 14px; text-align: right; }
    .logo { width: 100%; height: auto; }
    .title { font-size: 18px; font-weight: 700; margin: 0 0 2px; }
    .subtitle { font-size: 11px; margin: 0 0 6px; }
    .meta-line { font-size: 10px; margin: 0; }
    .grid { width: 100%; }
    .grid td { vertical-align: top; }
    .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 10px; background: #fff; }
    .card-label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
    .card-value { font-size: 18px; font-weight: 700; }
    .value-green { color: #166534; }
    .value-amber { color: #92400e; }
    .value-red { color: #991b1b; }
    .value-gray { color: #374151; }
    .section { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-top: 12px; }
    .section-title { font-size: 13px; font-weight: 700; margin: 0 0 8px; color: #7f1d1d; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead { display: table-header-group; }
    table.data th, table.data td { border: 1px solid #e5e7eb; padding: 7px 8px; vertical-align: top; }
    table.data th { background: #fee2e2; color: #7f1d1d; font-size: 9.8px; text-transform: uppercase; letter-spacing: 0.02em; }
    table.data tr { page-break-inside: avoid; }
    .nowrap { white-space: nowrap; }
    .small { font-size: 9.5px; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; border: 1px solid transparent; }
    .footer { margin-top: 10px; font-size: 9px; color: #6b7280; text-align: right; }
    .page-number:before { content: counter(page); }
    .page-count:before { content: counter(pages); }
  </style>
</head>
<body>
  <div class="header">
    <?php if ($logoSrc !== ''): ?>
      <div class="logo-wrap <?= $e($logoPos) ?>">
        <img src="<?= $e($logoSrc) ?>" class="logo" alt="Logo" />
      </div>
    <?php endif; ?>
    <div>
      <div class="title"><?= $e((string)($branding['header_title'] ?? 'Painel de Indicadores')) ?></div>
      <?php if (!empty($branding['header_subtitle'])): ?>
        <div class="subtitle muted"><?= $e((string)$branding['header_subtitle']) ?></div>
      <?php endif; ?>
      <p class="meta-line muted">Empresa: <strong><?= $e($clienteNome !== '' ? $clienteNome : '—') ?></strong> · Ano: <strong><?= $ano > 0 ? $ano : '—' ?></strong> · Período: <strong><?= $e($periodoInicio) ?></strong> até <strong><?= $e($periodoFim) ?></strong><?= $indicadorNome !== '' ? ' · Indicador: <strong>' . $e($indicadorNome) . '</strong>' : '' ?></p>
    </div>
  </div>

  <table class="grid" cellspacing="0" cellpadding="0">
    <tr>
      <td style="width:20%; padding-right:8px;">
        <div class="card">
          <div class="card-label">Total</div>
          <div class="card-value"><?= (int)($stats['total'] ?? 0) ?></div>
        </div>
      </td>
      <td style="width:20%; padding-right:8px;">
        <div class="card">
          <div class="card-label">Meta atingida</div>
          <div class="card-value value-green"><?= (int)($stats['atingida'] ?? 0) ?></div>
        </div>
      </td>
      <td style="width:20%; padding-right:8px;">
        <div class="card">
          <div class="card-label">Meta parcial</div>
          <div class="card-value value-amber"><?= (int)($stats['parcial'] ?? 0) ?></div>
        </div>
      </td>
      <td style="width:20%; padding-right:8px;">
        <div class="card">
          <div class="card-label">Meta não atingida</div>
          <div class="card-value value-red"><?= (int)($stats['nao_atingida'] ?? 0) ?></div>
        </div>
      </td>
      <td style="width:20%;">
        <div class="card">
          <div class="card-label">Pendente</div>
          <div class="card-value value-gray"><?= (int)($stats['pendente'] ?? 0) ?></div>
        </div>
      </td>
    </tr>
  </table>

  <div class="section">
    <div class="section-title">Ocorrências no período</div>
    <?php if (empty($items)): ?>
      <div class="muted">Nenhum indicador disponível para o painel informado.</div>
    <?php else: ?>
      <table class="data">
        <thead>
          <tr>
            <th style="width:28%;">Indicador</th>
            <th style="width:18%;">Período</th>
            <th style="width:13%;">Meta</th>
            <th style="width:13%;">Atingido</th>
            <th style="width:12%;">Cumprimento</th>
            <th style="width:16%;">Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
          <?php
            $key = (string)($item['meta_status_key'] ?? 'pendente');
            $st = $badgeStyleByKey($key);
            $label = (string)($item['meta_status_label'] ?? 'Pendente');
            $periodLabel = (string)($item['periodo_inicio'] ?? '') . ' até ' . (string)($item['periodo_fim'] ?? '');
            $depSet = trim((string)($item['departamento_nome'] ?? '') . ' · ' . (string)($item['setor_nome'] ?? ''), " \t\n\r\0\x0B·");
          ?>
          <tr>
            <td>
              <div style="font-weight:700;"><?= $e((string)($item['indicador'] ?? '')) ?></div>
              <?php if ($depSet !== ''): ?>
                <div class="muted small"><?= $e($depSet) ?></div>
              <?php endif; ?>
            </td>
            <td class="nowrap">
              <div><?= $e((string)($item['data_evento'] ?? '')) ?></div>
              <div class="muted small"><?= $e($periodLabel) ?></div>
            </td>
            <td class="nowrap"><?= $e($formatValue($item['valor_meta'] ?? 0, $item)) ?></td>
            <td class="nowrap"><?= ($item['valor_atingido'] ?? null) !== null ? $e($formatValue($item['valor_atingido'], $item)) : '—' ?></td>
            <td class="nowrap"><?= ($item['percentual_cumprimento'] ?? null) !== null ? $e(\App\Core\ValueFormatter::percent($item['percentual_cumprimento'])) : '—' ?></td>
            <td class="nowrap">
              <span class="badge" style="background:<?= $e($st['bg']) ?>;color:<?= $e($st['fg']) ?>;border-color:<?= $e($st['bd']) ?>;"><?= $e($label) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="footer">
    Página <span class="page-number"></span> de <span class="page-count"></span> · Gerado em <?= $e((string)($branding['generated_at'] ?? '')) ?>
  </div>
</body>
</html>
