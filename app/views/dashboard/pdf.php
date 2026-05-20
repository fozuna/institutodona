<?php
$branding = $branding ?? [];
$logoSrc = (string)($logoSrc ?? '');
$periodLabel = (string)($periodLabel ?? '');
$empresaLabel = (string)($empresaLabel ?? '');
$cronPct = (float)($cronPct ?? 0.0);
$cron = is_array($cron ?? null) ? $cron : [];
$bib = is_array($bib ?? null) ? $bib : [];
$plano = is_array($plano ?? null) ? $plano : [];
$aud = is_array($aud ?? null) ? $aud : [];
$ind = is_array($ind ?? null) ? $ind : [];
$tre = is_array($tre ?? null) ? $tre : [];
$cronBy = is_array($cronBy ?? null) ? $cronBy : [];
$planoBy = is_array($planoBy ?? null) ? $planoBy : [];
$indPct = (float)($indPct ?? 0.0);
$audPct = $audPct ?? null;
$trePct = (float)($trePct ?? 0.0);
$layout = is_array($layout ?? null) ? $layout : [];

$baseFont = (int)($layout['base_font'] ?? 9);
$titleFont = (int)($layout['title_font'] ?? 14);
$valueFont = (int)($layout['value_font'] ?? 18);
$gridPad = (int)($layout['grid_pad'] ?? 4);
$cardPadY = (int)($layout['card_pad_y'] ?? 7);
$cardPadX = (int)($layout['card_pad_x'] ?? 9);
$headerPadY = (int)($layout['header_pad_y'] ?? 7);
$headerPadX = (int)($layout['header_pad_x'] ?? 9);
$headerMarginBottom = (int)($layout['header_margin_bottom'] ?? 8);
$barHeight = (int)($layout['bar_height'] ?? 8);
$logoMaxHeight = (int)($layout['logo_max_height'] ?? 32);
$footerBottom = (int)($layout['footer_bottom_mm'] ?? 8);

