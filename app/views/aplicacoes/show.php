<?php /** @var array $app */ /** @var array $consultores */ /** @var array $funcoes */ ?>
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
      <div>
        <label class="block text-sm">Funções vinculadas (controle de acesso aos agendamentos)</label>
        <select name="funcao_ids[]" class="w-full" multiple size="8" required>
          <?php foreach ($funcoes as $fn): ?>
            <option value="<?= (int)$fn['id'] ?>" selected><?= htmlspecialchars(($fn['departamento'] ?? '') . ' / ' . ($fn['setor'] ?? '') . ' / ' . $fn['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-center gap-3">
        <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">SALVAR</button>
        <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=clientes/show&id=<?= (int)$app['id_cliente'] ?>">CANCELAR</a>
      </div>
    </form>
  <?php endif; ?>
</div>
