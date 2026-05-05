<?php
$t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace);
$formatValue = static function ($value) use ($item): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $item['unidade_simbolo'] ?? '',
        'tipo' => $item['unidade_tipo'] ?? '',
    ]);
};
?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.history')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($item['indicador']) ?></p>
    </div>
    <div class="flex gap-2">
      <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/charts&cliente=<?= (int)$item['cliente_id'] ?>">
        <?= htmlspecialchars($t('indicadores.action.charts')) ?>
      </a>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center" href="index.php?route=indicadores/index&cliente=<?= (int)$item['cliente_id'] ?>">
        <?= htmlspecialchars($t('indicadores.action.back')) ?>
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-sm text-gray-500">Ocorrências</div>
      <div class="text-2xl font-bold"><?= (int)$summary['total'] ?></div>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-sm text-gray-500">Lançadas</div>
      <div class="text-2xl font-bold"><?= (int)$summary['lancados'] ?></div>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-sm text-gray-500"><?= htmlspecialchars($t('indicadores.label.cumprimento')) ?> médio</div>
      <div class="text-2xl font-bold"><?= htmlspecialchars(\App\Core\ValueFormatter::percent($summary['media_cumprimento'])) ?></div>
    </div>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-sm text-gray-500"><?= htmlspecialchars($t('indicadores.label.tendencia')) ?></div>
      <?php
        $trendKey = $summary['trend'] === 'alta'
          ? 'indicadores.meta.trend.up'
          : ($summary['trend'] === 'queda' ? 'indicadores.meta.trend.down' : 'indicadores.meta.trend.stable');
      ?>
      <div class="text-2xl font-bold"><?= htmlspecialchars($t($trendKey)) ?></div>
    </div>
  </div>

  <?php if (empty($history)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.history')) ?></div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-left">
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.periodo_apuracao')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.meta')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.valor_atingido')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.cumprimento')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.status_controle')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.action.open_event')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $row): ?>
              <tr class="border-t">
                <td class="p-3">
                  <div><?= htmlspecialchars((string)$row['data_evento']) ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars((string)$row['periodo_inicio']) ?> até <?= htmlspecialchars((string)$row['periodo_fim']) ?></div>
                </td>
                <td class="p-3"><?= htmlspecialchars($formatValue($row['valor_meta'])) ?></td>
                <td class="p-3"><?= $row['valor_atingido'] !== null ? htmlspecialchars($formatValue($row['valor_atingido'])) : '—' ?></td>
                <td class="p-3"><?= $row['percentual_cumprimento'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::percent($row['percentual_cumprimento'])) : '—' ?></td>
                <td class="p-3"><span class="inline-flex px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($row['meta_status_class']) ?>"><?= htmlspecialchars($row['meta_status_label']) ?></span></td>
                <td class="p-3"><a class="text-brand-pink font-semibold" href="index.php?route=indicadores/evento&id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($t('indicadores.action.open_event')) ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
