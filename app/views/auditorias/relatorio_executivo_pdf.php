<?php
/** @var array $data */
/** @var string $resumoExecutivo */
/** @var array $filters */
/** @var array $branding */
use App\Core\DateHelper;

$e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$branding = is_array($branding ?? null) ? $branding : [];
$logoSrc = (string)($branding['logo_uri'] ?? '');
$logoPos = (string)($branding['logo_position'] ?? 'left');

$totalAuditorias = (int)($data['total_auditorias'] ?? 0);
$totalItens = (int)($data['total_itens'] ?? 0);
$totalConforme = (int)($data['total_conforme'] ?? 0);
$totalNaoConforme = (int)($data['total_nao_conforme'] ?? 0);
$totalNaoAplica = (int)($data['total_nao_aplica'] ?? 0);
$pctMedia = $data['conformidade_pct_media'] ?? null;
$porArea = is_array($data['por_area'] ?? null) ? $data['por_area'] : [];
$naoConformidades = is_array($data['nao_conformidades'] ?? null) ? $data['nao_conformidades'] : [];
$observacoesAdicionais = is_array($data['observacoes_adicionais'] ?? null) ? $data['observacoes_adicionais'] : [];

$naoConformidadesPorArea = [];
foreach ($naoConformidades as $item) {
    $naoConformidadesPorArea[(string)$item['setor_nome']][] = $item;
}

