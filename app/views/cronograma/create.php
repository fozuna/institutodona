<?php /** @var array $clientes */ /** @var int $pref */ /** @var array|null $crono */ ?>
<div class="p-6 max-w-xl">
  <?php
    $crono = $crono ?? null;
    $isEdit = is_array($crono) && !empty($crono['id']);
    $pref = $isEdit ? (int)($crono['id_cliente'] ?? 0) : (int)($pref ?? 0);
    $nome = $isEdit ? (string)($crono['nome'] ?? '') : '';
    $ano = $isEdit ? (int)($crono['ano'] ?? date('Y')) : (int)date('Y');
    $ativo = $isEdit ? ((int)($crono['ativo'] ?? 1) === 1 ? 1 : 0) : 1;
  ?>
  <h1 class="text-2xl font-bold text-brand-black mb-4"><?= $isEdit ? 'Editar Cronograma' : 'Novo Cronograma' ?></h1>
  <form method="post" action="index.php?route=cronograma/<?= $isEdit ? 'update' : 'store' ?>" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int)$crono['id'] ?>" />
    <?php endif; ?>
    <div>
      <label class="block text-sm">Cliente</label>
      <select name="id_cliente" class="border rounded p-2 w-full">
        <?php foreach ($clientes as $cli): ?>
          <option value="<?= (int)$cli['id'] ?>" <?= ($pref === (int)$cli['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cli['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Nome do Cronograma</label>
      <input type="text" name="nome" class="border rounded p-2 w-full" value="<?= htmlspecialchars($nome) ?>" />
    </div>
    <div>
      <label class="block text-sm">Ano</label>
      <input type="number" name="ano" class="border rounded p-2 w-full" value="<?= (int)$ano ?>" />
    </div>
    <div>
      <label class="block text-sm">Status</label>
      <select name="ativo" class="border rounded p-2 w-full">
        <option value="1" <?= $ativo === 1 ? 'selected' : '' ?>>Ativo</option>
        <option value="0" <?= $ativo === 0 ? 'selected' : '' ?>>Inativo</option>
      </select>
    </div>
    <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
  </form>
</div>
