<?php /** @var array $clientes */ ?>
<?php /** @var array $kanbanData */ ?>
<?php /** @var array $stats */ ?>
<?php /** @var array $totalsByStatus */ ?>
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

    <!-- Apenas Kanban; números totais quando nenhum cliente selecionado -->

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
                    <h2 class="font-semibold">Tarefas — <?= $status ?></h2>
                    <span class="text-xs px-2 py-1 rounded text-white <?= $color ?>"><?= count($kanbanData[$status] ?? []) ?></span>
                </div>
                <div class="p-4 space-y-4">
                    <?php if (!empty($kanbanData[$status])): ?>
                        <?php foreach ($kanbanData[$status] as $card): ?>
                            <div class="kanban-card bg-white rounded p-3">
                                <div class="flex justify-between items-center mb-1">
                                  <div class="text-sm text-gray-500">Pilar: <?= htmlspecialchars($card['pilar_nome']) ?></div>
                                  <?php if (!empty($card['data_prevista'])): ?>
                                    <?php $dt = htmlspecialchars($card['data_prevista']);
                                          $overdue = (strtotime($card['data_prevista']) < strtotime('today') && $card['status'] !== 'Concluído'); ?>
                                    <span class="text-xs px-2 py-1 rounded <?= $overdue ? 'bg-brand-red text-white' : 'bg-gray-200 text-gray-800' ?>">Prevista: <?= $dt ?></span>
                                  <?php endif; ?>
                                </div>
                                <div class="font-medium mb-1"><?= htmlspecialchars($card['item_pilar']) ?></div>
                                <?php if (!empty($card['cliente_nome'])): ?>
                                  <div class="text-xs text-gray-600">Cliente: <?= htmlspecialchars($card['cliente_nome']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($card['consultor_nome'])): ?>
                                  <div class="text-xs text-gray-600">Consultor: <?= htmlspecialchars($card['consultor_nome']) ?></div>
                                <?php endif; ?>
                                <div class="text-xs text-gray-500 mt-1">
                                  <a class="text-brand-red hover:underline" href="index.php?route=clientes/show&id=<?= (int)$card['id_cliente'] ?>">Abrir cliente</a>
                                  <span class="ml-2">#<?= (int)$card['id'] ?></span>
                                </div>
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
