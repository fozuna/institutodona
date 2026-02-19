<?php /** @var array $clientes */ /** @var int|null $selectedCliente */ /** @var array $items */ ?>
<div class="p-6 space-y-6">
  <div class="flex items-start justify-between gap-6">
    <div>
      <h1 class="text-2xl font-bold">PDCA</h1>
      <p class="text-sm text-gray-600">Selecione o cliente para visualizar e planejar tarefas</p>
      <div class="prose mt-2">
        <p><strong>PDCA</strong> é um ciclo de melhoria contínua com quatro etapas: <em>Planejar</em>, <em>Executar</em>, <em>Verificar</em> e <em>Agir</em>. Ele organiza metas, execução, análise de resultados e ações de melhoria para elevar a qualidade e a eficiência dos processos.</p>
      </div>
    </div>
    <form method="get" class="toolbar">
      <input type="hidden" name="route" value="pdca/index" />
      <label class="text-sm">Cliente</label>
      <select name="cliente" class="min-w-[16rem]">
        <option value="">-- Selecione --</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
      <a class="btn btn-primary" href="index.php?route=pdca/create">Nova Tarefa</a>
    </form>
  </div>

  <?php if (!$selectedCliente): ?>
    <div class="bg-white shadow rounded p-6 text-gray-600">Escolha um cliente para ver as tarefas.</div>
  <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <?php foreach ($items as $t): ?>
        <?php $p = max(0, min(100, (int)$t['progresso'])); ?>
        <?php
          $faseMap = ['PLAN' => 'Planejar','DO' => 'Executar','CHECK' => 'Verificar','ACT' => 'Agir'];
          $faseNome = $faseMap[$t['fase']] ?? $t['fase'];
        ?>
        <div class="card p-4">
          <div class="flex items-center justify-between">
            <div class="font-semibold"><?= htmlspecialchars($t['titulo']) ?></div>
            <span class="badge"><?= htmlspecialchars($faseNome) ?></span>
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
            <a class="text-brand-red hover:underline" href="index.php?route=pdca/show&id=<?= (int)$t['id'] ?>">Abrir</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