$colorRed = '#b91c1c';
$colorBrown = '#6b3b2a';
$colorGray = '#e5e7eb';
$colorText = '#111827';
$colorMuted = '#6b7280';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <style>
    @page {
      size: A4 landscape;
      margin: <?= (int)($branding['margins']['top'] ?? 14) ?>mm <?= (int)($branding['margins']['right'] ?? 12) ?>mm <?= (int)($branding['margins']['bottom'] ?? 14) ?>mm <?= (int)($branding['margins']['left'] ?? 12) ?>mm;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: <?= $baseFont ?>px; color: <?= $colorText ?>; }
    .header { border: 1px solid <?= $colorGray ?>; border-radius: 10px; padding: <?= $headerPadY ?>px <?= $headerPadX ?>px; margin-bottom: <?= $headerMarginBottom ?>px; }
    .header:after { content: ""; display: block; clear: both; }
    .logo-wrap { width: <?= (int)($branding['logo_width'] ?? 108) ?>px; }
    .logo-wrap.left { float: left; margin-right: 10px; }
    .logo-wrap.right { float: right; margin-left: 10px; text-align: right; }
    .logo { max-width: 100%; height: auto; max-height: <?= $logoMaxHeight ?>px; margin-bottom: 5px; }
    .title { font-size: <?= $titleFont ?>px; font-weight: bold; margin: 0 0 2px 0; }
    .muted { color: <?= $colorMuted ?>; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: <?= max(8, $baseFont - 1) ?>px; border: 1px solid <?= $colorGray ?>; background: #f9fafb; color: #374151; }
    .report-footer { position: fixed; bottom: -<?= $footerBottom ?>mm; left: 0; right: 0; font-size: <?= max(8, $baseFont - 1) ?>px; color: <?= $colorMuted ?>; text-align: center; }
    .report-footer .page-number:before { content: counter(page); }
    .report-footer .page-count:before { content: counter(pages); }
    .grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .grid td { vertical-align: top; padding: <?= $gridPad ?>px; }
    .card { border: 1px solid <?= $colorGray ?>; border-radius: 10px; padding: <?= $cardPadY ?>px <?= $cardPadX ?>px; }
    .k { font-size: <?= max(8, $baseFont - 1) ?>px; color: <?= $colorMuted ?>; text-transform: uppercase; letter-spacing: 0.08em; }
    .v { font-size: <?= $valueFont ?>px; font-weight: bold; color: <?= $colorBrown ?>; margin-top: 4px; }
    .sub { font-size: <?= $baseFont ?>px; color: <?= $colorMuted ?>; margin-top: 3px; }
    .bar { height: <?= $barHeight ?>px; border-radius: 999px; background: <?= $colorGray ?>; overflow: hidden; margin-top: 6px; }
    .bar > span { display: block; height: <?= $barHeight ?>px; background: <?= $colorRed ?>; }
    .mini-title { font-weight: bold; color: <?= $colorBrown ?>; margin-bottom: 6px; }
    .rows { width: 100%; border-collapse: collapse; }
    .rows td { padding: 4px 0; border-top: 1px solid <?= $colorGray ?>; }
    .rows tr:first-child td { border-top: 0; }
    .dot { display:inline-block; width: 8px; height: 8px; border-radius: 99px; background: <?= $colorRed ?>; margin-right: 6px; vertical-align: middle; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <div class="header">
    <?php if ($logoSrc !== ''): ?>
      <div class="logo-wrap <?= (($branding['logo_position'] ?? 'left') === 'right') ? 'right' : 'left' ?>">
        <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>" class="logo" alt="Logo" />
      </div>
    <?php endif; ?>
    <div style="overflow:hidden;">
      <div class="title"><?= htmlspecialchars((string)($branding['header_title'] ?? 'Dashboard'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php if (!empty($branding['header_subtitle'])): ?>
        <div class="muted" style="margin-bottom: 2px;"><?= htmlspecialchars((string)$branding['header_subtitle'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <div class="muted">
        Período: <strong><?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if ($empresaLabel !== ''): ?>
          · Empresas: <strong><?= htmlspecialchars($empresaLabel, ENT_QUOTES, 'UTF-8') ?></strong>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <table class="grid">
    <tr>
      <td style="width:33.33%;">
        <div class="card">
          <div class="k">Cumprimento do Cronograma</div>
          <div class="v"><?= htmlspecialchars(number_format($cronPct, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%</div>
          <div class="sub"><?= (int)($cron['finalizados'] ?? 0) ?> finalizados / <?= (int)($cron['total'] ?? 0) ?> no período</div>
          <div class="bar"><span style="width: <?= (int)max(0, min(100, $cronPct)) ?>%;"></span></div>
        </div>
      </td>
      <td style="width:33.33%;">
        <div class="card">
          <div class="k">Biblioteca</div>
          <div class="v"><?= (int)($bib['total_itens'] ?? 0) ?></div>
          <div class="sub">Itens cadastrados no período</div>
        </div>
      </td>
      <td style="width:33.33%;">
        <div class="card">
          <div class="k">Auditorias</div>
          <div class="v"><?= $audPct === null ? '—' : htmlspecialchars(number_format((float)$audPct, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%</div>
          <div class="sub">Média geral • <?= (int)($aud['total_realizadas'] ?? 0) ?> realizada(s)</div>
        </div>
      </td>
    </tr>
    <tr>
      <td style="width:33.33%;">
        <div class="card">
          <div class="k">Indicadores Estratégicos</div>
          <div class="v"><?= htmlspecialchars(number_format($indPct, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%</div>
          <div class="sub"><?= (int)($ind['total_eventos'] ?? 0) ?> evento(s) no período</div>
          <div class="bar"><span style="width: <?= (int)max(0, min(100, $indPct)) ?>%; background: <?= $colorBrown ?>;"></span></div>
        </div>
      </td>
      <td style="width:33.33%;">
        <div class="card">
          <div class="k">Planos de Ação</div>
          <div class="v"><?= (int)($plano['total'] ?? 0) ?></div>
          <div class="sub">Total no período</div>
          <?php if (!empty($planoBy)): ?>
            <table class="rows" style="margin-top:8px;">
              <?php foreach ($planoBy as $row): ?>
                <tr>
                  <td><span class="dot"></span><?= htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="right"><strong><?= (int)($row['total'] ?? 0) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>
      </td>
      <td style="width:33.33%;">
        <div class="card">
          <div class="k">Treinamentos</div>
          <table style="width:100%; border-collapse:collapse; margin-top:6px;">
            <tr>
              <td style="width:50%;">
                <div class="mini-title">Planejados</div>
                <div style="font-size:<?= max(14, (int)round($valueFont * 0.85)) ?>px; font-weight:bold; color: <?= $colorBrown ?>;"><?= (int)($tre['planejados'] ?? 0) ?></div>
              </td>
              <td style="width:50%;">
                <div class="mini-title">Realizados</div>
                <div style="font-size:<?= max(14, (int)round($valueFont * 0.85)) ?>px; font-weight:bold; color: <?= $colorBrown ?>;"><?= (int)($tre['realizados'] ?? 0) ?></div>
              </td>
            </tr>
          </table>
          <div class="sub" style="margin-top:6px;">Participação: <strong><?= htmlspecialchars(number_format($trePct, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%</strong> • Inscritos: <?= (int)($tre['total_inscritos'] ?? 0) ?> • Presentes: <?= (int)($tre['total_presentes'] ?? 0) ?></div>
          <div class="bar"><span style="width: <?= (int)max(0, min(100, $trePct)) ?>%;"></span></div>
        </div>
      </td>
    </tr>
    <tr>
      <td colspan="3">
        <div class="card" style="padding: <?= max(6, $cardPadY - 1) ?>px <?= max(8, $cardPadX) ?>px;">
          <div class="mini-title">Detalhamento do Cronograma</div>
          <?php if (empty($cronBy)): ?>
            <div class="muted">Sem dados no período.</div>
          <?php else: ?>
            <table class="rows" style="width:100%;">
              <?php
                $sumCron = 0;
                foreach ($cronBy as $k => $v) { $sumCron += (int)$v; }
              ?>
              <?php foreach ($cronBy as $st => $ct): ?>
                <?php $pctSt = $sumCron > 0 ? (int)round(((int)$ct / $sumCron) * 100) : 0; ?>
                <tr>
                  <td style="width:18%;"><?= htmlspecialchars((string)$st, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="width:72%;">
                    <div class="bar" style="margin-top:0;"><span style="width: <?= $pctSt ?>%;"></span></div>
                  </td>
                  <td class="right" style="width:10%;"><strong><?= (int)$ct ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </table>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  </table>

  <div class="report-footer">
    Página <span class="page-number"></span> de <span class="page-count"></span> · Gerado em <?= htmlspecialchars((string)($branding['generated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
  </div>
</body>
</html>
