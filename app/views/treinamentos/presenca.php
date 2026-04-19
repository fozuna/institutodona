<?php /** @var array $agenda */ /** @var array $participants */ /** @var array $pendingParticipants */ ?>
<div class="p-6 space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Lista de Presença') ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($agenda['treinamento_nome'] ?? '') ?> • <?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($agenda['data'] ?? ''))) ?></p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" onclick="window.print()" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Imprimir</button>
      <a class="px-4 py-2 rounded bg-brand-red text-white" href="index.php?route=treinamentos/show&id=<?= (int)($agenda['treinamento_id'] ?? 0) ?>">Voltar ao Treinamento</a>
    </div>
  </div>

  <div class="bg-white shadow rounded p-5">
    <h2 class="text-lg font-semibold mb-4">Adicionar Participantes Pendentes</h2>
    <form method="post" action="index.php?route=treinamentos/add_participantes" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="agenda_id" value="<?= (int)$agenda['id'] ?>" />
      <select name="colaborador_ids[]" multiple size="6" class="border rounded p-2 w-full">
        <?php foreach ($pendingParticipants as $pending): ?>
          <option value="<?= (int)$pending['colaborador_id'] ?>"><?= htmlspecialchars($pending['colaborador_nome'] . ' • ' . ($pending['funcao_nome'] ?? 'Sem função')) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Adicionar Selecionados</button>
    </form>
  </div>

  <form method="post" action="index.php?route=treinamentos/save_presence" class="bg-white shadow rounded p-5">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="agenda_id" value="<?= (int)$agenda['id'] ?>" />
    <div class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b text-left">
            <th class="p-3">Participante</th>
            <th class="p-3">Função</th>
            <th class="p-3">Presença</th>
            <th class="p-3">Certificado</th>
            <th class="p-3">Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($participants as $participant): ?>
            <tr class="border-b">
              <td class="p-3"><?= htmlspecialchars($participant['colaborador_nome']) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)($participant['funcao_nome'] ?? '—')) ?></td>
              <td class="p-3">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="presenca[<?= (int)$participant['colaborador_id'] ?>]" value="1" <?= !empty($participant['presenca']) ? 'checked' : '' ?> />
                  <span>Presente</span>
                </label>
              </td>
              <td class="p-3">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="certificado_emitido[<?= (int)$participant['colaborador_id'] ?>]" value="1" <?= !empty($participant['certificado_emitido']) ? 'checked' : '' ?> />
                  <span>Emitido</span>
                </label>
              </td>
              <td class="p-3">
                <?php if (!empty($participant['presenca']) || !empty($participant['certificado_emitido'])): ?>
                  <a class="px-3 py-1 rounded bg-blue-100 text-blue-700" href="index.php?route=treinamentos/certificado&agenda_id=<?= (int)$agenda['id'] ?>&colaborador_id=<?= (int)$participant['colaborador_id'] ?>" target="_blank">Gerar Certificado</a>
                <?php else: ?>
                  <span class="text-xs text-gray-500">Marque presença para liberar</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-4">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar Presença</button>
    </div>
  </form>
</div>
