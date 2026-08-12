<?php /** @var array $clientes */ /** @var int|null $selectedCliente */ /** @var array $items */ ?>
<?php
  $importEnabled = $importEnabled ?? false;
  $importAlreadyRun = $importAlreadyRun ?? false;
  $page = max(1, (int)($page ?? 1));
  $per = max(1, (int)($per ?? 12));
  $total = (int)($total ?? 0);
  $totalPages = max(1, (int)($totalPages ?? 1));
  $statusFilters = array_values(array_filter($statusFilters ?? []));
  $viewMode = (($viewMode ?? 'cards') === 'list') ? 'list' : 'cards';
  $canExportPlanos = (bool)($canExportPlanos ?? false);

  $deadlineBadgeClass = static function(array $task): string {
      $prazoDate = $task['prazo'] ?? null;
      if (($task['status'] ?? '') === 'Concluído' || !$prazoDate || $prazoDate === '0000-00-00') {
          return 'bg-gray-300';
      }
      $deadline = new DateTime($prazoDate);
      $today = new DateTime('today');
      if ($deadline < $today) {
          return 'bg-red-500';
      }
      $diff = $today->diff($deadline);
      return ($diff->invert === 0 && $diff->days <= 2) ? 'bg-yellow-400' : 'bg-green-500';
  };

  $statusClass = static function(string $status): string {
      return match ($status) {
          'Concluído' => 'bg-green-100 text-green-800',
          'Em Andamento' => 'bg-blue-100 text-blue-800',
          'Pendente' => 'bg-yellow-100 text-yellow-800',
          'Atrasado' => 'bg-red-100 text-red-800',
          default => 'bg-gray-100 text-gray-700',
      };
  };

  $formatPrazo = static function(?string $prazo): string {
      if (!$prazo || $prazo === '0000-00-00') {
          return '—';
      }
      return date('d/m/Y', strtotime($prazo));
  };

  $buildIndexUrl = static function(array $overrides = []) use ($selectedCliente, $statusFilters, $viewMode, $per): string {
      $params = [
          'route' => 'planoacao/index',
          'cliente' => $selectedCliente,
          'view' => $viewMode,
          'per' => $per,
      ];
      if (!empty($statusFilters)) {
          $params['status'] = $statusFilters;
      }
      foreach ($overrides as $key => $value) {
          if ($value === null || $value === '' || $value === []) {
              unset($params[$key]);
              continue;
          }
          $params[$key] = $value;
      }
      if (empty($params['cliente'])) {
          unset($params['cliente']);
      }
      if (empty($params['status'])) {
          unset($params['status']);
      }
      if (($params['view'] ?? 'cards') === 'cards') {
          unset($params['view']);
      }
      if ((int)($params['per'] ?? 12) === 12) {
          unset($params['per']);
      }
      if ((int)($params['page'] ?? 1) <= 1) {
          unset($params['page']);
      }
      return 'index.php?' . http_build_query($params);
  };

  $visibleFrom = ($total > 0) ? (($page - 1) * $per) + 1 : 0;
  $visibleTo = min($total, $page * $per);
  $allStatusOptions = ['Planejado','Em Andamento','Concluído','Pendente','Atrasado'];
