<?php use App\Core\Security; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Novo Cliente') ?></h1>
    <form method="post" action="index.php?route=clientes/store" class="space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <div>
            <label class="block text-sm">Nome da Empresa</label>
            <input name="nome_empresa" class="border rounded p-2 w-full" required />
        </div>
        <div>
            <label class="block text-sm">CNPJ</label>
            <input name="CNPJ" class="border rounded p-2 w-full" required />
        </div>
        <div>
            <label class="block text-sm">Contato</label>
            <input name="contato" class="border rounded p-2 w-full" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm">Tipo da Unidade</label>
                <select name="tipo_unidade" class="border rounded p-2 w-full">
                    <option value="matriz">Matriz</option>
                    <option value="filial">Filial</option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Matriz (quando Filial)</label>
                <select name="matriz_id" class="border rounded p-2 w-full">
                    <option value="">—</option>
                    <?php foreach ((new \App\Models\ClienteModel())->matrizes() as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nome_empresa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm">Logo (png, jpg, webp, svg)</label>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" />
        </div>
        <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
 </div>
