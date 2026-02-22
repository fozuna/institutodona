<?php /** @var array $clientes */ ?>
<div class="p-6 max-w-2xl">
  <h1 class="text-2xl font-bold mb-4">Novo Indicador</h1>
  <form method="post" action="index.php?route=indicadores/store" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div>
      <label class="block text-sm">Cliente</label>
      <select name="cliente_id" required>
        <?php foreach ($clientes as $cli): ?>
          <option value="<?= (int)$cli['id'] ?>"><?= htmlspecialchars($cli['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Indicador</label>
      <input type="text" name="nome" required />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm">Unidade</label>
        <input type="text" name="unidade" placeholder="% ou R$" />
      </div>
      <div>
        <label class="block text-sm">Referência (mês)</label>
        <input type="month" name="referencia" />
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Meta</label>
        <input type="number" step="0.01" name="meta" required />
      </div>
      <div>
        <label class="block text-sm">Realizado</label>
        <input type="number" step="0.01" name="realizado" required />
      </div>
    </div>
    <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
  </form>
</div>
