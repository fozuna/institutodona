<?php /** @var array $clientes */ /** @var int $cliente */ /** @var array $items */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Lançar Realizado</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=indicadores/index&cliente=<?= (int)$cliente ?>">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4 mb-4">
    <form method="get" action="index.php" class="flex items-end gap-3">
      <input type="hidden" name="route" value="indicadores/realizado" />
      <div class="flex-1">
        <label class="text-sm">Cliente</label>
        <select name="cliente" class="border rounded p-2 w-full">
          <option value="">— selecione —</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="px-4 py-2 rounded bg-brand-red text-white">Filtrar</button>
    </form>
  </div>
  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded p-4">Selecione um cliente para lançar valores realizados.</div>
  <?php elseif (empty($items)): ?>
    <div class="bg-white shadow rounded p-4">Nenhum indicador cadastrado para este cliente.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded p-4">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="p-2">Indicador</th>
            <th class="p-2">Referência</th>
            <th class="p-2">Meta (R$)</th>
            <th class="p-2">Realizado (R$)</th>
            <th class="p-2">Diferença</th>
            <th class="p-2">Lançar</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <?php $diff = (float)$it['realizado'] - (float)$it['meta']; $pos = $diff >= 0; ?>
          <tr class="border-b">
            <td class="p-2"><?= htmlspecialchars($it['nome']) ?></td>
            <td class="p-2"><?= htmlspecialchars($it['referencia'] ?? '') ?></td>
            <td class="p-2"><?= number_format((float)$it['meta'], 2, ',', '.') ?></td>
            <td class="p-2"><?= number_format((float)$it['realizado'], 2, ',', '.') ?></td>
            <td class="p-2 <?= $pos ? 'text-green-700' : 'text-brand-red' ?>"><?= number_format($diff, 2, ',', '.') ?></td>
            <td class="p-2">
              <form method="post" action="index.php?route=indicadores/updateRealizado" class="flex items-center gap-2">
                <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
                <input type="number" step="0.01" name="realizado" class="border rounded p-1 w-28" placeholder="Valor (R$)" required />
                <button class="icon-btn icon-btn--primary" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
