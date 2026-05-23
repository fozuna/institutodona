<?php use App\Core\Security; /** @var array|null $item */ /** @var array $funcoes */ /** @var int $cliente */ ?>
<?php $backUrl = ((int)($cliente ?? 0) > 0) ? ('index.php?route=clientes/show&id=' . (int)$cliente) : 'index.php?route=clientes/index'; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Editar Colaborador') ?></h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=colaboradores/update" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
            <div>
                <label class="block text-sm">Nome</label>
                <input name="nome" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">E-mail</label>
                <input name="email" class="border rounded p-2 w-full" type="email" value="<?= htmlspecialchars($item['email'] ?? '') ?>" />
            </div>
            <div>
                <label class="block text-sm">Função</label>
                <select name="funcao_id" class="border rounded p-2 w-full" required>
                    <?php foreach ($funcoes as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= ((int)$f['id'] === (int)$item['funcao_id']) ? 'selected' : '' ?>><?= htmlspecialchars($f['nome']) ?> (<?= htmlspecialchars($f['setor'] ?? '') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm">Líder</label>
                <select name="lider" class="border rounded p-2 w-40">
                    <option value="não" <?= (($item['lider'] ?? 'não') === 'não') ? 'selected' : '' ?>>Não</option>
                    <option value="sim" <?= (($item['lider'] ?? 'não') === 'sim') ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
                <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
            </div>
        </form>
    <?php endif; ?>
</div>
