<?php
$formatPercent = static function (int $value, int $total): int {
    return $total > 0 ? (int)round(($value / $total) * 100) : 0;
};
$renderItemList = static function (array $questions, array $selectedIds): string {
    $html = '';
    foreach ($questions as $index => $question) {
        $checked = in_array($index + 1, $selectedIds, true);
        $html .= '<div class="line-item ' . ($checked ? 'checked' : 'unchecked') . '">';
        $html .= '<span class="mark">' . ($checked ? '&#10003;' : '&#10007;') . '</span>';
        $html .= '<span class="label">' . htmlspecialchars((string)$question, ENT_QUOTES, 'UTF-8') . '</span>';
        $html .= '</div>';
    }
    return $html;
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Avaliação <?= htmlspecialchars((string)$meta['empresa'], ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    @page { size: A4; margin: 12mm; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: DejaVu Sans, sans-serif;
      color: #172033;
      font-size: 11px;
      background: #ffffff;
    }
    .page { width: 100%; }
    .card {
      border: 1px solid <?= $brand['grayBorder'] ?>;
      border-radius: 8px;
      padding: 14px;
      margin-bottom: 12px;
      background: #fff;
    }
    .header {
      background: <?= $brand['navy'] ?>;
      color: #fff;
      border-radius: 10px;
      padding: 18px;
      margin-bottom: 14px;
      overflow: hidden;
    }
    .logo { height: 36px; margin-bottom: 14px; }
    .eyebrow {
      font-size: 10px;
      letter-spacing: 2px;
      text-transform: uppercase;
      margin-bottom: 10px;
      opacity: .92;
    }
    h1 {
      margin: 0 0 8px 0;
      font-size: 28px;
      line-height: 1.15;
    }
    .subtitle {
      font-size: 12px;
      line-height: 1.6;
      width: 70%;
    }
    .steps { margin-top: 14px; }
    .step {
      border: 1px solid rgba(255,255,255,.25);
      border-radius: 6px;
      padding: 8px 10px;
      margin-bottom: 8px;
      width: 48%;
      float: left;
      margin-right: 2%;
      color: #fff;
      font-size: 10px;
    }
    .clearfix::after { content: ""; display: block; clear: both; }
    .section-title {
      font-size: 22px;
      margin: 0 0 12px 0;
    }
    .muted { color: <?= $brand['grayText'] ?>; }
    .grid-4, .grid-3, .grid-2 { width: 100%; }
    .col-4 { float: left; width: 25%; padding-right: 10px; }
    .col-3 { float: left; width: 33.3333%; padding-right: 10px; }
    .col-2 { float: left; width: 50%; padding-right: 10px; }
    .meta-label { color: <?= $brand['grayText'] ?>; font-size: 10px; margin-bottom: 4px; }
    .meta-value { font-size: 15px; font-weight: bold; }
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 6px;
      background: <?= $brand['badgeBg'] ?>;
      color: <?= $brand['badgeText'] ?>;
      font-size: 10px;
      margin-top: 8px;
    }
    .pillar-card {
      float: left;
      width: 24%;
      margin-right: 1.333%;
      border: 1px solid <?= $brand['grayBorder'] ?>;
      border-radius: 8px;
      padding: 12px;
      min-height: 88px;
    }
    .pillar-card.last { margin-right: 0; }
    .pillar-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
    .bar {
      width: 100%;
      height: 10px;
      background: #e5e7eb;
      border-radius: 999px;
      overflow: hidden;
      margin-bottom: 8px;
    }
    .bar > span {
      display: block;
      height: 10px;
      background: linear-gradient(90deg, <?= $brand['orange'] ?>, <?= $brand['orangeSoft'] ?>);
      border-radius: 999px;
    }
    .score-line { text-align: right; font-size: 11px; color: <?= $brand['grayText'] ?>; }
    .summary-box {
      float: left;
      width: 32%;
      margin-right: 2%;
      border: 1px solid <?= $brand['grayBorder'] ?>;
      border-radius: 8px;
      padding: 10px;
      min-height: 64px;
    }
    .summary-box.last { margin-right: 0; }
    .summary-number {
      font-size: 24px;
      text-align: center;
      margin-top: 8px;
      font-weight: bold;
    }
    .big-bar { margin-top: 10px; height: 12px; }
    .list-column {
      float: left;
      width: 24%;
      margin-right: 1.333%;
    }
    .list-column.last { margin-right: 0; }
    .list-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
    .line-item { padding: 5px 0; border-bottom: 1px solid #eef1f5; }
    .line-item .mark { display: inline-block; width: 16px; font-weight: bold; }
    .line-item.checked .mark { color: #16a34a; }
    .line-item.unchecked .mark { color: #9ca3af; }
    .footer-note {
      font-size: 9px;
      color: <?= $brand['grayText'] ?>;
      margin-top: 10px;
      text-align: right;
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="header">
      <?php if ($logo !== ''): ?>
        <img src="<?= $logo ?>" alt="Logo" class="logo" />
      <?php endif; ?>
      <div class="eyebrow">Avaliacao empresarial</div>
      <h1>Diagnostico empresarial estruturado para sua empresa.</h1>
      <div class="subtitle">Documento gerado a partir da avaliacao concluida no sistema, com consolidacao visual dos 4 pilares, dados cadastrais e itens pontuados.</div>
      <div class="steps clearfix">
        <div class="step"><strong>Etapa 1</strong><br>Dados iniciais da empresa e do responsavel.</div>
        <div class="step"><strong>Etapa 2</strong><br>Questionario sobre a saude da gestao da empresa.</div>
      </div>
    </div>

    <div class="card">
      <div class="section-title">Avaliacao</div>
      <div class="muted">Empresa</div>
      <div class="meta-value" style="margin-bottom: 4px;"><?= htmlspecialchars((string)$meta['empresa'], ENT_QUOTES, 'UTF-8') ?></div>
      <div class="muted">Data: <?= htmlspecialchars((string)$meta['data'], ENT_QUOTES, 'UTF-8') ?></div>
      <div class="badge"><?= htmlspecialchars((string)$meta['origem'], ENT_QUOTES, 'UTF-8') ?></div>
      <div class="clearfix" style="margin-top: 14px;">
        <div class="col-4"><div class="meta-label">Nome</div><div class="meta-value"><?= htmlspecialchars((string)$meta['nome'], ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="col-4"><div class="meta-label">WhatsApp</div><div class="meta-value"><?= htmlspecialchars((string)$meta['whatsapp'], ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="col-4"><div class="meta-label">E-mail</div><div class="meta-value"><?= htmlspecialchars((string)$meta['email'], ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="col-4" style="padding-right:0"><div class="meta-label">Funcionarios</div><div class="meta-value"><?= htmlspecialchars((string)$meta['funcionarios'], ENT_QUOTES, 'UTF-8') ?></div></div>
      </div>
      <div class="clearfix" style="margin-top: 12px;">
        <div class="col-4"><div class="meta-label">Lideres</div><div class="meta-value"><?= htmlspecialchars((string)$meta['lideres'], ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="col-4"><div class="meta-label">Faturamento anual</div><div class="meta-value"><?= htmlspecialchars((string)$meta['faturamento'], ENT_QUOTES, 'UTF-8') ?></div></div>
        <div class="col-4"><div class="meta-label">Tomador de decisao</div><div class="meta-value"><?= htmlspecialchars((string)$meta['decisor'], ENT_QUOTES, 'UTF-8') ?></div></div>
      </div>
    </div>

    <div class="clearfix" style="margin-bottom: 12px;">
      <?php $pillarOrder = ['financeiro', 'mercado', 'pessoas', 'processo']; ?>
      <?php foreach ($pillarOrder as $index => $pillar): ?>
        <?php $questions = $pillars[$pillar] ?? []; $score = (int)($scores[$pillar] ?? 0); $total = count($questions); ?>
        <div class="pillar-card<?= $index === 3 ? ' last' : '' ?>">
          <div class="pillar-title"><?= ucfirst($pillar) ?></div>
          <div class="bar"><span style="width: <?= $formatPercent($score, $total) ?>%"></span></div>
          <div class="score-line"><?= $score ?>/<?= $total ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="section-title">Nota total dos 4 pilares</div>
      <div class="clearfix">
        <div class="summary-box">
          <div class="meta-label">Mundo ideal (100%)</div>
          <div class="summary-number"><?= $sumIdeal ?></div>
        </div>
        <div class="summary-box">
          <div class="meta-label">Resultado (total)</div>
          <div class="summary-number"><?= $sumResult ?></div>
        </div>
        <div class="summary-box last">
          <div class="meta-label">Realidade do cliente (%)</div>
          <div class="summary-number"><?= $sumPercent ?>%</div>
        </div>
      </div>
      <div class="bar big-bar"><span style="width: <?= $sumPercent ?>%; background: <?= $brand['navy'] ?>;"></span></div>
    </div>

    <div class="card">
      <div class="section-title">Itens pontuados e nao pontuados</div>
      <div class="clearfix">
        <?php foreach ($pillarOrder as $index => $pillar): ?>
          <div class="list-column<?= $index === 3 ? ' last' : '' ?>">
            <div class="list-title"><?= ucfirst($pillar) ?></div>
            <?= $renderItemList($pillars[$pillar] ?? [], array_map('intval', (array)($respostas[$pillar] ?? []))) ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="footer-note">Gerado automaticamente em formato A4 para visualizacao e impressao.</div>
    </div>
  </div>
</body>
</html>
