<?php /** @var array $metodologias */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-brand-black">Metodologias</h1>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=metodologias/create">Nova</a>
    </div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Pilar</th>
                    <th class="p-3">Item</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($metodologias as $m): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($m['pilar_nome']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($m['item_pilar']) ?></td>
                        <td class="p-3">
                            <a class="text-brand-pink icon-action" href="index.php?route=metodologias/edit&id=<?= (int)$m['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=metodologias/delete&id=<?= (int)$m['id'] ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
