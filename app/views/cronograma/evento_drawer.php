<?php
use App\Core\Security;
/** @var array $event */
/** @var int $cronoId */
$eventId = (int)($event['id'] ?? 0);
$serieId = (int)($event['serie_id'] ?? $eventId);
$isReuniao = (string)($event['tipo_evento'] ?? '') === 'Reunião';
$isFinalizado = (string)($event['status'] ?? '') === 'Finalizado';
$anexos = $anexos ?? [];
$hasLegacyAta = !empty($hasLegacyAta);
$anexosCount = (int)count($anexos) + ($hasLegacyAta ? 1 : 0);
$scope = ($scope ?? 'evento') === 'serie' ? 'serie' : 'evento';
$mode = (string)($mode ?? 'edit');
$eventTypes = $eventTypes ?? [];
$pilares = $pilares ?? [];

$responsavelRaw = trim((string)($event['responsavel'] ?? ''));
$responsavelPrincipal = trim((string)($event['responsavel_principal'] ?? ''));
$responsaveisSecundarios = trim((string)($event['responsaveis_secundarios'] ?? ''));
if ($responsavelPrincipal === '' && $responsaveisSecundarios === '' && $responsavelRaw !== '') {
    $parts = array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $responsavelRaw) ?: []), static fn(string $p): bool => $p !== ''));
    $responsavelPrincipal = (string)($parts[0] ?? '');
    if (count($parts) > 1) {
        $responsaveisSecundarios = implode(', ', array_slice($parts, 1));
    }
}
$comentarios = (string)($event['comentarios'] ?? '');
$notasInternas = (string)($event['notas_internas'] ?? '');
?>

