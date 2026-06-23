<?php /** @var array $clientes */ /** @var string $catalogoEndpoint */ ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-treinamentos-form data-catalog-endpoint="<?= htmlspecialchars((string)($catalogoEndpoint ?? '')) ?>">
  <div class="md:col-span-2">
    <label class="block text-sm">Nome</label>
    <input name="nome" class="border rounded p-2 w-full" required value="<?= htmlspecialchars((string)($values['nome'] ?? '')) ?>" />
    <?php if (!empty($errors['nome'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['nome']) ?></p><?php endif; ?>
  </div>
  <div class="md:col-span-2">
    <label class="block text-sm">Objetivo</label>
    <textarea name="objetivo" class="border rounded p-2 w-full" rows="4"><?= htmlspecialchars((string)($values['objetivo'] ?? '')) ?></textarea>
  </div>
  <div>
    <label class="block text-sm">Público</label>
    <input name="publico" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['publico'] ?? '')) ?>" />
  </div>
  <div>
    <label class="block text-sm">Carga Horária</label>
    <input name="carga_horaria" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['carga_horaria'] ?? '')) ?>" />
    <?php if (!empty($errors['carga_horaria'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['carga_horaria']) ?></p><?php endif; ?>
  </div>
  <div class="md:col-span-2">
    <label class="block text-sm">Empresa</label>
    <select name="cliente_id" class="border rounded p-2 w-full" required id="treinamentosClienteId">
      <option value="0">Selecione</option>
      <?php foreach (($clientes ?? []) as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)($values['cliente_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['nome_empresa']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['cliente_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['cliente_id']) ?></p><?php endif; ?>
  </div>
  <div>
    <label class="block text-sm">Departamento</label>
    <select name="departamento_id" class="border rounded p-2 w-full" required id="treinamentosDepartamentoId">
      <option value="0">Selecione</option>
      <?php foreach ($departamentos as $departamento): ?>
        <option value="<?= (int)$departamento['id'] ?>"
                <?= ((int)($values['departamento_id'] ?? 0) === (int)$departamento['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)($departamento['label'] ?? $departamento['nome'] ?? '')) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['departamento_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['departamento_id']) ?></p><?php endif; ?>
  </div>
  <div>
    <label class="block text-sm">Departamentos (Filtro)</label>
    <select multiple size="4" class="border rounded p-2 w-full" id="treinamentosDepartamentosFiltro">
      <?php foreach ($departamentos as $departamento): ?>
        <option value="<?= (int)$departamento['id'] ?>">
          <?= htmlspecialchars((string)($departamento['label'] ?? $departamento['nome'] ?? '')) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Opcional: selecione um ou mais departamentos para filtrar Setores e Funções aplicáveis.</p>
  </div>
  <div>
    <label class="block text-sm">Periodicidade</label>
    <select name="periodicidade" class="border rounded p-2 w-full">
      <?php foreach ($periodicidades as $key => $label): ?>
        <option value="<?= htmlspecialchars($key) ?>" <?= (($values['periodicidade'] ?? 'avulso') === $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="md:col-span-2">
    <label class="block text-sm">Fornecedor</label>
    <input name="fornecedor" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['fornecedor'] ?? '')) ?>" />
  </div>
  <div>
    <label class="block text-sm">Tipo de Treinamento</label>
    <input name="tipo_treinamento" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['tipo_treinamento'] ?? '')) ?>" placeholder="Ex.: Integracao, NR, Lideranca" />
  </div>
  <div>
    <label class="block text-sm">Responsável pela Assinatura</label>
    <input name="assinatura_responsavel" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['assinatura_responsavel'] ?? '')) ?>" placeholder="Nome que aparecerá no certificado" />
    <?php if (!empty($errors['assinatura_responsavel'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['assinatura_responsavel']) ?></p><?php endif; ?>
  </div>
  <div class="md:col-span-2">
    <label class="block text-sm">Template do Certificado</label>
    <textarea name="template_certificado" class="border rounded p-2 w-full" rows="3" placeholder="Texto complementar exibido no certificado"><?= htmlspecialchars((string)($values['template_certificado'] ?? '')) ?></textarea>
    <p class="text-xs text-gray-500 mt-1">Permite personalizar a mensagem principal exibida no certificado emitido em lote ou individualmente.</p>
  </div>
  <div>
    <label class="block text-sm">Setores Aplicáveis</label>
    <select name="setor_ids[]" multiple size="6" class="border rounded p-2 w-full" id="treinamentosSetores">
      <?php foreach ($setores as $setor): ?>
        <option value="<?= (int)$setor['id'] ?>"
                data-departamento-id="<?= (int)($setor['departamento_id'] ?? 0) ?>"
                <?= in_array((int)$setor['id'], array_map('intval', $values['setor_ids'] ?? []), true) ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)($setor['label'] ?? (($setor['departamento_nome'] ?? '') . ' • ' . ($setor['nome'] ?? '')))) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['setor_ids'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['setor_ids']) ?></p><?php endif; ?>
    <p class="text-xs text-gray-500 mt-1">Você pode selecionar várias opções neste campo e também em Funções Aplicáveis para definir com clareza o público do treinamento.</p>
  </div>
  <div>
    <label class="block text-sm">Funções Aplicáveis</label>
    <select name="funcao_ids[]" multiple size="6" class="border rounded p-2 w-full" id="treinamentosFuncoes">
      <?php foreach ($funcoes as $funcao): ?>
        <option value="<?= (int)$funcao['id'] ?>"
                data-setor-id="<?= (int)($funcao['setor_id'] ?? 0) ?>"
                data-departamento-id="<?= (int)($funcao['departamento_id'] ?? 0) ?>"
                <?= in_array((int)$funcao['id'], array_map('intval', $values['funcao_ids'] ?? []), true) ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)($funcao['label'] ?? (($funcao['setor_nome'] ?? '') . ' • ' . ($funcao['nome'] ?? '')))) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['funcao_ids'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['funcao_ids']) ?></p><?php endif; ?>
    <p class="text-xs text-gray-500 mt-1">Selecione quantas funções forem necessárias para complementar a escolha feita em Setores Aplicáveis.</p>
  </div>
