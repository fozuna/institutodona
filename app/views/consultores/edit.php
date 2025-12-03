<?php /** @var array $item */ ?>
<div class="p-6">
  <h1 class="text-2xl font-bold text-brand-black mb-4">Editar Consultor</h1>
  <form method="post" action="index.php?route=consultores/update" class="space-y-4 bg-white shadow rounded p-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>" />
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Nome</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" required />
      </div>
      <div>
        <label class="block text-sm">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($item['email'] ?? '') ?>" required />
      </div>
      <div>
        <label class="block text-sm">Telefone</label>
        <input type="text" name="telefone" value="<?= htmlspecialchars($item['telefone'] ?? '') ?>" />
      </div>
      <div>
        <label class="block text-sm">Especialidade</label>
        <input type="text" name="especialidade" value="<?= htmlspecialchars($item['especialidade'] ?? '') ?>" />
      </div>
      <div>
        <label class="block text-sm">Senioridade</label>
        <select name="senioridade">
          <?php $sen = $item['senioridade'] ?? ''; ?>
          <option value="" <?= $sen === '' ? 'selected' : '' ?>>—</option>
          <option value="Junior" <?= $sen === 'Junior' ? 'selected' : '' ?>>Júnior</option>
          <option value="Pleno" <?= $sen === 'Pleno' ? 'selected' : '' ?>>Pleno</option>
          <option value="Senior" <?= $sen === 'Senior' ? 'selected' : '' ?>>Sênior</option>
        </select>
      </div>
      <div>
        <label class="block text-sm">Cidade</label>
        <input type="text" name="cidade" value="<?= htmlspecialchars($item['cidade'] ?? '') ?>" />
      </div>
      <div>
        <label class="block text-sm">Estado (UF)</label>
        <input type="text" name="estado" maxlength="2" value="<?= htmlspecialchars($item['estado'] ?? '') ?>" />
      </div>
    </div>
    <div>
      <label class="block text-sm">Observações</label>
      <textarea name="observacoes" rows="4"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
    </div>
    <div class="flex gap-3">
      <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
      <a class="icon-btn icon-btn--muted" href="index.php?route=consultores/index" title="Cancelar" aria-label="Cancelar"><span data-feather="x"></span></a>
    </div>
  </form>
</div>
