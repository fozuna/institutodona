<?php /** @var array $items */ /** @var array $clientes */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Avaliações</h1>
    <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=avaliacoes/create">Nova Avaliação</a>
  </div>
  <div class="bg-white shadow rounded">
    <table class="min-w-full">
      <thead>
        <tr class="text-left border-b">
          <th class="p-3">Empresa</th>
          <th class="p-3">Data</th>
          <th class="p-3">Financeiro</th>
          <th class="p-3">Mercado</th>
          <th class="p-3">Pessoas</th>
          <th class="p-3">Processo</th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i): $nm = $i['cliente_nome'] ?? ($i['empresa_nome'] ?? '—'); ?>
          <tr class="border-b">
            <td class="p-3"><?= htmlspecialchars($nm) ?></td>
            <td class="p-3"><?= htmlspecialchars(date('d/m/Y', strtotime($i['created_at']))) ?></td>
            <td class="p-3"><?= (int)$i['nota_financeiro'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_mercado'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_pessoas'] ?>/7</td>
            <td class="p-3"><?= (int)$i['nota_processo'] ?>/7</td>
            <td class="p-3">
              <a class="text-brand-pink icon-action" href="index.php?route=avaliacoes/show&id=<?= (int)$i['id'] ?>" title="Ver" aria-label="Ver"><span data-feather="bar-chart-2"></span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
