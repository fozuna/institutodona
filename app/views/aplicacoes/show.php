<?php /** @var array $app */ /** @var array $consultores */ ?>
<div class="p-6 max-w-3xl">
  <h1 class="text-2xl font-bold text-brand-black mb-2">Editar Tarefa</h1>
  <?php if (!$app): ?>
    <div class="bg-white shadow rounded p-4">Tarefa não encontrada.</div>
  <?php else: ?>
    <div class="bg-white shadow rounded p-4 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-500">Cliente</div>
          <div class="font-semibold"><?= htmlspecialchars($app['cliente_nome'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Pilar</div>
          <div class="font-semibold"><?= htmlspecialchars($app['pilar_nome'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Tarefa</div>
          <div class="font-semibold"><?= htmlspecialchars($app['item_pilar'] ?? '') ?></div>
        </div>
      </div>
    </div>

    <form method="post" action="index.php?route=aplicacoes/update" class="space-y-4 bg-white shadow rounded p-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="id_aplicacao" value="<?= (int)$app['id'] ?>" />
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm">Data prevista</label>
          <input type="date" name="data_prevista" value="<?= htmlspecialchars($app['data_prevista'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-sm">Consultor</label>
          <select name="consultor_id">
            <option value="">—</option>
            <?php foreach ($consultores as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((int)($app['consultor_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm">Status</label>
          <select name="status">
            <?php foreach (['A Fazer','Em Andamento','Concluído','Pendente'] as $s): ?>
              <option value="<?= $s ?>" <?= ($app['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="flex gap-2">
        <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
        <a class="icon-btn icon-btn--muted" href="index.php?route=clientes/show&id=<?= (int)$app['id_cliente'] ?>" title="Voltar" aria-label="Voltar"><span data-feather="arrow-left"></span></a>
      </div>
    </form>
  <?php endif; ?>
</div>
