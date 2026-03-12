<?php /** @var array $clientes */ /** @var int|null $selectedCliente */ /** @var array $items */ ?>
<?php
  $importEnabled = $importEnabled ?? false;
  $importAlreadyRun = $importAlreadyRun ?? false;
  $page = $page ?? 1;
  $per = $per ?? 20;
  $total = $total ?? 0;
  $totalPages = $totalPages ?? 1;
?>
<div class="p-6 space-y-6">
  <!-- Header Section -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div>
      <h1 class="text-2xl font-bold">Plano de Ação</h1>
      <p class="text-sm text-gray-600">Selecione o cliente para visualizar e criar planos de ação</p>
    </div>
    
    <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
      <form method="get" class="flex items-center gap-2 w-full md:w-auto" id="searchForm">
        <input type="hidden" name="route" value="planoacao/index" />
        <div class="flex items-center gap-2 w-full md:w-auto">
          <select name="cliente" id="clienteSelect" class="w-full md:w-64 border-gray-300 rounded-md shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
            <option value="">-- Selecione o Cliente --</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="p-2 h-10 w-10 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 flex items-center justify-center transition-colors" title="Buscar agora">
            <span data-feather="search" class="w-4 h-4"></span>
          </button>
        </div>
      </form>

      <div class="flex items-center gap-2 w-full md:w-auto">
          <?php if ($importEnabled && !$importAlreadyRun): ?>
          <button id="importOpenBtn" type="button" class="px-4 py-2 bg-brand-brown text-white rounded hover:bg-gray-800 transition-colors whitespace-nowrap flex items-center gap-2 justify-center flex-1 md:flex-none">
            <span data-feather="upload-cloud" class="w-4 h-4"></span>
            <span>Importar planos</span>
          </button>
          <?php endif; ?>
          <a id="createBtn" class="px-4 py-2 bg-brand-orange text-white rounded hover:bg-orange-700 transition-colors whitespace-nowrap text-center flex-1 md:flex-none" href="index.php?route=planoacao/create<?= $selectedCliente ? '&cliente=' . $selectedCliente : '' ?>">Novo Plano de Ação</a>
          
          <a id="backBtn" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors whitespace-nowrap text-center flex-1 md:flex-none <?= $selectedCliente ? '' : 'hidden' ?>" href="index.php?route=clientes/show&id=<?= (int)$selectedCliente ?>">Voltar ao perfil</a>
      </div>
    </div>
  </div>

  <!-- Results Section -->
  <div id="resultsContainer">
      <?php if (!$selectedCliente): ?>
        <div class="bg-white shadow rounded p-6 text-gray-600 text-center">
            <span data-feather="arrow-up" class="w-6 h-6 mx-auto mb-2 text-gray-400 md:hidden"></span>
            <span data-feather="arrow-up-right" class="w-6 h-6 mx-auto mb-2 text-gray-400 hidden md:block"></span>
            Escolha um cliente acima para ver as tarefas.
        </div>
      <?php elseif (empty($items)): ?>
        <div class="bg-white shadow rounded p-10 text-center text-gray-500">
          <div class="mb-3"><span data-feather="clipboard" class="w-12 h-12 mx-auto text-gray-300"></span></div>
          <p>Nenhum plano de ação encontrado para este cliente.</p>
          <a href="index.php?route=planoacao/create&cliente=<?= (int)$selectedCliente ?>" class="text-brand-orange hover:underline mt-2 inline-block">Criar o primeiro plano</a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($items as $t): ?>
            <div class="card bg-white shadow rounded p-4 border border-gray-100 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <div class="font-semibold text-gray-900"><?= htmlspecialchars($t['titulo']) ?></div>
              </div>
              <div class="text-xs text-gray-500 mt-2 flex flex-wrap items-center gap-2">
                 <?php 
                    // Logic for traffic light
                    $prazoDate = ($t['prazo'] && $t['prazo'] !== '0000-00-00') ? $t['prazo'] : null;
                    $colorClass = 'bg-gray-300';
                    if ($t['status'] !== 'Concluído' && $prazoDate) {
                        $deadline = new DateTime($prazoDate);
                        $today = new DateTime('today');
                        if ($deadline < $today) {
                            $colorClass = 'bg-red-500';
                        } else {
                            $diff = $today->diff($deadline);
                            if ($diff->days <= 2 && $diff->invert == 0) {
                                $colorClass = 'bg-yellow-400';
                            } else {
                                $colorClass = 'bg-green-500';
                            }
                        }
                    }
                    $prazoDisplay = $prazoDate ? date('d/m/Y', strtotime($prazoDate)) : '—';
                 ?>
                <span class="w-3 h-3 rounded-full <?= $colorClass ?>" title="Status do Prazo"></span>
                <span>Prazo: <?= $prazoDisplay ?></span>
                <span class="text-gray-300">|</span>
                <span>Resp.: <?= htmlspecialchars($t['responsavel'] ?? '—') ?></span>
              </div>
              
              <div class="mt-4 flex items-center justify-between">
                  <div class="text-sm font-medium text-gray-700">
                      Status: <span class="text-gray-900"><?= htmlspecialchars($t['status']) ?></span>
                  </div>
                  <a class="text-brand-orange hover:text-orange-800 text-sm font-semibold flex items-center gap-1 transition-colors" href="index.php?route=planoacao/show&id=<?= (int)$t['id'] ?>">
                      Abrir <span data-feather="chevron-right" class="w-4 h-4"></span>
                  </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="mt-4 flex items-center justify-between text-xs text-gray-600">
          <div>Total: <?= (int)$total ?> • Página <?= (int)$page ?> de <?= (int)$totalPages ?></div>
          <div class="flex items-center gap-2">
            <?php
              $base = 'index.php?route=planoacao/index&cliente=' . (int)$selectedCliente
                . '&per=' . (int)$per
                . '&page=';
              $prev = max(1, (int)$page - 1);
              $next = min((int)$totalPages, (int)$page + 1);
            ?>
            <a href="<?= $page > 1 ? $base . $prev : '#' ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">Anterior</a>
            <a href="<?= $page < $totalPages ? $base . $next : '#' ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">Próximo</a>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>
  </div>
