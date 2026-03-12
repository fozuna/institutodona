<?php /** @var array $clientes */ ?>
<?php
  $selectedCliente = $selectedCliente ?? null;
  $items = $items ?? [];
  $statusFilter = $statusFilter ?? '';
  $search = $search ?? '';
  $page = $page ?? 1;
  $per = $per ?? 20;
  $total = $total ?? 0;
  $totalPages = $totalPages ?? 1;
?>
<div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
  <!-- Coluna Esquerda: Formulário -->
  <div class="lg:col-span-5 xl:col-span-4">
    <div class="bg-white shadow rounded p-6">
        <h1 class="text-xl font-bold mb-5 text-gray-800 border-b pb-2">Novo plano de ação</h1>
        <form method="post" action="index.php?route=planoacao/store" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
            <select name="id_cliente" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" onchange="if(this.value) window.location.href='index.php?route=planoacao/create&cliente='+this.value">
              <option value="">-- Selecione --</option>
              <?php foreach ($clientes as $cli): ?>
                <option value="<?= (int)$cli['id'] ?>" <?= ($selectedCliente === (int)$cli['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cli['nome_empresa']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">O Quê? (Problema)</label>
            <input type="text" name="titulo" required class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Por que?</label>
            <textarea name="descricao" rows="2" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50"></textarea>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Como (Solução)</label>
            <textarea name="meta_valor" rows="2" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50"></textarea>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
              <input type="text" name="meta_unidade" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Prazo</label>
              <input type="date" name="prazo" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" />
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
             <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Responsável</label>
              <input type="text" name="responsavel" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
                  <option value="Planejado">Planejado</option>
                  <option value="Em Andamento">Em Andamento</option>
                  <option value="Concluído">Concluído</option>
                  <option value="Pendente">Pendente</option>
                </select>
            </div>
          </div>

          <div class="flex items-center justify-between pt-2">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" name="concluido" value="1" class="form-checkbox h-5 w-5 text-brand-orange rounded">
                <span class="text-sm font-medium text-gray-700">Marcar como Concluído</span>
            </label>
            <button class="btn btn-primary flex items-center gap-2 px-4 py-2" type="submit" title="Salvar">
                <span data-feather="check" class="w-4 h-4"></span> Salvar Plano
            </button>
          </div>
        </form>
    </div>
  </div>

  <!-- Coluna Direita: Listagem -->
  <div class="lg:col-span-7 xl:col-span-8">
  <?php if ($selectedCliente): ?>
    <div class="bg-white shadow rounded p-4 h-full">
      <div class="flex flex-col sm:flex-row items-center justify-between mb-4 gap-4 border-b pb-4">
        <div>
          <div class="font-bold text-lg text-gray-800">Planos de ação do cliente</div>
          <div class="text-xs text-gray-500">Gerencie os planos cadastrados</div>
        </div>
        <form method="get" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          <input type="hidden" name="route" value="planoacao/create" />
          <input type="hidden" name="cliente" value="<?= (int)$selectedCliente ?>" />
          
          <select name="status" class="text-sm border rounded px-2 py-1.5 focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
            <option value="">Todos os Status</option>
            <?php foreach (['Planejado','Em Andamento','Concluído','Pendente'] as $st): ?>
              <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          
          <div class="flex-1 sm:flex-none flex gap-2">
              <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar..." class="text-sm border rounded px-2 py-1.5 w-full sm:w-40 focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" />
              <button class="btn btn-neutral text-sm px-3 py-1.5" type="submit" title="Filtrar"><span data-feather="search" class="w-4 h-4"></span></button>
          </div>
          <select name="per" class="text-xs border rounded px-2 py-1.5">
            <?php foreach ([10,20,50,100] as $opt): ?>
              <option value="<?= $opt ?>" <?= ((int)$per === $opt) ? 'selected' : '' ?>><?= $opt ?> por página</option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      
      <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left border-b bg-gray-50">
                <th class="p-3 font-semibold text-gray-700">Título</th>
                <th class="p-3 font-semibold text-gray-700">Responsável</th>
                <th class="p-3 font-semibold text-gray-700">Prazo</th>
                <th class="p-3 font-semibold text-gray-700">Status</th>
                <th class="p-3 font-semibold text-gray-700 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php 
              $taskModel = new \App\Models\PlanoAcaoTaskModel();
              foreach ($items as $t): 
                $prazoStatus = $taskModel->getPrazoStatus($t['prazo'] ?? null, $t['status'] ?? '');
                $colorClass = match($prazoStatus) {
                    'red' => 'bg-red-500',
                    'yellow' => 'bg-yellow-400',
                    'green' => 'bg-green-500',
                    default => 'bg-gray-300'
                };
                $prazoDisplay = (!empty($t['prazo']) && $t['prazo'] !== '0000-00-00') ? date('d/m/Y', strtotime($t['prazo'])) : '—';
              ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="p-3 font-medium text-gray-800">
                      <div class="line-clamp-2" title="<?= htmlspecialchars($t['titulo']) ?>"><?= htmlspecialchars($t['titulo']) ?></div>
                  </td>
                  <td class="p-3 text-gray-600 whitespace-nowrap"><?= htmlspecialchars($t['responsavel'] ?? '—') ?></td>
                  <td class="p-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full <?= $colorClass ?> ring-2 ring-white" title="Status do Prazo"></span>
                        <span class="text-gray-600"><?= htmlspecialchars($prazoDisplay) ?></span>
                    </div>
                  </td>
                  <td class="p-3 whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium border
                        <?= ($t['status'] === 'Concluído') ? 'bg-green-50 text-green-700 border-green-200' : 
                           (($t['status'] === 'Em Andamento') ? 'bg-blue-50 text-blue-700 border-blue-200' : 
                           'bg-gray-50 text-gray-600 border-gray-200') ?>">
                        <?= htmlspecialchars($t['status'] ?? 'Planejado') ?>
                    </span>
                  </td>
                  <td class="p-3 text-right whitespace-nowrap">
                    <a class="text-brand-blue hover:text-brand-blue-dark font-medium hover:underline inline-flex items-center gap-1" href="index.php?route=planoacao/show&id=<?= (int)$t['id'] ?>">
                        Abrir <span data-feather="external-link" class="w-3 h-3"></span>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($items)): ?>
                <tr>
                    <td class="p-8 text-center text-gray-500" colspan="5">
                        <div class="mb-2"><span data-feather="inbox" class="w-8 h-8 mx-auto text-gray-300"></span></div>
                        Nenhum plano de ação encontrado.
                    </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
      </div>
      <?php if ($totalPages > 1): ?>
      <div class="mt-4 flex items-center justify-between text-xs text-gray-600">
        <div>Total: <?= (int)$total ?> • Página <?= (int)$page ?> de <?= (int)$totalPages ?></div>
        <div class="flex items-center gap-2">
          <?php
            $base = 'index.php?route=planoacao/create&cliente=' . (int)$selectedCliente
              . '&status=' . urlencode($statusFilter)
              . '&q=' . urlencode($search)
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
    </div>
  <?php else: ?>
    <div class="bg-white shadow rounded p-12 text-center border-2 border-dashed border-gray-200 h-full flex flex-col items-center justify-center text-gray-500">
        <div class="bg-gray-50 p-4 rounded-full mb-4">
            <span data-feather="layout" class="w-8 h-8 text-gray-400"></span>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Selecione um cliente</h3>
        <p class="max-w-xs mx-auto">Escolha um cliente no formulário ao lado para visualizar e gerenciar seus planos de ação.</p>
    </div>
  <?php endif; ?>
  </div>
</div>
