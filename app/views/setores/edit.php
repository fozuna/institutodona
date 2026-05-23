<?php use App\Core\Security; /** @var array|null $item */ /** @var array $departamentos */ /** @var int $cliente */ ?>
<?php $backUrl = ((int)($cliente ?? 0) > 0) ? ('index.php?route=clientes/show&id=' . (int)$cliente) : 'index.php?route=clientes/index'; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Editar Setor') ?></h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=setores/update" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
            <div>
                <label class="block text-sm">Nome</label>
                <input name="nome" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">Departamento</label>
                <select name="departamento_id" class="border rounded p-2 w-full" required>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= ((int)$d['id'] === (int)$item['departamento_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
                <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
            </div>
        </form>
    <?php endif; ?>
</div>
