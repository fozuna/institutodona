<?php use App\Core\Security; /** @var array $items */ /** @var array $filters */ /** @var array $clientes */ /** @var array $setores */ /** @var bool $canManage */ ?>
<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Auditorias</h1>
        <?php if (!empty($canManage)): ?>
            <a href="index.php?route=auditorias/create" class="px-4 py-2 rounded bg-brand-red text-white">Cadastrar Nova Auditoria</a>
        <?php endif; ?>
    </div>

    <form method="get" action="index.php" id="filtroAuditoriasForm" class="bg-white shadow rounded p-4 mb-4 grid grid-cols-1 md:grid-cols-12 gap-3">
        <input type="hidden" name="route" value="auditorias/index" />
        <div class="md:col-span-2">
            <label class="block text-sm">Empresa</label>
            <select name="cliente" class="border rounded p-2 w-full">
                <option value="">Todas</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($filters['cliente'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm">Setor</label>
            <select name="setor" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <?php foreach ($setores as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= ((int)($filters['setor'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm">Status</label>
            <select name="status" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <option value="Agendada" <?= (($filters['status'] ?? '') === 'Agendada') ? 'selected' : '' ?>>Agendada</option>
                <option value="Realizada" <?= (($filters['status'] ?? '') === 'Realizada') ? 'selected' : '' ?>>Realizada</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm">Início</label>
            <input name="inicio" value="<?= htmlspecialchars(isset($filters['inicio']) && $filters['inicio'] ? date('d/m/Y', strtotime($filters['inicio'])) : '') ?>" placeholder="DD/MM/YYYY" class="border rounded p-2 w-full" />
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm">Fim</label>
            <input name="fim" value="<?= htmlspecialchars(isset($filters['fim']) && $filters['fim'] ? date('d/m/Y', strtotime($filters['fim'])) : '') ?>" placeholder="DD/MM/YYYY" class="border rounded p-2 w-full" />
        </div>
        <div class="md:col-span-6">
            <label class="block text-sm">Busca por nome</label>
            <input name="q" id="filtroNomeTempoReal" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Nome da auditoria, empresa ou setor" class="border rounded p-2 w-full" />
        </div>
        <input type="hidden" name="sort_col" id="sortCol" value="<?= htmlspecialchars($filters['sort_col'] ?? 'data') ?>" />
        <input type="hidden" name="sort_dir" id="sortDir" value="<?= htmlspecialchars($filters['sort_dir'] ?? 'desc') ?>" />
        <div class="md:col-span-4 flex items-end gap-2">
            <button type="submit" id="btnFiltrar" class="px-4 py-2 rounded bg-brand-red text-white">Filtrar</button>
            <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Limpar</a>
        </div>
    </form>
    <div id="auditoriasSkeleton" class="hidden bg-white shadow rounded p-4 mb-4">
        <div class="animate-pulse space-y-2">
            <div class="h-4 bg-gray-200 rounded w-1/3"></div>
            <div class="h-4 bg-gray-200 rounded w-full"></div>
            <div class="h-4 bg-gray-200 rounded w-full"></div>
            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="bg-white shadow rounded p-10 text-center">
            <div class="text-xl font-semibold mb-2">Nenhuma auditoria cadastrada</div>
            <div class="text-gray-600 mb-4">Cadastre a primeira auditoria para iniciar o acompanhamento.</div>
            <?php if (!empty($canManage)): ?>
                <a href="index.php?route=auditorias/create" class="px-4 py-2 rounded bg-brand-red text-white">Cadastrar Nova Auditoria</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="bg-white shadow rounded overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="p-3"><button type="button" class="sort-link" data-col="nome">Nome da Auditoria</button></th>
                        <th class="p-3"><button type="button" class="sort-link" data-col="setor">Setor</button></th>
                        <th class="p-3"><button type="button" class="sort-link" data-col="data">Data Agendada</button></th>
                        <th class="p-3"><button type="button" class="sort-link" data-col="status">Status</button></th>
                        <th class="p-3">Questões</th>
                        <th class="p-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                        <?php $isAgendada = (($row['status'] ?? '') === 'Agendada'); ?>
                        <tr class="border-b">
                            <td class="p-3">
                                <div class="font-semibold"><?= htmlspecialchars($row['nome_auditoria'] ?? '') ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($row['cliente_nome'] ?? '') ?></div>
                            </td>
                            <td class="p-3"><?= htmlspecialchars($row['setor_nome'] ?? '') ?></td>
                            <td class="p-3"><?= htmlspecialchars(date('d/m/Y', strtotime($row['data_auditoria']))) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs <?= $isAgendada ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="p-3"><?= (int)($row['total_questoes'] ?? 0) ?></td>
                            <td class="p-3 whitespace-nowrap">
                                <a class="text-brand-brown icon-action mr-2" href="index.php?route=auditorias/show&id=<?= (int)$row['id'] ?>" title="Abrir"><span data-feather="eye"></span></a>
                                <?php if (!empty($canManage) && $isAgendada): ?>
                                    <a class="text-brand-pink icon-action mr-2" href="index.php?route=auditorias/edit&id=<?= (int)$row['id'] ?>" title="Editar"><span data-feather="edit"></span></a>
                                <?php endif; ?>
                                <?php if (!empty($canManage) && (($row['status'] ?? '') !== 'Realizada')): ?>
                                    <a class="text-brand-red icon-action mr-2" href="index.php?route=auditorias/auditar&id=<?= (int)$row['id'] ?>" title="Auditar"><span data-feather="check-circle"></span></a>
                                <?php endif; ?>
                                <?php if (!empty($canManage) && (($row['status'] ?? '') === 'Realizada')): ?>
                                    <a class="text-brand-pink icon-action mr-2" href="index.php?route=auditorias/editar_realizada&id=<?= (int)$row['id'] ?>" title="Editar Obs. (Realizada)"><span data-feather="edit-2"></span></a>
                                <?php endif; ?>
                                <?php if (!empty($canManage)): ?>
                                    <button type="button" class="text-brand-brown icon-action" data-open-delete="<?= (int)$row['id'] ?>" title="Excluir"><span data-feather="trash-2"></span></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
            $qsBase = $_GET;
            $qsBase['route'] = 'auditorias/index';
        ?>
        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">Total: <?= (int)$total ?> registros</div>
            <div class="flex items-center gap-2">
                <?php for ($p = 1; $p <= (int)$totalPages; $p++): ?>
                    <?php $qsBase['page'] = $p; ?>
                    <a href="index.php?<?= http_build_query($qsBase) ?>" class="px-3 py-1 rounded <?= ((int)$page === $p) ? 'bg-brand-red text-white' : 'bg-gray-200 text-brand-brown' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($canManage)): ?>
    <div id="modalDelete" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-3">Confirmar exclusão</h2>
            <p class="text-sm text-gray-700 mb-4">Deseja realmente excluir esta auditoria?</p>
            <form method="post" action="index.php?route=auditorias/delete" class="flex items-center justify-end gap-2">
                <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
                <input type="hidden" name="id" id="deleteId" />
                <button type="button" id="cancelDelete" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</button>
                <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white">Excluir</button>
            </form>
        </div>
    </div>

    <?php endif; ?>
</div>
<script>
    (function(){
        const canManage = <?= !empty($canManage) ? 'true' : 'false' ?>;
        if (canManage) {
        const modalDelete = document.getElementById('modalDelete');
        const deleteId = document.getElementById('deleteId');
        document.querySelectorAll('[data-open-delete]').forEach((btn)=>{
            btn.addEventListener('click', ()=>{
                deleteId.value = btn.getAttribute('data-open-delete');
                modalDelete.classList.remove('hidden');
                modalDelete.classList.add('flex');
            });
        });
        document.getElementById('cancelDelete')?.addEventListener('click', ()=>{
            modalDelete.classList.add('hidden');
            modalDelete.classList.remove('flex');
        });
        }
        const filtroForm = document.getElementById('filtroAuditoriasForm');
        const btnFiltrar = document.getElementById('btnFiltrar');
        const skeleton = document.getElementById('auditoriasSkeleton');
        const sortCol = document.getElementById('sortCol');
        const sortDir = document.getElementById('sortDir');
        const filtroNome = document.getElementById('filtroNomeTempoReal');
        let debounce = null;
        document.querySelectorAll('.sort-link').forEach((el)=>{
            el.addEventListener('click', ()=>{
                const col = el.getAttribute('data-col');
                if (sortCol.value === col) {
                    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
                } else {
                    sortCol.value = col;
                    sortDir.value = col === 'nome' || col === 'setor' ? 'asc' : 'desc';
                }
                filtroForm.submit();
            });
        });
        filtroNome?.addEventListener('input', ()=>{
            clearTimeout(debounce);
            debounce = setTimeout(()=>filtroForm.submit(), 450);
        });
        filtroForm.querySelector('[name="setor"]')?.addEventListener('change', ()=>filtroForm.submit());
        filtroForm?.addEventListener('submit', ()=>{
            btnFiltrar.disabled = true;
            btnFiltrar.textContent = 'Filtrando...';
            skeleton.classList.remove('hidden');
        });
    })();
</script>
