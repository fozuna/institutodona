<?php
$t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace);
$formatValue = static function ($value, array $item): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $item['unidade_simbolo'] ?? '',
        'tipo' => $item['unidade_tipo'] ?? '',
    ]);
};
$q = $q ?? '';
$dateStart = $dateStart ?? '';
$dateEnd = $dateEnd ?? '';
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
    <div id="indicadoresFilterError" class="hidden mb-3 px-4 py-3 rounded bg-red-100 text-red-700 text-sm"></div>
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-4" id="indicadoresFiltersForm">
      <input type="hidden" name="route" value="indicadores/index" />
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <select name="cliente" class="border border-gray-300 rounded-lg p-3 w-full" id="indicadoresClienteSelect">
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome_empresa']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
        <div class="sm:col-span-3">
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome do indicador</label>
          <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            class="border border-gray-300 rounded-lg p-3 w-full"
            placeholder="Digite para buscar..."
            list="indicadoresNameSuggestions"
            id="indicadoresNameInput"
            autocomplete="off"
          />
          <datalist id="indicadoresNameSuggestions"></datalist>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Data inicial</label>
          <input type="date" name="date_start" value="<?= htmlspecialchars($dateStart) ?>" class="border border-gray-300 rounded-lg p-3 w-full" id="indicadoresDateStart" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Data final</label>
          <input type="date" name="date_end" value="<?= htmlspecialchars($dateEnd) ?>" class="border border-gray-300 rounded-lg p-3 w-full" id="indicadoresDateEnd" />
        </div>
        <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-end sm:col-span-3">
          <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full sm:w-auto" type="submit" id="indicadoresSearchBtn"><?= htmlspecialchars($t('indicadores.action.filter')) ?></button>
          <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center w-full sm:w-auto" href="index.php?route=indicadores/index<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>">Limpar</a>
          <div class="flex-1"></div>
          <div class="text-xs text-gray-500 flex items-center" id="indicadoresLoading" style="display:none;">Carregando…</div>
        </div>
        <?php if ($cliente): ?>
          <div class="sm:col-span-3 flex flex-col sm:flex-row gap-2">
            <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/realizado&cliente=<?= (int)$cliente ?>"><?= htmlspecialchars($t('indicadores.action.value')) ?></a>
            <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/painel&cliente=<?= (int)$cliente ?>&ano=<?= (int)date('Y') ?>"><?= htmlspecialchars($t('indicadores.action.dashboard')) ?></a>
            <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center" href="index.php?route=indicadores/charts&cliente=<?= (int)$cliente ?>"><?= htmlspecialchars($t('indicadores.action.charts')) ?></a>
          </div>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div id="indicadoresResults">
    <?php require __DIR__ . '/_cards.php'; ?>
  </div>
</div>

<script>
  (function () {
    const form = document.getElementById('indicadoresFiltersForm');
    const results = document.getElementById('indicadoresResults');
    const errBox = document.getElementById('indicadoresFilterError');
    const loading = document.getElementById('indicadoresLoading');
    const clienteSelect = document.getElementById('indicadoresClienteSelect');
    const nameInput = document.getElementById('indicadoresNameInput');
    const dateStart = document.getElementById('indicadoresDateStart');
    const dateEnd = document.getElementById('indicadoresDateEnd');
    const datalist = document.getElementById('indicadoresNameSuggestions');
    let searchTimer = null;
    let suggestTimer = null;

    const showError = (msg) => {
      if (!errBox) return;
      errBox.textContent = msg;
      errBox.classList.remove('hidden');
    };
    const clearError = () => {
      if (!errBox) return;
      errBox.textContent = '';
      errBox.classList.add('hidden');
    };
    const setLoading = (isLoading) => {
      if (!loading) return;
      loading.style.display = isLoading ? 'flex' : 'none';
    };

    const validateDates = () => {
      const a = (dateStart && dateStart.value) ? dateStart.value : '';
      const b = (dateEnd && dateEnd.value) ? dateEnd.value : '';
      if (a && b && b < a) {
        showError('A data final não pode ser anterior à data inicial.');
        return false;
      }
      return true;
    };

    const updateUrl = () => {
      if (!form) return;
      const params = new URLSearchParams(new FormData(form));
      const url = new URL(window.location.href);
      url.search = params.toString();
      window.history.replaceState({}, '', url.toString());
    };

    const doSearch = async () => {
      if (!form || !results) return;
      clearError();
      if (!validateDates()) return;
      setLoading(true);
      const params = new URLSearchParams(new FormData(form));
      params.set('ajax', '1');
      try {
        const response = await fetch(`index.php?${params.toString()}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const contentType = (response.headers.get('content-type') || '').toLowerCase();
        if (!response.ok) {
          if (contentType.includes('application/json')) {
            const payload = await response.json();
            throw new Error(payload.message || 'Falha ao filtrar indicadores.');
          }
          throw new Error('Falha ao filtrar indicadores.');
        }
        const html = await response.text();
        results.innerHTML = html;
        updateUrl();
      } catch (e) {
        showError(e.message || 'Não foi possível atualizar os resultados.');
      } finally {
        setLoading(false);
      }
    };

    const refreshSuggestions = async () => {
      if (!datalist || !clienteSelect || !nameInput) return;
      const cliente = clienteSelect.value || '';
      const q = (nameInput.value || '').trim();
      if (!cliente || q.length < 2) {
        datalist.innerHTML = '';
        return;
      }
      try {
        const params = new URLSearchParams({ route: 'indicadores/autocomplete', cliente, q });
        const response = await fetch(`index.php?${params.toString()}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        const payload = await response.json();
        if (!payload || !payload.ok || !Array.isArray(payload.items)) return;
        datalist.innerHTML = '';
        payload.items.forEach((name) => {
          const opt = document.createElement('option');
          opt.value = String(name || '');
          datalist.appendChild(opt);
        });
      } catch (e) {
      }
    };

    const scheduleSearch = () => {
      if (searchTimer) clearTimeout(searchTimer);
      searchTimer = setTimeout(doSearch, 450);
    };
    const scheduleSuggest = () => {
      if (suggestTimer) clearTimeout(suggestTimer);
      suggestTimer = setTimeout(refreshSuggestions, 250);
    };

    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        doSearch();
      });
    }
    [clienteSelect, dateStart, dateEnd].forEach((el) => {
      if (!el) return;
      el.addEventListener('change', scheduleSearch);
    });
    if (nameInput) {
      nameInput.addEventListener('input', () => {
        scheduleSuggest();
        scheduleSearch();
      });
      nameInput.addEventListener('change', scheduleSearch);
    }
  })();
</script>
