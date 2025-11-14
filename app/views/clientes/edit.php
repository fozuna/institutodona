<?php /** @var array|null $item */ ?>
<?php use App\Core\Security; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4">Editar Cliente</h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=clientes/update" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <div>
                <label class="block text-sm">Nome da Empresa</label>
                <input name="nome_empresa" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome_empresa']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">CNPJ</label>
                <input name="CNPJ" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['CNPJ']) ?>" required />
            </div>
            <div>
                <label class="block text-sm">Contato</label>
                <input name="contato" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['contato']) ?>" />
            </div>
            <button class="px-4 py-2 bg-brand-red text-white rounded" type="submit">Salvar</button>
        </form>
    <?php endif; ?>
</div>