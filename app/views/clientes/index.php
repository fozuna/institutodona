<?php /** @var array $items */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-brand-black">Clientes</h1>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=clientes/create">Novo Cliente</a>
    </div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Empresa</th>
                    <th class="p-3">CNPJ</th>
                    <th class="p-3">Contato</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $c): ?>
                    <tr class="border-b">
                        <td class="p-3"><a class="text-brand-red hover:underline" href="index.php?route=clientes/show&id=<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome_empresa']) ?></a></td>
                        <td class="p-3"><?= htmlspecialchars($c['CNPJ']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['contato']) ?></td>
                        <td class="p-3">
                            <a class="text-brand-pink icon-action" href="index.php?route=clientes/edit&id=<?= (int)$c['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=clientes/delete&id=<?= (int)$c['id'] ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
