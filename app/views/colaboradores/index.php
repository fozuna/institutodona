<?php /** @var array $items */ /** @var array $funcoes */ /** @var int $cliente */ ?>
<div class="p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Colaboradores') ?></h1>
        <div class="flex items-center gap-2">
            <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=colaboradores/create<?= $cliente ? '&cliente='.(int)$cliente : '' ?>">Novo Colaborador</a>
            <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
        </div>
    </div>
    <form method="get" action="index.php" class="mb-4 flex items-center gap-2 flex-wrap">
        <input type="hidden" name="route" value="colaboradores/index" />
        <label class="text-sm">Cliente</label>
        <select name="cliente" class="border rounded p-2 min-w-[16rem]">
            <option value="">-- Selecione --</option>
            <?php foreach ($clientes as $cl): ?>
                <option value="<?= (int)$cl['id'] ?>" <?= ((int)$cliente === (int)$cl['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cl['nome_empresa']) ?> <?= ((int)($cl['is_matriz'] ?? 1) === 1) ? '(Matriz)' : '(Filial)' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label class="text-sm">Líder</label>
        <select name="lider" class="border rounded p-2">
            <option value="">Todos</option>
            <option value="sim" <?= (($filter_lider ?? '') === 'sim') ? 'selected' : '' ?>>Sim</option>
            <option value="não" <?= (($filter_lider ?? '') === 'não') ? 'selected' : '' ?>>Não</option>
        </select>
        <label class="text-sm">Departamento</label>
        <select name="departamento" class="border rounded p-2 min-w-[12rem]">
            <option value="0">Todos</option>
            <?php foreach ($departamentos as $d): ?>
                <option value="<?= (int)$d['id'] ?>" <?= ((int)($filter_departamento ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="text-sm">Função</label>
        <select name="funcao" class="border rounded p-2 min-w-[12rem]">
            <option value="0">Todas</option>
            <?php foreach ($funcoes as $f): ?>
                <option value="<?= (int)$f['id'] ?>" <?= ((int)($filter_funcao ?? 0) === (int)$f['id']) ? 'selected' : '' ?>><?= htmlspecialchars($f['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="text-sm">Por página</label>
        <select name="per" class="border rounded p-2">
            <?php foreach ([10,20,50,100] as $opt): ?>
                <option value="<?= $opt ?>" <?= ((int)($per ?? 20) === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
        <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Filtrar</button>
    </form>
    <div class="bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="text-left border-b">
                    <th class="p-3">Nome</th>
                    <th class="p-3">E-mail</th>
                    <th class="p-3">Unidade</th>
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
                        <td class="p-3"><?= htmlspecialchars($c['unidade'] ?? '') ?></td>
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
    <?php if (($total_pages ?? 1) > 1): ?>
        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">Total: <?= (int)($total ?? 0) ?> • Página <?= (int)($page ?? 1) ?> de <?= (int)($total_pages ?? 1) ?></div>
            <div class="flex items-center gap-2">
                <?php $prev = max(1, (int)($page ?? 1) - 1); $next = min((int)($total_pages ?? 1), (int)($page ?? 1) + 1); ?>
                <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=colaboradores/index&cliente=<?= (int)$cliente ?>&page=<?= $prev ?>&per=<?= (int)($per ?? 20) ?>">◀</a>
                <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=colaboradores/index&cliente=<?= (int)$cliente ?>&page=<?= $next ?>&per=<?= (int)($per ?? 20) ?>">▶</a>
            </div>
        </div>
    <?php endif; ?>
</div>
