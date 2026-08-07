<?php
$t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace);
$formatValue = static function ($value, array $item): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $item['unidade_simbolo'] ?? '',
        'tipo' => $item['unidade_tipo'] ?? '',
    ]);
};
$indicadorId = (int)($indicadorId ?? 0);
$periodoInicio = (string)($periodoInicio ?? '');
$periodoFim = (string)($periodoFim ?? '');
$indicadores = is_array($indicadores ?? null) ? $indicadores : [];
?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.dashboard')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.help.limites')) ?></p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
      <?php
        $canPdf = (bool)$cliente;
        $pdfHref = 'index.php?route=indicadores/painelPdf'
          . '&cliente=' . (int)$cliente
          . '&ano=' . (int)$ano
          . '&indicador_id=' . (int)$indicadorId
          . '&periodo_inicio=' . urlencode($periodoInicio)
          . '&periodo_fim=' . urlencode($periodoFim);
      ?>
      <?php if ($canPdf): ?>
        <a class="px-4 py-3 rounded-lg bg-brand-red text-white text-center" href="<?= htmlspecialchars($pdfHref) ?>">
          Exportar PDF
        </a>
      <?php else: ?>
        <span class="px-4 py-3 rounded-lg bg-gray-200 text-gray-400 text-center select-none" aria-disabled="true">
          Exportar PDF
        </span>
      <?php endif; ?>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center" href="index.php?route=indicadores/index<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>">
        <?= htmlspecialchars($t('indicadores.action.back')) ?>
      </a>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
      <input type="hidden" name="route" value="indicadores/painel" />
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <select name="cliente" class="border border-gray-300 rounded-lg p-3 w-full">
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.ano')) ?></label>
        <input type="number" name="ano" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= (int)$ano ?>" />
      </div>
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.indicador')) ?></label>
        <select name="indicador_id" class="border border-gray-300 rounded-lg p-3 w-full" <?= $cliente ? '' : 'disabled' ?>>
          <option value="0"><?= htmlspecialchars($t('indicadores.option.none')) ?></option>
          <?php foreach ($indicadores as $ind): ?>
            <option value="<?= (int)$ind['id'] ?>" <?= $indicadorId === (int)$ind['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($ind['indicador'] ?? $ind['nome'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.periodo_apuracao')) ?></label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Início</label>
            <input type="date" name="periodo_inicio" id="indicadoresPainelPeriodoInicio" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= htmlspecialchars($periodoInicio) ?>" <?= $cliente ? '' : 'disabled' ?> />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fim</label>
            <input type="date" name="periodo_fim" id="indicadoresPainelPeriodoFim" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= htmlspecialchars($periodoFim) ?>" <?= $cliente ? '' : 'disabled' ?> />
          </div>
        </div>
        <p class="mt-1 text-xs text-gray-500">Deixe em branco para ver todo o histórico.</p>
      </div>
      <div class="md:col-span-3 flex items-end">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full" type="submit"><?= htmlspecialchars($t('indicadores.action.filter')) ?></button>
      </div>
      <div class="md:col-span-3 flex items-end">
        <button type="button" id="indicadoresPainelClear" class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center w-full"><?= htmlspecialchars($t('indicadores.action.clear')) ?></button>
      </div>
      <div class="md:col-span-6">
        <div id="indicadoresPainelErro" class="hidden rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
      </div>
    </form>
  </div>

  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.client')) ?></div>
  <?php elseif (empty($items)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.dashboard')) ?></div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
      <div class="bg-white shadow rounded-xl p-4">
        <div class="text-sm text-gray-500">Total</div>
        <div class="text-2xl font-bold text-brand-black"><?= (int)$stats['total'] ?></div>
      </div>
      <div class="bg-white shadow rounded-xl p-4">
        <div class="text-sm text-gray-500"><?= htmlspecialchars($t('indicadores.meta.hit')) ?></div>
        <div class="text-2xl font-bold text-green-700"><?= (int)$stats['atingida'] ?></div>
      </div>
      <div class="bg-white shadow rounded-xl p-4">
        <div class="text-sm text-gray-500"><?= htmlspecialchars($t('indicadores.meta.partial')) ?></div>
        <div class="text-2xl font-bold text-amber-700"><?= (int)$stats['parcial'] ?></div>
      </div>
      <div class="bg-white shadow rounded-xl p-4">
        <div class="text-sm text-gray-500"><?= htmlspecialchars($t('indicadores.meta.missed')) ?></div>
        <div class="text-2xl font-bold text-red-700"><?= (int)$stats['nao_atingida'] ?></div>
      </div>
      <div class="bg-white shadow rounded-xl p-4">
        <div class="text-sm text-gray-500"><?= htmlspecialchars($t('indicadores.meta.pending')) ?></div>
        <div class="text-2xl font-bold text-gray-700"><?= (int)$stats['pendente'] ?></div>
      </div>
    </div>

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
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr class="border-t">
                <td class="p-3">
                  <div class="font-semibold"><?= htmlspecialchars($item['indicador']) ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars(($item['departamento_nome'] ?? '') . ' · ' . ($item['setor_nome'] ?? '')) ?></div>
                </td>
                <td class="p-3">
                  <div><?= htmlspecialchars((string)$item['data_evento']) ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars((string)$item['periodo_inicio']) ?> até <?= htmlspecialchars((string)$item['periodo_fim']) ?></div>
                </td>
                <td class="p-3"><?= htmlspecialchars($formatValue($item['valor_meta'], $item)) ?></td>
                <td class="p-3"><?= $item['valor_atingido'] !== null ? htmlspecialchars($formatValue($item['valor_atingido'], $item)) : '—' ?></td>
                <td class="p-3"><?= $item['percentual_cumprimento'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::percent($item['percentual_cumprimento'])) : '—' ?></td>
                <td class="p-3">
                  <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($item['meta_status_class']) ?>">
                    <?= htmlspecialchars($item['meta_status_label']) ?>
                  </span>
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
    const form = document.querySelector('form[action="index.php"][method="get"]');
    const ini = document.getElementById('indicadoresPainelPeriodoInicio');
    const fim = document.getElementById('indicadoresPainelPeriodoFim');
    const msg = document.getElementById('indicadoresPainelErro');
    const clearBtn = document.getElementById('indicadoresPainelClear');
    const indicadorSelect = form?.querySelector('select[name="indicador_id"]');

    function setMsg(text) {
      if (!msg) return;
      if (!text) {
        msg.classList.add('hidden');
        msg.textContent = '';
        return;
      }
      msg.classList.remove('hidden');
      msg.textContent = String(text);
    }

    function validatePeriodo() {
      const vIni = String(ini?.value || '').trim();
      const vFim = String(fim?.value || '').trim();
      if (!vIni && !vFim) {
        setMsg('');
        return true;
      }
      if (!vIni || !vFim) {
        setMsg('Informe as duas datas do período, ou deixe ambas em branco para ver todo o histórico.');
        return false;
      }
      if (vFim < vIni) {
        setMsg('A data final do período não pode ser anterior à data inicial.');
        return false;
      }
      setMsg('');
      return true;
    }

    if (form) {
      form.addEventListener('submit', (e) => {
        if (!validatePeriodo()) {
          e.preventDefault();
        }
      });
    }
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        if (indicadorSelect) indicadorSelect.value = '0';
        if (!validatePeriodo()) return;
        form?.submit();
      });
    }
  })();
</script>
