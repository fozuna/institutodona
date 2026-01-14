<?php /** @var array|null $metodologia */ ?>
<?php /** @var array $pilares */ ?>
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Editar Metodologia</h1>
    <?php if (!$metodologia): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=metodologias/update" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int)$metodologia['id'] ?>" />
            <div>
                <label class="block text-sm">Pilar</label>
                <select name="id_pilar" class="border rounded p-2 w-64">
                    <?php foreach ($pilares as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$metodologia['id_pilar']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm">Item do Pilar</label>
                <input name="item_pilar" class="border rounded p-2 w-full" value="<?= htmlspecialchars($metodologia['item_pilar']) ?>" />
            </div>
            <div>
                <label class="block text-sm">Tipo</label>
                <select name="tipo" class="border rounded p-2 w-64">
                    <option value="tarefa" <?= ($metodologia['tipo'] ?? 'tarefa') === 'tarefa' ? 'selected' : '' ?>>Tarefa</option>
                    <option value="manual" <?= ($metodologia['tipo'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Arquivo (opcional)</label>
                <?php if (!empty($metodologia['arquivo_path'])): ?>
                    <div class="text-xs mb-2">Atual: <a class="text-brand-red hover:underline" target="_blank" href="../<?= htmlspecialchars($metodologia['arquivo_path']) ?>">baixar</a></div>
                <?php endif; ?>
                <input type="file" name="arquivo" class="border rounded p-2 w-full" />
                <div class="text-xs text-gray-500 mt-1">PDF, DOC, DOCX, XLS, XLSX ou TXT</div>
            </div>
            <div>
                <label class="block text-sm">Observações (opcional)</label>
                <textarea name="observacoes" class="border rounded p-2 w-full" rows="4"><?= htmlspecialchars($metodologia['observacoes'] ?? '') ?></textarea>
            </div>
            <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
        </form>
    <?php endif; ?>
</div>
