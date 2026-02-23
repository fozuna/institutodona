<?php /** @var array $task */ /** @var array $metrics */ /** @var array $checks */ /** @var array $actions */ ?>
<div class="p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($task['titulo'] ?? '') ?></h1>
      <?php $faseMap = ['PLAN' => 'Planejar','DO' => 'Executar','CHECK' => 'Verificar','ACT' => 'Agir']; $faseNome = $faseMap[$task['fase'] ?? 'PLAN'] ?? ($task['fase'] ?? ''); ?>
      <p class="text-sm text-gray-600">Fase: <?= htmlspecialchars($faseNome) ?> · Prazo: <?= htmlspecialchars($task['prazo'] ?? '—') ?> · Resp.: <?= htmlspecialchars($task['responsavel'] ?? '—') ?></p>
    </div>
    <a class="px-3 py-2 rounded bg-brand-brown text-white" href="index.php?route=clientes/show&id=<?= (int)($task['id_cliente'] ?? 0) ?>">Voltar</a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-3 bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Descrição</div>
      <div class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($task['descricao'] ?? '—')) ?></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Planos de Ação</div>
      <form method="post" action="index.php?route=pdca/createAction" class="flex items-center gap-2 mb-3">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>" />
        <input type="text" name="titulo" placeholder="Plano de ação" class="w-48" required />
        <input type="text" name="owner" placeholder="Responsável" class="w-32" required />
        <input type="date" name="due_date" required />
        <select name="status">
          <option value="Planejado">Planejado</option>
          <option value="Em Execução">Em Execução</option>
          <option value="Concluído">Concluído</option>
        </select>
        <button class="icon-btn icon-btn--primary" title="Adicionar"><span data-feather="plus"></span></button>
      </form>
      <table class="min-w-full">
        <thead><tr class="text-left border-b"><th class="p-3">Plano de ação</th><th class="p-3">Responsável</th><th class="p-3">Data de término</th><th class="p-3">Status</th></tr></thead>
        <tbody>
          <?php foreach ($actions as $a): ?>
            <tr class="border-b"><td class="p-3"><?= htmlspecialchars($a['titulo']) ?></td><td class="p-3"><?= htmlspecialchars($a['owner'] ?? '') ?></td><td class="p-3"><?= htmlspecialchars($a['due_date'] ?? '') ?></td><td class="p-3"><?= htmlspecialchars($a['status']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
