<?php /** @var array $items */ /** @var array $departamentos */ /** @var int $selectedCliente */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Setores') ?></h1>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=setores/create<?= $selectedCliente ? '&cliente='.(int)$selectedCliente : '' ?>">Novo Setor</a>
    </div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Setor</th>
                    <th class="p-3">Departamento</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $s): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($s['nome']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($s['departamento'] ?? '') ?></td>
                        <td class="p-3 whitespace-nowrap">
                            <a class="text-brand-pink icon-action" href="index.php?route=setores/edit&id=<?= (int)$s['id'] ?><?= $selectedCliente ? '&cliente='.(int)$selectedCliente : '' ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=setores/delete&id=<?= (int)$s['id'] ?><?= $selectedCliente ? '&cliente='.(int)$selectedCliente : '' ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
