<?php /** @var array $items */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Cronogramas</h1>
    <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=cronograma/create">Novo Cronograma</a>
  </div>
  <div class="bg-white shadow rounded">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <th class="p-3">Cliente</th>
          <th class="p-3">Nome</th>
          <th class="p-3">Ano</th>
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
