<?php use App\Core\Security; /** @var array $items */ /** @var array $filters */ /** @var array $clientes */ /** @var array $setores */ /** @var array $responsaveis */ /** @var bool $canManage */ ?>
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
            <label class="block text-sm">Responsável</label>
            <select name="responsavel" class="border rounded p-2 w-full">
                <option value="">Todos</option>
                <?php foreach ($responsaveis as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($filters['responsavel'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
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
            <label class="block text-sm">Busca global</label>
            <input name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Pergunta, objetivo, empresa, setor ou responsável" class="border rounded p-2 w-full" />
        </div>
        <div class="md:col-span-3">
            <label class="block text-sm">Ordenação</label>
            <select name="sort" class="border rounded p-2 w-full">
                <option value="data_desc" <?= (($filters['sort'] ?? '') === 'data_desc') ? 'selected' : '' ?>>Mais recentes</option>
                <option value="data_asc" <?= (($filters['sort'] ?? '') === 'data_asc') ? 'selected' : '' ?>>Mais antigas</option>
                <option value="empresa" <?= (($filters['sort'] ?? '') === 'empresa') ? 'selected' : '' ?>>Empresa</option>
                <option value="setor" <?= (($filters['sort'] ?? '') === 'setor') ? 'selected' : '' ?>>Setor</option>
                <option value="responsavel" <?= (($filters['sort'] ?? '') === 'responsavel') ? 'selected' : '' ?>>Responsável</option>
                <option value="status" <?= (($filters['sort'] ?? '') === 'status') ? 'selected' : '' ?>>Status</option>
            </select>
        </div>
        <div class="md:col-span-3 flex items-end gap-2">
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
                        <th class="p-3">Data</th>
                        <th class="p-3">Empresa</th>
                        <th class="p-3">Setor</th>
                        <th class="p-3">Responsável</th>
                        <th class="p-3">Pergunta</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                        <?php $isAgendada = (($row['status'] ?? '') === 'Agendada'); ?>
                        <tr class="border-b">
                            <td class="p-3"><?= htmlspecialchars(date('d/m/Y', strtotime($row['data_auditoria']))) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['cliente_nome'] ?? '') ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['setor_nome'] ?? '') ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['responsavel_nome'] ?? '') ?></td>
                            <td class="p-3 max-w-md"><?= htmlspecialchars($row['pergunta'] ?? '') ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs <?= $isAgendada ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <?php if (!empty($canManage) && $isAgendada): ?>
                                    <a class="text-brand-pink icon-action mr-2" href="index.php?route=auditorias/edit&id=<?= (int)$row['id'] ?>" title="Editar"><span data-feather="edit"></span></a>
                                    <button type="button" class="text-brand-red icon-action mr-2" data-open-auditar="<?= (int)$row['id'] ?>" title="Auditar"><span data-feather="check-circle"></span></button>
                                <?php endif; ?>
                                <?php if (!empty($canManage)): ?>
                                    <button type="button" class="text-brand-brown icon-action" data-open-delete="<?= (int)$row['id'] ?>" title="Excluir"><span data-feather="trash-2"></span></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!$isAgendada): ?>
                        <tr class="border-b bg-gray-50">
                            <td class="p-3" colspan="7">
                                <div class="text-xs"><strong>Realizada em:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['realizada_at']))) ?></div>
                                <div class="text-xs mt-1"><strong>Avaliação:</strong> <?= nl2br(htmlspecialchars($row['avaliacao'] ?? '')) ?></div>
                                <?php if (!empty($row['obs'])): ?>
                                <div class="text-xs mt-1"><strong>Obs:</strong> <?= nl2br(htmlspecialchars($row['obs'])) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
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

    <div id="modalAuditar" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-2xl">
            <h2 class="text-lg font-semibold mb-3">Registrar Auditoria</h2>
            <form method="post" action="index.php?route=auditorias/auditar" id="auditarForm">
                <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
                <input type="hidden" name="id" id="auditarId" />
                <div class="mb-3">
                    <label class="block text-sm">AVALIAÇÃO</label>
                    <textarea name="avaliacao" id="avaliacaoField" class="border rounded p-2 w-full" rows="5" minlength="50" required></textarea>
                    <p class="text-xs text-gray-500">Mínimo 50 caracteres.</p>
                </div>
                <div class="mb-3">
                    <label class="block text-sm">OBS</label>
                    <textarea name="obs" class="border rounded p-2 w-full" rows="4" maxlength="1000"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" id="cancelAuditar" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</button>
                    <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white">Auditar</button>
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
        const modalAuditar = document.getElementById('modalAuditar');
        const auditarId = document.getElementById('auditarId');
        document.querySelectorAll('[data-open-auditar]').forEach((btn)=>{
            btn.addEventListener('click', ()=>{
                auditarId.value = btn.getAttribute('data-open-auditar');
                modalAuditar.classList.remove('hidden');
                modalAuditar.classList.add('flex');
            });
        });
        document.getElementById('cancelAuditar')?.addEventListener('click', ()=>{
            modalAuditar.classList.add('hidden');
            modalAuditar.classList.remove('flex');
        });
        const auditarForm = document.getElementById('auditarForm');
        const avaliacaoField = document.getElementById('avaliacaoField');
        auditarForm?.addEventListener('submit', (e)=>{
            if ((avaliacaoField.value || '').trim().length < 50) {
                e.preventDefault();
                alert('A avaliação precisa ter no mínimo 50 caracteres.');
            }
        });
        }
        const filtroForm = document.getElementById('filtroAuditoriasForm');
        const btnFiltrar = document.getElementById('btnFiltrar');
        const skeleton = document.getElementById('auditoriasSkeleton');
        filtroForm?.addEventListener('submit', ()=>{
            btnFiltrar.disabled = true;
            btnFiltrar.textContent = 'Filtrando...';
            skeleton.classList.remove('hidden');
        });
    })();
</script>
