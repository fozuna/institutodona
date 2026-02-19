<?php /** @var array $crono */ ?>
<div class="p-6 max-w-3xl">
  <div class="flex justify-between items-center mb-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Adicionar evento</h1>
      <div class="text-sm text-gray-600"><?= htmlspecialchars($crono['cliente'] ?? '') ?> · Ano <?= (int)($crono['ano'] ?? date('Y')) ?></div>
      <?php if (!empty($crono['nome'])): ?><div class="text-sm text-gray-600">Cronograma: <?= htmlspecialchars($crono['nome']) ?></div><?php endif; ?>
    </div>
    <a class="px-3 py-2 rounded bg-brand-brown text-white" href="index.php?route=cronograma/show&id=<?= (int)$crono['id'] ?>">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4">
    <form method="post" action="index.php?route=cronograma/addEvento" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
      <label class="block text-sm">Data</label>
      <input type="date" name="data" />
      <label class="block text-sm">Tópico</label>
      <input type="text" name="topico" />
      <label class="block text-sm">Unidade</label>
      <input type="text" name="unidade" />
      <label class="block text-sm">Atividade</label>
      <input type="text" name="atividade" />
      <label class="block text-sm">Responsável</label>
      <input type="text" name="responsavel" />
      <label class="block text-sm">Modelo</label>
      <select name="modelo">
        <option value="">—</option>
        <option value="Online">On-line</option>
        <option value="Presencial">Presencial</option>
      </select>
      <label class="block text-sm">Status</label>
      <select name="status">
        <option value="Planejado">Planejado</option>
        <option value="Realizado">Realizado</option>
        <option value="Não Realizado">Não Realizado</option>
      </select>
      <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
  </div>
</div>
