<?php /** @var array $pilares */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Nova Metodologia</h1>
        <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
    <form method="post" action="index.php?route=metodologias/store" class="space-y-4" enctype="multipart/form-data">
        <?php if (!empty($cliente)): ?>
            <input type="hidden" name="id_cliente" value="<?= (int)$cliente ?>" />
            <div class="text-xs text-gray-600">Tarefa será criada para a empresa #<?= (int)$cliente ?></div>
        <?php endif; ?>
        <div>
            <label class="block text-sm">Pilar</label>
            <select name="id_pilar" class="border rounded p-2 w-64">
                <?php foreach ($pilares as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm">Item do Pilar</label>
            <input name="item_pilar" class="border rounded p-2 w-full" />
        </div>
        <div>
            <label class="block text-sm">Tipo</label>
            <select name="tipo" class="border rounded p-2 w-64">
                <option value="tarefa">Tarefa</option>
                <option value="manual">Manual</option>
            </select>
        </div>
        <div>
            <label class="block text-sm">Arquivo (opcional)</label>
            <input type="file" name="arquivo" class="border rounded p-2 w-full" />
            <div class="text-xs text-gray-500 mt-1">PDF, DOC, DOCX, XLS, XLSX ou TXT</div>
        </div>
        <div>
            <label class="block text-sm">Observações (opcional)</label>
            <textarea name="observacoes" class="border rounded p-2 w-full" rows="4"></textarea>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">SALVAR</button>
            <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">CANCELAR</button>
        </div>
    </form>
</div>
<?php if (!empty($cliente)): ?>
<div class="p-6">
  <div class="bg-white shadow rounded">
    <div class="px-4 py-3 border-b font-semibold">Tarefas cadastradas para esta empresa</div>
    <div class="p-4">
      <?php if (empty($items ?? [])): ?>
        <div class="text-sm text-gray-600">Nenhuma tarefa cadastrada ainda.</div>
      <?php else: ?>
        <table class="min-w-full">
          <thead>
            <tr class="text-left border-b">
              <th class="p-3">Pilar</th>
              <th class="p-3">Item</th>
              <th class="p-3">Tipo</th>
              <th class="p-3">Arquivo</th>
              <th class="p-3">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $m): ?>
              <tr class="border-b">
                <td class="p-3"><?= htmlspecialchars($m['pilar_nome'] ?? '') ?></td>
                <td class="p-3"><?= htmlspecialchars($m['item_pilar'] ?? '') ?></td>
                <td class="p-3"><?= htmlspecialchars($m['tipo'] ?? '') ?></td>
                <td class="p-3">
                  <?php if (($m['tipo'] ?? 'tarefa') === 'manual' && !empty($m['arquivo_path'])): ?>
                    <a class="text-brand-red hover:underline" target="_blank" href="../<?= htmlspecialchars($m['arquivo_path']) ?>">baixar</a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td class="p-3 whitespace-nowrap">
                  <a class="text-brand-pink icon-action" href="index.php?route=metodologias/edit&id=<?= (int)$m['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                  <a class="text-brand-brown icon-action ml-2" href="index.php?route=metodologias/delete&id=<?= (int)$m['id'] ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>