</div>

<script>
  (function () {
    const root = document.querySelector('[data-treinamentos-form]');
    if (!root) return;
    const cliente = document.getElementById('treinamentosClienteId');
    const departamento = document.getElementById('treinamentosDepartamentoId');
    const departamentosFiltro = document.getElementById('treinamentosDepartamentosFiltro');
    const setores = document.getElementById('treinamentosSetores');
    const funcoes = document.getElementById('treinamentosFuncoes');
    const catalogEndpoint = root.getAttribute('data-catalog-endpoint') || '';
    if (!cliente || !departamento || !setores || !funcoes) return;

    const normalizeClientId = () => {
      const id = parseInt(cliente.value || '0', 10);
      return Number.isFinite(id) ? id : 0;
    };

    const buildOptions = (select, items, config = {}) => {
      if (!select) return;
      const {
        placeholder = false,
        labelKey = 'label',
        attrs = [],
      } = config;
      const selected = new Set(Array.from(select.selectedOptions).map((opt) => String(opt.value || '')));
      select.innerHTML = '';
      if (placeholder) {
        const opt = document.createElement('option');
        opt.value = '0';
        opt.textContent = 'Selecione';
        select.appendChild(opt);
      }
      items.forEach((item) => {
        const opt = document.createElement('option');
        opt.value = String(item.id || 0);
        opt.textContent = String(item[labelKey] || item.nome || '');
        attrs.forEach((attr) => {
          if (item[attr.key] !== undefined && item[attr.key] !== null) {
            opt.setAttribute(attr.name, String(item[attr.key]));
          }
        });
        if (selected.has(opt.value)) {
          opt.selected = true;
        }
        select.appendChild(opt);
      });
    };

    const selectedIdsFromSelect = (select) => {
      if (!select) return [];
      return Array.from(select.selectedOptions).map((o) => parseInt(o.value || '0', 10)).filter((n) => Number.isFinite(n) && n > 0);
    };

    const filterOptionsByDepartments = (select, departamentoIds) => {
      if (!select) return;
      const set = new Set(departamentoIds.map((n) => String(n)));
      Array.from(select.options).forEach((opt) => {
        const depId = String(opt.getAttribute('data-departamento-id') || '0');
        const isPlaceholder = opt.value === '0';
        const visible = isPlaceholder || set.size === 0 || set.has(depId);
        opt.hidden = opt.hidden || !visible;
        opt.disabled = opt.disabled || !visible;
      });
    };

    const filterOptionsBySetores = (select, setorIds, departamentoIds) => {
      if (!select) return;
      const setorSet = new Set(setorIds.map((n) => String(n)));
      const depSet = new Set(departamentoIds.map((n) => String(n)));
      Array.from(select.options).forEach((opt) => {
        const setorId = String(opt.getAttribute('data-setor-id') || '0');
        const depId = String(opt.getAttribute('data-departamento-id') || '0');
        const isPlaceholder = opt.value === '0';
        let visible = true;
        if (setorSet.size > 0) {
          visible = setorSet.has(setorId);
        } else if (depSet.size > 0) {
          visible = depSet.has(depId);
        }
        visible = isPlaceholder || visible;
        opt.hidden = opt.hidden || !visible;
        opt.disabled = opt.disabled || !visible;
      });
    };

    const dropInvalidSelections = (select) => {
      const selected = Array.from(select.selectedOptions);
      selected.forEach((opt) => {
        if (opt.disabled || opt.hidden) {
          opt.selected = false;
        }
      });
    };

    const ensureDepartamentoValid = () => {
      const opt = departamento.options[departamento.selectedIndex];
      if (!opt || opt.value === '0') return;
      if (opt.disabled || opt.hidden) {
        departamento.value = '0';
      }
    };

    const apply = () => {
      ensureDepartamentoValid();

      const depIds = selectedIdsFromSelect(departamentosFiltro);
      filterOptionsByDepartments(setores, depIds);

      const setorIds = selectedIdsFromSelect(setores);
      filterOptionsBySetores(funcoes, setorIds, depIds);

      dropInvalidSelections(setores);
      dropInvalidSelections(funcoes);
    };

    const loadCatalog = async () => {
      const clientId = normalizeClientId();
      if (!catalogEndpoint || clientId <= 0) {
        buildOptions(departamento, [], { placeholder: true });
        buildOptions(departamentosFiltro, []);
        buildOptions(setores, [], { attrs: [{ key: 'departamento_id', name: 'data-departamento-id' }] });
        buildOptions(funcoes, [], { attrs: [{ key: 'setor_id', name: 'data-setor-id' }, { key: 'departamento_id', name: 'data-departamento-id' }] });
        apply();
        return;
      }

      try {
        const response = await fetch(catalogEndpoint + '&cliente_id=' + encodeURIComponent(String(clientId)), {
          headers: {
            'Accept': 'application/json'
          }
        });
        const payload = await response.json();
        const catalogo = payload && payload.ok ? (payload.catalogo || {}) : {};
        buildOptions(departamento, catalogo.departamentos || [], { placeholder: true });
        buildOptions(departamentosFiltro, catalogo.departamentos || []);
        buildOptions(setores, catalogo.setores || [], {
          attrs: [{ key: 'departamento_id', name: 'data-departamento-id' }]
        });
        buildOptions(funcoes, catalogo.funcoes || [], {
          attrs: [
            { key: 'setor_id', name: 'data-setor-id' },
            { key: 'departamento_id', name: 'data-departamento-id' }
          ]
        });
        apply();
      } catch (error) {
        console.error('Falha ao carregar catálogo de treinamentos.', error);
      }
    };

    cliente.addEventListener('change', loadCatalog);
    if (departamentosFiltro) {
      departamentosFiltro.addEventListener('change', apply);
    }
    setores.addEventListener('change', apply);
    loadCatalog();
  })();
</script>
