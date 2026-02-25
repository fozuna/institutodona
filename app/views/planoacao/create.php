<?php /** @var array $clientes */ ?>
<?php
  $selectedCliente = $selectedCliente ?? null;
  $items = $items ?? [];
  $statusFilter = $statusFilter ?? '';
  $search = $search ?? '';
?>
<div class="p-6 space-y-6">
  <div class="max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Novo plano de ação</h1>
    <form method="post" action="index.php?route=planoacao/store" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <div>
        <label class="block text-sm">Cliente</label>
        <select name="id_cliente">
          <?php foreach ($clientes as $cli): ?>
            <option value="<?= (int)$cli['id'] ?>" <?= ($selectedCliente === (int)$cli['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cli['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm">O Quê? (Problema)</label>
        <input type="text" name="titulo" required />
      </div>
      <div>
        <label class="block text-sm">Por que?</label>
        <textarea name="descricao" rows="3"></textarea>
      </div>
      <div>
        <label class="block text-sm">Como (Solução)</label>
        <textarea name="meta_valor" rows="3"></textarea>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">Origem</label>
          <input type="text" name="meta_unidade" />
        </div>
        <div>
          <label class="block text-sm">Prazo</label>
          <input type="date" name="prazo" />
        </div>
        <div>
          <label class="block text-sm">Responsável</label>
          <input type="text" name="responsavel" />
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
        <div>
            <label class="flex items-center space-x-2 cursor-pointer mt-4">
                <input type="checkbox" name="concluido" value="1" class="form-checkbox h-5 w-5 text-brand-orange">
                <span class="text-sm font-medium text-gray-700">Concluído</span>
            </label>
        </div>
      </div>
      <div>
        <label class="block text-sm">Status</label>
        <select name="status">
          <option value="A Fazer">A Fazer</option>
          <option value="Em Andamento">Em Andamento</option>
          <option value="Concluído">Concluído</option>
          <option value="Pendente">Pendente</option>
        </select>
      </div>
      <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
  </div>

  <?php if ($selectedCliente): ?>
    <div class="bg-white shadow rounded p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="font-semibold">Planos de ação do cliente</div>
          <div class="text-xs text-gray-500">Use os filtros para localizar rapidamente os planos existentes.</div>
        </div>
        <form method="get" class="flex items-center gap-2">
          <input type="hidden" name="route" value="planoacao/create" />
          <input type="hidden" name="cliente" value="<?= (int)$selectedCliente ?>" />
          <select name="status" class="text-sm">
            <option value="">Status (todos)</option>
            <?php foreach (['A Fazer','Em Andamento','Concluído','Pendente'] as $st): ?>
              <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por título ou responsável" class="text-sm border rounded px-2 py-1" />
          <button class="btn btn-primary text-sm" type="submit">Filtrar</button>
        </form>
      </div>
      <table class="min-w-full">
        <thead>
          <tr class="text-left border-b">
            <th class="p-3">Título</th>
            <th class="p-3">Responsável</th>
            <th class="p-3">Prazo</th>
            <th class="p-3">Status</th>
            <th class="p-3">Ações</th>
          </tr>
        </thead>
        <tbody>
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
            <tr class="border-b">
              <td class="p-3"><?= htmlspecialchars($t['titulo']) ?></td>
              <td class="p-3"><?= htmlspecialchars($t['responsavel'] ?? '—') ?></td>
              <td class="p-3 flex items-center gap-2">
                <span class="w-3 h-3 rounded-full <?= $colorClass ?>" title="Status do Prazo"></span>
                <?= htmlspecialchars($prazoDisplay) ?>
              </td>
              <td class="p-3"><?= htmlspecialchars($t['status'] ?? 'A Fazer') ?></td>
              <td class="p-3">
                <a class="text-brand-red hover:underline" href="index.php?route=planoacao/show&id=<?= (int)$t['id'] ?>">Abrir</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
            <tr><td class="p-3 text-sm text-gray-500" colspan="6">Nenhum plano de ação encontrado com os filtros atuais.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
