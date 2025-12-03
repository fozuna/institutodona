<?php /** @var array $pilares */ ?>
<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Nova Metodologia</h1>
    <form method="post" action="index.php?route=metodologias/store" class="space-y-4">
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
        <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
</div>
