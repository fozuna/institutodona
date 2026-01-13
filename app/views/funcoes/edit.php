<?php use App\Core\Security; /** @var array|null $item */ /** @var array $setores */ /** @var int $cliente */ ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Editar Função') ?></h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=funcoes/update" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
            <div>
                <label class="block text-sm">Nome</label>
                <input name="nome" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">Setor</label>
                <select name="setor_id" class="border rounded p-2 w-full" required>
                    <?php foreach ($setores as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= ((int)$s['id'] === (int)$item['setor_id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
        </form>
    <?php endif; ?>
</div>
