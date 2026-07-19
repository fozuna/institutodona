<?php
/** @var array $data */
/** @var array $filters */
/** @var array $branding */

$e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$branding = is_array($branding ?? null) ? $branding : [];
$logoSrc = (string)($branding['logo_uri'] ?? '');
$logoPos = (string)($branding['logo_position'] ?? 'left');

$categorias = [
    'cronograma' => ['label' => 'Atividades do cronograma realizadas', 'colunas' => ['Tópico', 'Atividade', 'Data', 'Responsável', 'Empresa']],
    'treinamentos' => ['label' => 'Treinamentos aplicados', 'colunas' => ['Treinamento', 'Data', 'Empresa']],
    'biblioteca' => ['label' => 'Documentos incluídos na biblioteca', 'colunas' => ['Documento', 'Departamento', 'Incluído em', 'Empresa']],
    'auditorias' => ['label' => 'Auditorias realizadas', 'colunas' => ['Auditoria', 'Setor', 'Finalizada em', 'Conformidade', 'Empresa']],
    'indicadores' => ['label' => 'Indicadores lançados', 'colunas' => ['Indicador', 'Lançado em', 'Valor', 'Empresa']],
    'planoacao' => ['label' => 'Planos de ação concluídos', 'colunas' => ['Título', 'Concluído em', 'Empresa']],
    'tarefas' => ['label' => 'Tarefas concluídas', 'colunas' => ['Título', 'Finalizada em', 'Empresa']],
];

$monthStart = (string)($filters['month_start'] ?? '');
$monthEnd = (string)($filters['month_end'] ?? '');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Resumo do Mês</title>
  <style>
    @page {
      size: A4 portrait;
      margin: <?= (int)($branding['margins']['top'] ?? 14) ?>mm <?= (int)($branding['margins']['right'] ?? 12) ?>mm <?= (int)($branding['margins']['bottom'] ?? 14) ?>mm <?= (int)($branding['margins']['left'] ?? 12) ?>mm;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 10px; }
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
    .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px 9px; background: #fff; }
    .card-label { font-size: 8.5px; color: #6b7280; margin-bottom: 2px; }
    .card-value { font-size: 15px; font-weight: 700; color: #7f1d1d; }
    .section { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; margin-top: 10px; page-break-inside: avoid; }
    .section-title { font-size: 12px; font-weight: 700; margin: 0 0 6px; color: #7f1d1d; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead { display: table-header-group; }
    table.data th, table.data td { border: 1px solid #e5e7eb; padding: 5px 6px; vertical-align: top; }
    table.data th { background: #fee2e2; color: #7f1d1d; font-size: 8.8px; text-transform: uppercase; letter-spacing: 0.02em; }
    table.data tr { page-break-inside: avoid; }
    .small { font-size: 8.5px; }
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
      <div class="title"><?= $e((string)($branding['header_title'] ?? 'Resumo do Mês')) ?></div>
      <?php if (!empty($branding['header_subtitle'])): ?>
        <div class="subtitle muted"><?= $e((string)$branding['header_subtitle']) ?></div>
      <?php endif; ?>
      <p class="meta-line muted">Período: <strong><?= $e($monthStart) ?></strong> até <strong><?= $e($monthEnd) ?></strong></p>
    </div>
  </div>

  <table class="grid" cellspacing="0" cellpadding="0">
    <?php foreach (array_chunk($categorias, 4, true) as $linha): ?>
      <tr>
        <?php foreach ($linha as $key => $meta): ?>
          <td style="width:25%; padding: 0 6px 6px 0;">
            <div class="card">
              <div class="card-label"><?= $e($meta['label']) ?></div>
              <div class="card-value"><?= (int)($data[$key]['total'] ?? 0) ?></div>
            </div>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php foreach ($categorias as $key => $meta): ?>
    <?php $items = is_array($data[$key]['items'] ?? null) ? $data[$key]['items'] : []; $total = (int)($data[$key]['total'] ?? 0); ?>
    <div class="section">
      <div class="section-title"><?= $e($meta['label']) ?> (<?= $total ?>)</div>
      <?php if (empty($items)): ?>
        <div class="muted">Nenhum item no período selecionado.</div>
      <?php else: ?>
        <table class="data">
          <thead>
            <tr><?php foreach ($meta['colunas'] as $col): ?><th><?= $e($col) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <?php if ($key === 'cronograma'): ?>
                  <td><?= $e((string)$item['topico']) ?></td>
                  <td><?= $e((string)$item['atividade']) ?></td>
                  <td class="small"><?= $e((string)$item['data']) ?></td>
                  <td><?= $e((string)($item['responsavel'] ?? '')) ?></td>
                  <td><?= $e((string)$item['cliente_nome']) ?></td>
                <?php elseif ($key === 'treinamentos'): ?>
                  <td><?= $e((string)$item['treinamento_nome']) ?></td>
                  <td class="small"><?= $e((string)$item['data']) ?></td>
                  <td><?= $e((string)$item['cliente_nome']) ?></td>
                <?php elseif ($key === 'biblioteca'): ?>
                  <td><?= $e((string)$item['nome']) ?></td>
                  <td><?= $e((string)($item['departamento_nome'] ?? '')) ?></td>
                  <td class="small"><?= $e((string)$item['created_at']) ?></td>
                  <td><?= $e((string)$item['cliente_nome']) ?></td>
                <?php elseif ($key === 'auditorias'): ?>
                  <td><?= $e((string)$item['nome_auditoria']) ?></td>
                  <td><?= $e((string)($item['setor_nome'] ?? '')) ?></td>
                  <td class="small"><?= $e((string)$item['realizada_at']) ?></td>
                  <td><?= $item['conformidade_pct'] !== null ? $e(number_format((float)$item['conformidade_pct'], 2, ',', '.') . '%') : '—' ?></td>
                  <td><?= $e((string)$item['cliente_nome']) ?></td>
                <?php elseif ($key === 'indicadores'): ?>
                  <td><?= $e((string)$item['indicador_nome']) ?></td>
                  <td class="small"><?= $e((string)$item['lancado_em']) ?></td>
                  <td><?= $e((string)$item['valor_atingido']) ?></td>
                  <td><?= $e((string)$item['cliente_nome']) ?></td>
                <?php elseif ($key === 'planoacao'): ?>
                  <td><?= $e((string)$item['titulo']) ?></td>
                  <td class="small"><?= $e((string)$item['concluido_em']) ?></td>
                  <td><?= $e((string)$item['cliente_nome']) ?></td>
                <?php elseif ($key === 'tarefas'): ?>
                  <td><?= $e((string)$item['titulo']) ?></td>
                  <td class="small"><?= $e((string)$item['finalizado_em']) ?></td>
                  <td><?= $e((string)$item['cliente_nome']) ?></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if ($total > count($items)): ?>
          <div class="small muted" style="margin-top:4px;">Mostrando <?= count($items) ?> de <?= $total ?> itens.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="footer">
    Página <span class="page-number"></span> de <span class="page-count"></span> · Gerado em <?= $e((string)($branding['generated_at'] ?? '')) ?>
  </div>
</body>
</html>
