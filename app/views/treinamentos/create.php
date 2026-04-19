<?php use App\Core\Security; /** @var array $values */ /** @var array $errors */ /** @var array $departamentos */ /** @var array $setores */ /** @var array $funcoes */ /** @var array $periodicidades */ ?>
<div class="p-6 max-w-5xl">
  <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Novo Treinamento') ?></h1>
  <form method="post" action="index.php?route=treinamentos/store" class="space-y-5 bg-white shadow rounded p-5">
    <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
    <?php require __DIR__ . '/form_fields.php'; ?>
    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/index">Cancelar</a>
    </div>
  </form>
</div>
