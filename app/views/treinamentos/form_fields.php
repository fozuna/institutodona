<?php
  /** @var array $clientes */ /** @var string $catalogoEndpoint */
  // Item 14: ao editar um treinamento cujos setor_ids/funcao_ids salvos pertencem a
  // departamentos DIFERENTES do departamento_id principal (multi-departamento, regra
  // mantida desde o commit 7691b79), pré-selecionamos esses departamentos extras aqui
  // no campo "Departamentos adicionais" para que o filtro visual (JS) parta já
  // incluindo-os - senao o apply() no carregamento da pagina esconderia (e a UI
  // acabaria descasando ao salvar) selecoes legitimas de outros departamentos.
  $principalDepartamentoId = (int)($values['departamento_id'] ?? 0);
  $setorDepartamentoMap = [];
  foreach (($setores ?? []) as $s) {
    $setorDepartamentoMap[(int)($s['id'] ?? 0)] = (int)($s['departamento_id'] ?? 0);
  }
  $funcaoDepartamentoMap = [];
  foreach (($funcoes ?? []) as $f) {
    $funcaoDepartamentoMap[(int)($f['id'] ?? 0)] = (int)($f['departamento_id'] ?? 0);
  }
  $extraDepartamentoIds = [];
  foreach ((array)($values['setor_ids'] ?? []) as $sid) {
    $depId = $setorDepartamentoMap[(int)$sid] ?? 0;
    if ($depId > 0 && $depId !== $principalDepartamentoId) {
      $extraDepartamentoIds[$depId] = true;
    }
  }
  foreach ((array)($values['funcao_ids'] ?? []) as $fid) {
    $depId = $funcaoDepartamentoMap[(int)$fid] ?? 0;
    if ($depId > 0 && $depId !== $principalDepartamentoId) {
      $extraDepartamentoIds[$depId] = true;
    }
  }
  $extraDepartamentoIds = array_keys($extraDepartamentoIds);
