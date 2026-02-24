<?php /** @var array $clientes */ /** @var int|null $selectedCliente */ /** @var array $items */ ?>
<div class="p-6 space-y-6">
  <div class="flex items-start justify-between gap-6">
    <div>
      <h1 class="text-2xl font-bold">Plano de Ação</h1>
      <p class="text-sm text-gray-600">Selecione o cliente para visualizar e criar planos de ação</p>
    </div>
    <form method="get" class="toolbar">
      <input type="hidden" name="route" value="planoacao/index" />
      <label class="text-sm">Cliente</label>
      <select name="cliente" class="min-w-[16rem]">
        <option value="">-- Selecione --</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
      <a class="btn btn-primary" href="index.php?route=planoacao/create">Novo Plano de Ação</a>
    </form>
  </div>

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
