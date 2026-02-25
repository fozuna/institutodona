<?php /** @var array $clientes */ /** @var int|null $selectedCliente */ /** @var array $items */ ?>
<div class="p-6 space-y-6">
  <div class="flex items-start justify-between gap-6">
    <div>
      <h1 class="text-2xl font-bold">Plano de Ação</h1>
      <p class="text-sm text-gray-600">Selecione o cliente para visualizar e criar planos de ação</p>
    </div>
    <form method="get" class="toolbar" id="searchForm">
      <input type="hidden" name="route" value="planoacao/index" />
      <label class="text-sm">Cliente</label>
      <div class="flex items-center gap-2">
          <select name="cliente" id="clienteSelect" class="min-w-[16rem]">
            <option value="">-- Selecione --</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-neutral p-2 h-10 w-10 flex items-center justify-center" title="Buscar agora">
            <span data-feather="search" class="w-4 h-4"></span>
          </button>
      </div>
      <a id="createBtn" class="btn btn-primary" href="index.php?route=planoacao/create<?= $selectedCliente ? '&cliente=' . $selectedCliente : '' ?>">Novo Plano de Ação</a>
    </form>
  </div>

  <div id="resultsContainer">
      <?php if (!$selectedCliente): ?>
        <div class="bg-white shadow rounded p-6 text-gray-600">Escolha um cliente para ver as tarefas.</div>
      <?php elseif (empty($items)): ?>
        <div class="flex justify-end mb-6">
          <a class="btn btn-neutral" href="index.php?route=clientes/show&id=<?= (int)$selectedCliente ?>">Voltar ao perfil</a>
        </div>
        <div class="bg-white shadow rounded p-10 text-center text-gray-500">
          <div class="mb-3"><span data-feather="clipboard" class="w-12 h-12 mx-auto text-gray-300"></span></div>
          <p>Nenhum plano de ação encontrado para este cliente.</p>
          <a href="index.php?route=planoacao/create&cliente=<?= (int)$selectedCliente ?>" class="text-brand-red hover:underline mt-2 inline-block">Criar o primeiro plano</a>
        </div>
      <?php else: ?>
        <div class="flex justify-end mb-6">
          <a class="btn btn-neutral" href="index.php?route=clientes/show&id=<?= (int)$selectedCliente ?>">Voltar ao perfil</a>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <?php foreach ($items as $t): ?>
            <?php $p = max(0, min(100, (int)$t['progresso'])); ?>
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div class="font-semibold"><?= htmlspecialchars($t['titulo']) ?></div>
              </div>
              <div class="text-xs text-gray-500 mt-1">Prazo: <?= htmlspecialchars($t['prazo'] ?? '—') ?> · Resp.: <?= htmlspecialchars($t['responsavel'] ?? '—') ?></div>
              <div class="flex items-center gap-3 mt-3">
                <div class="progress-circle" data-progress="<?= $p ?>" style="--p: <?= $p ?>"></div>
                <div class="text-sm">
                  <div>Status: <?= htmlspecialchars($t['status']) ?></div>
                  <div>Progresso: <?= $p ?>%</div>
                </div>
              </div>
              <div class="mt-3">
                <a class="text-brand-red hover:underline" href="index.php?route=planoacao/show&id=<?= (int)$t['id'] ?>">Abrir</a>
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
    
    // Simple in-memory cache for this session
    const localCache = {};

    async function fetchPlans(clienteId) {
        // If no client selected, show empty state
        if (!clienteId) {
            container.innerHTML = '<div class="bg-white shadow rounded p-6 text-gray-600">Escolha um cliente para ver as tarefas.</div>';
            updateUrl(clienteId);
            updateCreateLink(clienteId);
            return;
        }

        const start = performance.now();
        
        // Check cache
        if (localCache[clienteId]) {
            console.log(`[Cache] Hit for cliente ${clienteId}`);
            renderResults(localCache[clienteId], clienteId);
            updateUrl(clienteId);
            updateCreateLink(clienteId);
            const duration = (performance.now() - start).toFixed(2);
            console.log(`[Performance] Rendered from cache in ${duration}ms`);
            return;
        }

        // Show loading state
        container.innerHTML = `
            <div class="flex flex-col justify-center items-center p-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-red mb-3"></div>
                <span class="text-gray-600">Carregando planos de ação...</span>
            </div>
        `;

        try {
            const response = await fetch(`index.php?route=planoacao/api_list&cliente=${clienteId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
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
        } finally {
             const duration = (performance.now() - start).toFixed(2);
             console.log(`[Performance] Fetch and render completed in ${duration}ms`);
             updateUrl(clienteId);
             updateCreateLink(clienteId);
        }
    }

    function renderResults(items, clienteId) {
        if (!items || items.length === 0) {
            container.innerHTML = `
                <div class="flex justify-end mb-6">
                  <a class="btn btn-neutral" href="index.php?route=clientes/show&id=${clienteId}">Voltar ao perfil</a>
                </div>
                <div class="bg-white shadow rounded p-10 text-center text-gray-500">
                  <div class="mb-3"><span data-feather="clipboard" class="w-12 h-12 mx-auto text-gray-300"></span></div>
                  <p>Nenhum plano de ação encontrado para este cliente.</p>
                  <a href="index.php?route=planoacao/create&cliente=${clienteId}" class="text-brand-red hover:underline mt-2 inline-block">Criar o primeiro plano</a>
                </div>`;
            if (window.feather) feather.replace();
            return;
        }

        const escapeHtml = (str) => str ? String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') : '';

        const cards = items.map(t => {
            const p = Math.max(0, Math.min(100, parseInt(t.progresso || 0)));
            
            // Logic for traffic light (replicated from PHP logic)
            let colorClass = 'bg-gray-300';
            if (t.status !== 'Concluído' && t.prazo && t.prazo !== '0000-00-00') {
                const deadline = new Date(t.prazo + 'T00:00:00'); // Ensure local time
                const today = new Date();
                today.setHours(0,0,0,0);
                
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
            <div class="card p-4">
              <div class="flex items-center justify-between">
                <div class="font-semibold">${escapeHtml(t.titulo)}</div>
              </div>
              <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                <span class="w-3 h-3 rounded-full ${colorClass}" title="Status do Prazo"></span>
                Prazo: ${prazoDisplay} · Resp.: ${escapeHtml(t.responsavel || '—')}
              </div>
              <div class="flex items-center gap-3 mt-3">
                <div class="progress-circle" data-progress="${p}" style="--p: ${p}"></div>
                <div class="text-sm">
                  <div>Status: ${escapeHtml(t.status)}</div>
                  <div>Progresso: ${p}%</div>
                </div>
              </div>
              <div class="mt-3">
                <a class="text-brand-red hover:underline" href="index.php?route=planoacao/show&id=${t.id}">Abrir</a>
              </div>
            </div>`;
        }).join('');

        container.innerHTML = `
            <div class="flex justify-end mb-6">
              <a class="btn btn-neutral" href="index.php?route=clientes/show&id=${clienteId}">Voltar ao perfil</a>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                ${cards}
            </div>`;
        
        if (window.feather) feather.replace();
    }

    function showError(msg) {
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded shadow-sm flex items-center gap-3">
                <span data-feather="alert-circle"></span>
                <span>${msg}</span>
            </div>
        `;
        if (window.feather) feather.replace();
    }

    function updateUrl(clienteId) {
        const url = new URL(window.location);
        if (clienteId) {
            url.searchParams.set('cliente', clienteId);
        } else {
            url.searchParams.delete('cliente');
        }
        // Use pushState to update URL without reload
        window.history.pushState({}, '', url);
    }
    
    function updateCreateLink(clienteId) {
        if (createBtn) {
            const baseUrl = 'index.php?route=planoacao/create';
            createBtn.href = clienteId ? `${baseUrl}&cliente=${clienteId}` : baseUrl;
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