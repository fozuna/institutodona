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
$viewMode = $viewMode ?? 'cards';
?>
<div class="p-4 md:p-6 space-y-6" data-disable-icon-only="1">
  <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.index')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.help.limites')) ?></p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
      <a class="px-4 py-3 rounded-lg bg-brand-red text-white text-center btn-with-icon" href="index.php?route=indicadores/create<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.new')) ?>" title="<?= htmlspecialchars($t('indicadores.action.new')) ?>">
        <span data-feather="plus"></span>
        <?= htmlspecialchars($t('indicadores.action.new')) ?>
      </a>
      <button
        type="button"
        id="indicadoresViewToggle"
        class="px-4 py-3 rounded-lg bg-white border border-gray-200 text-brand-brown text-center btn-with-icon"
        aria-label="Alternar visualização"
        title="Alternar visualização"
      >
        <span data-feather="list" data-view-icon="list" class="<?= $viewMode === 'list' ? 'hidden' : '' ?>"></span>
        <span data-feather="grid" data-view-icon="cards" class="<?= $viewMode === 'cards' ? 'hidden' : '' ?>"></span>
        <span class="hidden sm:inline">Visualização</span>
      </button>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center btn-with-icon" href="javascript:history.back()" aria-label="<?= htmlspecialchars($t('indicadores.action.back')) ?>" title="<?= htmlspecialchars($t('indicadores.action.back')) ?>">
        <span data-feather="arrow-left"></span>
        <?= htmlspecialchars($t('indicadores.action.back')) ?>
      </a>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <div id="indicadoresFilterError" class="hidden mb-3 px-4 py-3 rounded bg-red-100 text-red-700 text-sm"></div>
    <input type="hidden" id="indicadoresInlineCsrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <form method="get" action="index.php" class="grid grid-cols-1 xl:grid-cols-12 gap-4" id="indicadoresFiltersForm">
      <input type="hidden" name="route" value="indicadores/index" />
      <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>" id="indicadoresViewInput" />
      <div class="xl:col-span-5">
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
      <div class="xl:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-3">
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
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.setor')) ?></label>
          <select name="setor" class="border border-gray-300 rounded-lg p-3 w-full" id="indicadoresSetorSelect" <?= $cliente ? '' : 'disabled' ?>>
            <option value=""><?= htmlspecialchars($t('indicadores.option.select_all')) ?></option>
            <?php foreach (($setores ?? []) as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= ((int)($setorId ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars(($s['departamento'] ?? '') !== '' ? ($s['departamento'] . ' — ' . $s['nome']) : $s['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-end sm:col-span-3">
          <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full sm:w-auto btn-with-icon" type="submit" id="indicadoresSearchBtn" aria-label="<?= htmlspecialchars($t('indicadores.action.filter')) ?>" title="<?= htmlspecialchars($t('indicadores.action.filter')) ?>">
            <span data-feather="filter"></span>
            <?= htmlspecialchars($t('indicadores.action.filter')) ?>
          </button>
          <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center w-full sm:w-auto btn-with-icon" href="index.php?route=indicadores/index<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.clear')) ?>" title="<?= htmlspecialchars($t('indicadores.action.clear')) ?>">
            <span data-feather="x-circle"></span>
            <?= htmlspecialchars($t('indicadores.action.clear')) ?>
          </a>
          <div class="flex-1"></div>
          <div class="text-xs text-gray-500 flex items-center" id="indicadoresLoading" style="display:none;">Carregando…</div>
        </div>
        <?php if ($cliente): ?>
          <div class="sm:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center btn-with-icon" href="index.php?route=indicadores/realizado&cliente=<?= (int)$cliente ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.value')) ?>" title="<?= htmlspecialchars($t('indicadores.action.value')) ?>">
              <span data-feather="edit-3"></span>
              <?= htmlspecialchars($t('indicadores.action.value')) ?>
            </a>
            <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center btn-with-icon" href="index.php?route=indicadores/painel&cliente=<?= (int)$cliente ?>&ano=<?= (int)date('Y') ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.dashboard')) ?>" title="<?= htmlspecialchars($t('indicadores.action.dashboard')) ?>">
              <span data-feather="grid"></span>
              <?= htmlspecialchars($t('indicadores.action.dashboard')) ?>
            </a>
            <a class="px-4 py-3 rounded-lg bg-brand-brown text-white text-center btn-with-icon" href="index.php?route=indicadores/charts&cliente=<?= (int)$cliente ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.charts')) ?>" title="<?= htmlspecialchars($t('indicadores.action.charts')) ?>">
              <span data-feather="bar-chart-2"></span>
              <?= htmlspecialchars($t('indicadores.action.charts')) ?>
            </a>
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
    const csrf = document.getElementById('indicadoresInlineCsrf');
    const viewInput = document.getElementById('indicadoresViewInput');
    const viewToggle = document.getElementById('indicadoresViewToggle');
    const clienteSelect = document.getElementById('indicadoresClienteSelect');
    const nameInput = document.getElementById('indicadoresNameInput');
    const dateStart = document.getElementById('indicadoresDateStart');
    const dateEnd = document.getElementById('indicadoresDateEnd');
    const setorSelect = document.getElementById('indicadoresSetorSelect');
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

    const getViewMode = () => {
      const raw = viewInput ? String(viewInput.value || '').toLowerCase() : '';
      return (raw === 'list' || raw === 'cards') ? raw : 'cards';
    };
    const setViewMode = (mode) => {
      if (!viewInput) return;
      const next = (mode === 'list') ? 'list' : 'cards';
      viewInput.value = next;
      try { window.localStorage.setItem('indicadores_view_mode', next); } catch (e) {}
      if (viewToggle) {
        const iconList = viewToggle.querySelector('[data-view-icon="list"]');
        const iconCards = viewToggle.querySelector('[data-view-icon="cards"]');
        if (iconList) iconList.classList.toggle('hidden', next === 'list');
        if (iconCards) iconCards.classList.toggle('hidden', next === 'cards');
        viewToggle.setAttribute('aria-pressed', next === 'list' ? 'true' : 'false');
      }
      if (window.feather && typeof window.feather.replace === 'function') {
        window.feather.replace();
      }
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
        if (window.feather && typeof window.feather.replace === 'function') {
          window.feather.replace();
        }
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

    const refreshSetores = async () => {
      if (!setorSelect || !clienteSelect) return;
      const cliente = clienteSelect.value || '';
      if (!cliente) {
        setorSelect.innerHTML = '<option value="">Todos</option>';
        setorSelect.value = '';
        setorSelect.disabled = true;
        return;
      }
      try {
        const params = new URLSearchParams({ route: 'indicadores/apiSetoresByCliente', cliente_id: cliente });
        const response = await fetch(`index.php?${params.toString()}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        const payload = await response.json();
        if (!payload || !payload.success || !Array.isArray(payload.items)) return;
        setorSelect.innerHTML = '<option value="">Todos</option>';
        payload.items.forEach((item) => {
          const opt = document.createElement('option');
          opt.value = String(item.id);
          opt.textContent = item.departamento ? `${item.departamento} — ${item.nome}` : item.nome;
          setorSelect.appendChild(opt);
        });
        setorSelect.disabled = false;
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
    if (viewToggle) {
      viewToggle.addEventListener('click', () => {
        const cur = getViewMode();
        setViewMode(cur === 'list' ? 'cards' : 'list');
        doSearch();
      });
    }
    if (clienteSelect) {
      clienteSelect.addEventListener('change', () => {
        if (setorSelect) setorSelect.value = '';
        refreshSetores();
        scheduleSearch();
      });
    }
    [dateStart, dateEnd, setorSelect].forEach((el) => {
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

    const decimalRe = /^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,4})?$/;
    function normalizeDecimal(value) {
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
      if (raw.includes(',') && raw.includes('.')) {
        raw = raw.replace(/\./g, '');
      }
      raw = raw.replace(',', '.');
      const numeric = Number(raw);
      if (!Number.isFinite(numeric)) return '';
      return numeric.toFixed(2).replace('.', ',');
    }

    function setCardMsg(card, type, text) {
      if (!card) return;
      const box = card.querySelector('[data-indicador-msg]');
      if (!box) return;
      if (!text) {
        box.classList.add('hidden');
        box.textContent = '';
        return;
      }
      box.classList.remove('hidden');
      box.textContent = text;
      box.classList.remove('text-red-700', 'bg-red-50', 'border-red-200', 'text-green-700', 'bg-green-50', 'border-green-200');
      box.classList.add('rounded', 'border', 'px-3', 'py-2');
      if (type === 'error') {
        box.classList.add('text-red-700', 'bg-red-50', 'border-red-200');
      } else {
        box.classList.add('text-green-700', 'bg-green-50', 'border-green-200');
      }
    }

    function setCardEditing(card, editing) {
      if (!card) return;
      card.setAttribute('data-editing', editing ? '1' : '0');
      const view = card.querySelector('[data-indicador-valor-view]');
      const input = card.querySelector('[data-indicador-valor-input]');
      const saveBtn = card.querySelector('[data-indicador-save]');
      if (view) view.classList.toggle('hidden', !!editing);
      if (input) input.classList.toggle('hidden', !editing);
      if (saveBtn) saveBtn.disabled = !editing;
      if (saveBtn) saveBtn.classList.toggle('opacity-50', !editing);
    }

    function validateValueByUnit(card, value) {
      const tipo = String(card?.getAttribute('data-unidade-tipo') || '').toLowerCase();
      const normalized = normalizeDecimal(value);
      if (!normalized) return { ok: false, message: 'Valor inválido.' };
      const numeric = Number(normalized.replace(',', '.'));
      if (!Number.isFinite(numeric)) return { ok: false, message: 'Valor inválido.' };
      if (tipo === 'inteiro' && Math.floor(numeric) !== numeric) return { ok: false, message: 'Este indicador aceita apenas números inteiros.' };
      if (tipo === 'percentual' && numeric > 100) return { ok: false, message: 'Percentual deve ser menor ou igual a 100.' };
      return { ok: true, normalized, numeric };
    }

    async function saveValor(card) {
      const id = Number(card.getAttribute('data-indicador-id') || 0);
      const input = card.querySelector('[data-indicador-valor-input]');
      const editBtn = card.querySelector('[data-indicador-edit]');
      const saveBtn = card.querySelector('[data-indicador-save]');
      const delBtn = card.querySelector('[data-indicador-delete]');
      if (!id || !input) return;
      setCardMsg(card, '', '');

      const validation = validateValueByUnit(card, input.value);
      if (!validation.ok) {
        setCardMsg(card, 'error', validation.message);
        return;
      }

      [editBtn, saveBtn, delBtn].forEach((b) => {
        if (!b) return;
        b.disabled = true;
        b.classList.add('opacity-75', 'cursor-not-allowed');
      });
      try {
        const form = new FormData();
        form.append('csrf', csrf ? (csrf.value || '') : '');
        form.append('id', String(id));
        form.append('valor', validation.normalized);
        const res = await fetch('index.php?route=indicadores/updateValorAjax', {
          method: 'POST',
          body: form,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (!res.ok) {
          if (ct.includes('application/json')) {
            const payload = await res.json();
            throw new Error(payload.message || 'Falha ao salvar.');
          }
          throw new Error('Falha ao salvar.');
        }
        const payload = await res.json();
        if (!payload || !payload.ok) {
          throw new Error((payload && payload.message) ? payload.message : 'Falha ao salvar.');
        }
        const view = card.querySelector('[data-indicador-valor-view]');
        if (view) view.textContent = String(payload.valor_formatado || '');
        setCardEditing(card, false);
        setCardMsg(card, 'success', 'Valor atualizado com sucesso.');
      } catch (e) {
        setCardMsg(card, 'error', e.message || 'Não foi possível salvar agora.');
      } finally {
        [editBtn, saveBtn, delBtn].forEach((b) => {
          if (!b) return;
          b.disabled = false;
          b.classList.remove('opacity-75', 'cursor-not-allowed');
        });
        const saveBtn2 = card.querySelector('[data-indicador-save]');
        if (saveBtn2) {
          const editing = card.getAttribute('data-editing') === '1';
          saveBtn2.disabled = !editing;
          saveBtn2.classList.toggle('opacity-50', !editing);
        }
      }
    }

    async function deleteIndicador(card) {
      const id = Number(card.getAttribute('data-indicador-id') || 0);
      const delBtn = card.querySelector('[data-indicador-delete]');
      const editBtn = card.querySelector('[data-indicador-edit]');
      const saveBtn = card.querySelector('[data-indicador-save]');
      if (!id) return;
      setCardMsg(card, '', '');
      if (!confirm('Excluir este indicador?')) return;
      [editBtn, saveBtn, delBtn].forEach((b) => {
        if (!b) return;
        b.disabled = true;
        b.classList.add('opacity-75', 'cursor-not-allowed');
      });
      try {
        const form = new FormData();
        form.append('csrf', csrf ? (csrf.value || '') : '');
        form.append('id', String(id));
        const res = await fetch('index.php?route=indicadores/deleteAjax', {
          method: 'POST',
          body: form,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (!res.ok) {
          if (ct.includes('application/json')) {
            const payload = await res.json();
            throw new Error(payload.message || 'Falha ao excluir.');
          }
          throw new Error('Falha ao excluir.');
        }
        const payload = await res.json();
        if (!payload || !payload.ok) {
          throw new Error((payload && payload.message) ? payload.message : 'Falha ao excluir.');
        }
        card.remove();
      } catch (e) {
        setCardMsg(card, 'error', e.message || 'Não foi possível excluir agora.');
      } finally {
        [editBtn, saveBtn, delBtn].forEach((b) => {
          if (!b) return;
          b.disabled = false;
          b.classList.remove('opacity-75', 'cursor-not-allowed');
        });
      }
    }

    if (results) {
      results.addEventListener('click', (e) => {
        const target = e.target;
        const btn = target && target.closest ? target.closest('[data-indicador-edit],[data-indicador-save],[data-indicador-delete]') : null;
        if (!btn) return;
        const card = btn.closest('[data-indicador-id]');
        if (!card) return;
        if (btn.hasAttribute('data-indicador-edit')) {
          e.preventDefault();
          setCardMsg(card, '', '');
          setCardEditing(card, true);
          const input = card.querySelector('[data-indicador-valor-input]');
          if (input) input.focus();
          return;
        }
        if (btn.hasAttribute('data-indicador-save')) {
          e.preventDefault();
          saveValor(card);
          return;
        }
        if (btn.hasAttribute('data-indicador-delete')) {
          e.preventDefault();
          deleteIndicador(card);
        }
      });
      results.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter') return;
        const target = e.target;
        const input = target && target.matches ? (target.matches('[data-indicador-valor-input]') ? target : null) : null;
        if (!input) return;
        const card = input.closest('[data-indicador-id]');
        if (!card) return;
        e.preventDefault();
        saveValor(card);
      });
      results.addEventListener('blur', (e) => {
        const input = e.target && e.target.matches ? (e.target.matches('[data-indicador-valor-input]') ? e.target : null) : null;
        if (!input) return;
        input.value = normalizeDecimal(input.value);
      }, true);
    }

    if (viewInput && !viewInput.value) {
      try {
        const stored = String(window.localStorage.getItem('indicadores_view_mode') || '').toLowerCase();
        if (stored === 'list' || stored === 'cards') {
          setViewMode(stored);
        }
      } catch (e) {}
    } else {
      setViewMode(getViewMode());
    }
  })();
</script>
