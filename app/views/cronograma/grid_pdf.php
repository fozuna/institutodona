<?php
/** @var array $crono */
/** @var array $grid */
/** @var array $branding */

$e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$branding = is_array($branding ?? null) ? $branding : [];
$logoSrc = (string)($branding['logo_uri'] ?? '');
$logoPos = (string)($branding['logo_position'] ?? 'left');
$months = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];

// Mesmas cores usadas na grade em tela (Tailwind green/amber/red-100/800),
// traduzidas para hex porque o dompdf não processa classes Tailwind.
$trafficColors = [
    'finalizado' => ['bg' => '#dcfce7', 'fg' => '#166534'],
    'atrasado' => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
    'pendente' => ['bg' => '#fef3c7', 'fg' => '#92400e'],
    'vazio' => ['bg' => '#ffffff', 'fg' => '#d1d5db'],
];
$colorFor = static function (string $key) use ($trafficColors): array {
    return $trafficColors[$key] ?? $trafficColors['vazio'];
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Cronograma Anual</title>
  <style>
    @page {
      size: A4 landscape;
      margin: <?= (int)($branding['margins']['top'] ?? 12) ?>mm <?= (int)($branding['margins']['right'] ?? 10) ?>mm <?= (int)($branding['margins']['bottom'] ?? 14) ?>mm <?= (int)($branding['margins']['left'] ?? 10) ?>mm;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 8.5px; }
    .muted { color: #6b7280; }
    .header { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; }
    .header::after { content: ""; display: block; clear: both; }
    .logo-wrap { width: <?= (int)($branding['logo_width'] ?? 100) ?>px; }
    .logo-wrap.left { float: left; margin-right: 12px; }
    .logo-wrap.right { float: right; margin-left: 12px; text-align: right; }
    .logo { width: 100%; height: auto; }
    .title { font-size: 16px; font-weight: 700; margin: 0 0 2px; }
    .subtitle { font-size: 10.5px; margin: 0; }
    .legend { margin-bottom: 8px; }
    .legend-item { display: inline-block; margin-right: 16px; font-size: 9px; }
    .legend-swatch { display: inline-block; width: 9px; height: 9px; border-radius: 999px; margin-right: 4px; vertical-align: middle; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid thead { display: table-header-group; }
    table.grid th, table.grid td { border: 1px solid #e5e7eb; padding: 3px 4px; vertical-align: middle; }
    table.grid th { background: #f9fafb; color: #374151; font-size: 8px; text-transform: uppercase; letter-spacing: 0.02em; text-align: left; }
    table.grid th.month-col, table.grid td.month-col { text-align: center; width: 3.3%; }
    table.grid tr { page-break-inside: avoid; }
    .status-badge { display: inline-block; padding: 1px 5px; border-radius: 999px; font-size: 7.5px; font-weight: 700; }
    .footer { margin-top: 8px; font-size: 9px; color: #6b7280; text-align: right; }
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
      <div class="title"><?= $e((string)($branding['header_title'] ?? 'Cronograma Anual')) ?></div>
      <?php if (!empty($branding['header_subtitle'])): ?>
        <div class="subtitle muted"><?= $e((string)$branding['header_subtitle']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="legend">
    <span class="legend-item"><span class="legend-swatch" style="background:<?= $colorFor('finalizado')['bg'] ?>; border:1px solid <?= $colorFor('finalizado')['fg'] ?>;"></span>Finalizado</span>
    <span class="legend-item"><span class="legend-swatch" style="background:<?= $colorFor('pendente')['bg'] ?>; border:1px solid <?= $colorFor('pendente')['fg'] ?>;"></span>Pendente / Planejado</span>
    <span class="legend-item"><span class="legend-swatch" style="background:<?= $colorFor('atrasado')['bg'] ?>; border:1px solid <?= $colorFor('atrasado')['fg'] ?>;"></span>Atrasado</span>
  </div>

  <table class="grid">
    <thead>
      <tr>
        <th style="width:11%;">Pilar</th>
        <th style="width:10%;">Departamento</th>
        <th style="width:16%;">Atividade</th>
        <th style="width:10%;">Responsável</th>
        <th style="width:8%;">Status</th>
        <?php foreach ($months as $m): ?>
          <th class="month-col"><?= $e($m) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($grid)): ?>
        <tr><td colspan="17" style="text-align:center; color:#6b7280; padding:10px;">Nenhum evento cadastrado para este cronograma.</td></tr>
      <?php endif; ?>
      <?php foreach ($grid as $row): ?>
        <?php
          $rowTrafficKey = (string)($row['traffic']['key'] ?? 'pendente');
          $rowColor = $colorFor($rowTrafficKey);
        ?>
        <tr>
          <td><?= $e((string)$row['topico']) ?></td>
          <td><?= $e((string)($row['unidade'] ?? '')) ?></td>
          <td><?= $e((string)$row['atividade']) ?></td>
          <td><?= $e((string)($row['responsavel'] ?? '')) ?></td>
          <td>
            <span class="status-badge" style="background:<?= $rowColor['bg'] ?>; color:<?= $rowColor['fg'] ?>;"><?= $e((string)$row['status']) ?></span>
          </td>
          <?php for ($mIdx = 1; $mIdx <= 12; $mIdx++): ?>
            <?php
              $cell = $row['meses'][$mIdx] ?? ['marked' => false, 'traffic' => ['key' => 'vazio']];
              $cellKey = (string)($cell['traffic']['key'] ?? 'vazio');
              $cellColor = $colorFor($cellKey);
            ?>
            <td class="month-col" style="background:<?= $cellColor['bg'] ?>; color:<?= $cellColor['fg'] ?>;">
              <?= !empty($cell['marked']) ? 'X' : '&#8212;' ?>
            </td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">
    Página <span class="page-number"></span> de <span class="page-count"></span> · Gerado em <?= $e((string)($branding['generated_at'] ?? '')) ?>
  </div>
</body>
</html>
