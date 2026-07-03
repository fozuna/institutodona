<?php
/** @var array $formData */
/** @var array $errors */
/** @var array $departamentos */
/** @var array $setores */
/** @var array $unidades */
/** @var array $responsaveisSelecionados */
/** @var array $periodicidades */
/** @var array $metaTipos */
/** @var string $submitRoute */
/** @var string $title */
/** @var string $backUrl */
/** @var string $submitLabel */
/** @var array|null $item */

$t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace);
$fieldError = static function (string $field) use ($errors): string {
    return (string)($errors[$field] ?? '');
};
$value = static function (string $field, $fallback = '') use ($formData) {
    return $formData[$field] ?? $fallback;
};
$formatInput = static function ($raw): string {
    if ($raw === null || $raw === '') {
        return '';
    }
    return \App\Core\ValueFormatter::decimal($raw, 2);
};
?>
<div class="p-4 md:p-6 max-w-5xl mx-auto">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($title) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.help.periodicidade')) ?></p>
    </div>
    <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown w-full md:w-auto text-center" href="<?= htmlspecialchars($backUrl) ?>">
      <?= htmlspecialchars($t('indicadores.action.back')) ?>
    </a>
  </div>

  <form method="post" action="index.php?route=<?= htmlspecialchars($submitRoute) ?>" class="bg-white shadow rounded-xl p-4 md:p-6 space-y-6">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <?php if (!empty($item['id'])): ?>
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
    <?php endif; ?>
    <input type="hidden" name="cliente_id" id="indicadorClienteId" value="<?= (int)$value('cliente_id', 0) ?>" />
    <input type="hidden" name="cliente_nome" id="indicadorClienteNome" value="<?= htmlspecialchars((string)$value('cliente_nome', '')) ?>" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <div class="relative">
          <input
            type="text"
            id="indicadorClienteAutocomplete"
            class="border rounded-lg p-3 w-full <?= $fieldError('cliente_id') ? 'border-red-400' : 'border-gray-300' ?>"
            placeholder="<?= htmlspecialchars($t('indicadores.placeholder.cliente')) ?>"
            value="<?= htmlspecialchars((string)$value('cliente_nome', '')) ?>"
            autocomplete="off"
            required
          />
          <div id="indicadorClienteResults" class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow max-h-56 overflow-auto"></div>
        </div>
        <?php $clienteIdError = $fieldError('cliente_id'); ?>
        <p id="indicadorClienteIdError" class="text-sm text-red-600 mt-1<?= $clienteIdError ? '' : ' hidden' ?>"><?= htmlspecialchars($clienteIdError) ?></p>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.indicador')) ?></label>
        <input
          type="text"
          name="indicador"
          maxlength="255"
          class="border rounded-lg p-3 w-full <?= $fieldError('indicador') ? 'border-red-400' : 'border-gray-300' ?>"
          placeholder="<?= htmlspecialchars($t('indicadores.placeholder.indicador')) ?>"
          value="<?= htmlspecialchars((string)$value('indicador', '')) ?>"
          required
        />
        <?php if ($fieldError('indicador')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('indicador')) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.departamento')) ?></label>
        <select name="departamento_id" id="indicadorDepartamento" class="border rounded-lg p-3 w-full <?= $fieldError('departamento_id') ? 'border-red-400' : 'border-gray-300' ?>" required>
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($departamentos as $departamento): ?>
            <option value="<?= (int)$departamento['id'] ?>" <?= ((int)$value('departamento_id', 0) === (int)$departamento['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($departamento['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($fieldError('departamento_id')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('departamento_id')) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.setor')) ?></label>
        <select name="setor_id" id="indicadorSetor" class="border rounded-lg p-3 w-full <?= $fieldError('setor_id') ? 'border-red-400' : 'border-gray-300' ?>" required>
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($setores as $setor): ?>
            <option value="<?= (int)$setor['id'] ?>" <?= ((int)$value('setor_id', 0) === (int)$setor['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($setor['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($fieldError('setor_id')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('setor_id')) ?></p><?php endif; ?>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.responsavel')) ?></label>
        <div class="border rounded-lg p-3 <?= $fieldError('responsavel_ids') ? 'border-red-400' : 'border-gray-300' ?>">
          <div id="indicadorResponsaveisSelecionados" class="flex flex-wrap gap-2 mb-3">
            <?php foreach ($responsaveisSelecionados as $responsavel): ?>
              <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-red/10 text-brand-red text-sm" data-chip-id="<?= (int)$responsavel['id'] ?>">
                <span><?= htmlspecialchars($responsavel['nome']) ?><?php if (!empty($responsavel['email'])): ?> · <?= htmlspecialchars($responsavel['email']) ?><?php endif; ?></span>
                <button type="button" class="font-semibold" data-remove-chip="<?= (int)$responsavel['id'] ?>">×</button>
              </span>
              <input type="hidden" name="responsavel_ids[]" value="<?= (int)$responsavel['id'] ?>" data-hidden-responsavel="<?= (int)$responsavel['id'] ?>" />
            <?php endforeach; ?>
          </div>
          <div class="relative">
            <input
              type="text"
              id="indicadorResponsavelBusca"
              class="border rounded-lg p-3 w-full border-gray-300"
              placeholder="<?= htmlspecialchars($t('indicadores.placeholder.responsavel')) ?>"
              autocomplete="off"
            />
            <div id="indicadorResponsavelResults" class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow max-h-56 overflow-auto"></div>
          </div>
          <p class="text-xs text-gray-500 mt-2"><?= htmlspecialchars($t('indicadores.help.responsavel')) ?></p>
        </div>
        <?php if ($fieldError('responsavel_ids')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('responsavel_ids')) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.periodicidade')) ?></label>
        <select name="periodicidade_tipo" class="border rounded-lg p-3 w-full <?= $fieldError('periodicidade_tipo') ? 'border-red-400' : 'border-gray-300' ?>" required>
          <?php foreach ($periodicidades as $periodicidadeValue => $periodicidadeLabel): ?>
            <option value="<?= htmlspecialchars($periodicidadeValue) ?>" <?= ((string)$value('periodicidade_tipo', 'mensal') === (string)$periodicidadeValue) ? 'selected' : '' ?>>
              <?= htmlspecialchars($periodicidadeLabel) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($fieldError('periodicidade_tipo')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('periodicidade_tipo')) ?></p><?php endif; ?>
      </div>

      <div></div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.data_inicial')) ?></label>
        <input type="date" name="data_inicial" class="border rounded-lg p-3 w-full <?= $fieldError('data_inicial') ? 'border-red-400' : 'border-gray-300' ?>" value="<?= htmlspecialchars((string)$value('data_inicial', '')) ?>" required />
        <?php if ($fieldError('data_inicial')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('data_inicial')) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.data_final')) ?></label>
        <input type="date" name="data_final" class="border rounded-lg p-3 w-full <?= $fieldError('data_final') ? 'border-red-400' : 'border-gray-300' ?>" value="<?= htmlspecialchars((string)$value('data_final', '')) ?>" required />
        <?php if ($fieldError('data_final')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('data_final')) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.valor')) ?></label>
        <input type="text" inputmode="decimal" name="valor" data-indicador-decimal class="border rounded-lg p-3 w-full <?= $fieldError('valor') ? 'border-red-400' : 'border-gray-300' ?>" value="<?= htmlspecialchars($formatInput($value('valor', ''))) ?>" placeholder="<?= htmlspecialchars($t('indicadores.placeholder.valor')) ?>" required />
        <?php if ($fieldError('valor')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('valor')) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.tipo_meta')) ?></label>
        <select name="tipo_meta" class="border rounded-lg p-3 w-full <?= $fieldError('tipo_meta') ? 'border-red-400' : 'border-gray-300' ?>" required>
          <?php foreach ($metaTipos as $metaTipoValue => $metaTipoLabel): ?>
            <option value="<?= htmlspecialchars($metaTipoValue) ?>" <?= ((string)$value('tipo_meta', 'minimo') === (string)$metaTipoValue) ? 'selected' : '' ?>>
              <?= htmlspecialchars($metaTipoLabel) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($fieldError('tipo_meta')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('tipo_meta')) ?></p><?php endif; ?>
        <p class="text-xs text-gray-500 mt-2"><?= htmlspecialchars($t('indicadores.help.tipo_meta')) ?></p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.unidade_medida')) ?></label>
        <select name="unidade_medida_id" class="border rounded-lg p-3 w-full <?= $fieldError('unidade_medida_id') ? 'border-red-400' : 'border-gray-300' ?>" required>
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($unidades as $unidade): ?>
            <option value="<?= (int)$unidade['id'] ?>" <?= ((int)$value('unidade_medida_id', 0) === (int)$unidade['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($unidade['nome'] . ' (' . $unidade['simbolo'] . ' · ' . $unidade['tipo'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($fieldError('unidade_medida_id')): ?><p class="text-sm text-red-600 mt-1"><?= htmlspecialchars($fieldError('unidade_medida_id')) ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.valor_minimo')) ?></label>
        <input type="text" inputmode="decimal" name="valor_minimo" data-indicador-decimal class="border rounded-lg p-3 w-full <?= $fieldError('valor_minimo') ? 'border-red-400' : 'border-gray-300' ?>" value="<?= htmlspecialchars($formatInput($value('valor_minimo', ''))) ?>" placeholder="<?= htmlspecialchars($t('indicadores.placeholder.valor_minimo')) ?>" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.valor_maximo')) ?></label>
        <input type="text" inputmode="decimal" name="valor_maximo" data-indicador-decimal class="border rounded-lg p-3 w-full <?= $fieldError('valor_minimo') ? 'border-red-400' : 'border-gray-300' ?>" value="<?= htmlspecialchars($formatInput($value('valor_maximo', ''))) ?>" placeholder="<?= htmlspecialchars($t('indicadores.placeholder.valor_maximo')) ?>" />
      </div>
    </div>

    <?php if ($fieldError('valor_minimo')): ?><p class="text-sm text-red-600 -mt-4"><?= htmlspecialchars($fieldError('valor_minimo')) ?></p><?php endif; ?>
    <p class="text-xs text-gray-500"><?= htmlspecialchars($t('indicadores.help.limites')) ?></p>

    <div class="flex flex-col-reverse md:flex-row md:justify-end gap-3">
      <a class="px-4 py-3 rounded-lg bg-gray-100 text-gray-700 text-center" href="<?= htmlspecialchars($backUrl) ?>">
        <?= htmlspecialchars($t('indicadores.action.clear')) ?>
      </a>
      <button class="px-4 py-3 rounded-lg bg-brand-red text-white" type="submit">
        <?= htmlspecialchars($submitLabel) ?>
      </button>
    </div>
  </form>
</div>

<script>
  (function () {
    const clientInput = document.getElementById('indicadorClienteAutocomplete');
    const clientIdInput = document.getElementById('indicadorClienteId');
    const clientNameInput = document.getElementById('indicadorClienteNome');
    const clientResults = document.getElementById('indicadorClienteResults');
    const clientError = document.getElementById('indicadorClienteIdError');
    const departmentSelect = document.getElementById('indicadorDepartamento');
    const sectorSelect = document.getElementById('indicadorSetor');
    const responsavelInput = document.getElementById('indicadorResponsavelBusca');
    const responsavelResults = document.getElementById('indicadorResponsavelResults');
    const selectedContainer = document.getElementById('indicadorResponsaveisSelecionados');
    const invalidClientMsg = <?= json_encode($t('indicadores.validation.invalid_client'), JSON_UNESCAPED_UNICODE) ?>;

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function fetchJson(url) {
      return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((response) => response.json())
        .catch(() => ({ success: false, items: [] }));
    }

    function clearResults(node) {
      node.innerHTML = '';
      node.classList.add('hidden');
    }

    function setClientError(message) {
      if (clientError) {
        clientError.textContent = message || '';
        clientError.classList.toggle('hidden', !message);
      }
      if (clientInput) {
        clientInput.classList.toggle('border-red-400', !!message);
        clientInput.classList.toggle('border-gray-300', !message);
      }
    }

    function clearClientError() {
      setClientError('');
      if (clientInput) {
        clientInput.setCustomValidity('');
      }
    }

    function renderResults(node, items, onSelect, formatter) {
      if (!items.length) {
        clearResults(node);
        return;
      }
      node.innerHTML = items.map((item) => {
        return '<button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 border-b last:border-b-0" data-item=\'' + JSON.stringify(item).replace(/'/g, '&apos;') + '\'>' + formatter(item) + '</button>';
      }).join('');
      node.classList.remove('hidden');
      node.querySelectorAll('button[data-item]').forEach((button) => {
        button.addEventListener('click', function () {
          const raw = this.getAttribute('data-item').replace(/&apos;/g, "'");
          onSelect(JSON.parse(raw));
        });
      });
    }

    function selectedIds() {
      return Array.from(document.querySelectorAll('input[data-hidden-responsavel]')).map((input) => parseInt(input.value, 10));
    }

    function removeResponsavel(id) {
      document.querySelectorAll('[data-chip-id="' + id + '"]').forEach((node) => node.remove());
      document.querySelectorAll('input[data-hidden-responsavel="' + id + '"]').forEach((node) => node.remove());
    }

    function addResponsavel(item) {
      const id = parseInt(item.id, 10);
      if (!id || selectedIds().includes(id)) {
        responsavelInput.value = '';
        clearResults(responsavelResults);
        return;
      }
      const chip = document.createElement('span');
      chip.className = 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-red/10 text-brand-red text-sm';
      chip.setAttribute('data-chip-id', String(id));
      chip.innerHTML = '<span>' + escapeHtml(item.nome) + (item.email ? ' · ' + escapeHtml(item.email) : '') + '</span><button type="button" class="font-semibold" data-remove-chip="' + id + '">×</button>';
      selectedContainer.appendChild(chip);

      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'responsavel_ids[]';
      hidden.value = String(id);
      hidden.setAttribute('data-hidden-responsavel', String(id));
      selectedContainer.parentNode.appendChild(hidden);

      chip.querySelector('[data-remove-chip]').addEventListener('click', function () {
        removeResponsavel(id);
      });
      responsavelInput.value = '';
      clearResults(responsavelResults);
    }

    selectedContainer.querySelectorAll('[data-remove-chip]').forEach((button) => {
      button.addEventListener('click', function () {
        removeResponsavel(parseInt(this.getAttribute('data-remove-chip'), 10));
      });
    });

    function populateSelect(select, items, selectedValue, labelField) {
      const current = String(selectedValue || '');
      select.innerHTML = '<option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>';
      items.forEach((item) => {
        const option = document.createElement('option');
        option.value = String(item.id);
        option.textContent = item[labelField];
        if (current !== '' && String(item.id) === current) {
          option.selected = true;
        }
        select.appendChild(option);
      });
    }

    function normalizeDecimal(value) {
      let raw = String(value || '').trim();
      if (!raw) return '';
      raw = raw.replace(/\s+/g, '').replace(/R\$/g, '');
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
    });

    const form = document.querySelector('form[action*="indicadores/"]');
    form?.addEventListener('submit', function (e) {
      const clienteId = parseInt(clientIdInput?.value || '0', 10);
      if (!clienteId) {
        e.preventDefault();
        setClientError(invalidClientMsg);
        if (clientInput) {
          clientInput.setCustomValidity(invalidClientMsg);
          clientInput.reportValidity();
          clientInput.focus();
        }
        return;
      }
      clearClientError();
      this.querySelectorAll('[data-indicador-decimal]').forEach((input) => {
        input.value = normalizeDecimal(input.value);
      });
    });

    function loadDepartamentos(selectedValue) {
      const clienteId = parseInt(clientIdInput.value || '0', 10);
      populateSelect(departmentSelect, [], '', 'nome');
      populateSelect(sectorSelect, [], '', 'nome');
      if (!clienteId) {
        return;
      }
      fetchJson('index.php?route=indicadores/apiDepartamentos&cliente_id=' + clienteId).then((payload) => {
        populateSelect(departmentSelect, payload.items || [], selectedValue || '', 'nome');
      });
    }

    function loadSetores(selectedValue) {
      const departamentoId = parseInt(departmentSelect.value || '0', 10);
      populateSelect(sectorSelect, [], '', 'nome');
      if (!departamentoId) {
        return;
      }
      fetchJson('index.php?route=indicadores/apiSetores&departamento_id=' + departamentoId).then((payload) => {
        populateSelect(sectorSelect, payload.items || [], selectedValue || '', 'nome');
      });
    }

    clientInput.addEventListener('input', function () {
      clientIdInput.value = '';
      clientNameInput.value = this.value;
      if (this.value.trim() === '') {
        clearClientError();
      }
      if (this.value.trim().length < 2) {
        clearResults(clientResults);
        return;
      }
      fetchJson('index.php?route=indicadores/apiClientes&q=' + encodeURIComponent(this.value.trim())).then((payload) => {
        renderResults(clientResults, payload.items || [], function (item) {
          clientInput.value = item.nome_empresa || '';
          clientIdInput.value = item.id || '';
          clientNameInput.value = item.nome_empresa || '';
          clearClientError();
          clearResults(clientResults);
          loadDepartamentos('');
          selectedIds().forEach(removeResponsavel);
        }, function (item) {
          return '<div class="font-medium">' + escapeHtml(item.nome_empresa) + '</div><div class="text-xs text-gray-500">' + escapeHtml(item.CNPJ || '') + '</div>';
        });
      });
    });

    document.addEventListener('click', function (event) {
      if (!clientResults.contains(event.target) && event.target !== clientInput) {
        clearResults(clientResults);
      }
      if (!responsavelResults.contains(event.target) && event.target !== responsavelInput) {
        clearResults(responsavelResults);
      }
    });

    departmentSelect.addEventListener('change', function () {
      loadSetores('');
    });

    responsavelInput.addEventListener('input', function () {
      const clienteId = parseInt(clientIdInput.value || '0', 10);
      if (!clienteId || this.value.trim().length < 2) {
        clearResults(responsavelResults);
        return;
      }
      fetchJson('index.php?route=indicadores/apiResponsaveis&cliente_id=' + clienteId + '&q=' + encodeURIComponent(this.value.trim())).then((payload) => {
        renderResults(responsavelResults, payload.items || [], addResponsavel, function (item) {
          return '<div class="font-medium">' + escapeHtml(item.nome) + '</div><div class="text-xs text-gray-500">' + escapeHtml(item.email || '') + '</div>';
        });
      });
    });

    if (clientIdInput.value && departmentSelect.options.length <= 1) {
      loadDepartamentos('<?= (int)$value('departamento_id', 0) ?>');
    }
    if (departmentSelect.value && sectorSelect.options.length <= 1) {
      loadSetores('<?= (int)$value('setor_id', 0) ?>');
    }
  })();
</script>
