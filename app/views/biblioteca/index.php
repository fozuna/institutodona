<?php /** @var array $clientes */ /** @var int $cliente */ /** @var array $items */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Biblioteca de Arquivos</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4 mb-4">
    <form method="get" action="index.php" class="flex items-end gap-3">
      <input type="hidden" name="route" value="biblioteca/index" />
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
  <div class="bg-white shadow rounded p-4">
    <?php if (empty($items)): ?>
      <div class="text-sm text-gray-600">Selecione um cliente para listar os arquivos.</div>
    <?php else: ?>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="p-2">Arquivo</th>
            <th class="p-2">Tarefa</th>
            <th class="p-2">Pilar</th>
            <th class="p-2">Cliente</th>
            <th class="p-2">Tipo</th>
            <th class="p-2">Tamanho</th>
            <th class="p-2">Data</th>
            <th class="p-2">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr class="border-b">
              <td class="p-2"><?= htmlspecialchars($it['nome']) ?></td>
              <td class="p-2"><?= htmlspecialchars($it['tarefa']) ?> (#<?= (int)$it['aplicacao_id'] ?>)</td>
              <td class="p-2"><?= htmlspecialchars($it['pilar']) ?></td>
              <td class="p-2"><?= htmlspecialchars($it['cliente']) ?></td>
              <td class="p-2"><?= htmlspecialchars($it['mime']) ?></td>
              <td class="p-2"><?= number_format((int)$it['tamanho']/1024,1) ?> KB</td>
              <td class="p-2"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($it['uploaded_at']))) ?></td>
              <td class="p-2">
                <a class="text-brand-red hover:underline" target="_blank" href="../<?= htmlspecialchars($it['path']) ?>">Abrir</a>
                <a class="ml-2 hover:underline" href="index.php?route=aplicacoes/show&id=<?= (int)$it['aplicacao_id'] ?>">Tarefa</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="mt-3 flex items-center justify-between">
        <div class="text-xs text-gray-600">Total: <?= (int)$total ?> • Página <?= (int)$page ?>/<?= (int)$pages ?></div>
        <div class="flex items-center gap-2">
          <?php
            $base = 'index.php?route=biblioteca/index&cliente=' . (int)$cliente . '&perPage=' . (int)$perPage . '&page=';
          ?>
          <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown <?= $page<=1 ? 'opacity-50 pointer-events-none' : '' ?>" href="<?= $base . max(1, $page-1) ?>">Anterior</a>
          <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown <?= $page>=$pages ? 'opacity-50 pointer-events-none' : '' ?>" href="<?= $base . min($pages, $page+1) ?>">Próximo</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