?>
<div class="p-6 space-y-6">
  <div class="bg-white shadow rounded p-4 md:p-5 space-y-4">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold">Plano de Ação</h1>
        <p class="text-sm text-gray-600">Gerencie os planos por cliente com filtros e acesso rápido.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <?php if ($importEnabled && !$importAlreadyRun): ?>
        <button id="importOpenBtn" type="button" class="px-3 py-2 bg-brand-brown text-white rounded hover:bg-gray-800 transition-colors whitespace-nowrap flex items-center gap-2">
          <span data-feather="upload-cloud" class="w-4 h-4"></span>
          <span>Importar planos</span>
        </button>
        <?php endif; ?>
        <?php if ($selectedCliente && $canExportPlanos): ?>
        <form method="get" action="index.php" class="inline">
          <input type="hidden" name="route" value="planoacao/export" />
          <input type="hidden" name="cliente" value="<?= (int)$selectedCliente ?>" />
          <?php foreach ($statusFilters as $statusFilter): ?>
            <input type="hidden" name="plano_status[]" value="<?= htmlspecialchars($statusFilter) ?>" />
          <?php endforeach; ?>
          <button type="submit" aria-label="Exportar planos para Excel" class="px-3 py-2 bg-brand-brown text-white rounded hover:bg-gray-800 transition-colors whitespace-nowrap flex items-center gap-2">
            <span data-feather="download" class="w-4 h-4"></span>
            <span>Exportar Excel</span>
          </button>
        </form>
        <?php endif; ?>
        <a id="createBtn" class="px-3 py-2 bg-brand-orange text-white rounded hover:bg-orange-700 transition-colors whitespace-nowrap" href="index.php?route=planoacao/create<?= $selectedCliente ? '&cliente=' . $selectedCliente : '' ?>">Novo Plano de Ação</a>
        <a id="backBtn" class="px-3 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors whitespace-nowrap <?= $selectedCliente ? '' : 'hidden' ?>" href="index.php?route=clientes/show&id=<?= (int)$selectedCliente ?>">Voltar ao perfil</a>
      </div>
    </div>

    <form method="get" class="flex flex-col md:flex-row md:items-center gap-2" id="searchForm">
      <input type="hidden" name="route" value="planoacao/index" />
      <input type="hidden" name="per" value="<?= (int)$per ?>" />
      <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>" />
      <?php foreach ($statusFilters as $statusFilter): ?>
        <input type="hidden" name="status[]" value="<?= htmlspecialchars($statusFilter) ?>" />
      <?php endforeach; ?>
      <label for="clienteSelect" class="text-sm text-gray-600 md:whitespace-nowrap">Cliente</label>
      <select name="cliente" id="clienteSelect" class="w-full md:max-w-xl border-gray-300 rounded-md shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
        <option value="">-- Selecione o Cliente --</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="px-3 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 flex items-center justify-center gap-2 transition-colors md:w-auto w-full" title="Buscar agora">
        <span data-feather="search" class="w-4 h-4"></span>
        <span>Buscar</span>
      </button>
    </form>

    <?php if ($selectedCliente): ?>
    <div class="border-t pt-3 space-y-3">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="text-xs text-gray-500">
          Visualização disponível em cards e lista. Paginação padrão: <?= (int)$per ?> planos por tela.
        </div>
        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1">
          <a href="<?= htmlspecialchars($buildIndexUrl(['view' => 'cards', 'page' => 1])) ?>"
             class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $viewMode === 'cards' ? 'bg-brand-orange text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
            Cards
          </a>
          <a href="<?= htmlspecialchars($buildIndexUrl(['view' => 'list', 'page' => 1])) ?>"
             class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $viewMode === 'list' ? 'bg-brand-orange text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
            Lista
          </a>
        </div>
      </div>

      <form method="get" class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3" id="statusFilterForm">
        <input type="hidden" name="route" value="planoacao/index" />
        <input type="hidden" name="cliente" value="<?= (int)$selectedCliente ?>" />
        <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>" />
        <input type="hidden" name="per" value="<?= (int)$per ?>" />
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <label class="inline-flex items-center gap-1 cursor-pointer px-2 py-1 rounded bg-gray-100">
            <input type="checkbox" id="idxStAll" class="form-checkbox">
            <span>Selecionar Todos</span>
          </label>
          <?php foreach ($allStatusOptions as $opt): ?>
            <label class="inline-flex items-center gap-1 cursor-pointer px-2 py-1 rounded border border-gray-200">
              <input type="checkbox" name="status[]" value="<?= $opt ?>" class="form-checkbox idx-st-item"
                <?= in_array($opt, $statusFilters, true) ? 'checked' : '' ?>>
              <span><?= $opt ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-500">Exibindo <?= (int)$visibleFrom ?>-<?= (int)$visibleTo ?> de <?= (int)$total ?></span>
          <button type="submit" class="px-3 py-2 rounded bg-gray-200 text-xs text-brand-brown hover:bg-gray-300">Aplicar</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

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
        <?php if ($viewMode === 'list'): ?>
          <div class="bg-white shadow rounded overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Plano</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Responsável</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Prazo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Ações</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach ($items as $t): ?>
                    <tr class="hover:bg-gray-50">
                      <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                          <span class="mt-1 w-3 h-3 rounded-full <?= $deadlineBadgeClass($t) ?>" title="Status do Prazo"></span>
                          <div>
                            <div class="font-medium text-gray-900"><?= htmlspecialchars($t['titulo']) ?></div>
                            <div class="text-xs text-gray-500 mt-1">ID #<?= (int)$t['id'] ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($t['responsavel'] ?? '—') ?></td>
                      <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($formatPrazo($t['prazo'] ?? null)) ?></td>
                      <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $statusClass((string)($t['status'] ?? '')) ?>">
                          <?= htmlspecialchars($t['status'] ?? '—') ?>
                        </span>
                      </td>
                      <td class="px-4 py-3 text-right">
                        <a class="inline-flex items-center gap-1 text-sm font-semibold text-brand-orange hover:text-orange-800 transition-colors" href="index.php?route=planoacao/show&id=<?= (int)$t['id'] ?>">
                          Abrir <span data-feather="chevron-right" class="w-4 h-4"></span>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($items as $t): ?>
              <div class="card bg-white shadow rounded-lg p-4 border border-gray-100 hover:shadow-md transition-shadow">
                <div class="font-semibold text-gray-900 line-clamp-2 min-h-[2.75rem]"><?= htmlspecialchars($t['titulo']) ?></div>
                <div class="text-xs text-gray-500 mt-2 flex flex-wrap items-center gap-2">
                  <span class="w-3 h-3 rounded-full <?= $deadlineBadgeClass($t) ?>" title="Status do Prazo"></span>
                  <span>Prazo: <?= htmlspecialchars($formatPrazo($t['prazo'] ?? null)) ?></span>
                  <span class="text-gray-300">|</span>
                  <span>Resp.: <?= htmlspecialchars($t['responsavel'] ?? '—') ?></span>
                </div>

                <div class="mt-3 flex items-center justify-between gap-3 border-t pt-3">
                  <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $statusClass((string)($t['status'] ?? '')) ?>">
                    <?= htmlspecialchars($t['status']) ?>
                  </span>
                  <a class="text-brand-orange hover:text-orange-800 text-sm font-semibold flex items-center gap-1 transition-colors" href="index.php?route=planoacao/show&id=<?= (int)$t['id'] ?>">
                    Abrir <span data-feather="chevron-right" class="w-4 h-4"></span>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-gray-600">
          <div>Total: <?= (int)$total ?> • Exibindo <?= (int)$visibleFrom ?>-<?= (int)$visibleTo ?> • Página <?= (int)$page ?> de <?= (int)$totalPages ?></div>
          <div class="flex items-center gap-2 flex-wrap">
            <?php
              $prev = max(1, $page - 1);
              $next = min($totalPages, $page + 1);
              $windowStart = max(1, $page - 2);
              $windowEnd = min($totalPages, $windowStart + 4);
              $windowStart = max(1, $windowEnd - 4);
            ?>
            <a href="<?= htmlspecialchars($buildIndexUrl(['page' => 1])) ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">Primeira</a>
            <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $prev])) ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">Anterior</a>
            <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
              <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $p])) ?>"
                 class="px-2 py-1 rounded <?= $p === $page ? 'bg-brand-orange text-white' : 'bg-gray-100 text-brand-brown hover:bg-gray-200' ?>">
                <?= (int)$p ?>
              </a>
            <?php endfor; ?>
            <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $next])) ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">Próximo</a>
            <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $totalPages])) ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">Última</a>
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

    function redirectToIndex(clienteId) {
        const url = new URL(window.location.href);
        url.searchParams.set('route', 'planoacao/index');
        url.searchParams.delete('open_import');
        url.searchParams.delete('page');
        if (clienteId) {
            url.searchParams.set('cliente', clienteId);
        } else {
            url.searchParams.delete('cliente');
        }
        window.location.href = url.toString();
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
            setTimeout(() => {
                redirectToIndex((importClienteSelect && importClienteSelect.value) || (select && select.value) || '');
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
        updateButtons(e.target.value);
        form.submit();
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
    updateButtons(select ? select.value : '');
    if (shouldOpenImport && importOpenBtn) {
        openImportModal();
    }
    // Status checkboxes auto-submit
    const idxForm = document.getElementById('statusFilterForm');
    const idxAll = document.getElementById('idxStAll');
    const idxItems = document.querySelectorAll('#statusFilterForm .idx-st-item');
    if (idxForm) {
      idxItems.forEach(cb => cb.addEventListener('change', () => idxForm.submit()));
    }
    if (idxAll) {
      idxAll.addEventListener('change', () => {
        const checked = idxAll.checked;
        idxItems.forEach(cb => { cb.checked = checked; });
        idxForm.submit();
      });
      let allOn = true;
      idxItems.forEach(cb => { if (!cb.checked) allOn = false; });
      idxAll.checked = allOn;
    }
});
</script>
