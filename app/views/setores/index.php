<?php /** @var array $items */ /** @var array $departamentos */ /** @var int $selectedCliente */ ?>
<?php
$currentUser = $_SESSION['user'] ?? null;
$canClienteShow = \App\Core\AccessControl::canAccessRoute('clientes/show', 'GET', $currentUser);
$canCreate = \App\Core\AccessControl::canAccessRoute('setores/create', 'GET', $currentUser);
$canEdit = \App\Core\AccessControl::canAccessRoute('setores/edit', 'GET', $currentUser);
$canDelete = \App\Core\AccessControl::canAccessRoute('setores/delete', 'POST', $currentUser);
$backUrl = ($canClienteShow && $selectedCliente)
    ? ('index.php?route=clientes/show&id=' . (int)$selectedCliente)
    : 'index.php?route=setores/index' . ($selectedCliente ? '&cliente=' . (int)$selectedCliente : '');
?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Setores') ?></h1>
        <div class="flex items-center gap-2">
            <?php if ($canCreate): ?>
            <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=setores/create<?= $selectedCliente ? '&cliente='.(int)$selectedCliente : '' ?>">Novo Setor</a>
            <?php endif; ?>
            <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
        </div>
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
                            <?php if ($canEdit): ?>
                            <a class="text-brand-pink icon-action" href="index.php?route=setores/edit&id=<?= (int)$s['id'] ?><?= $selectedCliente ? '&cliente='.(int)$selectedCliente : '' ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=setores/delete&id=<?= (int)$s['id'] ?><?= $selectedCliente ? '&cliente='.(int)$selectedCliente : '' ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
