<?php /** @var array $item */ /** @var array $linked */ /** @var array $availableColaboradores */ /** @var array $eligibleRows */ /** @var array $eligibleFilters */ /** @var array $agendas */ /** @var array $pendingParticipants */ /** @var array $alerts */ /** @var array $unidades */ /** @var array $usuarios */ /** @var array $setores */ /** @var array $funcoes */ /** @var array $statusAtualOptions */ ?>
<div class="p-6 space-y-6">
  <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($item['nome']) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars(($item['unidade_nome'] ?? '') . ' • ' . ($item['departamento_nome'] ?? '')) ?></p>
      <p class="text-xs text-gray-500 mt-1">Tipo: <?= htmlspecialchars((string)($item['tipo_treinamento'] ?? 'Não informado')) ?> • Periodicidade: <?= htmlspecialchars(\App\Models\TreinamentoModel::periodicidadeOptions()[$item['periodicidade'] ?? 'avulso'] ?? 'Avulso') ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/index">Voltar</a>
      <a class="px-4 py-2 rounded bg-brand-red text-white" href="index.php?route=treinamentos/edit&id=<?= (int)$item['id'] ?>">Editar</a>
      <a class="px-4 py-2 rounded bg-blue-100 text-blue-700" href="index.php?route=treinamentos/dashboard">Dashboard</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Carga horária</div><div class="text-2xl font-bold text-brand-black"><?= htmlspecialchars((string)($item['carga_horaria'] ?? '0')) ?>h</div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Elegíveis filtrados</div><div class="text-2xl font-bold text-brand-black"><?= count($eligibleRows) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Vínculos ativos</div><div class="text-2xl font-bold text-brand-black"><?= count($linked) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Agendamentos</div><div class="text-2xl font-bold text-brand-black"><?= count($agendas) ?></div></div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
      <div class="bg-white shadow rounded p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
          <div>
            <h2 class="text-lg font-semibold">Dados do Treinamento</h2>
            <p class="text-sm text-gray-500">Template de certificado, escopo do público e dados operacionais.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <a class="px-3 py-2 rounded bg-red-100 text-red-700" id="treinamentosExportPdf" href="index.php?route=treinamentos/export_elegiveis&id=<?= (int)$item['id'] ?>&format=pdf">Exportar PDF</a>
            <a class="px-3 py-2 rounded bg-emerald-100 text-emerald-700" id="treinamentosExportXlsx" href="index.php?route=treinamentos/export_elegiveis&id=<?= (int)$item['id'] ?>&format=xlsx">Exportar XLSX</a>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div><span class="text-gray-500">Objetivo:</span><div><?= nl2br(htmlspecialchars((string)($item['objetivo'] ?? '—'))) ?></div></div>
          <div><span class="text-gray-500">Público:</span><div><?= htmlspecialchars((string)($item['publico'] ?? '—')) ?></div></div>
          <div><span class="text-gray-500">Fornecedor:</span><div><?= htmlspecialchars((string)($item['fornecedor'] ?? '—')) ?></div></div>
          <div><span class="text-gray-500">Assinatura:</span><div><?= htmlspecialchars((string)($item['assinatura_responsavel'] ?? '—')) ?></div></div>
          <div class="md:col-span-2"><span class="text-gray-500">Template do Certificado:</span><div><?= nl2br(htmlspecialchars((string)($item['template_certificado'] ?? 'Template padrão do sistema.'))) ?></div></div>
        </div>
      </div>

      <div class="bg-white shadow rounded p-5 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">Lista de Elegíveis e Pré-Cadastro</h2>
            <p class="text-sm text-gray-500">Filtros avançados para exportação e seleção de participantes elegíveis.</p>
          </div>
        </div>

        <?php
          $selectedDepartamentos = array_values(array_unique(array_map('intval', (array)($eligibleFilters['departamento_ids'] ?? []))));
          $selectedSetores = array_values(array_unique(array_map('intval', (array)($eligibleFilters['setor_ids'] ?? []))));
          $selectedFuncoes = array_values(array_unique(array_map('intval', (array)($eligibleFilters['funcao_ids'] ?? []))));
          $defaultSetores = !empty($selectedSetores) ? $selectedSetores : array_values(array_unique(array_map('intval', (array)($item['setor_ids'] ?? []))));
          $defaultFuncoes = !empty($selectedFuncoes) ? $selectedFuncoes : array_values(array_unique(array_map('intval', (array)($item['funcao_ids'] ?? []))));
        ?>
        <form method="get" action="index.php" class="space-y-4" id="treinamentosEligibleForm">
          <input type="hidden" name="route" value="treinamentos/show" />
          <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
          <div class="grid grid-cols-1 xl:grid-cols-12 gap-3">
            <div class="xl:col-span-4">
              <label class="block text-sm">Busca</label>
              <input type="text" name="q" value="<?= htmlspecialchars((string)($eligibleFilters['q'] ?? '')) ?>" class="border rounded p-2 w-full" placeholder="Nome, e-mail, matrícula ou CPF" />
            </div>
            <div class="xl:col-span-2">
              <label class="block text-sm">Admissão Inicial</label>
              <input type="date" name="data_admissao_inicio" value="<?= htmlspecialchars((string)($eligibleFilters['data_admissao_inicio'] ?? '')) ?>" class="border rounded p-2 w-full" />
            </div>
            <div class="xl:col-span-2">
              <label class="block text-sm">Admissão Final</label>
              <input type="date" name="data_admissao_fim" value="<?= htmlspecialchars((string)($eligibleFilters['data_admissao_fim'] ?? '')) ?>" class="border rounded p-2 w-full" />
            </div>
            <div class="xl:col-span-2">
              <label class="block text-sm">Tempo de empresa (mín. meses)</label>
              <input type="number" min="0" step="1" name="tempo_meses_min" value="<?= (int)($eligibleFilters['tempo_meses_min'] ?? 0) ?>" class="border rounded p-2 w-full" />
            </div>
            <div class="xl:col-span-2">
              <label class="block text-sm">Tempo de empresa (máx. meses)</label>
              <input type="number" min="0" step="1" name="tempo_meses_max" value="<?= (int)($eligibleFilters['tempo_meses_max'] ?? 0) ?>" class="border rounded p-2 w-full" />
            </div>
            <div class="xl:col-span-2">
              <label class="block text-sm">Status Atual</label>
              <select name="status_atual" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <?php foreach ($statusAtualOptions as $status): ?>
                  <option value="<?= htmlspecialchars($status) ?>" <?= (($eligibleFilters['status_atual'] ?? '') === $status) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($status)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="xl:col-span-2">
              <label class="block text-sm">Elegibilidade</label>
              <select name="status_elegibilidade" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <option value="Elegivel" <?= (($eligibleFilters['status_elegibilidade'] ?? '') === 'Elegivel') ? 'selected' : '' ?>>Elegível</option>
                <option value="Inelegivel" <?= (($eligibleFilters['status_elegibilidade'] ?? '') === 'Inelegivel') ? 'selected' : '' ?>>Inelegível</option>
              </select>
            </div>
            <div class="xl:col-span-3">
              <label class="block text-sm">Liderança</label>
              <select name="lideranca" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <option value="sim" <?= (($eligibleFilters['lideranca'] ?? '') === 'sim') ? 'selected' : '' ?>>Líder</option>
                <option value="nao" <?= (($eligibleFilters['lideranca'] ?? '') === 'nao') ? 'selected' : '' ?>>Não líder</option>
              </select>
            </div>
            <div class="xl:col-span-3">
              <label class="block text-sm">Histórico de Treinamentos</label>
              <select name="historico" class="border rounded p-2 w-full" id="treinamentosHistorico">
                <option value="">Todos</option>
                <option value="nunca" <?= (($eligibleFilters['historico'] ?? '') === 'nunca') ? 'selected' : '' ?>>Nunca treinou</option>
                <option value="ja" <?= (($eligibleFilters['historico'] ?? '') === 'ja') ? 'selected' : '' ?>>Já treinou</option>
                <option value="dias" <?= (($eligibleFilters['historico'] ?? '') === 'dias') ? 'selected' : '' ?>>Treinou nos últimos N dias</option>
              </select>
            </div>
            <div class="xl:col-span-2">
              <label class="block text-sm">Últimos (dias)</label>
              <input type="number" min="1" step="1" name="historico_dias" value="<?= (int)($eligibleFilters['historico_dias'] ?? 0) ?>" class="border rounded p-2 w-full" id="treinamentosHistoricoDias" />
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div>
              <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Departamentos</div>
                <div class="flex items-center gap-2">
                  <button type="button" class="text-xs text-brand-brown underline" data-cascade-select-all="dep">Selecionar todos</button>
                  <button type="button" class="text-xs text-brand-brown underline" data-cascade-clear-all="dep">Limpar</button>
                </div>
              </div>
              <input type="text" class="border rounded p-2 w-full mt-2 text-sm" placeholder="Buscar..." data-cascade-search="dep" />
              <div class="mt-2 border rounded p-2 max-h-[220px] overflow-auto space-y-2" data-cascade-list="dep">
                <?php foreach (($departamentos ?? []) as $dep): ?>
                  <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="departamento_ids[]" value="<?= (int)$dep['id'] ?>" data-dep-id="<?= (int)$dep['id'] ?>" <?= in_array((int)$dep['id'], $selectedDepartamentos, true) ? 'checked' : '' ?> />
                    <span class="truncate" title="<?= htmlspecialchars((string)$dep['nome']) ?>"><?= htmlspecialchars((string)$dep['nome']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div class="text-xs text-gray-500 mt-1" data-cascade-count="dep"></div>
            </div>

            <div>
              <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Setores</div>
                <div class="flex items-center gap-2">
                  <button type="button" class="text-xs text-brand-brown underline" data-cascade-select-all="set">Selecionar todos</button>
                  <button type="button" class="text-xs text-brand-brown underline" data-cascade-clear-all="set">Limpar</button>
                </div>
              </div>
              <input type="text" class="border rounded p-2 w-full mt-2 text-sm" placeholder="Buscar..." data-cascade-search="set" />
              <div class="mt-2 border rounded p-2 max-h-[220px] overflow-auto space-y-2" data-cascade-list="set">
                <?php foreach (($setores ?? []) as $setor): ?>
                  <?php $setorId = (int)$setor['id']; ?>
                  <label class="flex items-center gap-2 text-sm" data-setor-row data-dep-id="<?= (int)($setor['departamento_id'] ?? 0) ?>">
                    <input type="checkbox" name="setor_ids[]" value="<?= $setorId ?>" data-setor-id="<?= $setorId ?>" <?= (!empty($defaultSetores) && in_array($setorId, $defaultSetores, true)) ? 'checked' : '' ?> />
                    <span class="truncate" title="<?= htmlspecialchars((string)$setor['nome']) ?>"><?= htmlspecialchars((string)$setor['departamento_nome'] . ' • ' . $setor['nome']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div class="text-xs text-gray-500 mt-1" data-cascade-count="set"></div>
            </div>

            <div>
              <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Funções / Cargos</div>
                <div class="flex items-center gap-2">
                  <button type="button" class="text-xs text-brand-brown underline" data-cascade-select-all="fun">Selecionar todos</button>
                  <button type="button" class="text-xs text-brand-brown underline" data-cascade-clear-all="fun">Limpar</button>
                </div>
              </div>
              <input type="text" class="border rounded p-2 w-full mt-2 text-sm" placeholder="Buscar..." data-cascade-search="fun" />
              <div class="mt-2 border rounded p-2 max-h-[220px] overflow-auto space-y-2" data-cascade-list="fun">
                <?php foreach (($funcoes ?? []) as $funcao): ?>
                  <?php $funcaoId = (int)$funcao['id']; ?>
                  <label class="flex items-center gap-2 text-sm" data-funcao-row data-setor-id="<?= (int)($funcao['setor_id'] ?? 0) ?>" data-dep-id="<?= (int)($funcao['departamento_id'] ?? 0) ?>">
                    <input type="checkbox" name="funcao_ids[]" value="<?= $funcaoId ?>" data-funcao-id="<?= $funcaoId ?>" <?= (!empty($defaultFuncoes) && in_array($funcaoId, $defaultFuncoes, true)) ? 'checked' : '' ?> />
                    <span class="truncate" title="<?= htmlspecialchars((string)$funcao['nome']) ?>"><?= htmlspecialchars((string)($funcao['setor_nome'] ?? '') . ' • ' . $funcao['nome']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div class="text-xs text-gray-500 mt-1" data-cascade-count="fun"></div>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit" id="treinamentosEligibleApply">Aplicar Filtros</button>
            <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/show&id=<?= (int)$item['id'] ?>">Limpar</a>
            <div class="text-xs text-gray-500 flex items-center gap-2" id="treinamentosEligibleStatus" style="display:none;">
              <span class="inline-block w-2 h-2 rounded-full bg-brand-red"></span>
              <span>Atualizando…</span>
            </div>
            <div class="text-xs text-gray-500" id="treinamentosEligibleCount"></div>
          </div>
        </form>

        <form method="post" action="index.php?route=treinamentos/add_colaboradores" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="treinamento_id" value="<?= (int)$item['id'] ?>" />
          <div class="overflow-auto max-h-[420px] border rounded">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 sticky top-0">
                <tr class="border-b text-left">
                  <th class="p-2">Selecionar</th>
                  <th class="p-2">Nome</th>
                  <th class="p-2">Matrícula</th>
                  <th class="p-2">Setor</th>
                  <th class="p-2">Cargo</th>
                  <th class="p-2">CPF</th>
                  <th class="p-2">E-mail</th>
                  <th class="p-2">Status</th>
                  <th class="p-2">Elegibilidade</th>
                </tr>
              </thead>
              <tbody id="treinamentosEligibleTbody">
                <?php $eligibleRows = $eligibleRows ?? []; require __DIR__ . '/_eligible_rows.php'; ?>
              </tbody>
            </table>
          </div>
          <div class="text-xs text-gray-500">A emissão antecipada de certificado só será possível depois que o colaborador for incluído na lista pré-cadastrada do agendamento.</div>
          <div class="flex flex-col sm:flex-row gap-2">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Atualizar Pré-Cadastro de Colaboradores</button>
            <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" id="treinamentosExportSelecionados">Exportar Selecionados (CSV)</button>
            <button class="px-4 py-2 rounded bg-blue-100 text-blue-700" type="button" id="treinamentosRhSync">Sincronizar RH</button>
          </div>
        </form>

        <script>
          (function () {
            const form = document.getElementById('treinamentosEligibleForm');
            const tbody = document.getElementById('treinamentosEligibleTbody');
            const status = document.getElementById('treinamentosEligibleStatus');
            const count = document.getElementById('treinamentosEligibleCount');
            const hist = document.getElementById('treinamentosHistorico');
            const histDias = document.getElementById('treinamentosHistoricoDias');
            const btnExport = document.getElementById('treinamentosExportSelecionados');
            const btnRh = document.getElementById('treinamentosRhSync');
            const exportPdfLink = document.getElementById('treinamentosExportPdf');
            const exportXlsxLink = document.getElementById('treinamentosExportXlsx');
            if (!form || !tbody) return;

            function setLoading(on) {
              if (!status) return;
              status.style.display = on ? 'flex' : 'none';
            }

            function selectedValues(selector) {
              return Array.from(form.querySelectorAll(selector)).filter((el) => el && el.checked).map((el) => String(el.value));
            }

            function syncHistoricoDias() {
              if (!hist || !histDias) return;
              const v = String(hist.value || '');
              histDias.disabled = v !== 'dias';
              histDias.classList.toggle('opacity-50', v !== 'dias');
              histDias.classList.toggle('cursor-not-allowed', v !== 'dias');
              if (v !== 'dias') histDias.value = '';
            }

            function filterList(kind, predicate) {
              const list = form.querySelector('[data-cascade-list="' + kind + '"]');
              if (!list) return;
              list.querySelectorAll('label').forEach((row) => {
                const show = predicate(row);
                row.style.display = show ? '' : 'none';
                const input = row.querySelector('input[type="checkbox"]');
                if (input) {
                  input.disabled = !show;
                  if (!show) input.checked = false;
                }
              });
            }

            function applyCascade() {
              const depIds = new Set(selectedValues('input[data-dep-id]'));
              filterList('set', (row) => {
                const dep = String(row.getAttribute('data-dep-id') || '');
                return depIds.size === 0 || depIds.has(dep);
              });
              const setorIds = new Set(selectedValues('input[data-setor-id]'));
              filterList('fun', (row) => {
                const setor = String(row.getAttribute('data-setor-id') || '');
                const dep = String(row.getAttribute('data-dep-id') || '');
                if (setorIds.size > 0) return setorIds.has(setor);
                return depIds.size === 0 || depIds.has(dep);
              });

              const depCount = selectedValues('input[name="departamento_ids[]"]').length;
              const setorCount = selectedValues('input[name="setor_ids[]"]').length;
              const funCount = selectedValues('input[name="funcao_ids[]"]').length;
              const depBox = form.querySelector('[data-cascade-count="dep"]');
              const setBox = form.querySelector('[data-cascade-count="set"]');
              const funBox = form.querySelector('[data-cascade-count="fun"]');
              if (depBox) depBox.textContent = depCount ? (depCount + ' selecionado(s)') : 'Todos os departamentos (sem filtro).';
              if (setBox) setBox.textContent = setorCount ? (setorCount + ' selecionado(s)') : 'Todos os setores visíveis (sem filtro).';
              if (funBox) funBox.textContent = funCount ? (funCount + ' selecionado(s)') : 'Todas as funções visíveis (sem filtro).';
            }

            function applySearch(kind, value) {
              const list = form.querySelector('[data-cascade-list="' + kind + '"]');
              if (!list) return;
              const q = String(value || '').trim().toLowerCase();
              list.querySelectorAll('label').forEach((row) => {
                if (row.style.display === 'none') return;
                const text = (row.textContent || '').toLowerCase();
                row.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
              });
            }

            function attachSearch(kind) {
              const input = form.querySelector('[data-cascade-search="' + kind + '"]');
              if (!input) return;
              input.addEventListener('input', () => {
                applyCascade();
                applySearch(kind, input.value);
              });
            }

            ['dep','set','fun'].forEach(attachSearch);

            form.addEventListener('click', (e) => {
              const t = e.target;
              const selAll = t && t.getAttribute ? t.getAttribute('data-cascade-select-all') : null;
              const clrAll = t && t.getAttribute ? t.getAttribute('data-cascade-clear-all') : null;
              if (selAll) {
                e.preventDefault();
                const list = form.querySelector('[data-cascade-list="' + selAll + '"]');
                if (list) {
                  list.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
                    if (!cb.disabled) cb.checked = true;
                  });
                }
                applyCascade();
                return;
              }
              if (clrAll) {
                e.preventDefault();
                const list = form.querySelector('[data-cascade-list="' + clrAll + '"]');
                if (list) {
                  list.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
                }
                applyCascade();
              }
            });

            form.addEventListener('change', (e) => {
              const t = e.target;
              if (t && t.matches && (t.matches('input[type="checkbox"][data-dep-id]') || t.matches('input[type="checkbox"][data-setor-id]') || t.matches('input[type="checkbox"][data-funcao-id]'))) {
                applyCascade();
              }
              if (t === hist) syncHistoricoDias();
            });

            function updateUrlFromForm() {
              const url = new URL(window.location.href);
              const params = new URLSearchParams(new FormData(form));
              url.search = params.toString();
              window.history.replaceState({}, '', url.toString());
            }

            function syncExportLinks() {
              const params = new URLSearchParams(new FormData(form));
              params.set('id', '<?= (int)$item['id'] ?>');
              params.set('route', 'treinamentos/export_elegiveis');
              if (exportPdfLink) {
                const p = new URLSearchParams(params);
                p.set('format', 'pdf');
                exportPdfLink.href = 'index.php?' + p.toString();
              }
              if (exportXlsxLink) {
                const p2 = new URLSearchParams(params);
                p2.set('format', 'xlsx');
                exportXlsxLink.href = 'index.php?' + p2.toString();
              }
            }

            async function fetchEligible() {
              setLoading(true);
              const params = new URLSearchParams(new FormData(form));
              params.set('route', 'treinamentos/eligible_ajax');
              try {
                const res = await fetch('index.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const payload = await res.json();
                if (!payload || !payload.ok) {
                  throw new Error((payload && payload.message) ? payload.message : 'Falha ao atualizar elegíveis.');
                }
                tbody.innerHTML = payload.html || '';
                if (count) count.textContent = 'Elegíveis filtrados: ' + String(payload.count || 0);
                updateUrlFromForm();
                syncExportLinks();
              } catch (err) {
                if (count) count.textContent = (err && err.message) ? err.message : 'Falha ao atualizar elegíveis.';
              } finally {
                setLoading(false);
              }
            }

            form.addEventListener('submit', (e) => {
              e.preventDefault();
              fetchEligible();
            });

            syncHistoricoDias();
            applyCascade();
            if (count) count.textContent = 'Elegíveis filtrados: ' + String(<?= (int)count($eligibleRows ?? []) ?>);
            syncExportLinks();

            function selectedColaboradores() {
              return Array.from(document.querySelectorAll('input[name="colaborador_ids[]"]')).filter((el) => el.checked).map((el) => el.value);
            }

            function postTo(route, fields) {
              const f = document.createElement('form');
              f.method = 'post';
              f.action = 'index.php?route=' + route;
              Object.keys(fields).forEach((k) => {
                const v = fields[k];
                if (Array.isArray(v)) {
                  v.forEach((item) => {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = k + '[]';
                    i.value = String(item);
                    f.appendChild(i);
                  });
                  return;
                }
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = k;
                i.value = String(v);
                f.appendChild(i);
              });
              document.body.appendChild(f);
              f.submit();
            }

            if (btnExport) {
              btnExport.addEventListener('click', () => {
                const ids = selectedColaboradores();
                postTo('treinamentos/export_selecionados', {
                  csrf: '<?= \App\Core\Security::csrfToken() ?>',
                  treinamento_id: '<?= (int)$item['id'] ?>',
                  colaborador_ids: ids,
                });
              });
            }
            if (btnRh) {
              btnRh.addEventListener('click', () => {
                postTo('treinamentos/rh_sync', {
                  csrf: '<?= \App\Core\Security::csrfToken() ?>',
                  treinamento_id: '<?= (int)$item['id'] ?>',
                });
              });
            }
          })();
        </script>
      </div>

      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-4">Agendar Treinamento</h2>
        <form method="post" action="index.php?route=treinamentos/store_agenda" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="treinamento_id" value="<?= (int)$item['id'] ?>" />
          <div>
            <label class="block text-sm">Data / Hora</label>
            <input type="datetime-local" name="data" class="border rounded p-2 w-full" required />
          </div>
          <div>
            <label class="block text-sm">Unidade</label>
            <select name="unidade_id" class="border rounded p-2 w-full" required>
              <?php foreach ($unidades as $unidade): ?>
                <option value="<?= (int)$unidade['id'] ?>" <?= ((int)$item['cliente_id'] === (int)$unidade['id']) ? 'selected' : '' ?>><?= htmlspecialchars($unidade['nome_empresa']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm">Responsável</label>
            <select name="responsavel_id" class="border rounded p-2 w-full">
              <option value="0">—</option>
              <?php foreach ($usuarios as $usuario): ?>
                <option value="<?= (int)$usuario['id'] ?>"><?= htmlspecialchars($usuario['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm">Instrutor</label>
            <input type="text" name="instrutor" class="border rounded p-2 w-full" placeholder="Texto livre" />
          </div>
          <div>
            <label class="block text-sm">Local</label>
            <input type="text" name="local" class="border rounded p-2 w-full" />
          </div>
          <div>
            <label class="block text-sm">Participantes iniciais</label>
            <select name="participante_ids[]" multiple size="5" class="border rounded p-2 w-full">
              <?php foreach ($pendingParticipants as $pending): ?>
                <option value="<?= (int)$pending['colaborador_id'] ?>"><?= htmlspecialchars($pending['colaborador_nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm">Observações</label>
            <textarea name="observacoes" class="border rounded p-2 w-full" rows="3"></textarea>
          </div>
          <div class="md:col-span-2">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Criar Agendamento</button>
          </div>
        </form>

        <div class="mt-5 overflow-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b text-left">
                <th class="p-2">Data</th>
                <th class="p-2">Unidade</th>
                <th class="p-2">Instrutor</th>
                <th class="p-2">Participantes</th>
                <th class="p-2">Presença</th>
                <th class="p-2">Certificados</th>
                <th class="p-2">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($agendas as $agenda): ?>
                <tr class="border-b">
                  <td class="p-2"><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)$agenda['data'])) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)$agenda['unidade_nome']) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)($agenda['instrutor'] ?: ($agenda['responsavel_nome'] ?? '—'))) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_participantes'] ?? 0) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_presentes'] ?? 0) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_certificados'] ?? 0) ?></td>
                  <td class="p-2">
                    <div class="flex flex-wrap gap-2">
                      <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/presenca&agenda_id=<?= (int)$agenda['id'] ?>">Operar Lista</a>
                      <a class="px-3 py-1 rounded bg-red-100 text-red-700" href="index.php?route=treinamentos/presenca_pdf&agenda_id=<?= (int)$agenda['id'] ?>">PDF Presença</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($agendas)): ?>
                <tr><td colspan="7" class="p-4 text-center text-gray-500">Nenhum agendamento criado até o momento.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="space-y-6">
      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-3">Relacionamentos</h2>
        <div class="text-sm">
          <div class="mb-3">
            <div class="text-gray-500">Setores</div>
            <div><?= empty($item['setores']) ? '—' : htmlspecialchars(implode(', ', array_map(static fn($row) => $row['nome'], $item['setores']))) ?></div>
          </div>
          <div>
            <div class="text-gray-500">Funções</div>
            <div><?= empty($item['funcoes']) ? '—' : htmlspecialchars(implode(', ', array_map(static fn($row) => $row['nome'], $item['funcoes']))) ?></div>
          </div>
        </div>
      </div>

      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-3">Status dos Vínculos</h2>
        <div class="space-y-3 max-h-[360px] overflow-auto">
          <?php foreach ($linked as $row): ?>
            <div class="rounded border p-3">
              <div class="font-medium"><?= htmlspecialchars($row['colaborador_nome']) ?></div>
              <div class="text-sm text-gray-600"><?= htmlspecialchars((string)($row['funcao_nome'] ?? '—')) ?> • <?= htmlspecialchars((string)($row['setor_nome'] ?? '—')) ?></div>
              <div class="flex items-center justify-between mt-2">
                <span class="px-2 py-1 rounded-full text-xs <?= ($row['status'] ?? '') === 'concluido' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>"><?= htmlspecialchars(strtoupper((string)$row['status'])) ?></span>
                <span class="text-xs text-gray-500"><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($row['ultima_conclusao'] ?? ''))) ?: 'Sem conclusão' ?></span>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($linked)): ?>
            <p class="text-sm text-gray-500">Nenhum colaborador vinculado ao treinamento.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-3">Alertas de Vencimento</h2>
        <?php if (empty($alerts)): ?>
          <p class="text-sm text-gray-500">Nenhum alerta ativo.</p>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($alerts as $alert): ?>
              <div class="rounded border p-3 <?= ($alert['alerta'] ?? '') === 'vencido' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' ?>">
                <div class="font-medium"><?= htmlspecialchars($alert['colaborador_nome']) ?></div>
                <div class="text-sm text-gray-700"><?= htmlspecialchars($alert['unidade_nome'] ?? '') ?></div>
                <div class="text-xs text-gray-600 mt-1">
                  <?= ($alert['alerta'] ?? '') === 'vencido'
                    ? 'Treinamento vencido'
                    : 'Próximo do vencimento em ' . (int)($alert['dias_restantes'] ?? 0) . ' dia(s)' ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
