<?php use App\Core\Security; /** @var array $funcoes */ /** @var int $cliente */ ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Novo Colaborador') ?></h1>
    <form method="post" action="index.php?route=colaboradores/store" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="cliente" value="<?= (int)$cliente ?>" />
        <div>
            <label class="block text-sm">Nome</label>
            <input name="nome" class="border rounded p-2 w-full" required />
        </div>
        <div>
            <label class="block text-sm">E-mail</label>
            <input name="email" class="border rounded p-2 w-full" type="email" />
        </div>
        <div>
            <label class="block text-sm">Função</label>
            <select name="funcao_id" class="border rounded p-2 w-full" required>
                <?php foreach ($funcoes as $f): ?>
                    <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nome']) ?> (<?= htmlspecialchars($f['setor'] ?? '') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
            <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">Cancelar</button>
        </div>
    </form>
</div>
