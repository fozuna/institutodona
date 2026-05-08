<?php /** @var array $items */ /** @var array $order */ /** @var bool $isClientOrderable */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Cronogramas</h1>
    <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=cronograma/create">Novo Cronograma</a>
  </div>
  <div class="bg-white shadow rounded">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <?php
            $order = $order ?? ['column' => 'cliente', 'direction' => 'asc'];
            $sortColumn = $order['column'] ?? 'cliente';
            $sortDirection = $order['direction'] ?? 'asc';
            $baseParams = $_GET;
            $baseParams['route'] = 'cronograma/index';
          ?>
          <th class="p-3">
            <?php
              $nextDir = ($sortColumn === 'cliente' && $sortDirection === 'asc') ? 'desc' : 'asc';
              $params = array_merge($baseParams, ['sort' => 'cliente', 'dir' => $nextDir]);
              $active = $sortColumn === 'cliente';
            ?>
            <a class="inline-flex items-center gap-1 hover:underline" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
              Cliente
              <span class="text-xs <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" aria-hidden="true">
                <?= $active && $sortDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
              </span>
            </a>
          </th>
          <th class="p-3">
            <?php
              $nextDir = ($sortColumn === 'nome' && $sortDirection === 'asc') ? 'desc' : 'asc';
              $params = array_merge($baseParams, ['sort' => 'nome', 'dir' => $nextDir]);
              $active = $sortColumn === 'nome';
            ?>
            <a class="inline-flex items-center gap-1 hover:underline" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
              Nome
              <span class="text-xs <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" aria-hidden="true">
                <?= $active && $sortDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
              </span>
            </a>
          </th>
          <th class="p-3">
            <?php
              $nextDir = ($sortColumn === 'ano' && $sortDirection === 'asc') ? 'desc' : 'asc';
              $params = array_merge($baseParams, ['sort' => 'ano', 'dir' => $nextDir]);
              $active = $sortColumn === 'ano';
            ?>
            <a class="inline-flex items-center gap-1 hover:underline" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
              Ano
              <span class="text-xs <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" aria-hidden="true">
                <?= $active && $sortDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
              </span>
            </a>
          </th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $c): ?>
          <tr class="border-b">
            <td class="p-3"><?= htmlspecialchars($c['cliente'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($c['nome'] ?? '') ?></td>
            <td class="p-3"><?= (int)$c['ano'] ?></td>
            <td class="p-3">
              <a class="text-brand-red hover:underline" href="index.php?route=cronograma/show&id=<?= (int)$c['id'] ?>">Abrir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
