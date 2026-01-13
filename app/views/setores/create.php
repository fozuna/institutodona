<?php use App\Core\Security; /** @var array $departamentos */ /** @var int $cliente */ ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Novo Setor') ?></h1>
    <form method="post" action="index.php?route=setores/store" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
        <div>
            <label class="block text-sm">Nome</label>
            <input name="nome" class="border rounded p-2 w-full" required />
        </div>
        <div>
            <label class="block text-sm">Departamento</label>
            <select name="departamento_id" class="border rounded p-2 w-full" required>
                <?php foreach ($departamentos as $d): ?>
                    <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
</div>
