<?php
$t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace);
$formatValue = static function ($value, array $item): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $item['unidade_simbolo'] ?? '',
        'tipo' => $item['unidade_tipo'] ?? '',
    ]);
};
?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.index')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.help.limites')) ?></p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
      <a class="px-4 py-3 rounded-lg bg-brand-red text-white text-center" href="index.php?route=indicadores/create<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>">
        <?= htmlspecialchars($t('indicadores.action.new')) ?>
      </a>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center" href="javascript:history.back()">
        <?= htmlspecialchars($t('indicadores.action.back')) ?>
      </a>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
      <input type="hidden" name="route" value="indicadores/index" />
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <select name="cliente" class="border border-gray-300 rounded-lg p-3 w-full">
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome_empresa']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-3 flex flex-col sm:flex-row gap-2 items-stretch sm:items-end">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full sm:w-auto" type="submit"><?= htmlspecialchars($t('indicadores.action.filter')) ?></button>
        <?php if ($cliente): ?>
          <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/realizado&cliente=<?= (int)$cliente ?>"><?= htmlspecialchars($t('indicadores.action.value')) ?></a>
          <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/painel&cliente=<?= (int)$cliente ?>&ano=<?= (int)date('Y') ?>"><?= htmlspecialchars($t('indicadores.action.dashboard')) ?></a>
          <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/charts&cliente=<?= (int)$cliente ?>"><?= htmlspecialchars($t('indicadores.action.charts')) ?></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

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
</div>
