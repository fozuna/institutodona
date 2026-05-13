<?php /** @var array $lines */ /** @var string $q */ /** @var int $limit */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Logs do Sistema (Audit)</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <form method="get" action="index.php" class="bg-white shadow rounded p-4 mb-4 flex flex-col md:flex-row gap-3 items-end">
    <input type="hidden" name="route" value="logs/index" />
    <div class="flex-1">
      <label class="block text-sm">Buscar (error_id, ação, entidade, etc.)</label>
      <input class="border rounded p-2 w-full" type="text" name="q" value="<?= htmlspecialchars((string)($q ?? '')) ?>" placeholder="Ex.: dcd1516f1c" />
    </div>
    <div class="w-full md:w-40">
      <label class="block text-sm">Limite</label>
      <input class="border rounded p-2 w-full" type="number" min="50" max="5000" name="limit" value="<?= (int)($limit ?? 500) ?>" />
    </div>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Filtrar</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=logs/index">Limpar</a>
    </div>
  </form>
  <div class="bg-white shadow rounded p-4">
    <?php if (empty($lines)): ?>
      <div class="text-sm text-gray-600">Nenhum log registrado ainda.</div>
    <?php else: ?>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="p-2">Data/Hora</th>
            <th class="p-2">Usuário</th>
            <th class="p-2">Ação</th>
            <th class="p-2">Entidade</th>
            <th class="p-2">Registro</th>
            <th class="p-2">Detalhes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lines as $ln): $row = @json_decode($ln, true) ?: []; ?>
            <tr class="border-b">
              <td class="p-2"><?= htmlspecialchars($row['ts'] ?? '') ?></td>
              <td class="p-2"><?= htmlspecialchars(($row['user_nome'] ?? '') . ' <' . ($row['user_email'] ?? '') . '>') ?></td>
              <td class="p-2"><?= htmlspecialchars($row['action'] ?? '') ?></td>
              <td class="p-2"><?= htmlspecialchars($row['entity'] ?? '') ?></td>
              <td class="p-2"><?= htmlspecialchars((string)($row['record_id'] ?? '')) ?></td>
              <td class="p-2"><pre class="whitespace-pre-wrap text-xs"><?= htmlspecialchars(json_encode($row['payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
