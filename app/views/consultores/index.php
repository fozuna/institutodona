<?php /** @var array $items */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold text-brand-black">Consultores</h1>
    <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=consultores/create">Novo Consultor</a>
  </div>
  <div class="bg-white shadow rounded">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <th class="p-3">Nome</th>
          <th class="p-3">Email</th>
          <th class="p-3">Telefone</th>
          <th class="p-3">Especialidade</th>
          <th class="p-3">Senioridade</th>
          <th class="p-3">Cidade/UF</th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $c): ?>
          <tr class="border-b">
            <td class="p-3"><?= htmlspecialchars($c['nome'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($c['email'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($c['telefone'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($c['especialidade'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars($c['senioridade'] ?? '') ?></td>
            <td class="p-3"><?= htmlspecialchars(($c['cidade'] ?? '') . (isset($c['estado']) && $c['estado'] ? ' / ' . $c['estado'] : '')) ?></td>
            <td class="p-3">
              <a class="text-brand-pink icon-action" href="index.php?route=consultores/edit&id=<?= (int)$c['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
              <a class="text-brand-brown icon-action ml-2" href="index.php?route=consultores/delete&id=<?= (int)$c['id'] ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
