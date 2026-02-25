<?php /** @var array $clientes */ /** @var int|null $selectedCliente */ /** @var array $items */ ?>
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
      <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('searchForm');
    const select = document.getElementById('clienteSelect');
    const container = document.getElementById('resultsContainer');
    const createBtn = document.getElementById('createBtn');
    const backBtn = document.getElementById('backBtn');
    
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

    // Event Listeners
    select.addEventListener('change', (e) => {
        fetchPlans(e.target.value);
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchPlans(select.value);
    });
});
</script>