<div class="space-y-5" data-cronograma-drawer-content data-event-id="<?= $eventId ?>" data-serie-id="<?= $serieId ?>" data-mode="<?= htmlspecialchars($mode) ?>">
  <div class="flex items-start justify-between gap-3">
    <div class="min-w-0">
      <div class="text-xs text-gray-500">Evento #<?= $eventId ?> · Série #<?= $serieId ?></div>
      <h2 class="text-lg font-bold text-brand-black" id="cronogramaDrawerTitle"><?= $scope === 'serie' ? 'Editar série' : 'Editar evento' ?></h2>
      <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars((string)($event['data'] ?? '')) ?> · <?= htmlspecialchars((string)($event['topico'] ?? '')) ?></div>
    </div>
    <button type="button" class="icon-btn" data-cronograma-drawer-close aria-label="Fechar" title="Fechar"><span data-feather="x"></span></button>
  </div>

  <?php if ($scope === 'serie'): ?>
    <div class="p-3 rounded bg-amber-50 text-amber-800 text-xs">
      A edição da série pode atualizar ocorrências futuras conforme a periodicidade.
    </div>
  <?php endif; ?>

  <form method="post"
        action="index.php?route=cronograma/updateEvento"
        class="space-y-4"
        <?= $isReuniao ? 'enctype="multipart/form-data"' : '' ?>
        data-cronograma-drawer-form="edit"
        data-anexos-count="<?= $anexosCount ?>">
    <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
    <input type="hidden" name="id_evento" value="<?= $eventId ?>" />
    <input type="hidden" name="id_cronograma" value="<?= (int)$cronoId ?>" />
    <input type="hidden" name="status_filter" value="<?= htmlspecialchars((string)($_GET['status_filter'] ?? 'todos')) ?>" />
    <input type="hidden" name="escopo" value="<?= $scope ?>" />

    <section class="bg-white border rounded p-4 space-y-3">
      <div class="font-semibold text-sm">Informações gerais</div>
      <div class="grid grid-cols-1 gap-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_data">Data</label>
          <input id="drawer_data" class="border rounded p-3 w-full" type="date" name="data" value="<?= htmlspecialchars((string)($event['data'] ?? '')) ?>" required />
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_topico">Categoria</label>
          <input id="drawer_topico" class="border rounded p-3 w-full" type="text" name="topico" list="pilares_options_drawer" value="<?= htmlspecialchars((string)($event['topico'] ?? '')) ?>" required />
          <datalist id="pilares_options_drawer">
            <?php foreach ($pilares as $p): ?>
              <option value="<?= htmlspecialchars((string)($p['nome'] ?? '')) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_atividade">Descrição</label>
          <input id="drawer_atividade" class="border rounded p-3 w-full" type="text" name="atividade" value="<?= htmlspecialchars((string)($event['atividade'] ?? '')) ?>" required />
        </div>
      </div>
    </section>

    <section class="bg-white border rounded p-4 space-y-3">
      <div class="font-semibold text-sm">Participantes</div>
      <div class="grid grid-cols-1 gap-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_responsavel_principal">Responsável principal</label>
          <input id="drawer_responsavel_principal" class="border rounded p-3 w-full" type="text" name="responsavel_principal" value="<?= htmlspecialchars($responsavelPrincipal) ?>" placeholder="Ex.: Maria Silva" />
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_responsaveis_secundarios">Responsáveis secundários</label>
          <input id="drawer_responsaveis_secundarios" class="border rounded p-3 w-full" type="text" name="responsaveis_secundarios" value="<?= htmlspecialchars($responsaveisSecundarios) ?>" placeholder="Ex.: João Souza, Ana Lima" />
        </div>
      </div>
    </section>

    <section class="bg-white border rounded p-4 space-y-3">
      <div class="font-semibold text-sm">Classificação</div>
      <div class="grid grid-cols-1 gap-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_tipo_evento">Tipo do evento</label>
          <select id="drawer_tipo_evento" class="border rounded p-3 w-full" name="tipo_evento" required>
            <?php foreach ($eventTypes as $t): ?>
              <option value="<?= htmlspecialchars((string)$t) ?>" <?= (string)($event['tipo_evento'] ?? '') === (string)$t ? 'selected' : '' ?>><?= htmlspecialchars((string)$t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_modelo">Modalidade</label>
          <select id="drawer_modelo" class="border rounded p-3 w-full" name="modelo">
            <option value="">—</option>
            <option value="Online" <?= ((string)($event['modelo'] ?? '') === 'Online') ? 'selected' : '' ?>>On-line</option>
            <option value="Presencial" <?= ((string)($event['modelo'] ?? '') === 'Presencial') ? 'selected' : '' ?>>Presencial</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_periodicidade">Periodicidade</label>
          <select id="drawer_periodicidade"
                  class="border rounded p-3 w-full"
                  name="periodicidade"
                  data-initial-periodicidade="<?= htmlspecialchars((string)($event['periodicidade'] ?? 'unico')) ?>">
            <?php foreach (($periodicidades ?? []) as $value => $label): ?>
              <option value="<?= htmlspecialchars((string)$value) ?>" <?= ((string)($event['periodicidade'] ?? 'unico') === (string)$value) ? 'selected' : '' ?>><?= htmlspecialchars((string)$label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_status">Status</label>
          <select id="drawer_status" class="border rounded p-3 w-full" name="status" data-cronograma-status-select>
            <?php foreach (['Planejado','Pendente','Andamento','Adiado'] as $statusOpt): ?>
              <option value="<?= $statusOpt ?>" <?= ((string)($event['status'] ?? '') === $statusOpt) ? 'selected' : '' ?>><?= $statusOpt ?></option>
            <?php endforeach; ?>
            <?php if (!$isReuniao || $scope === 'evento'): ?>
              <option value="Finalizado" <?= ((string)($event['status'] ?? '') === 'Finalizado') ? 'selected' : '' ?>>Finalizado</option>
            <?php endif; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_local">Local</label>
          <input id="drawer_local" class="border rounded p-3 w-full" type="text" name="unidade" value="<?= htmlspecialchars((string)($event['unidade'] ?? '')) ?>" />
        </div>
      </div>
    </section>

    <section class="bg-white border rounded p-4 space-y-3">
      <div class="font-semibold text-sm">Observações</div>
      <div class="grid grid-cols-1 gap-3">
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_comentarios">Comentários</label>
          <textarea id="drawer_comentarios" class="border rounded p-3 w-full" name="comentarios" rows="3"><?= htmlspecialchars($comentarios) ?></textarea>
        </div>
        <div>
          <label class="block text-xs text-gray-600 mb-1" for="drawer_notas_internas">Notas internas</label>
          <textarea id="drawer_notas_internas" class="border rounded p-3 w-full" name="notas_internas" rows="3"><?= htmlspecialchars($notasInternas) ?></textarea>
        </div>
      </div>
    </section>

    <?php if ($isReuniao && !$isFinalizado && $scope === 'evento'): ?>
      <section class="bg-white border rounded p-4 space-y-3" data-cronograma-reuniao-anexos>
        <div class="font-semibold text-sm">Documentos para encerramento</div>
        <div class="text-xs text-gray-600">Para encerrar uma reunião, anexe pelo menos um documento.</div>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= 20 * 1024 * 1024 ?>" />
        <input class="border rounded p-3 w-full" type="file" name="anexos[]" multiple accept=".pdf,.doc,.docx,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.bmp,.svg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/rtf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml" />
        <div class="text-xs text-gray-500">Limite: 10 arquivos por envio, até 20MB por arquivo.</div>
      </section>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row gap-2">
      <button class="px-4 py-3 rounded bg-brand-red text-white font-semibold" type="submit" data-cronograma-save>Salvar alterações</button>
      <button class="px-4 py-3 rounded bg-gray-200 text-brand-brown" type="button" data-cronograma-drawer-close>Fechar</button>
    </div>
  </form>

  <section class="bg-white border rounded p-4 space-y-3" data-cronograma-docs>
    <div class="flex items-center justify-between gap-2">
      <div class="font-semibold text-sm">Documentos</div>
      <div class="text-xs text-gray-500"><?= $isReuniao ? ($anexosCount > 0 ? ('Anexados: ' . $anexosCount) : 'Nenhum documento') : 'Disponível para reuniões' ?></div>
    </div>
    <?php if (!$isReuniao): ?>
      <div class="text-xs text-gray-600">Este tipo de evento não possui documentos.</div>
    <?php else: ?>
      <div class="flex flex-col gap-1">
        <?php if ($hasLegacyAta): ?>
          <div class="text-xs">
            <a class="text-brand-red hover:underline" href="index.php?route=cronograma/ataDownload&id_evento=<?= $eventId ?>">Ata (legado)</a>
          </div>
        <?php endif; ?>
        <?php if (!empty($anexos)): ?>
          <?php foreach ($anexos as $a): ?>
            <div class="text-xs">
              <a class="text-brand-red hover:underline" href="index.php?route=cronograma/anexoDownload&id_anexo=<?= (int)($a['id'] ?? 0) ?>"><?= htmlspecialchars((string)($a['original_name'] ?? 'arquivo')) ?></a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($anexosCount <= 0): ?>
          <div class="text-xs text-gray-500">Nenhum documento anexado.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="bg-white border rounded p-4 space-y-3">
    <div class="font-semibold text-sm">Encerramento</div>
    <?php if ($isFinalizado): ?>
      <div class="text-xs text-gray-600">Este evento já está encerrado.</div>
    <?php elseif ($isReuniao): ?>
      <div class="text-xs text-gray-600">Para encerrar, defina o status como Finalizado e anexe documentos, se necessário.</div>
      <button type="button" class="px-4 py-3 rounded bg-green-600 text-white font-semibold w-full" data-cronograma-close-reuniao>Encerrar reunião</button>
    <?php else: ?>
      <form method="post" action="index.php?route=cronograma/toggleStatus" data-cronograma-close-form>
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <input type="hidden" name="id_evento" value="<?= $eventId ?>" />
        <input type="hidden" name="id_cronograma" value="<?= (int)$cronoId ?>" />
        <input type="hidden" name="status_filter" value="<?= htmlspecialchars((string)($_GET['status_filter'] ?? 'todos')) ?>" />
        <input type="hidden" name="realizado" value="0" />
        <button type="button" class="px-4 py-3 rounded bg-green-600 text-white font-semibold w-full" data-cronograma-close-event>Encerrar evento</button>
      </form>
    <?php endif; ?>
  </section>

  <section class="bg-white border rounded p-4 space-y-3">
    <div class="font-semibold text-sm text-red-700">Exclusão</div>
    <div class="text-xs text-gray-600">
      <?= $scope === 'serie'
        ? 'A exclusão da série remove a ocorrência atual e as demais ocorrências vinculadas.'
        : 'A exclusão remove permanentemente esta ocorrência do cronograma.' ?>
    </div>
    <form method="post" action="index.php?route=cronograma/deleteEvento" data-cronograma-delete-form>
      <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
      <input type="hidden" name="id" value="<?= $eventId ?>" />
      <input type="hidden" name="id_cronograma" value="<?= (int)$cronoId ?>" />
      <input type="hidden" name="status_filter" value="<?= htmlspecialchars((string)($_GET['status_filter'] ?? 'todos')) ?>" />
      <input type="hidden" name="escopo" value="<?= $scope ?>" />
      <button type="button"
              class="px-4 py-3 rounded bg-red-600 text-white font-semibold w-full"
              data-cronograma-delete-submit>
        <?= $scope === 'serie' ? 'Excluir série' : 'Excluir evento' ?>
      </button>
    </form>
  </section>
</div>
