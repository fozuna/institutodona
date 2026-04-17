<?php use App\Core\DateHelper; use App\Core\Security; /** @var array $items */ /** @var array $filters */ /** @var array $clientes */ /** @var array $departamentos */ /** @var array $setores */ /** @var array $metrics */ /** @var bool $canManage */ ?>
<?php
    $mediaGeral = $metrics['geral']['media'] ?? null;
    $countGeral = (int)($metrics['geral']['count'] ?? 0);
    $mediaFiltrada = $metrics['filtrada']['media'] ?? null;
    $countFiltrada = (int)($metrics['filtrada']['count'] ?? 0);
?>
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
            <label class="block text-sm">Departamento</label>
            <select name="departamento" id="filtroDepartamento" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <?php foreach ($departamentos as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" data-cliente="<?= (int)($d['cliente_id'] ?? 0) ?>" <?= ((int)($filters['departamento'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
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
            <label class="block text-sm">Farol</label>
            <select name="farol" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <option value="vermelho" <?= (($filters['farol'] ?? '') === 'vermelho') ? 'selected' : '' ?>>Vermelho</option>
                <option value="amarelo" <?= (($filters['farol'] ?? '') === 'amarelo') ? 'selected' : '' ?>>Amarelo</option>
                <option value="verde" <?= (($filters['farol'] ?? '') === 'verde') ? 'selected' : '' ?>>Verde</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm">Início</label>
            <input name="inicio" value="<?= htmlspecialchars(isset($filters['inicio']) && $filters['inicio'] ? DateHelper::formatDate((string)$filters['inicio']) : '') ?>" placeholder="DD/MM/YYYY" class="border rounded p-2 w-full" />
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm">Fim</label>
            <input name="fim" value="<?= htmlspecialchars(isset($filters['fim']) && $filters['fim'] ? DateHelper::formatDate((string)$filters['fim']) : '') ?>" placeholder="DD/MM/YYYY" class="border rounded p-2 w-full" />
        </div>
        <div class="md:col-span-4">
            <label class="block text-sm">Busca por nome</label>
            <input name="q" id="filtroNomeTempoReal" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Nome da auditoria, empresa, setor ou departamento" class="border rounded p-2 w-full" />
        </div>
        <input type="hidden" name="sort_col" id="sortCol" value="<?= htmlspecialchars($filters['sort_col'] ?? 'data') ?>" />
        <input type="hidden" name="sort_dir" id="sortDir" value="<?= htmlspecialchars($filters['sort_dir'] ?? 'desc') ?>" />
        <div class="md:col-span-2 flex items-end gap-2">
            <button type="submit" id="btnFiltrar" class="px-4 py-2 rounded bg-brand-red text-white">Filtrar</button>
            <a href="index.php?route=auditorias/index" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Limpar</a>
        </div>
        <div class="md:col-span-6">
            <div class="h-full border rounded p-3 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-white border rounded p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Média Geral Abrangente</div>
                        <?php if ($countGeral > 0 && $mediaGeral !== null): ?>
                            <div class="text-2xl font-bold text-brand-brown mt-1"><?= number_format((float)$mediaGeral, 2, ',', '.') ?></div>
                            <div class="text-xs text-gray-500 mt-1">Base: <?= $countGeral ?> auditoria(s) realizada(s)</div>
                        <?php else: ?>
                            <div class="text-sm text-gray-500 mt-2">Sem dados suficientes para cálculo.</div>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white border rounded p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Média Filtrada Pelo Farol</div>
                        <?php if ($countFiltrada > 0 && $mediaFiltrada !== null): ?>
                            <div class="text-2xl font-bold text-brand-brown mt-1"><?= number_format((float)$mediaFiltrada, 2, ',', '.') ?></div>
                            <div class="text-xs text-gray-500 mt-1">Base: <?= $countFiltrada ?> auditoria(s) com filtros ativos</div>
                        <?php else: ?>
                            <div class="text-sm text-gray-500 mt-2">Nenhuma auditoria compatível com os filtros atuais.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
                        <th class="p-3"><button type="button" class="sort-link" data-col="departamento">Departamento</button></th>
                        <th class="p-3"><button type="button" class="sort-link" data-col="data">Data Agendada</button></th>
                        <th class="p-3"><button type="button" class="sort-link" data-col="status">Status</button></th>
                        <th class="p-3">Farol</th>
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
                                <?php if (!empty($row['responsaveis_nomes'])): ?>
                                    <div class="text-xs text-gray-500">Responsáveis: <?= htmlspecialchars($row['responsaveis_nomes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-3"><?= htmlspecialchars($row['setor_nome'] ?? '') ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['departamento_nome'] ?? 'Não informado') ?></td>
                            <td class="p-3"><?= htmlspecialchars(DateHelper::formatDate((string)($row['data_auditoria'] ?? ''))) ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs <?= $isAgendada ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="p-3">
                                <?php if (($row['status'] ?? '') === 'Realizada'): ?>
                                    <?php $pct = isset($row['conformidade_pct']) ? (float)$row['conformidade_pct'] : null; ?>
                                    <?php if ($pct !== null): ?>
                                        <?php
                                            $color = 'bg-red-100 text-red-700';
                                            if ($pct >= 100) $color = 'bg-green-100 text-green-700';
                                            elseif ($pct >= 75) $color = 'bg-yellow-100 text-yellow-700';
                                        ?>
                                        <span class="px-2 py-1 rounded text-xs font-semibold <?= $color ?>"><?= number_format($pct, 0) ?>%</span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500">-</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3"><?= (int)($row['total_questoes'] ?? 0) ?></td>
                            <td class="p-3 whitespace-nowrap">
                                <a class="text-brand-brown icon-action mr-2" href="index.php?route=auditorias/show&id=<?= (int)$row['id'] ?>" title="Abrir"><span data-feather="eye"></span></a>
                                <?php if (!empty($canManage)): ?>
                                    <a class="text-brand-pink icon-action mr-2" href="index.php?route=auditorias/edit&id=<?= (int)$row['id'] ?>" title="Editar"><span data-feather="edit"></span></a>
                                <?php endif; ?>
                                <?php if (!empty($canManage) && (($row['status'] ?? '') !== 'Realizada')): ?>
                                    <a class="text-brand-red icon-action mr-2" href="index.php?route=auditorias/auditar&id=<?= (int)$row['id'] ?>" title="Auditar"><span data-feather="check-circle"></span></a>
                                <?php endif; ?>
                                <?php if (!empty($canManage)): ?>
                                    <button type="button" class="text-brand-brown icon-action" data-open-delete="<?= (int)$row['id'] ?>" title="Excluir"><span data-feather="trash-2"></span></button>
                                <?php endif; ?>
                                <?php if (!empty($canManage)): ?>
                                    <button type="button" class="text-brand-brown icon-action ml-2" data-duplicate
                                        data-id="<?= (int)$row['id'] ?>"
                                        data-nome="<?= htmlspecialchars($row['nome_auditoria'] ?? '') ?>"
                                        data-data="<?= htmlspecialchars($row['data_auditoria'] ?? '') ?>"
                                        title="Duplicar Auditoria"><span data-feather="copy"></span></button>
                                    
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

    </div>

    <div id="modalDuplicate" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-3">Duplicar Auditoria</h2>
            <form method="post" action="index.php?route=auditorias/duplicate" id="duplicateForm" class="space-y-3">
                <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
                <input type="hidden" name="id" id="dupId" />
                <div>
                    <label class="block text-sm">Novo nome</label>
                    <input class="border rounded p-2 w-full" name="nome_auditoria" id="dupNome" required />
                </div>
                <div>
                    <label class="block text-sm">Nova data</label>
                    <input class="border rounded p-2 w-full" name="data_auditoria" id="dupData" placeholder="YYYY-MM-DD" />
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" id="cancelDuplicate" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</button>
                    <button type="submit" id="btnDuplicate" class="px-3 py-2 rounded bg-brand-red text-white">Duplicar</button>
                </div>
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
        const filtroCliente = filtroForm.querySelector('[name="cliente"]');
        const filtroDepartamento = document.getElementById('filtroDepartamento');
        let debounce = null;
        const syncDepartamentos = ()=>{
            if (!filtroCliente || !filtroDepartamento) return;
            const clienteId = filtroCliente.value;
            Array.from(filtroDepartamento.options).forEach((option, idx)=>{
                if (idx === 0) return;
                const optCliente = option.getAttribute('data-cliente') || '';
                const matches = !clienteId || optCliente === clienteId;
                option.hidden = !matches;
                if (!matches && option.selected) {
                    filtroDepartamento.value = '';
                }
            });
        };
        syncDepartamentos();
        filtroCliente?.addEventListener('change', ()=>{
            syncDepartamentos();
            filtroForm.submit();
        });
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
        filtroForm.querySelector('[name="farol"]')?.addEventListener('change', ()=>filtroForm.submit());
        filtroForm.querySelector('[name="departamento"]')?.addEventListener('change', ()=>filtroForm.submit());
        filtroForm.querySelector('[name="setor"]')?.addEventListener('change', ()=>filtroForm.submit());
        filtroForm?.addEventListener('submit', ()=>{
            btnFiltrar.disabled = true;
            btnFiltrar.textContent = 'Filtrando...';
            skeleton.classList.remove('hidden');
        });
        // Duplicar
        const modalDup = document.getElementById('modalDuplicate');
        const btnDup = document.getElementById('btnDuplicate');
        const dupId = document.getElementById('dupId');
        const dupNome = document.getElementById('dupNome');
        const dupData = document.getElementById('dupData');
        document.querySelectorAll('[data-duplicate]').forEach((el)=>{
            el.addEventListener('click', ()=>{
                dupId.value = el.getAttribute('data-id');
                const nome = el.getAttribute('data-nome') || '';
                dupNome.value = nome ? (nome + ' (cópia)') : '';
                dupData.value = (new Date()).toISOString().slice(0,10);
                modalDup.classList.remove('hidden');
                modalDup.classList.add('flex');
            });
        });
        document.getElementById('cancelDuplicate')?.addEventListener('click', ()=>{
            modalDup.classList.add('hidden');
            modalDup.classList.remove('flex');
        });
        document.getElementById('duplicateForm')?.addEventListener('submit', ()=>{
            const b = document.getElementById('btnDuplicate');
            if (b) { b.disabled = true; b.textContent = 'Duplicando...'; }
        });
        
    })();
</script>
