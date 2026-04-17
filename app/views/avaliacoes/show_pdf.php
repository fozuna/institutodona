<?php
// PDF layout derived from the same "resultado" screen (avaliacoes/show.php),
// but stripped from any interactive/admin-only elements (forms, selects, buttons).
// Dompdf-friendly: avoids flex/grid; uses floats/tables and inline CSS.
/** @var array $item */
/** @var array $labels */
/** @var array $qs */
/** @var array $resp */
/** @var string $empresa */
/** @var bool $isPotencial */
/** @var int $total */
/** @var int $realPct */
/** @var string $logoSrc */
/** @var array $pillarTitles */
/** @var array $branding */
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Avaliação <?= htmlspecialchars((string)$empresa, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    @page {
      size: A4 landscape;
      margin: <?= (int)($branding['margins']['top'] ?? 18) ?>mm <?= (int)($branding['margins']['right'] ?? 12) ?>mm <?= (int)($branding['margins']['bottom'] ?? 18) ?>mm <?= (int)($branding['margins']['left'] ?? 12) ?>mm;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
    .header { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-bottom: 12px; }
    .header::after, .row.clearfix::after { content: ""; display: block; clear: both; }
    .logo-wrap { width: <?= (int)($branding['logo_width'] ?? 108) ?>px; }
    .logo-wrap.left { float: left; margin-right: 14px; }
    .logo-wrap.right { float: right; margin-left: 14px; text-align: right; }
    .header-body { overflow: hidden; }
    .logo { max-width: 100%; height: auto; max-height: 40px; margin-bottom: 8px; }
    .title { font-size: 18px; font-weight: bold; margin: 0 0 4px 0; }
    .muted { color: #6b7280; }
    .report-footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 9px; color: #6b7280; text-align: center; }
    .report-footer .page-number:before { content: counter(page); }
    .report-footer .page-count:before { content: counter(pages); }
    .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; page-break-inside: avoid; }
    .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 10px; border: 1px solid #e5e7eb; background: #f9fafb; color: #374151; }
    .badge.yellow { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
    .badge.green { background: #D1FAE5; color: #065F46; border-color: #A7F3D0; }
    .meta-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .meta-table td { padding: 6px 6px; vertical-align: top; }
    .meta-label { color: #6b7280; font-size: 10px; margin-bottom: 2px; }
    .meta-value { font-weight: bold; }
    .row { width: 100%; }
    .col { float: left; padding-right: 10px; }
    .clearfix::after { content: ""; display: block; clear: both; }
    .col-4 { width: 25%; }
    .col-3 { width: 33.3333%; }
    .col-2 { width: 50%; }
    .col-last { padding-right: 0; }
    .pillar { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; min-height: 76px; }
    .pillar-title { font-weight: bold; margin-bottom: 8px; }
    .bar { width: 100%; height: 10px; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
    .bar > span { display: block; height: 10px; background: #b91c1c; }
    .score { text-align: right; font-size: 10px; color: #6b7280; margin-top: 6px; }
    .summary-box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; min-height: 60px; }
    .summary-number { font-size: 18px; font-weight: bold; text-align: center; margin-top: 8px; }
    .list-title { font-weight: bold; margin-bottom: 6px; }
    .list { margin: 0; padding-left: 14px; }
    .list li { margin: 0 0 3px 0; }
    .ok { font-weight: bold; color: #111827; }
    .nok { color: #b91c1c; }
  </style>
</head>
<body>
  <div class="header">
    <?php if (!empty($logoSrc)): ?>
      <div class="logo-wrap <?= (($branding['logo_position'] ?? 'left') === 'right') ? 'right' : 'left' ?>">
        <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>" class="logo" alt="Logo" />
      </div>
    <?php endif; ?>
    <div class="header-body">
      <div class="title"><?= htmlspecialchars((string)($branding['header_title'] ?? 'Avaliação'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php if (!empty($branding['header_subtitle'])): ?>
        <div class="muted" style="margin-bottom: 4px;"><?= htmlspecialchars((string)$branding['header_subtitle'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <div class="muted">Empresa: <strong><?= htmlspecialchars((string)$empresa, ENT_QUOTES, 'UTF-8') ?></strong> · Data: <?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($item['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>

  <div class="card">
    <div class="muted">Empresa</div>
    <div style="font-size: 14px; font-weight: bold; margin-top: 2px;"><?= htmlspecialchars((string)$empresa ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
    <div style="margin-top: 8px;">
      <?php if ($isPotencial): ?>
        <span class="badge yellow">Potencial cliente</span>
      <?php else: ?>
        <span class="badge green">Cliente associado</span>
      <?php endif; ?>
      <?php if (!empty($item['cliente_associado_em'])): ?>
        <span class="badge">Associado em <?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)$item['cliente_associado_em']), ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
    </div>

    <table class="meta-table">
      <tr>
        <td class="col-4">
          <div class="meta-label">Nome</div>
          <div class="meta-value"><?= htmlspecialchars((string)($item['nome'] ?? ($item['contato'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></div>
        </td>
        <td class="col-4">
          <div class="meta-label">WhatsApp</div>
          <div class="meta-value"><?= htmlspecialchars((string)($item['whatsapp'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
        </td>
        <td class="col-4">
          <div class="meta-label">E-mail</div>
          <div class="meta-value"><?= htmlspecialchars((string)($item['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
        </td>
        <td class="col-4">
          <div class="meta-label">Funcionários</div>
          <div class="meta-value"><?= isset($item['numero_funcionarios']) ? (int)$item['numero_funcionarios'] : '—' ?></div>
        </td>
      </tr>
      <?php if (!empty($item['cliente_id']) && !empty($item['empresa_nome'])): ?>
      <tr>
        <td class="col-4">
          <div class="meta-label">Empresa informada no cadastro</div>
          <div class="meta-value"><?= htmlspecialchars((string)$item['empresa_nome'], ENT_QUOTES, 'UTF-8') ?></div>
        </td>
        <td class="col-4"></td>
        <td class="col-4"></td>
        <td class="col-4"></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="col-4">
          <div class="meta-label">Líderes</div>
          <div class="meta-value"><?= isset($item['numero_lideres']) ? (int)$item['numero_lideres'] : '—' ?></div>
        </td>
        <td class="col-4">
          <div class="meta-label">Faturamento anual</div>
          <div class="meta-value"><?= htmlspecialchars(\App\Core\FaturamentoFaixas::descricao($item['faturamento_faixa_id'] ?? null, $item['faturamento_medio_anual'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
        </td>
        <td class="col-4">
          <div class="meta-label">Tomador de decisão</div>
          <div class="meta-value"><?= isset($item['tomador_decisao']) ? ((int)$item['tomador_decisao'] === 1 ? 'Sim' : 'Não') : '—' ?></div>
        </td>
        <td class="col-4 col-last"></td>
      </tr>
    </table>
  </div>

  <div class="row clearfix" style="margin-bottom: 10px;">
    <?php
      $max = 7;
      $pct = static fn(int $n): int => (int)round(($n / $max) * 100);
      $i = 0;
      foreach ($labels as $name => $score):
        $i++;
    ?>
      <div class="col col-4<?= $i === 4 ? ' col-last' : '' ?>">
        <div class="pillar">
          <div class="pillar-title"><?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="bar"><span style="width: <?= $pct((int)$score) ?>%"></span></div>
          <div class="score"><?= (int)$score ?>/7</div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div style="font-weight: bold; margin-bottom: 8px;">Nota total dos 4 pilares</div>
    <div class="row clearfix">
      <div class="col col-3">
        <div class="summary-box">
          <div class="meta-label">Mundo ideal (100%)</div>
          <div class="summary-number">28</div>
        </div>
      </div>
      <div class="col col-3">
        <div class="summary-box">
          <div class="meta-label">Resultado (total)</div>
          <div class="summary-number"><?= (int)$total ?></div>
        </div>
      </div>
      <div class="col col-3 col-last">
        <div class="summary-box">
          <div class="meta-label">Realidade do cliente (%)</div>
          <div class="summary-number"><?= (int)$realPct ?>%</div>
        </div>
      </div>
    </div>
    <div class="bar" style="height: 12px; margin-top: 10px;">
      <span style="height: 12px; width: <?= (int)$realPct ?>%; background: #6b3b2a;"></span>
    </div>
  </div>

  <div class="card">
    <div style="font-weight: bold; margin-bottom: 10px;">Itens pontuados e não pontuados</div>
    <div class="row clearfix">
      <?php
        $keys = ['eu', 'lideranca', 'processo', 'gestao'];
        $col = 0;
        foreach ($keys as $key):
          $col++;
          $selected = array_map('intval', (array)($resp[$key] ?? []));
      ?>
        <div class="col col-4<?= $col === 4 ? ' col-last' : '' ?>">
          <div class="list-title"><?= htmlspecialchars((string)($pillarTitles[$key] ?? strtoupper($key)), ENT_QUOTES, 'UTF-8') ?></div>
          <ul class="list">
            <?php foreach (($qs[$key] ?? []) as $idx => $q): ?>
              <?php $on = in_array($idx + 1, $selected, true); ?>
              <li class="<?= $on ? 'ok' : 'nok' ?>"><?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="report-footer">
    Página <span class="page-number"></span> de <span class="page-count"></span> · Gerado em <?= htmlspecialchars((string)($branding['generated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
  </div>
</body>
</html>
