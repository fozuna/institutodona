<?php use App\Core\Security; ?>
<div class="p-6 max-w-lg">
    <h1 class="text-2xl font-bold mb-4">Novo Pilar</h1>
    <form method="post" action="index.php?route=pilares/store" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <div>
            <label class="block text-sm">Nome</label>
            <input name="nome" class="border rounded p-2 w-full" required />
        </div>
        <button class="px-4 py-2 bg-brand-red text-white rounded" type="submit">Salvar</button>
    </form>
</div>