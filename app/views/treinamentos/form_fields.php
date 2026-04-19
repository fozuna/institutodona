<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="md:col-span-2">
    <label class="block text-sm">Nome</label>
    <input name="nome" class="border rounded p-2 w-full" required value="<?= htmlspecialchars((string)($values['nome'] ?? '')) ?>" />
    <?php if (!empty($errors['nome'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['nome']) ?></p><?php endif; ?>
  </div>
  <div class="md:col-span-2">
    <label class="block text-sm">Objetivo</label>
    <textarea name="objetivo" class="border rounded p-2 w-full" rows="4"><?= htmlspecialchars((string)($values['objetivo'] ?? '')) ?></textarea>
  </div>
  <div>
    <label class="block text-sm">Público</label>
    <input name="publico" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['publico'] ?? '')) ?>" />
  </div>
  <div>
    <label class="block text-sm">Carga Horária</label>
    <input name="carga_horaria" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['carga_horaria'] ?? '')) ?>" />
    <?php if (!empty($errors['carga_horaria'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['carga_horaria']) ?></p><?php endif; ?>
  </div>
  <div>
    <label class="block text-sm">Departamento</label>
    <select name="departamento_id" class="border rounded p-2 w-full" required>
      <option value="0">Selecione</option>
      <?php foreach ($departamentos as $departamento): ?>
        <option value="<?= (int)$departamento['id'] ?>" <?= ((int)($values['departamento_id'] ?? 0) === (int)$departamento['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($departamento['nome_empresa'] . ' • ' . $departamento['nome']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['departamento_id'])): ?><p class="text-xs text-red-600 mt-1"><?= htmlspecialchars($errors['departamento_id']) ?></p><?php endif; ?>
  </div>
  <div>
    <label class="block text-sm">Periodicidade</label>
    <select name="periodicidade" class="border rounded p-2 w-full">
      <?php foreach ($periodicidades as $key => $label): ?>
        <option value="<?= htmlspecialchars($key) ?>" <?= (($values['periodicidade'] ?? 'avulso') === $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="md:col-span-2">
    <label class="block text-sm">Fornecedor</label>
    <input name="fornecedor" class="border rounded p-2 w-full" value="<?= htmlspecialchars((string)($values['fornecedor'] ?? '')) ?>" />
  </div>
  <div>
    <label class="block text-sm">Setores Aplicáveis</label>
    <select name="setor_ids[]" multiple size="6" class="border rounded p-2 w-full">
      <?php foreach ($setores as $setor): ?>
        <option value="<?= (int)$setor['id'] ?>" <?= in_array((int)$setor['id'], array_map('intval', $values['setor_ids'] ?? []), true) ? 'selected' : '' ?>>
          <?= htmlspecialchars(($setor['departamento_nome'] ?? '') . ' • ' . $setor['nome']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Relacionamento N:N com setores. Não restringe a vinculação de colaboradores.</p>
  </div>
  <div>
    <label class="block text-sm">Funções Aplicáveis</label>
    <select name="funcao_ids[]" multiple size="6" class="border rounded p-2 w-full">
      <?php foreach ($funcoes as $funcao): ?>
        <option value="<?= (int)$funcao['id'] ?>" <?= in_array((int)$funcao['id'], array_map('intval', $values['funcao_ids'] ?? []), true) ? 'selected' : '' ?>>
          <?= htmlspecialchars(($funcao['setor_nome'] ?? '') . ' • ' . $funcao['nome']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Relacionamento N:N com funções. A participação continua livre por colaborador.</p>
  </div>
</div>
