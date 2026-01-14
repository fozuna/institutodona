<?php /** @var array $pilares */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Nova Metodologia</h1>
        <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
    </div>
    <form method="post" action="index.php?route=metodologias/store" class="space-y-4" enctype="multipart/form-data">
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
        <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
</div>
