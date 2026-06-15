<?php /** @var array $items */ /** @var array $funcoes */ /** @var int $cliente */ ?>
<?php
$currentUser = $_SESSION['user'] ?? null;
$canClienteShow = \App\Core\AccessControl::canAccessRoute('clientes/show', 'GET', $currentUser);
$canCreate = \App\Core\AccessControl::canAccessRoute('colaboradores/create', 'GET', $currentUser);
$canEdit = \App\Core\AccessControl::canAccessRoute('colaboradores/edit', 'GET', $currentUser);
$canDelete = \App\Core\AccessControl::canAccessRoute('colaboradores/delete', 'POST', $currentUser);
$canImport = \App\Core\AccessControl::canAccessRoute('colaboradores/import', 'POST', $currentUser);
$canDownloadTemplate = \App\Core\AccessControl::canAccessRoute('colaboradores/importTemplate', 'GET', $currentUser);
$backUrl = ($canClienteShow && (int)($cliente ?? 0) > 0)
    ? ('index.php?route=clientes/show&id=' . (int)$cliente)
    : 'index.php?route=colaboradores/index' . ($cliente ? '&cliente=' . (int)$cliente : '');
?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Colaboradores') ?></h1>
        <div class="flex items-center gap-2">
            <?php if ($canCreate): ?>
            <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=colaboradores/create<?= $cliente ? '&cliente='.(int)$cliente : '' ?>">Novo Colaborador</a>
            <?php endif; ?>
            <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
        </div>
    </div>
    <?php if ($canImport): ?>
      <div class="bg-white shadow rounded p-4 mb-4">
        <div class="font-semibold mb-2">Importar colaboradores</div>
        <form id="colabImportForm" method="post" action="index.php?route=colaboradores/import" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="MAX_FILE_SIZE" value="<?= 50 * 1024 * 1024 ?>" />
          <div class="md:col-span-6">
            <label class="text-sm">Arquivo (CSV, XLS, XLSX)</label>
            <input type="file" name="arquivo" id="colabImportFile" class="border rounded p-2 w-full" accept=".csv,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required />
            <div class="text-xs text-gray-500 mt-1">Baixe o modelo padrão para ver todas as colunas aceitas. Documento, Celular e Email são opcionais. Máximo: 50MB.</div>
            <?php if ($canDownloadTemplate): ?>
              <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm w-full sm:w-auto text-center"
                   href="index.php?route=colaboradores/importTemplate&format=xlsx">
                  Baixar modelo (XLSX)
                </a>
                <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm w-full sm:w-auto text-center"
                   href="index.php?route=colaboradores/importTemplate&format=csv">
                  Baixar modelo (CSV)
                </a>
                <div class="text-xs text-gray-500">O XLSX contém uma aba extra com orientações detalhadas.</div>
              </div>
            <?php endif; ?>
          </div>
          <div class="md:col-span-3">
            <label class="text-sm">Pré-visualização</label>
            <div id="colabImportPreviewMeta" class="border rounded p-2 bg-gray-50 text-sm text-gray-700">Nenhum arquivo selecionado</div>
          </div>
          <div class="md:col-span-3 flex gap-2 md:justify-end">
            <button type="button" id="colabImportClear" class="px-4 py-2 rounded bg-gray-200 text-brand-brown w-full md:w-auto" disabled>Remover</button>
            <button type="submit" id="colabImportSubmit" class="px-4 py-2 rounded bg-brand-red text-white w-full md:w-auto" disabled>Importar</button>
          </div>
          <div class="md:col-span-12 hidden" id="colabImportProgressWrap">
            <div class="text-xs text-gray-600 mb-1" id="colabImportProgressText"></div>
            <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
              <div id="colabImportProgressBar" class="bg-brand-red h-2" style="width: 0%"></div>
            </div>
          </div>
          <div class="md:col-span-12 hidden" id="colabImportErrors"></div>
          <div class="md:col-span-12 hidden" id="colabImportCsvPreviewWrap">
            <div class="text-sm font-semibold text-gray-800 mb-2">Prévia do CSV (primeiras 5 linhas)</div>
            <div class="overflow-auto border rounded">
              <table class="min-w-full text-sm" id="colabImportCsvPreview"></table>
            </div>
          </div>
        </form>
      </div>
      <script>
        (function() {
          const form = document.getElementById('colabImportForm');
          const input = document.getElementById('colabImportFile');
          const meta = document.getElementById('colabImportPreviewMeta');
          const clearBtn = document.getElementById('colabImportClear');
          const submitBtn = document.getElementById('colabImportSubmit');
          const errorsBox = document.getElementById('colabImportErrors');
          const progressWrap = document.getElementById('colabImportProgressWrap');
          const progressBar = document.getElementById('colabImportProgressBar');
          const progressText = document.getElementById('colabImportProgressText');
          const csvPreviewWrap = document.getElementById('colabImportCsvPreviewWrap');
          const csvPreviewTable = document.getElementById('colabImportCsvPreview');

          function humanSize(bytes) {
            const n = Number(bytes || 0);
            if (!n) return '0 B';
            const units = ['B','KB','MB','GB'];
            let u = 0, v = n;
            while (v >= 1024 && u < units.length - 1) { v /= 1024; u++; }
            return (v >= 10 || u === 0 ? v.toFixed(0) : v.toFixed(1)) + ' ' + units[u];
          }
          function setErrorHtml(html) {
            setNoticeHtml('error', html);
          }
          function setNoticeHtml(kind, html) {
            if (!errorsBox) return;
            if (!html) {
              errorsBox.classList.add('hidden');
              errorsBox.innerHTML = '';
              return;
            }
            const k = String(kind || 'info');
            if (k === 'success') {
              errorsBox.className = 'md:col-span-12 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800';
            } else if (k === 'info') {
              errorsBox.className = 'md:col-span-12 rounded border border-brand-brown/10 bg-white p-3 text-sm text-brand-brown';
            } else {
              errorsBox.className = 'md:col-span-12 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700';
            }
            errorsBox.innerHTML = html;
            errorsBox.classList.remove('hidden');
          }
          function setProgress(active, pct) {
            if (!progressWrap || !progressBar || !progressText) return;
            if (!active) {
              progressWrap.classList.add('hidden');
              progressBar.style.width = '0%';
              progressText.textContent = '';
              return;
            }
            progressWrap.classList.remove('hidden');
            const p = Math.max(0, Math.min(100, Number(pct || 0)));
            progressBar.style.width = p + '%';
            progressText.textContent = 'Enviando... ' + p.toFixed(0) + '%';
          }
          function setSubmitting(submitting) {
            if (!submitBtn) return;
            submitBtn.disabled = !!submitting;
            submitBtn.classList.toggle('opacity-75', !!submitting);
            submitBtn.classList.toggle('cursor-not-allowed', !!submitting);
            if (clearBtn) clearBtn.disabled = !!submitting || !(input && input.files && input.files.length);
            if (input) input.disabled = !!submitting;
          }
          function clearFile() {
            if (input) input.value = '';
            if (meta) meta.textContent = 'Nenhum arquivo selecionado';
            if (clearBtn) clearBtn.disabled = true;
            if (submitBtn) submitBtn.disabled = true;
            setErrorHtml('');
            if (csvPreviewWrap) csvPreviewWrap.classList.add('hidden');
            if (csvPreviewTable) csvPreviewTable.innerHTML = '';
          }
          function parseCsvPreview(text) {
            const lines = text.split(/\r\n|\n|\r/).filter(l => l.trim() !== '').slice(0, 6);
            if (!lines.length) return [];
            const headerLine = lines[0];
            const semis = (headerLine.match(/;/g) || []).length;
            const commas = (headerLine.match(/,/g) || []).length;
            const delim = semis > commas ? ';' : ',';
            return lines.map(line => {
              const out = [];
              let cur = '';
              let inQ = false;
              for (let i = 0; i < line.length; i++) {
                const ch = line[i];
                if (ch === '\"') {
                  if (inQ && line[i + 1] === '\"') { cur += '\"'; i++; continue; }
                  inQ = !inQ;
                  continue;
                }
                if (!inQ && ch === delim) { out.push(cur); cur = ''; continue; }
                cur += ch;
              }
              out.push(cur);
              return out.map(v => v.trim());
            });
          }
          function renderCsvPreview(rows) {
            if (!csvPreviewWrap || !csvPreviewTable) return;
            if (!rows.length) { csvPreviewWrap.classList.add('hidden'); csvPreviewTable.innerHTML = ''; return; }
            const header = rows[0] || [];
            const body = rows.slice(1);
            let html = '<thead><tr class=\"border-b bg-gray-50\">';
            for (const h of header) {
              html += '<th class=\"p-2 text-left whitespace-nowrap\">' + String(h).replace(/</g,'&lt;') + '</th>';
            }
            html += '</tr></thead><tbody>';
            for (const r of body) {
              html += '<tr class=\"border-b\">';
              for (let i = 0; i < header.length; i++) {
                const v = r[i] || '';
                html += '<td class=\"p-2 whitespace-nowrap\">' + String(v).replace(/</g,'&lt;') + '</td>';
              }
              html += '</tr>';
            }
            html += '</tbody>';
            csvPreviewTable.innerHTML = html;
            csvPreviewWrap.classList.remove('hidden');
          }

          if (input) {
            input.addEventListener('change', function() {
              setErrorHtml('');
              const file = input.files && input.files[0];
              if (!file) { clearFile(); return; }
              if (file.size > (50 * 1024 * 1024)) {
                clearFile();
                setErrorHtml('Arquivo excede o limite de 50MB.');
                return;
              }
              const ext = (file.name.split('.').pop() || '').toLowerCase();
              if (!['csv','xls','xlsx'].includes(ext)) {
                clearFile();
                setErrorHtml('Formato inválido. Use CSV, XLS ou XLSX.');
                return;
              }
              if (meta) meta.textContent = file.name + ' • ' + humanSize(file.size);
              if (clearBtn) clearBtn.disabled = false;
              if (submitBtn) submitBtn.disabled = false;
              if (ext === 'csv') {
                const reader = new FileReader();
                reader.onload = function() {
                  const rows = parseCsvPreview(String(reader.result || ''));
                  renderCsvPreview(rows);
                };
                reader.onerror = function() {
                  renderCsvPreview([]);
                };
                reader.readAsText(file);
              } else {
                renderCsvPreview([]);
              }
            });
          }
          if (clearBtn) {
            clearBtn.addEventListener('click', function() {
              clearFile();
            });
          }
          if (form) {
            form.addEventListener('submit', function(e) {
              e.preventDefault();
              setErrorHtml('');
              const file = input && input.files && input.files[0];
              if (!file) { setErrorHtml('Selecione um arquivo para importar.'); return; }
              const data = new FormData(form);
              setSubmitting(true);
              setProgress(true, 0);
              const xhr = new XMLHttpRequest();
              xhr.open('POST', form.action, true);
              xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
              xhr.upload.addEventListener('progress', function(ev) {
                if (!ev.lengthComputable) return;
                setProgress(true, (ev.loaded / ev.total) * 100);
              });
              xhr.addEventListener('load', function() {
                setSubmitting(false);
                setProgress(false, 0);
                let payload = null;
                try { payload = JSON.parse(xhr.responseText || '{}'); } catch (err) { payload = null; }
                if (xhr.status >= 200 && xhr.status < 300 && payload && payload.ok) {
                  const inserted = Number(payload.inserted || 0);
                  const msg = payload.message ? String(payload.message) : ('Importação concluída. Inseridos: ' + inserted);
                  setNoticeHtml('success', '<div class="font-semibold mb-1">Importação concluída</div><div>' + msg.replace(/</g,'&lt;') + '</div>');
                  if (input) input.value = '';
                  if (meta) meta.textContent = 'Nenhum arquivo selecionado';
                  if (clearBtn) clearBtn.disabled = true;
                  if (submitBtn) submitBtn.disabled = true;
                  const ids = (payload && Array.isArray(payload.cliente_ids)) ? payload.cliente_ids.map((v)=>parseInt(v,10)).filter((n)=>Number.isFinite(n) && n>0) : [];
                  const clienteSelect = document.getElementById('colaboradoresCliente');
                  if (clienteSelect && (!clienteSelect.value || clienteSelect.value === '0' || clienteSelect.value === '') && ids.length === 1) {
                    clienteSelect.value = String(ids[0]);
                    clienteSelect.dispatchEvent(new Event('change', { bubbles: true }));
                  } else {
                    const filterForm = document.getElementById('colaboradoresFilterForm');
                    if (filterForm) {
                      filterForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    } else {
                      window.location.reload();
                    }
                  }
                  return;
                }
                const errs = payload && Array.isArray(payload.errors) ? payload.errors : [];
                if (errs.length) {
                  const max = 200;
                  const show = errs.slice(0, max);
                  let html = '<div class=\"font-semibold mb-1\">Erros encontrados (' + errs.length + ')</div><ul class=\"list-disc pl-5 space-y-1\">';
                  for (const it of show) {
                    const line = it.line || 0;
                    const field = it.field || '';
                    const msg = it.message || 'Erro';
                    html += '<li><span class=\"font-semibold\">Linha ' + line + '</span> • ' + String(field) + ': ' + String(msg).replace(/</g,'&lt;') + '</li>';
                  }
                  html += '</ul>';
                  if (errs.length > max) {
                    html += '<div class=\"mt-2 text-xs text-gray-600\">Mostrando ' + max + ' de ' + errs.length + ' erros.</div>';
                  }
                  setErrorHtml(html);
                } else {
                  const msg = (payload && payload.message) ? payload.message : 'Falha na importação.';
                  setErrorHtml(String(msg).replace(/</g,'&lt;'));
                }
              });
              xhr.addEventListener('error', function() {
                setSubmitting(false);
                setProgress(false, 0);
                setErrorHtml('Erro de rede ao enviar o arquivo.');
              });
              xhr.send(data);
            });
          }
        })();
      </script>
    <?php elseif ($canDownloadTemplate): ?>
      <div class="bg-white shadow rounded p-4 mb-4">
        <div class="font-semibold mb-2">Modelo de importação</div>
        <div class="text-sm text-gray-600">Baixe a planilha modelo padrão para preparar os registros de colaboradores no formato aceito pelo sistema.</div>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm w-full sm:w-auto text-center"
             href="index.php?route=colaboradores/importTemplate&format=xlsx">
            Baixar modelo (XLSX)
          </a>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm w-full sm:w-auto text-center"
             href="index.php?route=colaboradores/importTemplate&format=csv">
            Baixar modelo (CSV)
          </a>
          <div class="text-xs text-gray-500">O XLSX contém uma aba extra com orientações detalhadas.</div>
        </div>
      </div>
    <?php endif; ?>
    <form method="get" action="index.php" id="colaboradoresFilterForm" class="mb-4 grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
        <input type="hidden" name="route" value="colaboradores/index" />
        <div class="md:col-span-4">
          <label class="text-sm">Cliente</label>
          <select name="cliente" id="colaboradoresCliente" class="border rounded p-2 w-full">
            <option value="">-- Selecione --</option>
            <?php foreach ($clientes as $cl): ?>
                <option value="<?= (int)$cl['id'] ?>" <?= ((int)$cliente === (int)$cl['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cl['nome_empresa']) ?> <?= ((int)($cl['is_matriz'] ?? 1) === 1) ? '(Matriz)' : '(Filial)' ?>
                </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($can_all_funcionarios)): ?>
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" name="all_funcionarios" value="1" id="colaboradoresAllFuncionarios" class="form-checkbox h-4 w-4 text-brand-red" <?= !empty($filter_all_funcionarios) ? 'checked' : '' ?> />
              <span>Selecionar todos os funcionários (matriz + filiais)</span>
            </label>
          <?php endif; ?>
        </div>
        <div class="md:col-span-2">
          <label class="text-sm">Unidade</label>
          <select name="unidade_id" id="colaboradoresUnidade" class="border rounded p-2 w-full" <?= !empty($cliente) ? '' : 'disabled' ?>>
              <option value="0">Todas</option>
              <?php foreach (($unidades ?? []) as $u): ?>
                <option value="<?= (int)$u['id'] ?>" <?= ((int)($filter_unidade ?? 0) === (int)$u['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string)($u['nome_empresa'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="text-sm">Líder</label>
          <select name="lider" id="colaboradoresLider" class="border rounded p-2 w-full" <?= !empty($cliente) ? '' : 'disabled' ?>>
              <option value="">Todos</option>
              <option value="sim" <?= (($filter_lider ?? '') === 'sim') ? 'selected' : '' ?>>Sim</option>
              <option value="não" <?= (($filter_lider ?? '') === 'não') ? 'selected' : '' ?>>Não</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="text-sm">Status</label>
          <select name="status" id="colaboradoresStatus" class="border rounded p-2 w-full" <?= !empty($cliente) ? '' : 'disabled' ?>>
              <option value="">Todos</option>
              <?php foreach (($status_options ?? []) as $st): ?>
                <option value="<?= htmlspecialchars((string)$st) ?>" <?= (($filter_status ?? '') === (string)$st) ? 'selected' : '' ?>><?= htmlspecialchars((string)$st) ?></option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="text-sm">Departamento</label>
          <select name="departamento" id="colaboradoresDepartamento" class="border rounded p-2 w-full" <?= !empty($cliente) ? '' : 'disabled' ?>>
              <option value="0">Todos</option>
              <?php foreach ($departamentos as $d): ?>
                  <?php $depLabel = isset($d['cliente']) ? ((string)$d['cliente'] . ' — ' . (string)$d['nome']) : (string)$d['nome']; ?>
                  <option value="<?= (int)$d['id'] ?>" <?= ((int)($filter_departamento ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($depLabel) ?></option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="text-sm">Setor</label>
          <select name="setor" id="colaboradoresSetor" class="border rounded p-2 w-full" <?= !empty($cliente) ? '' : 'disabled' ?>>
              <option value="0">Todos</option>
              <?php foreach (($setores ?? []) as $s): ?>
                  <?php $setLabel = isset($s['departamento']) ? ((string)$s['departamento'] . ' — ' . (string)$s['nome']) : (string)$s['nome']; ?>
                  <option value="<?= (int)$s['id'] ?>" <?= ((int)($filter_setor ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($setLabel) ?></option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-3">
          <label class="text-sm">Função</label>
          <select name="funcao" id="colaboradoresFuncao" class="border rounded p-2 w-full" <?= !empty($cliente) ? '' : 'disabled' ?>>
              <option value="0">Todas</option>
              <?php foreach ($funcoes as $f): ?>
                  <?php
                    $funcLabel = (string)($f['nome'] ?? '');
                    if (isset($f['departamento']) || isset($f['setor'])) {
                        $parts = [];
                        if (!empty($f['departamento'])) { $parts[] = (string)$f['departamento']; }
                        if (!empty($f['setor'])) { $parts[] = (string)$f['setor']; }
                        if ($funcLabel !== '') { $parts[] = $funcLabel; }
                        $funcLabel = implode(' — ', $parts);
                    }
                  ?>
                  <option value="<?= (int)$f['id'] ?>" <?= ((int)($filter_funcao ?? 0) === (int)$f['id']) ? 'selected' : '' ?>><?= htmlspecialchars($funcLabel) ?></option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-1">
          <label class="text-sm">Por página</label>
          <select name="per" id="colaboradoresPer" class="border rounded p-2 w-full">
              <?php foreach ([10,20,50,100] as $opt): ?>
                  <option value="<?= $opt ?>" <?= ((int)($per ?? 20) === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="md:col-span-12 flex flex-col md:flex-row gap-2 md:items-center md:justify-end">
          <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown w-full md:w-auto" type="button" id="colaboradoresClear">Limpar</button>
          <button class="px-4 py-2 rounded bg-brand-red text-white w-full md:w-auto" type="submit" id="colaboradoresSubmit">Filtrar</button>
        </div>
    </form>
    <div id="colaboradoresFilterError" class="hidden mb-3 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Nome</th>
                    <th class="p-3">E-mail</th>
                    <th class="p-3">Unidade</th>
                    <th class="p-3">Função</th>
                    <th class="p-3">Setor</th>
                    <th class="p-3">Departamento</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody id="colaboradoresTableBody">
                <?php if (empty($items)): ?>
                  <tr data-empty-row><td class="p-3 text-sm text-gray-600" colspan="7">Nenhum colaborador encontrado para os filtros selecionados.</td></tr>
                <?php else: ?>
                  <?php foreach ($items as $c): ?>
                      <tr class="border-b">
                          <td class="p-3"><?= htmlspecialchars($c['nome']) ?></td>
                          <td class="p-3"><?= htmlspecialchars($c['email'] ?? '') ?></td>
                          <td class="p-3"><?= htmlspecialchars($c['unidade'] ?? '') ?></td>
                          <td class="p-3"><?= htmlspecialchars($c['funcao'] ?? '') ?></td>
                          <td class="p-3"><?= htmlspecialchars($c['setor'] ?? '') ?></td>
                          <td class="p-3"><?= htmlspecialchars($c['departamento'] ?? '') ?></td>
                          <td class="p-3 whitespace-nowrap">
                              <?php if ($canEdit): ?>
                              <a class="text-brand-pink icon-action" href="index.php?route=colaboradores/edit&id=<?= (int)$c['id'] ?><?= $cliente ? '&cliente='.(int)$cliente : '' ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                              <?php endif; ?>
                              <?php if ($canDelete): ?>
                              <a class="text-brand-brown icon-action ml-2" href="index.php?route=colaboradores/delete&id=<?= (int)$c['id'] ?><?= $cliente ? '&cliente='.(int)$cliente : '' ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                              <?php endif; ?>
                          </td>
                      </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-gray-600" id="colaboradoresSummary">Total: <?= (int)($total ?? 0) ?> • Página <?= (int)($page ?? 1) ?> de <?= (int)($total_pages ?? 1) ?></div>
        <div class="flex items-center gap-2" id="colaboradoresPager">
          <?php if (($total_pages ?? 1) > 1): ?>
              <?php
                  $prev = max(1, (int)($page ?? 1) - 1);
                  $next = min((int)($total_pages ?? 1), (int)($page ?? 1) + 1);
                  $base = [
                      'route' => 'colaboradores/index',
                      'cliente' => (int)$cliente,
                      'per' => (int)($per ?? 20),
                      'lider' => ($filter_lider ?? '') !== '' ? (string)$filter_lider : null,
                      'unidade_id' => (int)($filter_unidade ?? 0) ?: null,
                      'status' => ($filter_status ?? '') !== '' ? (string)$filter_status : null,
                      'departamento' => (int)($filter_departamento ?? 0) ?: null,
                      'setor' => (int)($filter_setor ?? 0) ?: null,
                      'funcao' => (int)($filter_funcao ?? 0) ?: null,
                      'all_funcionarios' => !empty($filter_all_funcionarios) ? '1' : null,
                  ];
                  $prevQuery = http_build_query(array_merge($base, ['page' => $prev]));
                  $nextQuery = http_build_query(array_merge($base, ['page' => $next]));
              ?>
              <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" data-page="<?= (int)$prev ?>" href="index.php?<?= htmlspecialchars($prevQuery) ?>">◀</a>
              <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" data-page="<?= (int)$next ?>" href="index.php?<?= htmlspecialchars($nextQuery) ?>">▶</a>
          <?php endif; ?>
        </div>
    </div>
</div>

<script>
  (function () {
    const form = document.getElementById('colaboradoresFilterForm');
    if (!form) return;

    const cliente = document.getElementById('colaboradoresCliente');
    const all = document.getElementById('colaboradoresAllFuncionarios');
    const unidade = document.getElementById('colaboradoresUnidade');
    const lider = document.getElementById('colaboradoresLider');
    const status = document.getElementById('colaboradoresStatus');
    const departamento = document.getElementById('colaboradoresDepartamento');
    const setor = document.getElementById('colaboradoresSetor');
    const funcao = document.getElementById('colaboradoresFuncao');
    const per = document.getElementById('colaboradoresPer');
    const clearBtn = document.getElementById('colaboradoresClear');
    const tbody = document.getElementById('colaboradoresTableBody');
    const summary = document.getElementById('colaboradoresSummary');
    const pager = document.getElementById('colaboradoresPager');
    const err = document.getElementById('colaboradoresFilterError');

    const setError = (message) => {
      if (!err) return;
      const text = String(message || '').trim();
      if (!text) {
        err.classList.add('hidden');
        err.textContent = '';
        return;
      }
      err.classList.remove('hidden');
      err.textContent = text;
    };

    const getParams = (page) => {
      const p = new URLSearchParams(new FormData(form));
      p.set('route', 'colaboradores/filterAjax');
      p.set('page', String(page || 1));
      return p;
    };

    const buildOptions = (select, options, currentValue, buildLabel) => {
      if (!select) return;
      const cur = String(currentValue ?? select.value ?? '');
      const keepPlaceholder = select.querySelector('option[value="0"], option[value=""]');
      const placeholderHtml = keepPlaceholder ? keepPlaceholder.outerHTML : '';
      let html = placeholderHtml;
      (options || []).forEach((row) => {
        const id = row && (row.id !== undefined) ? String(row.id) : '';
        if (!id) return;
        const label = buildLabel(row);
        html += '<option value="' + String(id).replace(/"/g, '&quot;') + '">' + String(label).replace(/</g, '&lt;') + '</option>';
      });
      select.innerHTML = html;
      const exists = Array.from(select.options).some((o) => o.value === cur);
      select.value = exists ? cur : (keepPlaceholder ? keepPlaceholder.value : '');
    };

    const buildSimpleOptions = (select, values, currentValue) => {
      if (!select) return;
      const cur = String(currentValue ?? select.value ?? '');
      const keepPlaceholder = select.querySelector('option[value=""], option[value="0"]');
      const placeholderHtml = keepPlaceholder ? keepPlaceholder.outerHTML : '';
      let html = placeholderHtml;
      (values || []).forEach((v) => {
        const val = String(v || '').trim();
        if (!val) return;
        html += '<option value="' + val.replace(/"/g, '&quot;') + '">' + val.replace(/</g, '&lt;') + '</option>';
      });
      select.innerHTML = html;
      const exists = Array.from(select.options).some((o) => o.value === cur);
      select.value = exists ? cur : (keepPlaceholder ? keepPlaceholder.value : '');
    };

    const setDisabledDependents = (disabled) => {
      [unidade, lider, status, departamento, setor, funcao].forEach((el) => {
        if (el) el.disabled = !!disabled;
      });
      if (all) all.disabled = !!disabled;
    };

    const buildPager = (page, totalPages) => {
      if (!pager) return;
      pager.innerHTML = '';
      if (!totalPages || totalPages <= 1) return;
      const prev = Math.max(1, page - 1);
      const next = Math.min(totalPages, page + 1);
      const buildHref = (targetPage) => {
        const p = new URLSearchParams(new FormData(form));
        p.set('route', 'colaboradores/index');
        p.set('page', String(targetPage));
        return 'index.php?' + p.toString();
      };
      pager.innerHTML =
        '<a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" data-page="' + prev + '" href="' + buildHref(prev) + '">◀</a>' +
        '<a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" data-page="' + next + '" href="' + buildHref(next) + '">▶</a>';
    };

    const refresh = (page) => {
      const clientId = parseInt((cliente && cliente.value) ? cliente.value : '0', 10) || 0;
      setError('');
      if (!clientId) {
        setDisabledDependents(true);
        if (tbody) tbody.innerHTML = '<tr data-empty-row><td class="p-3 text-sm text-gray-600" colspan="7">Selecione um cliente para listar colaboradores.</td></tr>';
        if (summary) summary.textContent = 'Total: 0 • Página 1 de 1';
        if (pager) pager.innerHTML = '';
        return;
      }

      setDisabledDependents(false);
      const url = 'index.php?' + getParams(page).toString();
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((res) => res.json().then((data) => ({ res, data })))
        .then(({ res, data }) => {
          if (!res.ok || !data || !data.ok) {
            throw new Error((data && data.message) ? data.message : ('Erro ao filtrar (' + res.status + ').'));
          }
          const opts = data.options || {};
          const f = data.filters || {};

          if (unidade) {
            buildOptions(unidade, opts.unidades || [], f.unidade_id || 0, (row) => row.nome_empresa || '');
          }
          if (departamento) {
            buildOptions(departamento, opts.departamentos || [], f.departamento || 0, (row) => row.cliente ? (row.cliente + ' — ' + (row.nome || '')) : (row.nome || ''));
          }
          if (setor) {
            buildOptions(setor, opts.setores || [], f.setor || 0, (row) => row.departamento ? (row.departamento + ' — ' + (row.nome || '')) : (row.nome || ''));
          }
          if (funcao) {
            buildOptions(funcao, opts.funcoes || [], f.funcao || 0, (row) => {
              const dep = row.departamento || '';
              const set = row.setor || '';
              const nome = row.nome || '';
              const parts = [];
              if (dep) parts.push(dep);
              if (set) parts.push(set);
              if (nome) parts.push(nome);
              return parts.length ? parts.join(' — ') : nome;
            });
          }
          if (status) {
            buildSimpleOptions(status, opts.status || [], f.status || '');
          }

          if (tbody) tbody.innerHTML = String(data.rows_html || '');
          if (summary) summary.textContent = String(data.summary || '');
          buildPager(parseInt(data.page || 1, 10) || 1, parseInt(data.total_pages || 1, 10) || 1);

          if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
          }
        })
        .catch((e) => {
          setError(e && e.message ? e.message : 'Falha ao aplicar filtros.');
        });
    };

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      refresh(1);
    });

    const resetSelect = (sel, value) => { if (sel) sel.value = String(value); };

    const onClienteChange = () => {
      resetSelect(unidade, '0');
      resetSelect(departamento, '0');
      resetSelect(setor, '0');
      resetSelect(funcao, '0');
      resetSelect(status, '');
      refresh(1);
    };

    if (cliente) cliente.addEventListener('change', onClienteChange);
    if (all) all.addEventListener('change', onClienteChange);
    if (unidade) unidade.addEventListener('change', () => {
      resetSelect(departamento, '0');
      resetSelect(setor, '0');
      resetSelect(funcao, '0');
      refresh(1);
    });
    if (departamento) departamento.addEventListener('change', () => {
      resetSelect(setor, '0');
      resetSelect(funcao, '0');
      refresh(1);
    });
    if (setor) setor.addEventListener('change', () => {
      resetSelect(funcao, '0');
      refresh(1);
    });
    if (funcao) funcao.addEventListener('change', () => refresh(1));
    if (lider) lider.addEventListener('change', () => refresh(1));
    if (status) status.addEventListener('change', () => refresh(1));
    if (per) per.addEventListener('change', () => refresh(1));

    if (pager) {
      pager.addEventListener('click', (e) => {
        const link = e.target && e.target.closest ? e.target.closest('a[data-page]') : null;
        if (!link) return;
        e.preventDefault();
        const page = parseInt(link.getAttribute('data-page') || '1', 10) || 1;
        refresh(page);
      });
    }

    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        resetSelect(cliente, '');
        if (all) all.checked = false;
        resetSelect(unidade, '0');
        resetSelect(lider, '');
        resetSelect(status, '');
        resetSelect(departamento, '0');
        resetSelect(setor, '0');
        resetSelect(funcao, '0');
        resetSelect(per, '20');
        refresh(1);
      });
    }

    refresh(parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1);
  })();
</script>
