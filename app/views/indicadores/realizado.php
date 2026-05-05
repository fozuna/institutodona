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
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.value')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.help.limites')) ?></p>
    </div>
    <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center" href="index.php?route=indicadores/index<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>">
      <?= htmlspecialchars($t('indicadores.action.back')) ?>
    </a>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
      <input type="hidden" name="route" value="indicadores/realizado" />
      <div class="md:col-span-4">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <select name="cliente" class="border border-gray-300 rounded-lg p-3 w-full">
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2 flex items-end">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full" type="submit"><?= htmlspecialchars($t('indicadores.action.filter')) ?></button>
      </div>
    </form>
  </div>

  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.client')) ?></div>
  <?php elseif (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.value')) ?></div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-left">
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.indicador')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.periodo_apuracao')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.meta')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.valor_atingido')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.cumprimento')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.label.status_controle')) ?></th>
              <th class="p-3"><?= htmlspecialchars($t('indicadores.action.open_event')) ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr class="border-t">
                <td class="p-3 align-top">
                  <div class="font-semibold"><?= htmlspecialchars($item['indicador']) ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars(($item['departamento_nome'] ?? '') . ' · ' . ($item['setor_nome'] ?? '')) ?></div>
                </td>
                <td class="p-3 align-top">
                  <div><?= htmlspecialchars((string)$item['data_evento']) ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars((string)$item['periodo_inicio']) ?> até <?= htmlspecialchars((string)$item['periodo_fim']) ?></div>
                </td>
                <td class="p-3 align-top"><?= htmlspecialchars($formatValue($item['valor_meta'], $item)) ?></td>
                <td class="p-3 align-top">
                  <form method="post" action="index.php?route=indicadores/updateRealizado" class="flex flex-col gap-2 max-w-xs">
                    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                    <input type="hidden" name="evento_id" value="<?= (int)$item['id'] ?>" />
                    <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
                    <input type="text" inputmode="decimal" name="valor" data-indicador-decimal class="border border-gray-300 rounded-lg p-2 w-full" value="<?= $item['valor_atingido'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::decimal($item['valor_atingido'], 2)) : '' ?>" required />
                    <button class="px-4 py-2 rounded-lg bg-brand-red text-white" type="submit"><?= htmlspecialchars($t('indicadores.action.save')) ?></button>
                  </form>
                </td>
                <td class="p-3 align-top"><?= $item['percentual_cumprimento'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::percent($item['percentual_cumprimento'])) : '—' ?></td>
                <td class="p-3 align-top">
                  <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($item['meta_status_class']) ?>">
                    <?= htmlspecialchars($item['meta_status_label']) ?>
                  </span>
                </td>
                <td class="p-3 align-top">
                  <a class="text-brand-pink font-semibold" href="index.php?route=indicadores/evento&id=<?= (int)$item['id'] ?>">
                    <?= htmlspecialchars($t('indicadores.action.open_event')) ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
<script>
  (function () {
    function normalizeDecimal(value) {
      let raw = String(value || '').trim();
      if (!raw) return '';
      raw = raw.replace(/\s+/g, '');
      if (raw.includes(',') && raw.includes('.')) {
        raw = raw.replace(/\./g, '');
      }
      raw = raw.replace(',', '.');
      const numeric = Number(raw);
      if (!Number.isFinite(numeric)) return '';
      return numeric.toFixed(2).replace('.', ',');
    }

    document.querySelectorAll('[data-indicador-decimal]').forEach((input) => {
      input.addEventListener('blur', function () {
        this.value = normalizeDecimal(this.value);
      });
      input.form?.addEventListener('submit', function () {
        input.value = normalizeDecimal(input.value);
      });
    });
  })();
</script>