</div>

<?php if ($importEnabled && !$importAlreadyRun): ?>
<div id="importModal" class="fixed inset-0 z-40 hidden items-center justify-center">
  <div id="importOverlay" class="absolute inset-0 bg-black bg-opacity-40"></div>
  <div class="relative bg-white rounded shadow-lg max-w-lg w-full mx-4 p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <div class="text-lg font-semibold text-gray-900">Importar planos de ação</div>
        <div class="text-xs text-gray-500">Selecione o arquivo da planilha para realizar a importação única.</div>
      </div>
      <button type="button" id="importCloseBtn" class="text-gray-500 hover:text-gray-800">
        <span data-feather="x" class="w-4 h-4"></span>
      </button>
    </div>
    <form id="importForm" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente padrão (opcional)</label>
        <select name="id_cliente" id="importClienteSelect" class="border rounded p-2 w-full text-sm">
          <option value="">Sem cliente padrão (usar coluna de cliente na planilha)</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo da planilha</label>
        <input type="file" name="arquivo" id="importFile" accept=".xlsx,.csv" class="border rounded p-2 w-full text-sm" />
        <div class="text-xs text-gray-500 mt-1">
          Formatos aceitos: XLSX ou CSV. A primeira linha deve conter os cabeçalhos.
        </div>
      </div>
      <div id="importStatus" class="text-xs mt-1 min-h-[1.25rem]"></div>
      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" id="importCancelBtn" class="px-3 py-1.5 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-100">Cancelar</button>
        <button type="submit" id="importSubmitBtn" class="px-4 py-1.5 rounded bg-brand-red text-white text-sm font-semibold hover:bg-red-700 flex items-center gap-2">
          <span data-feather="play" class="w-4 h-4"></span>
          <span>Executar importação</span>
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('searchForm');
    const select = document.getElementById('clienteSelect');
    const container = document.getElementById('resultsContainer');
    const createBtn = document.getElementById('createBtn');
    const backBtn = document.getElementById('backBtn');
    const importOpenBtn = document.getElementById('importOpenBtn');
    const importModal = document.getElementById('importModal');
    const importOverlay = document.getElementById('importOverlay');
    const importCloseBtn = document.getElementById('importCloseBtn');
    const importCancelBtn = document.getElementById('importCancelBtn');
    const importForm = document.getElementById('importForm');
    const importFile = document.getElementById('importFile');
    const importStatus = document.getElementById('importStatus');
    const importSubmitBtn = document.getElementById('importSubmitBtn');
    const importClienteSelect = document.getElementById('importClienteSelect');
    const urlParams = new URLSearchParams(window.location.search);
    const shouldOpenImport = urlParams.get('open_import') === '1';
    
    // Simple in-memory cache for this session
    const localCache = {};

    async function fetchPlans(clienteId) {
        // Update UI controls immediately
        updateUrl(clienteId);
        updateButtons(clienteId);

        // If no client selected, show empty state
        if (!clienteId) {
            container.innerHTML = `
                <div class="bg-white shadow rounded p-6 text-gray-600 text-center">
                    <span data-feather="arrow-up" class="w-6 h-6 mx-auto mb-2 text-gray-400 md:hidden"></span>
                    <span data-feather="arrow-up-right" class="w-6 h-6 mx-auto mb-2 text-gray-400 hidden md:block"></span>
                    Escolha um cliente acima para ver as tarefas.
                </div>`;
            feather.replace();
            return;
        }

        const start = performance.now();
        
        // Check cache
        if (localCache[clienteId]) {
            renderResults(localCache[clienteId], clienteId);
            return;
        }

        // Show loading state
        container.innerHTML = `
            <div class="flex flex-col justify-center items-center p-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-orange mb-3"></div>
                <span class="text-gray-600">Carregando planos de ação...</span>
            </div>
        `;

        try {
            const response = await fetch(`index.php?route=planoacao/api_list&cliente=${clienteId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!response.ok) throw new Error('Falha na comunicação com o servidor');
            
            const data = await response.json();
            
            if (data.success) {
                localCache[clienteId] = data.data; // Save to cache
                renderResults(data.data, clienteId);
            } else {
                showError(data.error || 'Erro desconhecido ao carregar dados.');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            showError('Não foi possível carregar os planos de ação. Verifique sua conexão.');
        }
    }

    function renderResults(items, clienteId) {
        if (!items || items.length === 0) {
            container.innerHTML = `
                <div class="bg-white shadow rounded p-10 text-center text-gray-500">
                  <div class="mb-3"><span data-feather="clipboard" class="w-12 h-12 mx-auto text-gray-300"></span></div>
                  <p>Nenhum plano de ação encontrado para este cliente.</p>
                  <a href="index.php?route=planoacao/create&cliente=${clienteId}" class="text-brand-orange hover:underline mt-2 inline-block">Criar o primeiro plano</a>
                </div>`;
            feather.replace();
            return;
        }

        const escapeHtml = (str) => str ? String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : '';

        const cards = items.map(t => {
            // Logic for traffic light (replicated from PHP logic)
            let colorClass = 'bg-gray-300';
            let deadlineDate = null;
            
            if (t.status !== 'Concluído' && t.prazo && t.prazo !== '0000-00-00') {
                const deadline = new Date(t.prazo + 'T00:00:00'); // Ensure local time
                const today = new Date();
                today.setHours(0,0,0,0);
                deadlineDate = deadline;
                
                if (deadline < today) {
                    colorClass = 'bg-red-500';
                } else {
                    const diffTime = deadline - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                    if (diffDays <= 2) {
                        colorClass = 'bg-yellow-400';
                    } else {
                        colorClass = 'bg-green-500';
                    }
                }
            }

            const prazoDisplay = (t.prazo && t.prazo !== '0000-00-00') ? new Date(t.prazo + 'T12:00:00').toLocaleDateString('pt-BR') : '—';

            return `
            <div class="card bg-white shadow rounded p-4 border border-gray-100 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <div class="font-semibold text-gray-900">${escapeHtml(t.titulo)}</div>
              </div>
              <div class="text-xs text-gray-500 mt-2 flex flex-wrap items-center gap-2">
                <span class="w-3 h-3 rounded-full ${colorClass}" title="Status do Prazo"></span>
                <span>Prazo: ${prazoDisplay}</span>
                <span class="text-gray-300">|</span>
                <span>Resp.: ${escapeHtml(t.responsavel || '—')}</span>
              </div>
              <div class="mt-4 flex items-center justify-between">
                  <div class="text-sm font-medium text-gray-700">
                      Status: <span class="text-gray-900">${escapeHtml(t.status)}</span>
                  </div>
                  <a class="text-brand-orange hover:text-orange-800 text-sm font-semibold flex items-center gap-1 transition-colors" href="index.php?route=planoacao/show&id=${t.id}">
                      Abrir <span data-feather="chevron-right" class="w-4 h-4"></span>
                  </a>
              </div>
            </div>`;
        }).join('');

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                ${cards}
            </div>`;
        
        feather.replace();
    }

    function showError(msg) {
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded shadow-sm flex items-center gap-3">
                <span data-feather="alert-circle"></span>
                <span>${msg}</span>
            </div>
        `;
        feather.replace();
    }

    function updateUrl(clienteId) {
        const url = new URL(window.location);
        if (clienteId) {
            url.searchParams.set('cliente', clienteId);
        } else {
            url.searchParams.delete('cliente');
        }
        window.history.pushState({}, '', url);
    }
    
    function updateButtons(clienteId) {
        if (createBtn) {
            const baseUrl = 'index.php?route=planoacao/create';
            createBtn.href = clienteId ? `${baseUrl}&cliente=${clienteId}` : baseUrl;
        }
        if (backBtn) {
            if (clienteId) {
                backBtn.classList.remove('hidden');
                backBtn.href = `index.php?route=clientes/show&id=${clienteId}`;
            } else {
                backBtn.classList.add('hidden');
            }
        }
    }

    function openImportModal() {
        if (!importModal) return;
        importModal.classList.remove('hidden');
        importModal.classList.add('flex');
        if (importClienteSelect && select) {
            importClienteSelect.value = select.value || '';
        }
        if (importStatus) {
            importStatus.textContent = '';
            importStatus.className = 'text-xs mt-1 min-h-[1.25rem]';
        }
        if (importFile) {
            importFile.value = '';
        }
        if (importSubmitBtn) {
            importSubmitBtn.disabled = false;
        }
        feather.replace();
    }

    function closeImportModal() {
        if (!importModal) return;
        importModal.classList.add('hidden');
        importModal.classList.remove('flex');
    }

    async function handleImportSubmit(e) {
        e.preventDefault();
        if (!importForm || !importFile || !importSubmitBtn || !importStatus) return;
        if (!importFile.files || !importFile.files.length) {
            importStatus.textContent = 'Selecione um arquivo para importação.';
            importStatus.className = 'text-xs mt-1 text-red-700';
            return;
        }
        const file = importFile.files[0];
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'xlsx' && ext !== 'csv') {
            importStatus.textContent = 'Apenas arquivos XLSX ou CSV são aceitos.';
            importStatus.className = 'text-xs mt-1 text-red-700';
            return;
        }
        const fd = new FormData(importForm);
        fd.set('id_cliente', importClienteSelect ? (importClienteSelect.value || '') : '');
        importSubmitBtn.disabled = true;
        importStatus.innerHTML = '<span class="inline-flex items-center gap-2 text-gray-700"><span class="animate-spin rounded-full h-4 w-4 border-b-2 border-brand-orange"></span><span>Processando importação, aguarde...</span></span>';
        importStatus.className = 'text-xs mt-1';
        try {
            const resp = await fetch('index.php?route=planoacao/importRun&ajax=1', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const isJson = resp.headers.get('Content-Type') && resp.headers.get('Content-Type').indexOf('application/json') !== -1;
            let data = null;
            if (isJson) {
                data = await resp.json();
            } else {
                const text = await resp.text();
                data = { success: resp.ok, message: text };
            }
            if (!data.success) {
                importStatus.textContent = data.message || 'Falha ao realizar importação.';
                importStatus.className = 'text-xs mt-1 text-red-700';
                importSubmitBtn.disabled = false;
                return;
            }
            const report = data.report || {};
            const imported = report.imported || 0;
            const ignored = report.ignored || 0;
            const errors = report.errors || 0;
            importStatus.textContent = 'Importação concluída. Importados: ' + imported + ', ignorados: ' + ignored + ', com erro: ' + errors + '.';
            importStatus.className = 'text-xs mt-1 text-green-700';
            if (select && select.value) {
                fetchPlans(select.value);
            }
            setTimeout(() => {
                closeImportModal();
            }, 2000);
        } catch (err) {
            console.error(err);
            importStatus.textContent = 'Erro inesperado ao realizar importação.';
            importStatus.className = 'text-xs mt-1 text-red-700';
            importSubmitBtn.disabled = false;
        }
    }

    // Event Listeners
    select.addEventListener('change', (e) => {
        fetchPlans(e.target.value);
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchPlans(select.value);
    });
    if (importOpenBtn) {
        importOpenBtn.addEventListener('click', openImportModal);
    }
    if (importOverlay) {
        importOverlay.addEventListener('click', closeImportModal);
    }
    if (importCloseBtn) {
        importCloseBtn.addEventListener('click', closeImportModal);
    }
    if (importCancelBtn) {
        importCancelBtn.addEventListener('click', closeImportModal);
    }
    if (importForm) {
        importForm.addEventListener('submit', handleImportSubmit);
    }
    if (shouldOpenImport && importOpenBtn) {
        openImportModal();
    }
});
</script>
