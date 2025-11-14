<?php /** @var array $clientes */ ?>
<?php /** @var array $kanbanData */ ?>
<?php /** @var array $stats */ ?>
<?php /** @var int|null $selectedCliente */ ?>
<?php /** @var array $user */ ?>

<div class="space-y-6">
    <!-- Cabeçalho e filtro -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-sm text-gray-600">
                Visão <?= htmlspecialchars($user['tipo_acesso']) ?>
            </p>
        </div>

        <?php if ($user['tipo_acesso'] === 'instituto'): ?>
            <form method="get" class="flex items-center gap-2">
                <input type="hidden" name="route" value="dashboard/index" />
                <label class="text-sm">Cliente</label>
                <select name="cliente" class="border rounded p-2 min-w-[16rem]">
                    <option value="">-- Geral --</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $selectedCliente === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome_empresa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Filtrar</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Cards de estatísticas por pilar -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
        <?php
        // Agrupar stats por pilar
        $grouped = [];
        foreach ($stats as $s) {
            $grouped[$s['pilar']][$s['status']] = (int)$s['total'];
        }
        ?>
        <?php foreach ($grouped as $pilar => $data): ?>
            <div class="bg-white shadow rounded p-4 border">
                <div class="font-semibold mb-2 text-brand-brown"><?= htmlspecialchars($pilar) ?></div>
                <div class="flex gap-3 text-sm">
                    <span class="px-2 py-1 rounded bg-brand-pink text-white">A Fazer: <?= $data['A Fazer'] ?? 0 ?></span>
                    <span class="px-2 py-1 rounded bg-brand-red text-white">Em Andamento: <?= $data['Em Andamento'] ?? 0 ?></span>
                    <span class="px-2 py-1 rounded bg-green-600 text-white">Concluído: <?= $data['Concluído'] ?? 0 ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Kanban -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php
        $columns = [
            'A Fazer' => 'bg-brand-pink',
            'Em Andamento' => 'bg-brand-red',
            'Concluído' => 'bg-green-600',
        ];
        ?>
        <?php foreach ($columns as $status => $color): ?>
            <div class="kanban-column bg-white shadow rounded border">
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <h2 class="font-semibold"><?= $status ?></h2>
                    <span class="text-xs px-2 py-1 rounded text-white <?= $color ?>"><?= count($kanbanData[$status] ?? []) ?></span>
                </div>
                <div class="p-4 space-y-4">
                    <?php if (!empty($kanbanData[$status])): ?>
                        <?php foreach ($kanbanData[$status] as $card): ?>
                            <div class="kanban-card bg-white rounded p-3">
                                <div class="text-sm text-gray-500 mb-1">Pilar: <?= htmlspecialchars($card['pilar_nome']) ?></div>
                                <div class="font-medium"><?= htmlspecialchars($card['item_pilar']) ?></div>
                                <div class="text-xs text-gray-500 mt-1">#<?= (int)$card['id'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-sm text-gray-500">Sem itens para este status.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Dicas removidas -->
</div>