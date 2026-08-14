<?php
/** @var array $items */
/** @var array $clientes */
/** @var int $selectedCliente */
/** @var string $selectedStatus */
/** @var string $selectedPrioridade */
/** @var string $selectedOrdem */
/** @var array $statusOptions */
/** @var array $prioridadeOptions */
/** @var int $page */
/** @var int $perPage */
/** @var int $total */
/** @var int $totalPages */
$prioridadeLabels = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
$orderLabels = [
    'data_inicio_desc' => 'Data de início (mais recentes)',
    'data_inicio_asc' => 'Data de início (mais antigas)',
    'created_at_desc' => 'Criadas recentemente',
    'created_at_asc' => 'Criadas primeiro',
    'prioridade_desc' => 'Prioridade (Alta → Baixa)',
    'prioridade_asc' => 'Prioridade (Baixa → Alta)',
];
$hasActiveFilters = ($selectedCliente > 0) || ($selectedStatus !== '') || ($selectedPrioridade !== '');
$visibleFrom = ($total > 0) ? (($page - 1) * $perPage) + 1 : 0;
$visibleTo = min($total, $page * $perPage);

$buildIndexUrl = static function (array $overrides = []) use ($selectedCliente, $selectedStatus, $selectedPrioridade, $selectedOrdem): string {
    $params = [
        'route' => 'tarefas/index',
        'cliente' => $selectedCliente > 0 ? $selectedCliente : null,
        'status' => $selectedStatus !== '' ? $selectedStatus : null,
        'prioridade' => $selectedPrioridade !== '' ? $selectedPrioridade : null,
        'ordem' => $selectedOrdem !== 'data_inicio_desc' ? $selectedOrdem : null,
    ];
    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }
    if ((int)($params['page'] ?? 1) <= 1) {
        unset($params['page']);
    }
    return 'index.php?' . http_build_query($params);
};
?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Tarefas') ?></h1>
    <div class="flex items-center gap-2">
      <a class="icon-btn icon-btn--primary icon-btn--lg" href="index.php?route=tarefas/create" title="Nova tarefa" aria-label="Nova tarefa"><span data-feather="plus"></span></a>
      <a class="icon-btn icon-btn--muted icon-btn--lg" href="javascript:history.back()" title="Voltar" aria-label="Voltar"><span data-feather="arrow-left"></span></a>
    </div>
  </div>

  <form method="get" action="index.php" class="mb-3 flex flex-wrap items-end gap-2">
    <input type="hidden" name="route" value="tarefas/index" />
    <div>
      <label class="text-sm">Cliente</label>
      <select name="cliente" class="border rounded p-2 min-w-[14rem]">
        <option value="">-- Todos --</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nome_empresa']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Status</label>
      <select name="status" class="border rounded p-2 min-w-[9rem]">
        <option value="">-- Todos --</option>
        <?php foreach ($statusOptions as $st): ?>
          <option value="<?= htmlspecialchars($st) ?>" <?= $selectedStatus === $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Prioridade</label>
      <select name="prioridade" class="border rounded p-2 min-w-[8rem]">
        <option value="">-- Todas --</option>
        <?php foreach ($prioridadeOptions as $pr): ?>
          <option value="<?= htmlspecialchars($pr) ?>" <?= $selectedPrioridade === $pr ? 'selected' : '' ?>><?= htmlspecialchars($prioridadeLabels[$pr] ?? $pr) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Ordenar por</label>
      <select name="ordem" class="border rounded p-2 min-w-[14rem]">
        <?php foreach ($orderLabels as $key => $label): ?>
          <option value="<?= htmlspecialchars($key) ?>" <?= $selectedOrdem === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Filtrar</button>
    <?php if ($hasActiveFilters || $selectedOrdem !== 'data_inicio_desc'): ?>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=tarefas/index">Limpar filtros</a>
    <?php endif; ?>
  </form>

  <div class="text-xs text-gray-500 mb-3">
    <?php if ($total > 0): ?>
      Exibindo <?= (int)$visibleFrom ?>–<?= (int)$visibleTo ?> de <?= (int)$total ?> tarefas
    <?php endif; ?>
  </div>

  <div class="bg-white shadow rounded overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left border-b bg-gray-50">
          <th class="p-3">Título</th>
          <th class="p-3">Cliente</th>
          <th class="p-3">Início</th>
          <th class="p-3">Status</th>
          <th class="p-3">Prioridade</th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td class="p-4 text-center text-gray-500" colspan="6">
              <?= $hasActiveFilters ? 'Nenhuma tarefa encontrada para os filtros selecionados.' : 'Nenhuma tarefa cadastrada.' ?>
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($items as $i): ?>
          <tr class="border-b">
            <td class="p-3">
              <div class="font-medium"><?= htmlspecialchars((string)($i['titulo'] ?? '')) ?></div>
              <?php if (!empty($i['descricao'])): ?><div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)$i['descricao']) ?></div><?php endif; ?>
            </td>
            <td class="p-3"><?= htmlspecialchars((string)($i['cliente'] ?? '')) ?></td>
            <td class="p-3 whitespace-nowrap"><?= htmlspecialchars((string)($i['data_inicio'] ?? '')) ?></td>
            <td class="p-3"><?= htmlspecialchars((string)($i['status'] ?? '')) ?></td>
            <td class="p-3"><?= htmlspecialchars((string)($i['prioridade'] ?? '')) ?></td>
            <td class="p-3 whitespace-nowrap">
              <a class="text-brand-pink icon-action" href="index.php?route=tarefas/edit&id=<?= (int)$i['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
              <form method="post" action="index.php?route=tarefas/finalizar" class="inline">
                <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                <input type="hidden" name="id" value="<?= (int)$i['id'] ?>" />
                <input type="hidden" name="cliente_id" value="<?= (int)($i['cliente_id'] ?? 0) ?>" />
                <button class="text-green-700 icon-action ml-2" type="submit" title="Finalizar" aria-label="Finalizar"><span data-feather="check-circle"></span></button>
              </form>
              <a class="text-brand-brown icon-action ml-2" href="index.php?route=tarefas/delete&id=<?= (int)$i['id'] ?>" title="Excluir" aria-label="Excluir" onclick="return confirm('Excluir esta tarefa?');"><span data-feather="trash-2"></span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-gray-600">
      <div>Página <?= (int)$page ?> de <?= (int)$totalPages ?></div>
      <div class="flex items-center gap-2 flex-wrap">
        <?php
          $prev = max(1, $page - 1);
          $next = min($totalPages, $page + 1);
          $windowStart = max(1, $page - 2);
          $windowEnd = min($totalPages, $windowStart + 4);
          $windowStart = max(1, $windowEnd - 4);
        ?>
        <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $prev])) ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">Anterior</a>
        <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
          <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $p])) ?>"
             class="px-2 py-1 rounded <?= $p === $page ? 'bg-brand-orange text-white' : 'bg-gray-100 text-brand-brown hover:bg-gray-200' ?>">
            <?= (int)$p ?>
          </a>
        <?php endfor; ?>
        <a href="<?= htmlspecialchars($buildIndexUrl(['page' => $next])) ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">Próxima</a>
      </div>
    </div>
  <?php endif; ?>
</div>
