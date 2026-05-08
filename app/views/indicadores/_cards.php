<?php /** @var array $items */ ?>
<?php $t = $t ?? (static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace)); ?>
<?php
$formatValue = $formatValue ?? static function ($value, array $item): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $item['unidade_simbolo'] ?? '',
        'tipo' => $item['unidade_tipo'] ?? '',
    ]);
};
?>
<?php if (empty($items)): ?>
  <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.index')) ?></div>
<?php else: ?>
  <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <?php foreach ($items as $item): ?>
      <div class="bg-white shadow rounded-xl p-5 border border-gray-100">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-brand-black"><?= htmlspecialchars($item['indicador']) ?></h2>
            <p class="text-sm text-gray-600"><?= htmlspecialchars(($item['cliente_nome'] ?? '') . ' · ' . ($item['departamento_nome'] ?? '') . ' · ' . ($item['setor_nome'] ?? '')) ?></p>
          </div>
          <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($item['control_status_class']) ?>">
            <?= htmlspecialchars($item['control_status_label']) ?>
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 text-sm">
          <div class="rounded-lg bg-gray-50 p-3">
            <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.valor')) ?></div>
            <div class="font-semibold text-base"><?= htmlspecialchars($formatValue($item['valor'], $item)) ?></div>
          </div>
          <div class="rounded-lg bg-gray-50 p-3">
            <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.periodicidade')) ?></div>
            <div class="font-semibold text-base"><?= htmlspecialchars(\App\Core\I18n::t('indicadores.periodicidade.' . ($item['periodicidade_tipo'] ?? ''), [], null)) ?></div>
          </div>
          <div class="rounded-lg bg-gray-50 p-3">
            <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.periodo')) ?></div>
            <div class="font-semibold text-base"><?= htmlspecialchars(($item['data_inicial'] ?? '') . ' até ' . ($item['data_final'] ?? '')) ?></div>
          </div>
          <div class="rounded-lg bg-gray-50 p-3">
            <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.unidade_medida')) ?></div>
            <div class="font-semibold text-base"><?= htmlspecialchars(($item['unidade_nome'] ?? '') . ' (' . ($item['unidade_tipo'] ?? '') . ')') ?></div>
          </div>
        </div>

        <div class="mt-4 text-sm">
          <div class="text-gray-500 mb-1"><?= htmlspecialchars($t('indicadores.label.responsaveis_vinculados')) ?></div>
          <div class="flex flex-wrap gap-2">
            <?php if (!empty($item['responsaveis'])): ?>
              <?php foreach ($item['responsaveis'] as $responsavel): ?>
                <span class="px-3 py-1 rounded-full bg-brand-red/10 text-brand-red text-xs"><?= htmlspecialchars($responsavel) ?></span>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="text-gray-400"><?= htmlspecialchars($t('indicadores.option.none')) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-5 pt-4 border-t border-gray-100">
          <div class="text-sm text-gray-600">
            <?= htmlspecialchars($t('indicadores.label.valor_minimo')) ?>: <?= $item['valor_minimo'] !== null ? htmlspecialchars($formatValue($item['valor_minimo'], $item)) : '—' ?>
            ·
            <?= htmlspecialchars($t('indicadores.label.valor_maximo')) ?>: <?= $item['valor_maximo'] !== null ? htmlspecialchars($formatValue($item['valor_maximo'], $item)) : '—' ?>
          </div>
          <div class="flex items-center gap-3">
            <a class="text-brand-brown icon-action" href="index.php?route=indicadores/historico&id=<?= (int)$item['id'] ?>" title="<?= htmlspecialchars($t('indicadores.action.history')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.history')) ?>">
              <span data-feather="bar-chart-2"></span>
            </a>
            <a class="text-brand-pink icon-action" href="index.php?route=indicadores/edit&id=<?= (int)$item['id'] ?>" title="<?= htmlspecialchars($t('indicadores.action.edit')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.edit')) ?>">
              <span data-feather="edit"></span>
            </a>
            <form method="post" action="index.php?route=indicadores/delete" onsubmit="return confirm('<?= htmlspecialchars($t('indicadores.action.delete')) ?>?')">
              <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
              <input type="hidden" name="cliente" value="<?= (int)$item['cliente_id'] ?>" />
              <button class="text-brand-brown icon-action" type="submit" title="<?= htmlspecialchars($t('indicadores.action.delete')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.delete')) ?>">
                <span data-feather="trash-2"></span>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

