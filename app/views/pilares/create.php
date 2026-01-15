<?php use App\Core\Security; ?>
<div class="p-6 max-w-lg">
    <h1 class="text-2xl font-bold mb-4">Novo Pilar</h1>
    <form method="post" action="index.php?route=pilares/store" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <div>
            <label class="block text-sm">Nome</label>
            <input name="nome" class="border rounded p-2 w-full" required />
        </div>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">SALVAR</button>
            <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">CANCELAR</button>
        </div>
    </form>
</div>
