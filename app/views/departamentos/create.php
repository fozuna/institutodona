<?php use App\Core\Security; /** @var array $clientes */ /** @var int $cliente */ ?>
<?php $backUrl = ((int)($cliente ?? 0) > 0) ? ('index.php?route=clientes/show&id=' . (int)$cliente) : 'index.php?route=clientes/index'; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Novo Departamento') ?></h1>
    <form method="post" action="index.php?route=departamentos/store" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <div>
            <label class="block text-sm">Nome</label>
            <input name="nome" class="border rounded p-2 w-full" required />
        </div>
        <div>
            <label class="block text-sm">Cliente</label>
            <select name="cliente_id" class="border rounded p-2 w-full" required>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($cliente ?? 0) > 0 && (int)$c['id'] === (int)$cliente) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
            <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
        </div>
    </form>
</div>
