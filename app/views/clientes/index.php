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
                        <td class="p-3"><?= htmlspecialchars($c['nome_empresa']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['CNPJ']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['contato']) ?></td>
                        <td class="p-3">
                            <a class="text-brand-pink" href="index.php?route=clientes/edit&id=<?= (int)$c['id'] ?>">Editar</a>
                            <a class="text-brand-brown ml-4" href="index.php?route=clientes/delete&id=<?= (int)$c['id'] ?>">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>