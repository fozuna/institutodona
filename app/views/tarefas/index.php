<?php /** @var array $items */ /** @var array $clientes */ /** @var int $selectedCliente */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Tarefas') ?></h1>
    <div class="flex items-center gap-2">
      <a class="icon-btn icon-btn--primary icon-btn--lg" href="index.php?route=tarefas/create" title="Nova tarefa" aria-label="Nova tarefa"><span data-feather="plus"></span></a>
      <a class="icon-btn icon-btn--muted icon-btn--lg" href="javascript:history.back()" title="Voltar" aria-label="Voltar"><span data-feather="arrow-left"></span></a>
    </div>
  </div>

  <form method="get" action="index.php" class="mb-4 flex flex-wrap items-end gap-2">
    <input type="hidden" name="route" value="tarefas/index" />
    <div>
      <label class="text-sm">Cliente</label>
      <select name="cliente" class="border rounded p-2 min-w-[16rem]">
        <option value="">-- Todos --</option>
        <?php foreach ($clientes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nome_empresa']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Filtrar</button>
  </form>

  <div class="bg-white shadow rounded overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left border-b bg-gray-50">
          <th class="p-3">Título</th>
          <th class="p-3">Cliente</th>
          <th class="p-3">Início</th>
          <th class="p-3">Status</th>
          <th class="p-3">Prioridade</th>
          <th class="p-3">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td class="p-4 text-center text-gray-500" colspan="6">Nenhuma tarefa cadastrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $i): ?>
          <tr class="border-b">
            <td class="p-3">
              <div class="font-medium"><?= htmlspecialchars((string)($i['titulo'] ?? '')) ?></div>
              <?php if (!empty($i['descricao'])): ?><div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)$i['descricao']) ?></div><?php endif; ?>
            </td>
            <td class="p-3"><?= htmlspecialchars((string)($i['cliente'] ?? '')) ?></td>
            <td class="p-3 whitespace-nowrap"><?= htmlspecialchars((string)($i['data_inicio'] ?? '')) ?></td>
            <td class="p-3"><?= htmlspecialchars((string)($i['status'] ?? '')) ?></td>
            <td class="p-3"><?= htmlspecialchars((string)($i['prioridade'] ?? '')) ?></td>
            <td class="p-3 whitespace-nowrap">
              <a class="text-brand-pink icon-action" href="index.php?route=tarefas/edit&id=<?= (int)$i['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
              <form method="post" action="index.php?route=tarefas/finalizar" class="inline">
                <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                <input type="hidden" name="id" value="<?= (int)$i['id'] ?>" />
                <input type="hidden" name="cliente_id" value="<?= (int)($i['cliente_id'] ?? 0) ?>" />
                <button class="text-green-700 icon-action ml-2" type="submit" title="Finalizar" aria-label="Finalizar"><span data-feather="check-circle"></span></button>
              </form>
              <a class="text-brand-brown icon-action ml-2" href="index.php?route=tarefas/delete&id=<?= (int)$i['id'] ?>" title="Excluir" aria-label="Excluir" onclick="return confirm('Excluir esta tarefa?');"><span data-feather="trash-2"></span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

