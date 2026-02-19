<?php /** @var array $clientes */ /** @var int $pref */ ?>
<div class="p-6 max-w-xl">
  <h1 class="text-2xl font-bold text-brand-black mb-4">Novo Cronograma</h1>
  <form method="post" action="index.php?route=cronograma/store" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div>
      <label class="block text-sm">Cliente</label>
      <select name="id_cliente">
        <?php foreach ($clientes as $cli): ?>
          <option value="<?= (int)$cli['id'] ?>" <?= ($pref === (int)$cli['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cli['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Nome do Cronograma</label>
      <input type="text" name="nome" />
    </div>
    <div>
      <label class="block text-sm">Ano</label>
      <input type="number" name="ano" value="<?= date('Y') ?>" />
    </div>
    <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
  </form>
</div>