$periodoInicio = !empty($filters['inicio']) ? DateHelper::formatDate((string)$filters['inicio']) : '';
$periodoFim = !empty($filters['fim']) ? DateHelper::formatDate((string)$filters['fim']) : '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Relatório Executivo de Fechamento de Auditorias</title>
  <style>
    @page {
      size: A4 portrait;
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
    .title { font-size: 17px; font-weight: 700; margin: 0 0 2px; }
    .subtitle { font-size: 11px; margin: 0 0 6px; }
    .meta-line { font-size: 10px; margin: 0; }
    .grid { width: 100%; }
    .grid td { vertical-align: top; }
    .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 10px; background: #fff; }
    .card-label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
    .card-value { font-size: 17px; font-weight: 700; }
    .value-green { color: #166534; }
    .value-red { color: #991b1b; }
    .value-gray { color: #374151; }
    .section { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-top: 12px; page-break-inside: avoid; }
    .section-title { font-size: 13px; font-weight: 700; margin: 0 0 8px; color: #7f1d1d; }
    .resumo-executivo { font-size: 10.5px; line-height: 1.5; margin: 0; text-align: justify; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead { display: table-header-group; }
    table.data th, table.data td { border: 1px solid #e5e7eb; padding: 7px 8px; vertical-align: top; }
    table.data th { background: #fee2e2; color: #7f1d1d; font-size: 9.8px; text-transform: uppercase; letter-spacing: 0.02em; }
    table.data tr { page-break-inside: avoid; }
    .nowrap { white-space: nowrap; }
    .small { font-size: 9.5px; }
    .area-block { margin-top: 10px; page-break-inside: avoid; }
    .area-title { font-size: 11.5px; font-weight: 700; color: #111827; margin: 0 0 4px; }
    .questao-item { margin: 0 0 6px 14px; }
    .questao-numero { font-weight: 700; }
    .questao-texto { margin: 2px 0 0 14px; }
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
      <div class="title"><?= $e((string)($branding['header_title'] ?? 'Relatório Executivo de Fechamento de Auditorias')) ?></div>
      <?php if (!empty($branding['header_subtitle'])): ?>
        <div class="subtitle muted"><?= $e((string)$branding['header_subtitle']) ?></div>
      <?php endif; ?>
      <p class="meta-line muted">
        <?php if ($periodoInicio !== '' || $periodoFim !== ''): ?>
          Período: <strong><?= $periodoInicio !== '' ? $e($periodoInicio) : '—' ?></strong> até <strong><?= $periodoFim !== '' ? $e($periodoFim) : '—' ?></strong>
        <?php else: ?>
          Período: <strong>todas as auditorias realizadas</strong>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Resumo executivo</div>
    <p class="resumo-executivo"><?= $e($resumoExecutivo) ?></p>
  </div>

  <table class="grid" cellspacing="0" cellpadding="0">
    <tr>
      <td style="width:25%; padding-right:8px; padding-top:8px;">
        <div class="card">
          <div class="card-label">Auditorias realizadas</div>
          <div class="card-value"><?= $totalAuditorias ?></div>
        </div>
      </td>
      <td style="width:25%; padding-right:8px; padding-top:8px;">
        <div class="card">
          <div class="card-label">Itens conformes</div>
          <div class="card-value value-green"><?= $totalConforme ?></div>
        </div>
      </td>
      <td style="width:25%; padding-right:8px; padding-top:8px;">
        <div class="card">
          <div class="card-label">Itens não conformes</div>
          <div class="card-value value-red"><?= $totalNaoConforme ?></div>
        </div>
      </td>
      <td style="width:25%; padding-top:8px;">
        <div class="card">
          <div class="card-label">Conformidade média</div>
          <div class="card-value value-gray"><?= $pctMedia !== null ? $e(number_format((float)$pctMedia, 2, ',', '.') . '%') : '—' ?></div>
        </div>
      </td>
    </tr>
  </table>

  <div class="section">
    <div class="section-title">Consolidado por área</div>
    <?php if (empty($porArea)): ?>
      <div class="muted">Nenhuma área com auditorias no período informado.</div>
    <?php else: ?>
      <table class="data">
        <thead>
          <tr>
            <th style="width:26%;">Área</th>
            <th style="width:22%;">Departamento</th>
            <th style="width:13%;">Auditorias</th>
            <th style="width:13%;">Conforme</th>
            <th style="width:13%;">Não conforme</th>
            <th style="width:13%;">Conformidade</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($porArea as $area): ?>
            <tr>
              <td><?= $e((string)$area['setor_nome']) ?></td>
              <td><?= $e((string)$area['departamento_nome']) ?></td>
              <td class="nowrap"><?= (int)$area['total_auditorias'] ?></td>
              <td class="nowrap"><?= (int)$area['conforme'] ?></td>
              <td class="nowrap"><?= (int)$area['nao_conforme'] ?></td>
              <td class="nowrap"><?= $e(number_format((float)$area['conformidade_pct'], 2, ',', '.') . '%') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="section">
    <div class="section-title">Itens não conforme</div>
    <?php if (empty($naoConformidadesPorArea)): ?>
      <div class="muted">Nenhuma não conformidade identificada no período informado.</div>
    <?php else: ?>
      <?php foreach ($naoConformidadesPorArea as $areaNome => $itens): ?>
        <div class="area-block">
          <div class="area-title"><?= $e((string)$areaNome) ?>:</div>
          <?php foreach ($itens as $item): ?>
            <div class="questao-item">
              <div><span class="questao-numero">Questão <?= (int)$item['questao_numero'] ?></span></div>
              <div class="questao-texto small"><?= $e((string)$item['pergunta']) ?><?= $item['observacao'] !== '' ? ' — ' . $e((string)$item['observacao']) : '' ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!empty($observacoesAdicionais)): ?>
    <div class="section">
      <div class="section-title">Observações adicionais registradas</div>
      <table class="data">
        <thead>
          <tr>
            <th style="width:20%;">Área</th>
            <th style="width:24%;">Auditoria</th>
            <th style="width:12%;">Questão</th>
            <th style="width:12%;">Status</th>
            <th style="width:32%;">Observação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($observacoesAdicionais as $obs): ?>
            <tr>
              <td><?= $e((string)$obs['setor_nome']) ?></td>
              <td><?= $e((string)$obs['auditoria_nome']) ?></td>
              <td class="nowrap">Questão <?= (int)$obs['questao_numero'] ?></td>
              <td class="nowrap"><?= $e((string)$obs['conformidade']) ?></td>
              <td><?= $e((string)$obs['observacao']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="footer">
    Página <span class="page-number"></span> de <span class="page-count"></span> · Gerado em <?= $e((string)($branding['generated_at'] ?? '')) ?>
  </div>
</body>
</html>
