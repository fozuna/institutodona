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
$renderOnlyTable = (bool)($renderOnlyTable ?? false);
$canEdit = \App\Core\Auth::isInstituto() || \App\Core\Auth::isClienteAdmin();

$renderTable = static function (array $items, int $cliente, int $indicadorId, string $periodoInicio, string $periodoFim, bool $canEdit, callable $t, callable $formatValue): void {
    if (empty($items)) {
        echo '<div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600">' . htmlspecialchars($t('indicadores.empty.value')) . '</div>';
        return;
    }
    ?>
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
                  <?php if ($canEdit): ?>
                    <form method="post" action="index.php?route=indicadores/updateRealizado" class="flex flex-col gap-2 max-w-xs">
                      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                      <input type="hidden" name="evento_id" value="<?= (int)$item['id'] ?>" />
                      <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
                      <input type="hidden" name="indicador_id" value="<?= (int)$indicadorId ?>" />
                      <input type="hidden" name="periodo_inicio" value="<?= htmlspecialchars($periodoInicio) ?>" />
                      <input type="hidden" name="periodo_fim" value="<?= htmlspecialchars($periodoFim) ?>" />
                      <input type="text"
                             inputmode="decimal"
                             pattern="^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,4})?$"
                             name="valor"
                             data-indicador-decimal
                             data-unidade-tipo="<?= htmlspecialchars((string)($item['unidade_tipo'] ?? '')) ?>"
                             class="border border-gray-300 rounded-lg p-2 w-full"
                             value="<?= $item['valor_atingido'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::decimal($item['valor_atingido'], 2)) : '' ?>"
                             required />
                      <button class="px-4 py-2 rounded-lg bg-brand-red text-white" type="submit"><?= htmlspecialchars($t('indicadores.action.save')) ?></button>
                    </form>
                  <?php else: ?>
                    <div class="text-sm text-gray-600 select-text">
                      <?= $item['valor_atingido'] !== null ? htmlspecialchars($formatValue($item['valor_atingido'], $item)) : '—' ?>
                    </div>
                  <?php endif; ?>
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
    <?php
};
?>

<?php if ($renderOnlyTable): ?>
  <?php $renderTable($items, (int)$cliente, $indicadorId, $periodoInicio, $periodoFim, $canEdit, $t, $formatValue); ?>
  <?php return; ?>
<?php endif; ?>

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
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-4" id="indicadoresRealizadoFilterForm">
      <input type="hidden" name="route" value="indicadores/realizado" />
      <div class="md:col-span-4">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <select name="cliente" id="indicadoresCliente" class="border border-gray-300 rounded-lg p-3 w-full">
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.indicador')) ?></label>
        <select name="indicador_id" id="indicadoresIndicador" class="border border-gray-300 rounded-lg p-3 w-full" <?= $cliente ? '' : 'disabled' ?>>
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
            <input type="date" name="periodo_inicio" id="indicadoresPeriodoInicio" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= htmlspecialchars($periodoInicio) ?>" <?= $cliente ? '' : 'disabled' ?> />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fim</label>
            <input type="date" name="periodo_fim" id="indicadoresPeriodoFim" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= htmlspecialchars($periodoFim) ?>" <?= $cliente ? '' : 'disabled' ?> />
          </div>
        </div>
        <p class="mt-1 text-xs text-gray-500">Deixe em branco para ver todo o histórico.</p>
      </div>
      <div class="md:col-span-6">
        <div id="indicadoresFiltroErro" class="hidden rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
      </div>
      <div class="md:col-span-3 flex items-end">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full" type="submit" id="indicadoresAplicarBtn"><?= htmlspecialchars($t('indicadores.action.filter')) ?></button>
      </div>
      <div class="md:col-span-3 flex items-end">
        <button type="button" id="indicadoresLimparFiltros" class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown w-full"><?= htmlspecialchars($t('indicadores.action.clear')) ?></button>
      </div>
    </form>
  </div>

  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.client')) ?></div>
  <?php else: ?>
    <div id="indicadoresRealizadoList">
      <?php $renderTable($items, (int)$cliente, $indicadorId, $periodoInicio, $periodoFim, $canEdit, $t, $formatValue); ?>
    </div>
  <?php endif; ?>