?>
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
    <p class="text-xs text-gray-500 mt-1 hidden" id="treinamentosCatalogoStatus"></p>
  </div>
  <div>
    <label class="block text-sm">Departamentos adicionais (opcional)</label>
    <select multiple size="4" class="border rounded p-2 w-full" id="treinamentosDepartamentosFiltro">
      <?php foreach ($departamentos as $departamento): ?>
        <option value="<?= (int)$departamento['id'] ?>"
                <?= in_array((int)$departamento['id'], $extraDepartamentoIds, true) ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)($departamento['label'] ?? $departamento['nome'] ?? '')) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Utilize apenas quando o treinamento também se aplicar a setores ou funções de outros departamentos da mesma empresa.</p>
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
    const catalogStatus = document.getElementById('treinamentosCatalogoStatus');
    const catalogEndpoint = root.getAttribute('data-catalog-endpoint') || '';
    if (!cliente || !departamento || !setores || !funcoes) return;
    let activeRequest = 0;

    const normalizeClientId = () => {
      const id = parseInt(cliente.value || '0', 10);
      return Number.isFinite(id) ? id : 0;
    };

    const normalizeSelectedId = (select) => {
      if (!select) return 0;
      const id = parseInt(select.value || '0', 10);
      return Number.isFinite(id) && id > 0 ? id : 0;
    };

    const buildOptions = (select, items, config = {}) => {
      if (!select) return;
      const {
        placeholder = false,
        labelKey = 'label',
        attrs = [],
        // Item 14: ao trocar de Empresa, a troca de catalogo NAO deve preservar
        // selecoes antigas (departamento/setores/funcoes de OUTRA empresa nao fazem
        // mais sentido). preserve=true e usado apenas no carregamento inicial da
        // pagina, para reconstituir corretamente os vinculos de um treinamento em
        // edicao (a partir das options ja marcadas "selected" no HTML renderizado
        // pelo servidor).
        preserve = true,
      } = config;
      const selected = preserve
        ? new Set(Array.from(select.selectedOptions).map((opt) => String(opt.value || '')))
        : new Set();
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

    const setCatalogStatus = (message, type = 'info') => {
      if (!catalogStatus) return;
      catalogStatus.textContent = message || '';
      catalogStatus.className = 'text-xs mt-1';
      if (!message) {
        catalogStatus.classList.add('hidden');
        return;
      }
      if (type === 'error') {
        catalogStatus.classList.add('text-red-600');
      } else {
        catalogStatus.classList.add('text-gray-500');
      }
    };

    const setCatalogLoading = (loading) => {
      departamento.disabled = loading;
      if (departamentosFiltro) departamentosFiltro.disabled = loading;
      setores.disabled = loading;
      funcoes.disabled = loading;
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
        opt.hidden = !visible;
        opt.disabled = !visible;
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
        opt.hidden = !visible;
        opt.disabled = !visible;
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

    // Item 14: o conjunto de departamentos que filtra Setores/Funcoes e a UNIAO do
    // Departamento principal (campo obrigatorio, agora participa do filtro visual)
    // com os "Departamentos adicionais" (campo opcional multi-select, para o caso
    // legitimo - mantido desde o commit 7691b79 - de um treinamento se aplicar a
    // setores/funcoes de mais de um departamento da mesma empresa).
    const activeDepartamentoIds = () => {
      const ids = selectedIdsFromSelect(departamentosFiltro);
      const principalId = normalizeSelectedId(departamento);
      if (principalId > 0) ids.push(principalId);
      return Array.from(new Set(ids));
    };

    const apply = () => {
      ensureDepartamentoValid();

      const depIds = activeDepartamentoIds();
      filterOptionsByDepartments(setores, depIds);
      // Remove selecoes de Setor que ficaram incompativeis ANTES de calcular o filtro
      // de Funcoes - senao uma Funcao ligada a um Setor recem-invalidado continuaria
      // visivel/selecionada por mais um ciclo (ID antigo "escondido" mas ainda no
      // formulario), o que o Item 14 pede explicitamente para nao acontecer.
      dropInvalidSelections(setores);

      const setorIds = selectedIdsFromSelect(setores);
      filterOptionsBySetores(funcoes, setorIds, depIds);
      dropInvalidSelections(funcoes);
    };

    // preserve=true (padrao): mantem as selecoes atuais dos <select> ao reconstruir as
    // options (usado no carregamento inicial da pagina, para nao perder os vinculos de
    // um treinamento em edicao). preserve=false: reconstroi tudo "zerado" - usado
    // quando a propria Empresa muda, pois Departamento/Setores/Funcoes da empresa
    // anterior nao tem mais sentido nenhum (Item 14, regra de troca de Empresa).
    const loadCatalog = async (preserve = true) => {
      const clientId = normalizeClientId();
      if (!catalogEndpoint || clientId <= 0) {
        setCatalogLoading(false);
        setCatalogStatus('');
        buildOptions(departamento, [], { placeholder: true, preserve });
        buildOptions(departamentosFiltro, [], { preserve });
        buildOptions(setores, [], { attrs: [{ key: 'departamento_id', name: 'data-departamento-id' }], preserve });
        buildOptions(funcoes, [], { attrs: [{ key: 'setor_id', name: 'data-setor-id' }, { key: 'departamento_id', name: 'data-departamento-id' }], preserve });
        apply();
        return;
      }

      const requestId = ++activeRequest;
      setCatalogLoading(true);
      setCatalogStatus('Carregando departamentos da empresa selecionada...');

      try {
        const url = new URL(catalogEndpoint, window.location.href);
        url.searchParams.set('cliente_id', String(clientId));
        const response = await fetch(url.toString(), {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!response.ok) {
          throw new Error('Falha ao carregar o catálogo da empresa selecionada.');
        }
        const payload = await response.json();
        if (requestId !== activeRequest) {
          return;
        }
        if (!payload || !payload.ok) {
          throw new Error((payload && payload.message) ? payload.message : 'Falha ao carregar o catálogo da empresa selecionada.');
        }
        const catalogo = payload && payload.ok ? (payload.catalogo || {}) : {};
        buildOptions(departamento, catalogo.departamentos || [], { placeholder: true, preserve });
        buildOptions(departamentosFiltro, catalogo.departamentos || [], { preserve });
        buildOptions(setores, catalogo.setores || [], {
          attrs: [{ key: 'departamento_id', name: 'data-departamento-id' }],
          preserve
        });
        buildOptions(funcoes, catalogo.funcoes || [], {
          attrs: [
            { key: 'setor_id', name: 'data-setor-id' },
            { key: 'departamento_id', name: 'data-departamento-id' }
          ],
          preserve
        });
        apply();
        setCatalogStatus('');
      } catch (error) {
        console.error('Falha ao carregar catálogo de treinamentos.', error);
        buildOptions(departamento, [], { placeholder: true, preserve });
        buildOptions(departamentosFiltro, [], { preserve });
        buildOptions(setores, [], { attrs: [{ key: 'departamento_id', name: 'data-departamento-id' }], preserve });
        buildOptions(funcoes, [], { attrs: [{ key: 'setor_id', name: 'data-setor-id' }, { key: 'departamento_id', name: 'data-departamento-id' }], preserve });
        apply();
        setCatalogStatus('Não foi possível carregar os departamentos da empresa selecionada.', 'error');
      } finally {
        if (requestId === activeRequest) {
          setCatalogLoading(false);
        }
      }
    };

    cliente.addEventListener('change', () => loadCatalog(false));
    departamento.addEventListener('change', apply);
    if (departamentosFiltro) {
      departamentosFiltro.addEventListener('change', apply);
    }
    setores.addEventListener('change', apply);
    loadCatalog(true);
  })();
</script>
