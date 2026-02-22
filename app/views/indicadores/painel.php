<?php /** @var array $clientes */ /** @var int $cliente */ /** @var int $ano */ /** @var array $months */ /** @var array $rows */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Painel de Indicadores</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=indicadores/index&cliente=<?= (int)$cliente ?>">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4 mb-4">
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
      <input type="hidden" name="route" value="indicadores/painel" />
      <div class="md:col-span-3">
        <label class="text-sm">Cliente</label>
        <select name="cliente" class="border rounded p-2 w-full">
          <option value="">— selecione —</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm">Ano</label>
        <input type="number" name="ano" class="border rounded p-2 w-full" value="<?= (int)$ano ?>" />
      </div>
      <div class="md:col-span-1">
        <button class="px-4 py-2 rounded bg-brand-red text-white w-full">Filtrar</button>
      </div>
    </form>
  </div>
  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded p-4">Selecione um cliente para visualizar o painel.</div>
  <?php elseif (empty($rows)): ?>
    <div class="bg-white shadow rounded p-4">Nenhum registro para o ano selecionado.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded p-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="p-2">Indicador</th>
            <th class="p-2">Meta Total</th>
            <?php foreach ($months as $i=>$m): ?>
              <th class="p-2"><?= htmlspecialchars($m) ?></th>
            <?php endforeach; ?>
            <th class="p-2">Realizado Total</th>
            <th class="p-2">% Atingido</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
              $tm = (float)$row['total_meta'];
              $tr = (float)$row['total_real'];
              $pct = $tm > 0 ? round(($tr / $tm) * 100) : 0;
            ?>
            <tr class="border-b">
              <td class="p-2 font-semibold"><?= htmlspecialchars($row['nome']) ?></td>
              <td class="p-2 whitespace-nowrap"><?= 'R$ ' . number_format($tm, 2, ',', '.') ?></td>
              <?php foreach ($row['meses'] as $mm): ?>
                <?php
                  $meta = (float)$mm['meta'];
                  $real = (float)$mm['real'];
                  $p = $meta > 0 ? ($real / $meta) : 0;
                  $bg = $p >= 1 ? 'bg-green-100' : ($p >= 0.7 ? 'bg-yellow-100' : 'bg-orange-100');
                ?>
                <td class="p-2 <?= $meta || $real ? $bg : '' ?>">
                  <?php if ($meta || $real): ?>
                    <div class="text-[11px] text-gray-600">Meta</div>
                    <div class="font-medium"><?= 'R$ ' . number_format($meta, 2, ',', '.') ?></div>
                    <div class="text-[11px] text-gray-600 mt-1">Realizado</div>
                    <div><?= 'R$ ' . number_format($real, 2, ',', '.') ?></div>
                  <?php else: ?>
                    <span class="text-gray-400">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td class="p-2 whitespace-nowrap"><?= 'R$ ' . number_format($tr, 2, ',', '.') ?></td>
              <td class="p-2"><?= $pct ?>%</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
