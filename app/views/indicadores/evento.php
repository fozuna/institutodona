<?php
$t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace);
$formatValue = static function ($value) use ($evento): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $evento['unidade_simbolo'] ?? '',
        'tipo' => $evento['unidade_tipo'] ?? '',
    ]);
};
?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.event')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.help.meta_eventos')) ?></p>
    </div>
    <div class="flex gap-2">
      <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/historico&id=<?= (int)$evento['indicador_id'] ?>">
        <?= htmlspecialchars($t('indicadores.action.history')) ?>
      </a>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center" href="index.php?route=indicadores/realizado&cliente=<?= (int)$evento['cliente_id'] ?>">
        <?= htmlspecialchars($t('indicadores.action.back')) ?>
      </a>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-5 space-y-5">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold"><?= htmlspecialchars($evento['indicador']) ?></h2>
        <p class="text-sm text-gray-600"><?= htmlspecialchars(($evento['cliente_nome'] ?? '') . ' · ' . ($evento['departamento_nome'] ?? '') . ' · ' . ($evento['setor_nome'] ?? '')) ?></p>
      </div>
      <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($evento['meta_status_class']) ?>">
        <?= htmlspecialchars($evento['meta_status_label']) ?>
      </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="rounded-lg bg-gray-50 p-4">
        <div class="text-gray-500 text-sm"><?= htmlspecialchars($t('indicadores.label.periodo_apuracao')) ?></div>
        <div class="font-semibold"><?= htmlspecialchars((string)$evento['data_evento']) ?></div>
        <div class="text-xs text-gray-500"><?= htmlspecialchars((string)$evento['periodo_inicio']) ?> até <?= htmlspecialchars((string)$evento['periodo_fim']) ?></div>
      </div>
      <div class="rounded-lg bg-gray-50 p-4">
        <div class="text-gray-500 text-sm"><?= htmlspecialchars($t('indicadores.label.meta')) ?></div>
        <div class="font-semibold"><?= htmlspecialchars($formatValue($evento['valor_meta'])) ?></div>
      </div>
      <div class="rounded-lg bg-gray-50 p-4">
        <div class="text-gray-500 text-sm"><?= htmlspecialchars($t('indicadores.label.valor_atingido')) ?></div>
        <div class="font-semibold"><?= $evento['valor_atingido'] !== null ? htmlspecialchars($formatValue($evento['valor_atingido'])) : '—' ?></div>
      </div>
      <div class="rounded-lg bg-gray-50 p-4">
        <div class="text-gray-500 text-sm"><?= htmlspecialchars($t('indicadores.label.cumprimento')) ?></div>
        <div class="font-semibold"><?= $evento['percentual_cumprimento'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::percent($evento['percentual_cumprimento'])) : '—' ?></div>
      </div>
    </div>

    <form method="post" action="index.php?route=indicadores/updateRealizado" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="evento_id" value="<?= (int)$evento['id'] ?>" />
      <input type="hidden" name="cliente" value="<?= (int)$evento['cliente_id'] ?>" />
      <input type="hidden" name="redirect_evento" value="1" />
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.valor_atingido')) ?></label>
        <input type="text" inputmode="decimal" name="valor" data-indicador-decimal class="border border-gray-300 rounded-lg p-3 w-full" value="<?= $evento['valor_atingido'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::decimal($evento['valor_atingido'], 2)) : '' ?>" required />
      </div>
      <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.observacao')) ?></label>
        <input type="text" name="observacao" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= htmlspecialchars((string)($evento['observacao'] ?? '')) ?>" placeholder="<?= htmlspecialchars($t('indicadores.placeholder.observacao')) ?>" />
      </div>
      <div class="lg:col-span-3 flex justify-end">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white" type="submit"><?= htmlspecialchars($t('indicadores.action.save')) ?></button>
      </div>
    </form>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white shadow rounded-xl p-4">
      <div class="text-sm text-gray-500">Ocorrências</div>
      <div class="text-2xl font-bold"><?= (int)$summary['total'] ?></div>
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
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $row): ?>
            <tr class="border-t <?= (int)$row['id'] === (int)$evento['id'] ? 'bg-brand-red/5' : '' ?>">
              <td class="p-3"><?= htmlspecialchars((string)$row['data_evento']) ?></td>
              <td class="p-3"><?= htmlspecialchars($formatValue($row['valor_meta'])) ?></td>
              <td class="p-3"><?= $row['valor_atingido'] !== null ? htmlspecialchars($formatValue($row['valor_atingido'])) : '—' ?></td>
              <td class="p-3"><?= $row['percentual_cumprimento'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::percent($row['percentual_cumprimento'])) : '—' ?></td>
              <td class="p-3"><span class="inline-flex px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($row['meta_status_class']) ?>"><?= htmlspecialchars($row['meta_status_label']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
  (function () {
    function normalizeDecimal(value) {
      let raw = String(value || '').trim();
      if (!raw) return '';
      if (raw.includes(',') && raw.includes('.')) raw = raw.replace(/\./g, '');
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
