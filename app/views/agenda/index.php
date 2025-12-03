<?php /** @var array $items */ ?>
<div class="p-6">
  <h1 class="text-2xl font-bold text-brand-black mb-4">Agenda</h1>
  <div class="bg-white shadow rounded">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <th class="p-3">Data</th>
          <th class="p-3">Cliente</th>
          <th class="p-3">Pilar</th>
          <th class="p-3">Tarefa</th>
          <th class="p-3">Consultor</th>
          <th class="p-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i): ?>
          <tr class="border-b">
            <td class="p-3"><?= htmlspecialchars($i['data_prevista'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($i['cliente'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($i['pilar'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($i['tarefa'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($i['consultor'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($i['status'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
