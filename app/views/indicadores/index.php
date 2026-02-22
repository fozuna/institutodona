<?php /** @var array $clientes */ /** @var int $cliente */ /** @var array $items */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Indicadores</h1>
    <div class="flex items-center gap-2">
      <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=indicadores/create">Novo Indicador</a>
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
  </div>
  <div class="bg-white shadow rounded p-4 mb-4">
    <form method="get" action="index.php" class="flex items-end gap-3">
      <input type="hidden" name="route" value="indicadores/index" />
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
      <?php if ($cliente): ?>
        <a class="px-4 py-2 rounded bg-brand-brown text-white" href="index.php?route=indicadores/charts&cliente=<?= (int)$cliente ?>">Gráficos</a>
      <?php endif; ?>
    </form>
  </div>
  <div class="bg-white shadow rounded p-4">
    <?php if (!$cliente): ?>
      <div class="text-sm text-gray-600">Selecione um cliente para visualizar os indicadores.</div>
    <?php elseif (empty($items)): ?>
      <div class="text-sm text-gray-600">Nenhum indicador cadastrado para este cliente.</div>
    <?php else: ?>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="p-2">Indicador</th>
            <th class="p-2">Unidade</th>
            <th class="p-2">Referência</th>
            <th class="p-2">Meta</th>
            <th class="p-2">Realizado</th>
            <th class="p-2">Diferença</th>
            <th class="p-2">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr class="border-b">
              <td class="p-2"><?= htmlspecialchars($it['nome']) ?></td>
              <td class="p-2"><?= htmlspecialchars($it['unidade'] ?? '') ?></td>
              <td class="p-2"><?= htmlspecialchars($it['referencia'] ?? '') ?></td>
              <td class="p-2"><?= number_format((float)$it['meta'], 2, ',', '.') ?></td>
              <td class="p-2">
                <div class="flex items-center gap-2">
                  <span><?= number_format((float)$it['realizado'], 2, ',', '.') ?></span>
                  <form method="post" action="index.php?route=indicadores/updateRealizado" class="flex items-center gap-2">
                    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                    <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
                    <input type="number" step="0.01" name="realizado" class="border rounded p-1 w-24" placeholder="Atualizar" />
                    <button class="icon-btn icon-btn--primary" title="Atualizar" aria-label="Atualizar"><span data-feather="refresh-ccw"></span></button>
                  </form>
                </div>
              </td>
              <?php $diff = (float)$it['realizado'] - (float)$it['meta']; $pos = $diff >= 0; ?>
              <td class="p-2 <?= $pos ? 'text-green-700' : 'text-brand-red' ?>"><?= number_format($diff, 2, ',', '.') ?></td>
              <td class="p-2 whitespace-nowrap">
                <a class="text-brand-pink icon-action" href="index.php?route=indicadores/edit&id=<?= (int)$it['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                <a class="text-brand-brown icon-action ml-2" href="index.php?route=indicadores/delete&id=<?= (int)$it['id'] ?>" title="Excluir" aria-label="Excluir" onclick="return confirm('Excluir este indicador?')"><span data-feather="trash-2"></span></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
