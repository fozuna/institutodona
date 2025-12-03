<?php /** @var array|null $item */ ?>
<?php use App\Core\Security; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4">Editar Cliente</h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=clientes/update" class="space-y-4" enctype="multipart/form-data">
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
            <div>
                <label class="block text-sm">Logo (png, jpg, webp, svg)</label>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" />
                <?php if (!empty($item['logo_path'])): ?>
                  <div class="mt-2">
                    <img src="../<?= htmlspecialchars($item['logo_path']) ?>" alt="Logo atual" class="h-10 w-auto" />
                  </div>
                <?php endif; ?>
            </div>
            <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
        </form>
    <?php endif; ?>
</div>
