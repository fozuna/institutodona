<?php /** @var array $items */ /** @var array $setores */ /** @var int $cliente */ ?>
<?php
$currentUser = $_SESSION['user'] ?? null;
$canClienteShow = \App\Core\AccessControl::canAccessRoute('clientes/show', 'GET', $currentUser);
$canCreate = \App\Core\AccessControl::canAccessRoute('funcoes/create', 'GET', $currentUser);
$canEdit = \App\Core\AccessControl::canAccessRoute('funcoes/edit', 'GET', $currentUser);
$canDelete = \App\Core\AccessControl::canAccessRoute('funcoes/delete', 'POST', $currentUser);
$backUrl = ($canClienteShow && (int)($cliente ?? 0) > 0)
    ? ('index.php?route=clientes/show&id=' . (int)$cliente)
    : 'index.php?route=funcoes/index' . ($cliente ? '&cliente=' . (int)$cliente : '');
?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Funções') ?></h1>
        <div class="flex items-center gap-2">
            <?php if ($canCreate): ?>
            <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=funcoes/create<?= $cliente ? '&cliente='.(int)$cliente : '' ?>">Nova Função</a>
            <?php endif; ?>
            <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
        </div>
    </div>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Função</th>
                    <th class="p-3">Setor</th>
                    <th class="p-3">Departamento</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $f): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($f['nome']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($f['setor'] ?? '') ?></td>
                        <td class="p-3"><?= htmlspecialchars($f['departamento'] ?? '') ?></td>
                        <td class="p-3 whitespace-nowrap">
                            <?php if ($canEdit): ?>
                            <a class="text-brand-pink icon-action" href="index.php?route=funcoes/edit&id=<?= (int)$f['id'] ?><?= $cliente ? '&cliente='.(int)$cliente : '' ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=funcoes/delete&id=<?= (int)$f['id'] ?><?= $cliente ? '&cliente='.(int)$cliente : '' ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
