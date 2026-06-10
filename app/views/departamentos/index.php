<?php /** @var array $items */ /** @var array $clientes */ /** @var int $selectedCliente */ ?>
<?php
$currentUser = $_SESSION['user'] ?? null;
$canClienteShow = \App\Core\AccessControl::canAccessRoute('clientes/show', 'GET', $currentUser);
$canCreate = \App\Core\AccessControl::canAccessRoute('departamentos/create', 'GET', $currentUser);
$canEdit = \App\Core\AccessControl::canAccessRoute('departamentos/edit', 'GET', $currentUser);
$canDelete = \App\Core\AccessControl::canAccessRoute('departamentos/delete', 'POST', $currentUser);
$backUrl = ($canClienteShow && $selectedCliente)
    ? ('index.php?route=clientes/show&id=' . (int)$selectedCliente)
    : 'index.php?route=departamentos/index' . ($selectedCliente ? '&cliente=' . (int)$selectedCliente : '');
?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Departamentos') ?></h1>
        <div class="flex items-center gap-2">
            <?php if ($canCreate): ?>
            <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=departamentos/create<?= $selectedCliente ? '&cliente=' . (int)$selectedCliente : '' ?>">Novo Departamento</a>
            <?php endif; ?>
            <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
        </div>
    </div>
    <form method="get" action="index.php" class="mb-4 flex items-center gap-2">
        <input type="hidden" name="route" value="departamentos/index" />
        <label class="text-sm">Cliente</label>
        <select name="cliente" class="border rounded p-2 min-w-[16rem]">
            <option value="">-- Todos --</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nome_empresa']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Filtrar</button>
    </form>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Departamento</th>
                    <th class="p-3">Cliente</th>
                    <th class="p-3">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $d): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= htmlspecialchars($d['nome']) ?></td>
                        <td class="p-3">
                            <?php if (!empty($d['cliente'])): ?>
                                <?= htmlspecialchars($d['cliente']) ?>
                            <?php else: ?>
                                <?php foreach ($clientes as $c): if ((int)$c['id'] === (int)$d['cliente_id']) { echo htmlspecialchars($c['nome_empresa']); break; } endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            <?php if ($canEdit): ?>
                            <a class="text-brand-pink icon-action" href="index.php?route=departamentos/edit&id=<?= (int)$d['id'] ?>" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <a class="text-brand-brown icon-action ml-2" href="index.php?route=departamentos/delete&id=<?= (int)$d['id'] ?>" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
