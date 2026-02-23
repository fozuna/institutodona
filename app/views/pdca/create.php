<?php /** @var array $clientes */ ?>
<?php
  $selectedCliente = $selectedCliente ?? null;
  $items = $items ?? [];
  $statusFilter = $statusFilter ?? '';
  $faseFilter = $faseFilter ?? '';
  $search = $search ?? '';
?>
<div class="p-6 space-y-6">
  <div class="max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Novo plano de ação (PDCA)</h1>
    <form method="post" action="index.php?route=pdca/store" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <div>
        <label class="block text-sm">Cliente</label>
        <select name="id_cliente">
          <?php foreach ($clientes as $cli): ?>
            <option value="<?= (int)$cli['id'] ?>" <?= ($selectedCliente === (int)$cli['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cli['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm">Título</label>
        <input type="text" name="titulo" required />
      </div>
      <div>
        <label class="block text-sm">Descrição</label>
        <textarea name="descricao" rows="3"></textarea>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">Meta (quantitativa/qualitativa)</label>
          <input type="number" step="0.01" name="meta_valor" />
        </div>
        <div>
          <label class="block text-sm">Indicador/Unidade</label>
          <input type="text" name="meta_unidade" />
        </div>
        <div>
          <label class="block text-sm">Prazo</label>
          <input type="date" name="prazo" />
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">Responsável</label>
          <input type="text" name="responsavel" />
        </div>
        <div>
          <label class="block text-sm">Fase</label>
          <select name="fase">
            <option value="PLAN">Planejar</option>
            <option value="DO">Executar</option>
            <option value="CHECK">Verificar</option>
            <option value="ACT">Agir</option>
          </select>
        </div>
        <div>
          <label class="block text-sm">Progresso (%)</label>
          <input type="number" name="progresso" min="0" max="100" value="0" />
        </div>
      </div>
      <div>
        <label class="block text-sm">Status</label>
        <select name="status">
          <option value="A Fazer">A Fazer</option>
          <option value="Em Andamento">Em Andamento</option>
          <option value="Concluído">Concluído</option>
          <option value="Pendente">Pendente</option>
        </select>
      </div>
      <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
  </div>

  <?php if ($selectedCliente): ?>
    <div class="bg-white shadow rounded p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="font-semibold">Planos de ação do cliente</div>
          <div class="text-xs text-gray-500">Use os filtros para localizar rapidamente os planos existentes.</div>
        </div>
        <form method="get" class="flex items-center gap-2">
          <input type="hidden" name="route" value="pdca/create" />
          <input type="hidden" name="cliente" value="<?= (int)$selectedCliente ?>" />
          <select name="status" class="text-sm">
            <option value="">Status (todos)</option>
            <?php foreach (['A Fazer','Em Andamento','Concluído','Pendente'] as $st): ?>
              <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          <select name="fase" class="text-sm">
            <option value="">Fase (todas)</option>
            <option value="PLAN" <?= $faseFilter === 'PLAN' ? 'selected' : '' ?>>Planejar</option>
            <option value="DO" <?= $faseFilter === 'DO' ? 'selected' : '' ?>>Executar</option>
            <option value="CHECK" <?= $faseFilter === 'CHECK' ? 'selected' : '' ?>>Verificar</option>
            <option value="ACT" <?= $faseFilter === 'ACT' ? 'selected' : '' ?>>Agir</option>
          </select>
          <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por título ou responsável" class="text-sm border rounded px-2 py-1" />
          <button class="btn btn-primary text-sm" type="submit">Filtrar</button>
        </form>
      </div>
      <table class="min-w-full">
        <thead>
          <tr class="text-left border-b">
            <th class="p-3">Título</th>
            <th class="p-3">Responsável</th>
            <th class="p-3">Fase</th>
            <th class="p-3">Prazo</th>
            <th class="p-3">Status</th>
            <th class="p-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $t): ?>
            <?php
              $faseMap = ['PLAN' => 'Planejar','DO' => 'Executar','CHECK' => 'Verificar','ACT' => 'Agir'];
              $faseNome = $faseMap[$t['fase']] ?? $t['fase'];
            ?>
            <tr class="border-b">
              <td class="p-3"><?= htmlspecialchars($t['titulo']) ?></td>
              <td class="p-3"><?= htmlspecialchars($t['responsavel'] ?? '—') ?></td>
              <td class="p-3"><?= htmlspecialchars($faseNome) ?></td>
              <td class="p-3"><?= htmlspecialchars($t['prazo'] ?? '—') ?></td>
              <td class="p-3"><?= htmlspecialchars($t['status']) ?></td>
              <td class="p-3">
                <a class="text-brand-red hover:underline" href="index.php?route=pdca/show&id=<?= (int)$t['id'] ?>">Abrir</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
            <tr><td class="p-3 text-sm text-gray-500" colspan="6">Nenhum plano de ação encontrado com os filtros atuais.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
