<?php /** @var array $agenda */ /** @var array $participants */ /** @var array $pendingParticipants */ ?>
<div class="p-6 space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Lista de Presença') ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($agenda['treinamento_nome'] ?? '') ?> • <?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($agenda['data'] ?? ''))) ?></p>
      <p class="text-xs text-gray-500 mt-1">Presença confirmada e certificado emitido possuem status independentes.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (!empty($agenda['encerrada_em'])): ?>
        <span class="px-3 py-2 rounded bg-gray-800 text-white text-sm">
          Turma Encerrada em <?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)$agenda['encerrada_em'])) ?>
        </span>
      <?php else: ?>
        <form method="post" action="index.php?route=treinamentos/close_agenda" onsubmit="return confirm('Encerrar esta turma? Participantes sem presença confirmada serão contabilizados como não participantes e não será mais possível adicionar novos participantes a esta turma. Esta ação não pode ser desfeita.');">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="agenda_id" value="<?= (int)$agenda['id'] ?>" />
          <button class="px-4 py-2 rounded bg-yellow-100 text-yellow-800 border border-yellow-300" type="submit">Encerrar Turma</button>
        </form>
      <?php endif; ?>
      <a class="px-4 py-2 rounded bg-red-100 text-red-700" href="index.php?route=treinamentos/presenca_pdf&agenda_id=<?= (int)$agenda['id'] ?>">Baixar PDF da Lista</a>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/show&id=<?= (int)($agenda['treinamento_id'] ?? 0) ?>">Voltar ao Treinamento</a>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Participantes</div><div class="text-2xl font-bold"><?= count($participants) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Presenças Confirmadas</div><div class="text-2xl font-bold"><?= count(array_filter($participants, static fn(array $row): bool => !empty($row['presenca']))) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Certificados Emitidos</div><div class="text-2xl font-bold"><?= count(array_filter($participants, static fn(array $row): bool => !empty($row['certificado_emitido']))) ?></div></div>
  </div>

  <?php if (empty($agenda['encerrada_em'])): ?>
  <div class="bg-white shadow rounded p-5">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
      <div>
        <h2 class="text-lg font-semibold">Adicionar Participantes Pendentes</h2>
        <p class="text-sm text-gray-500">A lista pré-cadastrada controla quem pode receber certificado antecipado.</p>
      </div>
      <form method="post" action="index.php?route=treinamentos/certificado_lote" class="flex items-center gap-2" target="_blank">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <input type="hidden" name="agenda_id" value="<?= (int)$agenda['id'] ?>" />
        <button class="px-4 py-2 rounded bg-emerald-100 text-emerald-700" type="submit">Emitir Certificados em Lote</button>
      </form>
    </div>
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
  <?php else: ?>
  <div class="bg-white shadow rounded p-5 text-sm text-gray-500">
    Turma encerrada — não é possível adicionar novos participantes.
  </div>
  <?php endif; ?>

  <form method="post" action="index.php?route=treinamentos/save_presence" class="bg-white shadow rounded p-5">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="agenda_id" value="<?= (int)$agenda['id'] ?>" />
    <?php if (!empty($agenda['encerrada_em'])): ?>
      <div class="mb-4 px-4 py-2 rounded bg-yellow-50 text-yellow-800 text-sm border border-yellow-200">
        Esta turma está encerrada. Você ainda pode corrigir registros de presença já lançados, mas novos participantes não podem ser adicionados.
      </div>
    <?php endif; ?>
    <div class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b text-left">
            <th class="p-3">Participante</th>
            <th class="p-3">Matrícula</th>
            <th class="p-3">Cargo</th>
            <th class="p-3">Presença Confirmada</th>
            <th class="p-3">Entrada</th>
            <th class="p-3">Saída</th>
            <th class="p-3">Status do Certificado</th>
            <th class="p-3">Observação</th>
            <th class="p-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($participants as $participant): ?>
            <tr class="border-b">
              <td class="p-3"><?= htmlspecialchars($participant['colaborador_nome']) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)($participant['matricula'] ?? '—')) ?></td>
              <td class="p-3"><?= htmlspecialchars((string)($participant['funcao_nome'] ?? '—')) ?></td>
              <td class="p-3">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="presenca[<?= (int)$participant['colaborador_id'] ?>]" value="1" <?= !empty($participant['presenca']) ? 'checked' : '' ?> />
                  <span>Presente</span>
                </label>
              </td>
              <td class="p-3">
                <input type="time" name="hora_entrada[<?= (int)$participant['colaborador_id'] ?>]" value="<?= htmlspecialchars(!empty($participant['hora_entrada']) ? substr((string)$participant['hora_entrada'], 0, 5) : '') ?>" class="border rounded p-2 w-28" />
              </td>
              <td class="p-3">
                <input type="time" name="hora_saida[<?= (int)$participant['colaborador_id'] ?>]" value="<?= htmlspecialchars(!empty($participant['hora_saida']) ? substr((string)$participant['hora_saida'], 0, 5) : '') ?>" class="border rounded p-2 w-28" />
              </td>
              <td class="p-3">
                <span class="px-2 py-1 rounded-full text-xs <?= !empty($participant['certificado_emitido']) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' ?>">
                  <?= !empty($participant['certificado_emitido']) ? 'Certificado Emitido' : 'Pendente' ?>
                </span>
                <?php if (!empty($participant['certificado_numero'])): ?>
                  <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string)$participant['certificado_numero']) ?></div>
                <?php endif; ?>
              </td>
              <td class="p-3">
                <input type="text" name="observacao[<?= (int)$participant['colaborador_id'] ?>]" value="<?= htmlspecialchars((string)($participant['observacao'] ?? '')) ?>" class="border rounded p-2 w-full min-w-[180px]" placeholder="Observações da presença" />
              </td>
              <td class="p-3">
                <div class="flex flex-col gap-2">
                  <a class="px-3 py-1 rounded bg-blue-100 text-blue-700 text-center" href="index.php?route=treinamentos/certificado&agenda_id=<?= (int)$agenda['id'] ?>&colaborador_id=<?= (int)$participant['colaborador_id'] ?>" target="_blank">Emitir / Reemitir</a>
                  <?php if (!empty($participant['certificado_arquivo'])): ?>
                    <a class="px-3 py-1 rounded bg-gray-100 text-gray-700 text-center" href="index.php?route=treinamentos/certificado&agenda_id=<?= (int)$agenda['id'] ?>&colaborador_id=<?= (int)$participant['colaborador_id'] ?>&download=1" target="_blank">Baixar</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($participants)): ?>
            <tr><td colspan="9" class="p-4 text-center text-gray-500">Nenhum participante pré-cadastrado para este agendamento.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar Presença</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/presenca_pdf&agenda_id=<?= (int)$agenda['id'] ?>">Versão para Impressão</a>
    </div>
  </form>
</div>
