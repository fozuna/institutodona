<?php /** @var array $items */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Usuários') ?></h1>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=usuarios/create">Novo Usuário</a>
    </div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Nome</th>
                    <th class="p-3">E-mail</th>
                    <th class="p-3">Perfil</th>
                    <th class="p-3">Pertence a</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $u): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($u['nome']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="p-3"><?= htmlspecialchars(ucfirst($u['tipo_acesso'])) ?></td>
                        <td class="p-3"><?= htmlspecialchars($u['pertence'] ?? '') ?></td>
                        <td class="p-3 whitespace-nowrap">
                            <a class="text-brand-pink icon-action" href="index.php?route=usuarios/edit&id=<?= (int)$u['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=usuarios/delete&id=<?= (int)$u['id'] ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
 </div>
