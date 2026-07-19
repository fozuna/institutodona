<?php
/** @var array $data */
/** @var array $filters */
/** @var array $clientes */

$monthStart = (string)($filters['month_start'] ?? '');
$monthEnd = (string)($filters['month_end'] ?? '');
$selectedClienteIds = is_array($filters['cliente_ids'] ?? null) ? array_map('intval', $filters['cliente_ids']) : [];

$pdfQuery = http_build_query(array_filter([
    'route' => 'dashboard/resumo_mes_pdf',
    'month_start' => $monthStart !== '' ? $monthStart : null,
    'month_end' => $monthEnd !== '' ? $monthEnd : null,
], static fn($v): bool => $v !== null && $v !== ''));
foreach ($selectedClienteIds as $cid) {
    $pdfQuery .= '&clientes[]=' . (int)$cid;
}

$categorias = [
    'cronograma' => ['label' => 'Atividades do cronograma realizadas', 'colunas' => ['Tópico', 'Atividade', 'Data', 'Responsável', 'Empresa']],
    'treinamentos' => ['label' => 'Treinamentos aplicados', 'colunas' => ['Treinamento', 'Data', 'Empresa']],
    'biblioteca' => ['label' => 'Documentos incluídos na biblioteca', 'colunas' => ['Documento', 'Departamento', 'Incluído em', 'Empresa']],
    'auditorias' => ['label' => 'Auditorias realizadas', 'colunas' => ['Auditoria', 'Setor', 'Finalizada em', 'Conformidade', 'Empresa']],
    'indicadores' => ['label' => 'Indicadores lançados', 'colunas' => ['Indicador', 'Lançado em', 'Valor', 'Empresa']],
    'planoacao' => ['label' => 'Planos de ação concluídos', 'colunas' => ['Título', 'Concluído em', 'Empresa']],
    'tarefas' => ['label' => 'Tarefas concluídas', 'colunas' => ['Título', 'Finalizada em', 'Empresa']],
];
?>
<div class="p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Resumo do Mês</h1>
    <div class="flex items-center gap-2">
      <a href="index.php?<?= $pdfQuery ?>&preview=1" target="_blank" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Visualizar</a>
      <a href="index.php?<?= $pdfQuery ?>" class="px-4 py-2 rounded bg-brand-red text-white">Gerar PDF</a>
      <a href="index.php?route=dashboard/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Voltar</a>
    </div>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded p-4 mb-4 grid grid-cols-1 md:grid-cols-12 gap-3">
    <input type="hidden" name="route" value="dashboard/resumo_mes" />
    <div class="md:col-span-3">
      <label class="block text-sm">Empresa(s)</label>
      <select name="clientes[]" multiple size="1" class="border rounded p-2 w-full" style="height:42px;">
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'], $selectedClienteIds, true) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm">Mês inicial</label>
      <input type="month" name="month_start" value="<?= htmlspecialchars($monthStart) ?>" class="border rounded p-2 w-full" />
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm">Mês final</label>
      <input type="month" name="month_end" value="<?= htmlspecialchars($monthEnd) ?>" class="border rounded p-2 w-full" />
    </div>
    <div class="md:col-span-2 flex items-end gap-2">
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Filtrar</button>
      <a href="index.php?route=dashboard/resumo_mes" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Limpar</a>
    </div>
  </form>

  <?php if (($data['ok'] ?? false) !== true): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 rounded p-4 mb-4 text-sm"><?= htmlspecialchars((string)($data['message'] ?? 'Período inválido.')) ?></div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
      <?php foreach ($categorias as $key => $meta): ?>
        <div class="bg-white shadow rounded p-4">
          <div class="text-xs uppercase tracking-wide text-gray-500"><?= htmlspecialchars($meta['label']) ?></div>
          <div class="text-2xl font-bold text-brand-brown mt-1"><?= (int)($data[$key]['total'] ?? 0) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php foreach ($categorias as $key => $meta): ?>
      <?php $items = is_array($data[$key]['items'] ?? null) ? $data[$key]['items'] : []; $total = (int)($data[$key]['total'] ?? 0); ?>
      <div class="bg-white shadow rounded overflow-x-auto mb-4">
        <div class="px-4 py-3 border-b font-semibold"><?= htmlspecialchars($meta['label']) ?> (<?= $total ?>)</div>
        <?php if (empty($items)): ?>
          <div class="p-4 text-sm text-gray-600">Nenhum item no período selecionado.</div>
        <?php else: ?>
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b bg-gray-50">
                <?php foreach ($meta['colunas'] as $col): ?><th class="p-3"><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr class="border-b">
                  <?php if ($key === 'cronograma'): ?>
                    <td class="p-3"><?= htmlspecialchars((string)$item['topico']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['atividade']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['data']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)($item['responsavel'] ?? '')) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['cliente_nome']) ?></td>
                  <?php elseif ($key === 'treinamentos'): ?>
                    <td class="p-3"><?= htmlspecialchars((string)$item['treinamento_nome']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['data']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['cliente_nome']) ?></td>
                  <?php elseif ($key === 'biblioteca'): ?>
                    <td class="p-3"><?= htmlspecialchars((string)$item['nome']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)($item['departamento_nome'] ?? '')) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['created_at']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['cliente_nome']) ?></td>
                  <?php elseif ($key === 'auditorias'): ?>
                    <td class="p-3"><?= htmlspecialchars((string)$item['nome_auditoria']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)($item['setor_nome'] ?? '')) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['realizada_at']) ?></td>
                    <td class="p-3"><?= $item['conformidade_pct'] !== null ? htmlspecialchars(number_format((float)$item['conformidade_pct'], 2, ',', '.') . '%') : '—' ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['cliente_nome']) ?></td>
                  <?php elseif ($key === 'indicadores'): ?>
                    <td class="p-3"><?= htmlspecialchars((string)$item['indicador_nome']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['lancado_em']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['valor_atingido']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['cliente_nome']) ?></td>
                  <?php elseif ($key === 'planoacao'): ?>
                    <td class="p-3"><?= htmlspecialchars((string)$item['titulo']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['concluido_em']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['cliente_nome']) ?></td>
                  <?php elseif ($key === 'tarefas'): ?>
                    <td class="p-3"><?= htmlspecialchars((string)$item['titulo']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['finalizado_em']) ?></td>
                    <td class="p-3"><?= htmlspecialchars((string)$item['cliente_nome']) ?></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php if ($total > count($items)): ?>
            <div class="px-4 py-2 text-xs text-gray-500 border-t">Mostrando <?= count($items) ?> de <?= $total ?> itens.</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
