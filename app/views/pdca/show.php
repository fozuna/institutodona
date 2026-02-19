<?php /** @var array $task */ /** @var array $metrics */ /** @var array $checks */ /** @var array $actions */ ?>
<div class="p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($task['titulo'] ?? '') ?></h1>
      <?php $faseMap = ['PLAN' => 'Planejar','DO' => 'Executar','CHECK' => 'Verificar','ACT' => 'Agir']; $faseNome = $faseMap[$task['fase'] ?? 'PLAN'] ?? ($task['fase'] ?? ''); ?>
      <p class="text-sm text-gray-600">Fase: <?= htmlspecialchars($faseNome) ?> · Prazo: <?= htmlspecialchars($task['prazo'] ?? '—') ?> · Resp.: <?= htmlspecialchars($task['responsavel'] ?? '—') ?></p>
    </div>
    <a class="px-3 py-2 rounded bg-brand-brown text-white" href="index.php?route=pdca/index">Voltar</a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-1 bg-white shadow rounded p-4">
      <?php $p = max(0, min(100, (int)($task['progresso'] ?? 0))); ?>
      <div class="progress-circle" data-progress="<?= $p ?>" style="--p: <?= $p ?>"></div>
      <div class="mt-2 text-center text-sm">Progresso: <?= $p ?>%</div>
    </div>
    <div class="lg:col-span-3 bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Descrição</div>
      <div class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($task['descricao'] ?? '—')) ?></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white shadow rounded p-4">
      <div class="flex items-center justify-between mb-2">
        <div class="font-semibold">Métricas (Planejado/Realizado)</div>
        <form method="post" action="index.php?route=pdca/upsertMetric" class="flex items-center gap-2">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>" />
          <input type="text" name="nome" placeholder="Indicador" class="w-28" required />
          <input type="number" step="0.01" name="planejado" placeholder="Planejado" class="w-24" />
          <input type="number" step="0.01" name="realizado" placeholder="Realizado" class="w-24" />
          <input type="text" name="unidade" placeholder="Unidade" class="w-16" />
          <button class="icon-btn icon-btn--primary" title="Adicionar"><span data-feather="plus"></span></button>
        </form>
      </div>
      <table class="min-w-full">
        <thead><tr class="text-left border-b"><th class="p-3">Indicador</th><th class="p-3">Planejado</th><th class="p-3">Realizado</th><th class="p-3">Unidade</th></tr></thead>
        <tbody>
          <?php foreach ($metrics as $m): ?>
            <tr class="border-b"><td class="p-3"><?= htmlspecialchars($m['nome']) ?></td><td class="p-3"><?= htmlspecialchars($m['planejado']) ?></td><td class="p-3"><?= htmlspecialchars($m['realizado']) ?></td><td class="p-3"><?= htmlspecialchars($m['unidade'] ?? '') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Verificação (Check)</div>
      <form method="post" action="index.php?route=pdca/addCheck" class="flex items-center gap-2 mb-3">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>" />
        <input type="number" step="0.01" name="gap" placeholder="Gap" class="w-24" />
        <input type="text" name="analise" placeholder="Análise de gaps" class="w-64" />
        <button class="icon-btn icon-btn--primary" title="Registrar"><span data-feather="check"></span></button>
      </form>
      <table class="min-w-full">
        <thead><tr class="text-left border-b"><th class="p-3">Data</th><th class="p-3">Gap</th><th class="p-3">Análise</th></tr></thead>
        <tbody>
          <?php foreach ($checks as $c): ?>
            <tr class="border-b"><td class="p-3"><?= htmlspecialchars($c['created_at']) ?></td><td class="p-3"><?= htmlspecialchars($c['gap'] ?? '') ?></td><td class="p-3"><?= htmlspecialchars($c['analise'] ?? '') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Ações de melhoria (Act)</div>
      <form method="post" action="index.php?route=pdca/createAction" class="flex items-center gap-2 mb-3">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>" />
        <input type="text" name="titulo" placeholder="Ação" class="w-48" required />
        <input type="text" name="owner" placeholder="Responsável" class="w-32" />
        <input type="date" name="due_date" />
        <select name="status">
          <option value="Planejado">Planejado</option>
          <option value="Em Execução">Em Execução</option>
          <option value="Concluído">Concluído</option>
        </select>
        <button class="icon-btn icon-btn--primary" title="Adicionar"><span data-feather="plus"></span></button>
      </form>
      <table class="min-w-full">
        <thead><tr class="text-left border-b"><th class="p-3">Ação</th><th class="p-3">Owner</th><th class="p-3">Prazo</th><th class="p-3">Status</th></tr></thead>
        <tbody>
          <?php foreach ($actions as $a): ?>
            <tr class="border-b"><td class="p-3"><?= htmlspecialchars($a['titulo']) ?></td><td class="p-3"><?= htmlspecialchars($a['owner'] ?? '') ?></td><td class="p-3"><?= htmlspecialchars($a['due_date'] ?? '') ?></td><td class="p-3"><?= htmlspecialchars($a['status']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
