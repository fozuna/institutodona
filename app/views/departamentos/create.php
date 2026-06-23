<?php
use App\Core\Security;
/** @var array $clientes */
/** @var int $cliente */
/** @var array $clientRootMap */
/** @var array $selectedClienteIds */
/** @var bool $compartilharTodasFiliais */
?>
<?php $backUrl = ((int)($cliente ?? 0) > 0) ? ('index.php?route=clientes/show&id=' . (int)$cliente) : 'index.php?route=clientes/index'; ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Novo Departamento') ?></h1>
    <form method="post" action="index.php?route=departamentos/store" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <div>
            <label class="block text-sm">Nome</label>
            <input name="nome" class="border rounded p-2 w-full" required />
        </div>
        <div>
            <label class="block text-sm">Empresa de origem</label>
            <select id="cliente_id" name="cliente_id" class="border rounded p-2 w-full" required>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($cliente ?? 0) > 0 && (int)$c['id'] === (int)$cliente) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rounded border p-4 space-y-3">
            <div class="flex items-center gap-2">
                <input id="compartilhar_todas_filiais" type="checkbox" name="compartilhar_todas_filiais" value="1" <?= !empty($compartilharTodasFiliais) ? 'checked' : '' ?> />
                <label for="compartilhar_todas_filiais" class="text-sm font-medium">Compartilhar com todas as empresas do grupo</label>
            </div>
            <div>
                <div class="text-sm font-medium mb-2">Empresas com acesso</div>
                <div id="cliente-share-list" class="space-y-2">
                    <?php foreach ($clientes as $empresa): ?>
                        <?php $empresaId = (int)($empresa['id'] ?? 0); ?>
                        <label class="flex items-center gap-2 text-sm" data-root-id="<?= (int)($clientRootMap[$empresaId] ?? 0) ?>">
                            <input type="checkbox" name="cliente_ids[]" value="<?= $empresaId ?>" <?= in_array($empresaId, array_map('intval', $selectedClienteIds ?? []), true) ? 'checked' : '' ?> />
                            <span><?= htmlspecialchars((string)($empresa['nome_empresa'] ?? '')) ?></span>
                            <?php if ((int)($empresa['is_matriz'] ?? 0) === 1): ?>
                                <span class="text-xs text-gray-500">(Matriz)</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
            <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($backUrl) ?>">Voltar</a>
        </div>
    </form>
</div>
<script>
(() => {
    const select = document.getElementById('cliente_id');
    const rows = Array.from(document.querySelectorAll('#cliente-share-list [data-root-id]'));
    const rootMap = <?= json_encode($clientRootMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const syncRows = () => {
        const selectedId = Number(select?.value || 0);
        const selectedRoot = Number(rootMap[selectedId] || 0);
        rows.forEach((row) => {
            const rowRoot = Number(row.getAttribute('data-root-id') || 0);
            const visible = selectedRoot > 0 && rowRoot === selectedRoot;
            row.style.display = visible ? '' : 'none';
            const input = row.querySelector('input[type="checkbox"]');
            if (!visible && input) {
                input.checked = false;
            }
            if (visible && input && Number(input.value) === selectedId) {
                input.checked = true;
            }
        });
    };
    if (select) {
        select.addEventListener('change', syncRows);
        syncRows();
    }
})();
</script>