</div>
<script>
  (function () {
    const decimalRe = /^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,4})?$/;

    function toPtBrDecimal(value) {
      let raw = String(value || '').trim();
      if (!raw) return '';
      raw = raw.replace(/\s+/g, '').replace(/R\$/g, '');
      raw = raw.replace(/[^0-9,.\-]/g, '');
      const parts = raw.split(',');
      if (parts.length > 2) return '';
      if (parts.length === 2) {
        raw = parts[0].replace(/\./g, '') + ',' + parts[1];
      }
      if (!decimalRe.test(raw)) return '';
      if (raw.includes(',') && raw.includes('.')) raw = raw.replace(/\./g, '');
      raw = raw.replace(',', '.');
      const numeric = Number(raw);
      if (!Number.isFinite(numeric)) return '';
      return numeric.toFixed(2).replace('.', ',');
    }

    function normalizeDecimal(value) {
      return toPtBrDecimal(value);
    }

    document.querySelectorAll('[data-indicador-decimal]').forEach((input) => {
      input.addEventListener('blur', function () {
        this.value = normalizeDecimal(this.value);
      });
      input.addEventListener('input', function () {
        const raw = String(this.value || '').replace(/[^0-9,.\-]/g, '');
        const parts = raw.split(',');
        this.value = parts.length > 2 ? (parts[0] + ',' + parts.slice(1).join('')) : raw;
      });
      input.form?.addEventListener('submit', function (e) {
        const normalized = normalizeDecimal(input.value);
        if (!normalized) {
          if (e && typeof e.preventDefault === 'function') e.preventDefault();
          alert('Informe um valor válido. Use apenas números e, se necessário, uma vírgula para decimais.');
          input.focus();
          return;
        }
        const tipo = String(input.getAttribute('data-unidade-tipo') || '').toLowerCase();
        const numeric = Number(normalized.replace(',', '.'));
        if (!Number.isFinite(numeric)) {
          if (e && typeof e.preventDefault === 'function') e.preventDefault();
          alert('Informe um valor válido.');
          input.focus();
          return;
        }
        if (tipo === 'inteiro' && Math.floor(numeric) !== numeric) {
          if (e && typeof e.preventDefault === 'function') e.preventDefault();
          alert('Este indicador aceita apenas números inteiros.');
          input.focus();
          return;
        }
        if (tipo === 'percentual' && numeric > 100) {
          if (e && typeof e.preventDefault === 'function') e.preventDefault();
          alert('Percentual deve ser menor ou igual a 100.');
          input.focus();
          return;
        }
        input.value = normalized;
      });
    });

    const form = document.getElementById('indicadoresRealizadoFilterForm');
    const indicadorSelect = document.getElementById('indicadoresIndicador');
    const clienteSelect = document.getElementById('indicadoresCliente');
    const periodoInicioInput = document.getElementById('indicadoresPeriodoInicio');
    const periodoFimInput = document.getElementById('indicadoresPeriodoFim');
    const listWrap = document.getElementById('indicadoresRealizadoList');
    const limparBtn = document.getElementById('indicadoresLimparFiltros');
    const msg = document.getElementById('indicadoresFiltroErro');

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
      const ini = String(periodoInicioInput?.value || '').trim();
      const fim = String(periodoFimInput?.value || '').trim();
      if (!ini && !fim) {
        setMsg('');
        return { ok: true, ini: '', fim: '' };
      }
      if (!ini || !fim) {
        setMsg('Informe as duas datas do período, ou deixe ambas em branco para ver todo o histórico.');
        return { ok: false, ini: '', fim: '' };
      }
      if (fim < ini) {
        setMsg('A data final do período não pode ser anterior à data inicial.');
        return { ok: false, ini, fim };
      }
      setMsg('');
      return { ok: true, ini, fim };
    }

    async function refreshList() {
      if (!form || !clienteSelect || !listWrap) return;
      const clienteId = String(clienteSelect.value || '');
      if (!clienteId) return;
      const period = validatePeriodo();
      if (!period.ok) return;
      const params = new URLSearchParams(new FormData(form));
      const url = 'index.php?' + params.toString();
      try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await res.json().catch(() => null);
        if (!res.ok) {
          setMsg(payload && payload.message ? payload.message : 'Não foi possível aplicar o filtro agora.');
          return;
        }
        if (!payload || !payload.ok) {
          setMsg(payload && payload.message ? payload.message : 'Não foi possível aplicar o filtro agora.');
          return;
        }
        if (typeof payload.table_html === 'string') listWrap.innerHTML = payload.table_html;
        window.history.replaceState({}, '', url);
      } catch (e) {
      }
    }

    if (clienteSelect) {
      clienteSelect.addEventListener('change', () => {
        if (!form) return;
        // Troca de empresa: limpa indicador e período da empresa anterior
        // e recarrega a página inteira, pois o servidor precisa recalcular
        // a lista de indicadores e os estados dos campos para a nova empresa.
        if (indicadorSelect) indicadorSelect.value = '0';
        if (periodoInicioInput) periodoInicioInput.value = '';
        if (periodoFimInput) periodoFimInput.value = '';
        form.submit();
      });
    }
    if (indicadorSelect) {
      indicadorSelect.addEventListener('change', () => refreshList());
    }
    [periodoInicioInput, periodoFimInput].forEach((el) => {
      if (!el) return;
      el.addEventListener('change', () => refreshList());
    });
    if (limparBtn) {
      limparBtn.addEventListener('click', () => {
        if (indicadorSelect) indicadorSelect.value = '0';
        refreshList();
      });
    }
    if (form) {
      form.addEventListener('submit', (e) => {
        // Sem empresa/lista carregada ainda: deixa o submit nativo (GET)
        // navegar e recarregar a página com o cliente escolhido.
        if (!clienteSelect || !clienteSelect.value || !listWrap) return;
        e.preventDefault();
        refreshList();
      });
    }
  })();
</script>
