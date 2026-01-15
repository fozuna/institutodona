<?php /** @var array|null $item */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Avaliação</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <?php if (!$item): ?>
    <div class="bg-white shadow rounded p-4">Registro não encontrado.</div>
  <?php else:
    $labels = ['Financeiro' => (int)$item['nota_financeiro'], 'Mercado' => (int)$item['nota_mercado'], 'Pessoas' => (int)$item['nota_pessoas'], 'Processo' => (int)$item['nota_processo']];
    $max = 7;
    $total = array_sum($labels);
    $pct = fn($n) => round(($n / $max) * 100);
    $empresa = $item['cliente_id'] ? ($item['cliente_nome'] ?? '') : ($item['empresa_nome'] ?? '');
  ?>
  <div class="bg-white shadow rounded p-4 mb-4">
    <div class="mb-2 text-sm text-gray-600">Empresa</div>
    <div class="font-semibold"><?= htmlspecialchars($empresa ?: '—') ?></div>
    <div class="text-sm text-gray-600 mt-1">Data: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['created_at']))) ?></div>
  </div>
  <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
    <?php foreach ($labels as $name => $score): ?>
      <div class="bg-white shadow rounded p-4">
        <div class="font-semibold mb-2"><?= $name ?></div>
        <div class="w-full h-3 bg-gray-200 rounded mb-1">
          <div class="h-3 bg-brand-red rounded" style="width: <?= $pct($score) ?>%"></div>
        </div>
        <div class="text-xs text-gray-600 text-right"><?= (int)$score ?>/7</div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php
    $reals = [];
    foreach (['realidade_financeiro','realidade_mercado','realidade_pessoas','realidade_processo'] as $rk) {
      if (isset($item[$rk]) && $item[$rk] !== null) { $reals[] = (int)$item[$rk]; }
    }
    $realPct = $reals ? round(array_sum($reals) / count($reals)) : round(($total / 28) * 100);
  ?>
  <div class="bg-white shadow rounded p-4 mt-4">
    <div class="font-semibold mb-2">Nota total dos 4 pilares</div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
      <div>
        <div class="text-xs text-gray-600 mb-1">Mundo ideal (100%)</div>
        <div class="border rounded p-2 text-center">28</div>
      </div>
      <div>
        <div class="text-xs text-gray-600 mb-1">Resultado (total)</div>
        <div class="border rounded p-2 text-center"><?= (int)$total ?></div>
      </div>
      <div>
        <div class="text-xs text-gray-600 mb-1">Realidade do cliente (%)</div>
        <div class="border rounded p-2 text-center"><?= (int)$realPct ?>%</div>
      </div>
    </div>
    <div class="w-full h-3 bg-gray-200 rounded">
      <div class="h-3 bg-brand-brown rounded" style="width: <?= round(($total / 28) * 100) ?>%"></div>
    </div>
  </div>
  <?php endif; ?>
</div>
