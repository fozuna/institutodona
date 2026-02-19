<?php /** @var array $clientes */ ?>
<div class="p-6 max-w-xl">
  <h1 class="text-2xl font-bold text-brand-black mb-4">Selecionar Cliente</h1>
  <form method="get" action="index.php" class="space-y-4">
    <input type="hidden" name="route" value="cronograma/create" />
    <div>
      <label class="block text-sm">Cliente</label>
      <select name="id_cliente">
        <?php foreach ($clientes as $cli): ?>
          <option value="<?= (int)$cli['id'] ?>"><?= htmlspecialchars($cli['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="icon-btn icon-btn--primary" type="submit" title="Avançar" aria-label="Avançar"><span data-feather="arrow-right"></span></button>
  </form>
</div>
