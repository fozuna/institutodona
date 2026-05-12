<?php /** @var array $eligibleRows */ ?>
<?php foreach ($eligibleRows as $row): ?>
  <tr class="border-b">
    <td class="p-2"><input type="checkbox" name="colaborador_ids[]" value="<?= (int)$row['id'] ?>" <?= !empty($row['pre_cadastrado']) ? 'checked' : '' ?> /></td>
    <td class="p-2"><?= htmlspecialchars((string)$row['nome']) ?></td>
    <td class="p-2"><?= htmlspecialchars((string)($row['matricula'] ?? '—')) ?></td>
    <td class="p-2"><?= htmlspecialchars((string)($row['setor'] ?? '—')) ?></td>
    <td class="p-2"><?= htmlspecialchars((string)($row['cargo'] ?? '—')) ?></td>
    <td class="p-2"><?= htmlspecialchars((string)($row['cpf'] ?? '—')) ?></td>
    <td class="p-2"><?= htmlspecialchars((string)($row['email_corporativo'] ?? '—')) ?></td>
    <td class="p-2"><?= htmlspecialchars((string)($row['status_atual'] ?? '—')) ?></td>
    <td class="p-2"><span class="px-2 py-1 rounded-full text-xs <?= ($row['status_elegibilidade'] ?? '') === 'Elegivel' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= htmlspecialchars((string)($row['status_elegibilidade'] ?? '—')) ?></span></td>
  </tr>
<?php endforeach; ?>
<?php if (empty($eligibleRows)): ?>
  <tr><td colspan="9" class="p-4 text-center text-gray-500">Nenhum colaborador encontrado para os filtros informados.</td></tr>
<?php endif; ?>
