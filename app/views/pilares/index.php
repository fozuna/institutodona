<?php /** @var array $items */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Pilares</h1>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=pilares/create">Novo Pilar</a>
    </div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Nome</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $p): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($p['nome']) ?></td>
                        <td class="p-3">
                            <a class="text-brand-pink icon-action" href="index.php?route=pilares/edit&id=<?= (int)$p['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=pilares/delete&id=<?= (int)$p['id'] ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
