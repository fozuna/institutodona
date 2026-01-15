<?php /** @var array $items */ /** @var array $funcoes */ /** @var int $cliente */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Colaboradores') ?></h1>
        <div class="flex items-center gap-2">
            <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=colaboradores/create<?= $cliente ? '&cliente='.(int)$cliente : '' ?>">Novo Colaborador</a>
            <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
        </div>
    </div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Nome</th>
                    <th class="p-3">E-mail</th>
                    <th class="p-3">Função</th>
                    <th class="p-3">Setor</th>
                    <th class="p-3">Departamento</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $c): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($c['nome']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['email'] ?? '') ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['funcao'] ?? '') ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['setor'] ?? '') ?></td>
                        <td class="p-3"><?= htmlspecialchars($c['departamento'] ?? '') ?></td>
                        <td class="p-3 whitespace-nowrap">
                            <a class="text-brand-pink icon-action" href="index.php?route=colaboradores/edit&id=<?= (int)$c['id'] ?><?= $cliente ? '&cliente='.(int)$cliente : '' ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=colaboradores/delete&id=<?= (int)$c['id'] ?><?= $cliente ? '&cliente='.(int)$cliente : '' ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
