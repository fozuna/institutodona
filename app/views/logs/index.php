<?php /** @var array $lines */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Logs do Sistema (Audit)</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
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
