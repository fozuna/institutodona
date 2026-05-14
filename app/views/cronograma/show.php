<?php /** @var array $crono */ /** @var array $events */ /** @var array $grid */ ?>
<?php
  $months = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];
  $statusFilter = $statusFilter ?? 'todos';
?>
<style>
  .cronograma-traffic-cell,
  .cronograma-traffic-badge,
  .cronograma-traffic-row {
    transition: background-color .2s ease, color .2s ease, border-color .2s ease, opacity .2s ease;
  }
  .cronograma-toggle {
    width: 2.8rem;
    height: 1.55rem;
    border-radius: 9999px;
    position: relative;
    display: inline-flex;
    align-items: center;
    padding: 0 .2rem;
    transition: background-color .2s ease;
  }
  .cronograma-toggle::after {
    content: '';
    width: 1.1rem;
    height: 1.1rem;
    border-radius: 9999px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.18);
    transform: translateX(0);
    transition: transform .2s ease;
  }
  .cronograma-toggle[data-checked="1"]::after {
    transform: translateX(1.2rem);
  }
  .cronograma-sort-icon {
    transition: color .2s ease, transform .2s ease;
  }
</style>
<div class="p-6 space-y-6">
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Cronograma</h1>
      <div class="text-sm text-gray-600"><?= htmlspecialchars($crono['cliente'] ?? '') ?> · Ano <?= (int)($crono['ano'] ?? date('Y')) ?></div>
      <?php if (!empty($crono['nome'])): ?><div class="text-sm text-gray-600">Nome: <?= htmlspecialchars($crono['nome']) ?></div><?php endif; ?>
    </div>
    <div class="flex items-center gap-2">
      <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=cronograma/addEventoForm&id=<?= (int)$crono['id'] ?>">Adicionar evento</a>
      <a class="px-3 py-2 rounded bg-brand-brown text-white" href="index.php?route=clientes/show&id=<?= (int)($crono['id_cliente'] ?? 0) ?>">Voltar</a>
    </div>
  </div>

  <?php if (!empty($flashSuccess)): ?>
    <div class="px-4 py-3 rounded bg-green-100 text-green-800"><?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if (!empty($flashError)): ?>
    <div class="px-4 py-3 rounded bg-red-100 text-red-800"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <div class="bg-white shadow rounded">
    <div class="p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3 text-xs">
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Finalizado</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span> Pendente / Planejado</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Atrasado</span>
      </div>
      <form method="get" action="index.php" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="route" value="cronograma/show" />
        <input type="hidden" name="id" value="<?= (int)$crono['id'] ?>" />
        <label class="text-sm text-gray-600" for="status_filter">Filtro do farol</label>
        <select id="status_filter" name="status_filter" class="border rounded p-2 text-sm" onchange="this.form.submit()">
          <option value="todos" <?= $statusFilter === 'todos' ? 'selected' : '' ?>>Todos</option>
          <option value="finalizado" <?= $statusFilter === 'finalizado' ? 'selected' : '' ?>>Apenas finalizados</option>
          <option value="pendente" <?= $statusFilter === 'pendente' ? 'selected' : '' ?>>Apenas pendentes</option>
        </select>
      </form>
    </div>
  </div>

  <div class="bg-white shadow rounded">
    <div class="px-4 py-3 border-b font-semibold">Grade anual estilo planilha</div>
    <div class="p-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <th class="p-3">Pilar</th>
            <th class="p-3">Departamento</th>
            <th class="p-3">Atividade</th>
            <th class="p-3">Responsável</th>
            <th class="p-3">Status</th>
            <?php foreach ($months as $m): ?>
              <th class="p-3 text-center"><?= $m ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($grid)): ?>
            <tr><td class="p-4 text-center text-gray-500" colspan="17">Nenhum evento cadastrado para este cronograma.</td></tr>
          <?php endif; ?>
          <?php foreach ($grid as $row): ?>
            <tr class="border-b cronograma-traffic-row" data-serie-id="<?= (int)$row['serie_id'] ?>">
              <td class="p-3 font-medium"><?= htmlspecialchars($row['topico']) ?></td>
              <td class="p-3"><?= htmlspecialchars($row['unidade']) ?></td>
              <td class="p-3"><?= htmlspecialchars($row['atividade']) ?></td>
              <td class="p-3"><?= htmlspecialchars($row['responsavel']) ?></td>
              <td class="p-3">
                <span class="text-xs px-2 py-1 rounded cronograma-traffic-badge <?= htmlspecialchars($row['traffic']['badge_class'] ?? 'bg-gray-200 text-gray-700') ?>" data-series-badge="<?= (int)$row['serie_id'] ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <?php for ($mIdx = 1; $mIdx <= 12; $mIdx++): ?>
                <?php $cell = $row['meses'][$mIdx] ?? ['marked' => false, 'count' => 0, 'events' => [], 'traffic' => ['cell_class' => 'text-gray-300', 'label' => 'Sem evento']]; ?>
                <td class="p-3 text-center cronograma-traffic-cell <?= htmlspecialchars($cell['traffic']['cell_class'] ?? 'text-gray-300') ?>" data-series-month="<?= (int)$row['serie_id'] ?>-<?= $mIdx ?>">
                  <?php if (!empty($cell['marked'])): ?>
                    <?php
                      $tooltipDates = array_map(static fn(array $event): string => (string)$event['data'], $cell['events']);
                      $tooltip = implode(', ', $tooltipDates);
                    ?>
                    <span title="<?= htmlspecialchars(($cell['traffic']['label'] ?? 'Evento') . ': ' . $tooltip) ?>">X</span>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              <?php endfor; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php
    $occFilters = $occFilters ?? ['date_start' => '', 'date_end' => '', 'tipo' => [], 'status' => [], 'responsavel' => '', 'local' => '', 'error' => null];
    $occOrder = $occOrder ?? ['column' => 'data', 'direction' => 'asc'];
    $occOptions = $occOptions ?? ['tipos' => [], 'responsaveis' => [], 'locais' => [], 'status' => []];
    $totalEvents = $totalEvents ?? count($events ?? []);
    $isSmallDataset = $totalEvents < 1000;
    $occFilterError = $occFilterError ?? $occFilters['error'] ?? null;
  ?>
  <div class="bg-white shadow rounded">
    <div class="px-4 py-3 border-b font-semibold">Ocorrências materializadas</div>
    <div class="px-4 py-3 border-b bg-gray-50">
      <div id="occClientError" class="hidden px-3 py-2 mb-2 text-xs text-red-700 bg-red-100 rounded"></div>
      <?php if (!empty($occFilterError)): ?>
        <div class="px-3 py-2 mb-2 text-xs text-red-700 bg-red-100 rounded"><?= htmlspecialchars($occFilterError) ?></div>
      <?php endif; ?>
      <form method="get" action="index.php" class="space-y-3" id="occFiltersForm">
        <input type="hidden" name="route" value="cronograma/show" />
        <input type="hidden" name="id" value="<?= (int)$crono['id'] ?>" />
        <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
        <input type="hidden" name="occ_sort" value="<?= htmlspecialchars($occOrder['column'] ?? 'data') ?>" />
        <input type="hidden" name="occ_dir" value="<?= htmlspecialchars($occOrder['direction'] ?? 'asc') ?>" />
        <input type="hidden" name="occ_dataset" value="<?= $isSmallDataset ? 'client' : 'server' ?>" />
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
          <div>
            <label class="text-xs text-gray-600 block mb-1">Período (início)</label>
            <input type="date" name="occ_date_start" value="<?= htmlspecialchars($occFilters['date_start'] ?? '') ?>" class="w-full border rounded p-2 text-sm" />
          </div>
          <div>
            <label class="text-xs text-gray-600 block mb-1">Período (fim)</label>
            <input type="date" name="occ_date_end" value="<?= htmlspecialchars($occFilters['date_end'] ?? '') ?>" class="w-full border rounded p-2 text-sm" />
          </div>
          <div>
            <label class="text-xs text-gray-600 block mb-1">Tipo de evento</label>
            <select name="occ_tipo[]" class="w-full border rounded p-2 text-sm" multiple>
              <?php foreach (($occOptions['tipos'] ?? []) as $tipo): ?>
                <option value="<?= htmlspecialchars($tipo) ?>" <?= in_array($tipo, $occFilters['tipo'] ?? [], true) ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-600 block mb-1">Status</label>
            <div class="space-y-1 max-h-24 overflow-auto border rounded p-2 bg-white">
              <?php foreach (($occOptions['status'] ?? []) as $statusOpt): ?>
                <label class="flex items-center gap-2 text-xs">
                  <input type="checkbox" name="occ_status[]" value="<?= htmlspecialchars($statusOpt) ?>" <?= in_array($statusOpt, $occFilters['status'] ?? [], true) ? 'checked' : '' ?> />
                  <span><?= htmlspecialchars($statusOpt) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div>
            <label class="text-xs text-gray-600 block mb-1">Responsável</label>
            <input type="text" name="occ_responsavel" value="<?= htmlspecialchars($occFilters['responsavel'] ?? '') ?>" class="w-full border rounded p-2 text-sm" list="occ_responsaveis" />
            <datalist id="occ_responsaveis">
              <?php foreach (($occOptions['responsaveis'] ?? []) as $resp): ?>
                <option value="<?= htmlspecialchars($resp) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div>
            <label class="text-xs text-gray-600 block mb-1">Local</label>
            <input type="text" name="occ_local" value="<?= htmlspecialchars($occFilters['local'] ?? '') ?>" class="w-full border rounded p-2 text-sm" list="occ_locais" />
            <datalist id="occ_locais">
              <?php foreach (($occOptions['locais'] ?? []) as $local): ?>
                <option value="<?= htmlspecialchars($local) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-xs">Aplicar filtros</button>
          <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-xs" href="index.php?route=cronograma/show&id=<?= (int)$crono['id'] ?>&status_filter=<?= urlencode($statusFilter) ?>">Limpar filtros</a>
          <span class="text-xs text-gray-500">Modo de ordenação: <?= $isSmallDataset ? 'client-side' : 'server-side' ?></span>
        </div>
      </form>
    </div>
    <div class="p-4 overflow-x-auto">
      <table class="min-w-full text-sm" id="occTable">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <?php
              $occOrderColumn = $occOrder['column'] ?? 'data';
              $occOrderDirection = $occOrder['direction'] ?? 'asc';
              $occBase = $_GET;
              $occBase['route'] = 'cronograma/show';
              $occBase['id'] = (int)$crono['id'];
            ?>
            <th class="p-3">
              <?php
                $nextDir = ($occOrderColumn === 'data' && $occOrderDirection === 'asc') ? 'desc' : 'asc';
                $params = array_merge($occBase, ['occ_sort' => 'data', 'occ_dir' => $nextDir]);
                $active = $occOrderColumn === 'data';
              ?>
              <a class="inline-flex items-center gap-1 hover:underline" data-occ-sort="data" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                Data
                <span class="text-xs cronograma-sort-icon <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" data-sort-icon="data" aria-hidden="true">
                  <?= $active && $occOrderDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
                </span>
              </a>
            </th>
            <th class="p-3">
              <?php
                $nextDir = ($occOrderColumn === 'topico' && $occOrderDirection === 'asc') ? 'desc' : 'asc';
                $params = array_merge($occBase, ['occ_sort' => 'topico', 'occ_dir' => $nextDir]);
                $active = $occOrderColumn === 'topico';
              ?>
              <a class="inline-flex items-center gap-1 hover:underline" data-occ-sort="topico" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                Pilar / Departamento
                <span class="text-xs cronograma-sort-icon <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" data-sort-icon="topico" aria-hidden="true">
                  <?= $active && $occOrderDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
                </span>
              </a>
            </th>
            <th class="p-3">
              <?php
                $nextDir = ($occOrderColumn === 'atividade' && $occOrderDirection === 'asc') ? 'desc' : 'asc';
                $params = array_merge($occBase, ['occ_sort' => 'atividade', 'occ_dir' => $nextDir]);
                $active = $occOrderColumn === 'atividade';
              ?>
              <a class="inline-flex items-center gap-1 hover:underline" data-occ-sort="atividade" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                Atividade
                <span class="text-xs cronograma-sort-icon <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" data-sort-icon="atividade" aria-hidden="true">
                  <?= $active && $occOrderDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
                </span>
              </a>
            </th>
            <th class="p-3">
              <?php
                $nextDir = ($occOrderColumn === 'responsavel' && $occOrderDirection === 'asc') ? 'desc' : 'asc';
                $params = array_merge($occBase, ['occ_sort' => 'responsavel', 'occ_dir' => $nextDir]);
                $active = $occOrderColumn === 'responsavel';
              ?>
              <a class="inline-flex items-center gap-1 hover:underline" data-occ-sort="responsavel" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                Responsável
                <span class="text-xs cronograma-sort-icon <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" data-sort-icon="responsavel" aria-hidden="true">
                  <?= $active && $occOrderDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
                </span>
              </a>
            </th>
            <th class="p-3">
              <?php
                $nextDir = ($occOrderColumn === 'periodicidade' && $occOrderDirection === 'asc') ? 'desc' : 'asc';
                $params = array_merge($occBase, ['occ_sort' => 'periodicidade', 'occ_dir' => $nextDir]);
                $active = $occOrderColumn === 'periodicidade';
              ?>
              <a class="inline-flex items-center gap-1 hover:underline" data-occ-sort="periodicidade" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                Periodicidade
                <span class="text-xs cronograma-sort-icon <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" data-sort-icon="periodicidade" aria-hidden="true">
                  <?= $active && $occOrderDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
                </span>
              </a>
            </th>
            <th class="p-3">
              <?php
                $nextDir = ($occOrderColumn === 'status' && $occOrderDirection === 'asc') ? 'desc' : 'asc';
                $params = array_merge($occBase, ['occ_sort' => 'status', 'occ_dir' => $nextDir]);
                $active = $occOrderColumn === 'status';
              ?>
              <a class="inline-flex items-center gap-1 hover:underline" data-occ-sort="status" href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                Status
                <span class="text-xs cronograma-sort-icon <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" data-sort-icon="status" aria-hidden="true">
                  <?= $active && $occOrderDirection === 'asc' ? '▲' : ($active ? '▼' : '↕') ?>
                </span>
              </a>
            </th>
            <th class="p-3">Farol</th>
            <th class="p-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)): ?>
            <tr data-empty-row><td class="p-4 text-center text-gray-500" colspan="8">Nenhuma ocorrência encontrada com os filtros aplicados.</td></tr>
          <?php endif; ?>
          <?php foreach ($events as $ev): ?>
            <?php $isSeries = ($ev['periodicidade'] ?? 'unico') !== 'unico' || !empty($ev['evento_pai_id']); ?>
            <tr class="border-b align-top cronograma-traffic-row <?= htmlspecialchars($ev['traffic']['row_class'] ?? '') ?>"
                data-event-row="<?= (int)$ev['id'] ?>"
                data-ev-date="<?= htmlspecialchars($ev['data']) ?>"
                data-ev-topico="<?= htmlspecialchars($ev['topico']) ?>"
                data-ev-atividade="<?= htmlspecialchars($ev['atividade']) ?>"
                data-ev-responsavel="<?= htmlspecialchars($ev['responsavel'] ?? '') ?>"
                data-ev-periodicidade="<?= htmlspecialchars($ev['periodicidade'] ?? 'unico') ?>"
                data-ev-status="<?= htmlspecialchars($ev['traffic']['label'] ?? $ev['status'] ?? '') ?>"
                data-ev-local="<?= htmlspecialchars($ev['unidade'] ?? '') ?>"
            >
              <td class="p-3"><?= htmlspecialchars($ev['data']) ?></td>
              <td class="p-3">
                <div class="font-medium"><?= htmlspecialchars($ev['topico']) ?></div>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($ev['unidade'] ?? '') ?></div>
              </td>
              <td class="p-3"><?= htmlspecialchars($ev['atividade']) ?></td>
              <td class="p-3"><?= htmlspecialchars($ev['responsavel'] ?? '') ?></td>
              <td class="p-3"><?= htmlspecialchars($periodicidades[$ev['periodicidade'] ?? 'unico'] ?? ($ev['periodicidade'] ?? 'unico')) ?></td>
              <td class="p-3">
                <span class="text-xs px-2 py-1 rounded cronograma-traffic-badge <?= htmlspecialchars($ev['traffic']['badge_class'] ?? 'bg-gray-200 text-gray-700') ?>" data-event-badge="<?= (int)$ev['id'] ?>"><?= htmlspecialchars($ev['traffic']['label'] ?? $ev['status']) ?></span>
              </td>
              <td class="p-3">
                <form method="post" action="index.php?route=cronograma/toggleStatus" class="cronograma-toggle-form">
                  <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                  <input type="hidden" name="id_evento" value="<?= (int)$ev['id'] ?>" />
                  <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                  <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
                  <input type="hidden" name="occ_date_start" value="<?= htmlspecialchars($occFilters['date_start'] ?? '') ?>" />
                  <input type="hidden" name="occ_date_end" value="<?= htmlspecialchars($occFilters['date_end'] ?? '') ?>" />
                  <?php foreach (($occFilters['tipo'] ?? []) as $tipo): ?>
                    <input type="hidden" name="occ_tipo[]" value="<?= htmlspecialchars($tipo) ?>" />
                  <?php endforeach; ?>
                  <?php foreach (($occFilters['status'] ?? []) as $statusOpt): ?>
                    <input type="hidden" name="occ_status[]" value="<?= htmlspecialchars($statusOpt) ?>" />
                  <?php endforeach; ?>
                  <input type="hidden" name="occ_responsavel" value="<?= htmlspecialchars($occFilters['responsavel'] ?? '') ?>" />
                  <input type="hidden" name="occ_local" value="<?= htmlspecialchars($occFilters['local'] ?? '') ?>" />
                  <input type="hidden" name="occ_sort" value="<?= htmlspecialchars($occOrder['column'] ?? 'data') ?>" />
                  <input type="hidden" name="occ_dir" value="<?= htmlspecialchars($occOrder['direction'] ?? 'asc') ?>" />
                  <input type="hidden" name="realizado" value="<?= !empty($ev['traffic']['toggle_checked']) ? '1' : '0' ?>" data-realizado-input="<?= (int)$ev['id'] ?>" />
                  <button
                    type="button"
                    class="cronograma-toggle <?= !empty($ev['traffic']['toggle_checked']) ? 'bg-green-500' : 'bg-gray-300' ?>"
                    data-toggle-status
                    data-event-id="<?= (int)$ev['id'] ?>"
                    data-serie-id="<?= (int)($ev['serie_id'] ?? $ev['id']) ?>"
                    data-checked="<?= !empty($ev['traffic']['toggle_checked']) ? '1' : '0' ?>"
                    aria-label="Alternar finalizado"
                    title="Marcar ou desmarcar como finalizado"
                  ></button>
                </form>
              </td>
              <td class="p-3">
                <div class="mb-2 flex flex-wrap items-center gap-2" data-ata-wrap="<?= (int)$ev['id'] ?>">
                  <?php if (($ev['tipo_evento'] ?? '') === 'Reunião'): ?>
                    <form method="post" action="index.php?route=cronograma/ataUpload" class="cronograma-ata-form" enctype="multipart/form-data">
                      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                      <input type="hidden" name="id_evento" value="<?= (int)$ev['id'] ?>" />
                      <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                      <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
                      <input type="hidden" name="occ_date_start" value="<?= htmlspecialchars($occFilters['date_start'] ?? '') ?>" />
                      <input type="hidden" name="occ_date_end" value="<?= htmlspecialchars($occFilters['date_end'] ?? '') ?>" />
                      <?php foreach (($occFilters['tipo'] ?? []) as $tipo): ?>
                        <input type="hidden" name="occ_tipo[]" value="<?= htmlspecialchars($tipo) ?>" />
                      <?php endforeach; ?>
                      <?php foreach (($occFilters['status'] ?? []) as $statusOpt): ?>
                        <input type="hidden" name="occ_status[]" value="<?= htmlspecialchars($statusOpt) ?>" />
                      <?php endforeach; ?>
                      <input type="hidden" name="occ_responsavel" value="<?= htmlspecialchars($occFilters['responsavel'] ?? '') ?>" />
                      <input type="hidden" name="occ_local" value="<?= htmlspecialchars($occFilters['local'] ?? '') ?>" />
                      <input type="hidden" name="occ_sort" value="<?= htmlspecialchars($occOrder['column'] ?? 'data') ?>" />
                      <input type="hidden" name="occ_dir" value="<?= htmlspecialchars($occOrder['direction'] ?? 'asc') ?>" />
                      <input type="hidden" name="MAX_FILE_SIZE" value="<?= 50 * 1024 * 1024 ?>" />
                      <input
                        class="hidden"
                        type="file"
                        name="ata"
                        data-ata-input="<?= (int)$ev['id'] ?>"
                        accept=".pdf,.doc,.docx,.txt,.rtf,.xls,.xlsx,.ppt,.pptx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/rtf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                      />
                      <button type="button" class="icon-btn" title="Anexar ata" aria-label="Anexar ata" data-ata-upload="<?= (int)$ev['id'] ?>"><span data-feather="paperclip"></span></button>
                    </form>
                    <?php if (!empty($ev['ata_path'])): ?>
                      <a class="icon-btn" title="Baixar ata" aria-label="Baixar ata" data-ata-download="<?= (int)$ev['id'] ?>" href="index.php?route=cronograma/ataDownload&id_evento=<?= (int)$ev['id'] ?>"><span data-feather="file-text"></span></a>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
                <form method="post" action="index.php?route=cronograma/updateEvento" class="space-y-2">
                  <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                  <input type="hidden" name="id_evento" value="<?= (int)$ev['id'] ?>" />
                  <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                  <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
                  <input type="hidden" name="occ_date_start" value="<?= htmlspecialchars($occFilters['date_start'] ?? '') ?>" />
                  <input type="hidden" name="occ_date_end" value="<?= htmlspecialchars($occFilters['date_end'] ?? '') ?>" />
                  <?php foreach (($occFilters['tipo'] ?? []) as $tipo): ?>
                    <input type="hidden" name="occ_tipo[]" value="<?= htmlspecialchars($tipo) ?>" />
                  <?php endforeach; ?>
                  <?php foreach (($occFilters['status'] ?? []) as $statusOpt): ?>
                    <input type="hidden" name="occ_status[]" value="<?= htmlspecialchars($statusOpt) ?>" />
                  <?php endforeach; ?>
                  <input type="hidden" name="occ_responsavel" value="<?= htmlspecialchars($occFilters['responsavel'] ?? '') ?>" />
                  <input type="hidden" name="occ_local" value="<?= htmlspecialchars($occFilters['local'] ?? '') ?>" />
                  <input type="hidden" name="occ_sort" value="<?= htmlspecialchars($occOrder['column'] ?? 'data') ?>" />
                  <input type="hidden" name="occ_dir" value="<?= htmlspecialchars($occOrder['direction'] ?? 'asc') ?>" />
                  <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input class="border rounded p-2" type="date" name="data" value="<?= htmlspecialchars($ev['data']) ?>" />
                    <input class="border rounded p-2" type="text" name="topico" list="pilares_options" value="<?= htmlspecialchars($ev['topico']) ?>" placeholder="Pilar" />
                    <input class="border rounded p-2" type="text" name="unidade" value="<?= htmlspecialchars($ev['unidade'] ?? '') ?>" placeholder="Departamento" />
                    <input class="border rounded p-2" type="text" name="atividade" value="<?= htmlspecialchars($ev['atividade']) ?>" placeholder="Atividade" />
                    <input class="border rounded p-2" type="text" name="responsavel" value="<?= htmlspecialchars($ev['responsavel'] ?? '') ?>" placeholder="Responsável" />
                    <select class="border rounded p-2" name="periodicidade">
                      <?php foreach (($periodicidades ?? []) as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= ($ev['periodicidade'] ?? 'unico') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <select class="border rounded p-2" name="modelo">
                      <option value="">—</option>
                      <option value="Online" <?= ($ev['modelo'] ?? '') === 'Online' ? 'selected' : '' ?>>On-line</option>
                      <option value="Presencial" <?= ($ev['modelo'] ?? '') === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                    </select>
                    <select class="border rounded p-2" name="status">
                      <?php foreach (['Planejado','Pendente','Andamento','Adiado','Finalizado'] as $status): ?>
                        <option value="<?= $status ?>" <?= ($ev['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="flex flex-wrap items-center gap-2">
                    <button class="px-3 py-2 rounded bg-brand-red text-white text-xs" type="submit" name="escopo" value="evento">Salvar este evento</button>
                    <?php if ($isSeries): ?>
                      <button class="px-3 py-2 rounded bg-brand-brown text-white text-xs" type="submit" name="escopo" value="serie">Salvar toda a série</button>
                    <?php endif; ?>
                  </div>
                  <?php if ($isSeries): ?>
                    <p class="text-xs text-gray-500">Para alterar a recorrência ou regenerar as datas futuras, use a ação da série completa.</p>
                  <?php endif; ?>
                </form>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                  <form method="post" action="index.php?route=cronograma/deleteEvento" onsubmit="return confirm('Excluir apenas esta ocorrência?');">
                    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                    <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>" />
                    <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
                    <input type="hidden" name="occ_date_start" value="<?= htmlspecialchars($occFilters['date_start'] ?? '') ?>" />
                    <input type="hidden" name="occ_date_end" value="<?= htmlspecialchars($occFilters['date_end'] ?? '') ?>" />
                    <?php foreach (($occFilters['tipo'] ?? []) as $tipo): ?>
                      <input type="hidden" name="occ_tipo[]" value="<?= htmlspecialchars($tipo) ?>" />
                    <?php endforeach; ?>
                    <?php foreach (($occFilters['status'] ?? []) as $statusOpt): ?>
                      <input type="hidden" name="occ_status[]" value="<?= htmlspecialchars($statusOpt) ?>" />
                    <?php endforeach; ?>
                    <input type="hidden" name="occ_responsavel" value="<?= htmlspecialchars($occFilters['responsavel'] ?? '') ?>" />
                    <input type="hidden" name="occ_local" value="<?= htmlspecialchars($occFilters['local'] ?? '') ?>" />
                    <input type="hidden" name="occ_sort" value="<?= htmlspecialchars($occOrder['column'] ?? 'data') ?>" />
                    <input type="hidden" name="occ_dir" value="<?= htmlspecialchars($occOrder['direction'] ?? 'asc') ?>" />
                    <input type="hidden" name="escopo" value="evento" />
                    <button class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-xs" type="submit">Excluir este evento</button>
                  </form>
                  <?php if ($isSeries): ?>
                    <form method="post" action="index.php?route=cronograma/deleteEvento" onsubmit="return confirm('Excluir toda a série deste evento?');">
                      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                      <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>" />
                      <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                      <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
                      <input type="hidden" name="occ_date_start" value="<?= htmlspecialchars($occFilters['date_start'] ?? '') ?>" />
                      <input type="hidden" name="occ_date_end" value="<?= htmlspecialchars($occFilters['date_end'] ?? '') ?>" />
                      <?php foreach (($occFilters['tipo'] ?? []) as $tipo): ?>
                        <input type="hidden" name="occ_tipo[]" value="<?= htmlspecialchars($tipo) ?>" />
                      <?php endforeach; ?>
                      <?php foreach (($occFilters['status'] ?? []) as $statusOpt): ?>
                        <input type="hidden" name="occ_status[]" value="<?= htmlspecialchars($statusOpt) ?>" />
                      <?php endforeach; ?>
                      <input type="hidden" name="occ_responsavel" value="<?= htmlspecialchars($occFilters['responsavel'] ?? '') ?>" />
                      <input type="hidden" name="occ_local" value="<?= htmlspecialchars($occFilters['local'] ?? '') ?>" />
                      <input type="hidden" name="occ_sort" value="<?= htmlspecialchars($occOrder['column'] ?? 'data') ?>" />
                      <input type="hidden" name="occ_dir" value="<?= htmlspecialchars($occOrder['direction'] ?? 'asc') ?>" />
                      <input type="hidden" name="escopo" value="serie" />
                      <button class="px-3 py-2 rounded bg-red-100 text-red-700 text-xs" type="submit">Excluir série</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!empty($events)): ?>
            <tr data-empty-row class="hidden"><td class="p-4 text-center text-gray-500" colspan="8">Nenhuma ocorrência encontrada com os filtros aplicados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
  (function () {
    const pillars = <?= json_encode(array_values(array_filter(array_map(static fn($p) => (string)($p['nome'] ?? ''), $pilares ?? []))), JSON_UNESCAPED_UNICODE) ?>;
    const datalist = document.getElementById('pilares_options');
    if (!datalist && pillars.length) {
      const dl = document.createElement('datalist');
      dl.id = 'pilares_options';
      pillars.forEach((name) => {
        const opt = document.createElement('option');
        opt.value = name;
        dl.appendChild(opt);
      });
      document.body.appendChild(dl);
    }
    const activeFilter = <?= json_encode($statusFilter, JSON_UNESCAPED_UNICODE) ?>;
    const applyToggleVisualState = (button, checked, traffic) => {
      button.dataset.checked = checked ? '1' : '0';
      button.classList.toggle('bg-green-500', checked);
      button.classList.toggle('bg-gray-300', !checked);
      const eventId = button.dataset.eventId;
      const badge = document.querySelector(`[data-event-badge="${eventId}"]`);
      const row = document.querySelector(`[data-event-row="${eventId}"]`);
      const hidden = document.querySelector(`[data-realizado-input="${eventId}"]`);
      if (hidden) hidden.value = checked ? '1' : '0';
      if (badge && traffic) {
        badge.className = `text-xs px-2 py-1 rounded cronograma-traffic-badge ${traffic.badge_class}`;
        badge.textContent = traffic.label;
      }
      if (row && traffic) {
        row.className = `border-b align-top cronograma-traffic-row ${traffic.row_class}`;
      }
    };

    const applySeriesState = (serieId, payload) => {
      if (!payload || !payload.traffic) return;
      const badge = document.querySelector(`[data-series-badge="${serieId}"]`);
      if (badge) {
        badge.className = `text-xs px-2 py-1 rounded cronograma-traffic-badge ${payload.traffic.badge_class}`;
        badge.textContent = payload.traffic.label;
      }
      if (!payload.months) return;
      Object.keys(payload.months).forEach((monthKey) => {
        const cell = document.querySelector(`[data-series-month="${serieId}-${monthKey}"]`);
        const month = payload.months[monthKey];
        if (!cell || !month || !month.traffic) return;
        cell.className = `p-3 text-center cronograma-traffic-cell ${month.traffic.cell_class}`;
      });
    };

    document.querySelectorAll('[data-toggle-status]').forEach((button) => {
      button.addEventListener('click', async () => {
        const form = button.closest('.cronograma-toggle-form');
        if (!form) return;
        const eventId = button.dataset.eventId;
        const serieId = button.dataset.serieId;
        const checked = button.dataset.checked === '1';
        const formData = new FormData(form);
        formData.set('realizado', checked ? '0' : '1');
        try {
          const response = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
          });
          const payload = await response.json();
          if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'Nao foi possivel atualizar o status.');
          }
          if (activeFilter !== 'todos') {
            window.location.reload();
            return;
          }
          applyToggleVisualState(button, !checked, payload.occurrence ? payload.occurrence.traffic : null);
          applySeriesState(serieId, payload.series || null);
        } catch (error) {
          alert(error.message || 'Erro ao atualizar o status do evento.');
        }
      });
    });

    document.querySelectorAll('[data-ata-upload]').forEach((button) => {
      button.addEventListener('click', () => {
        const eventId = button.getAttribute('data-ata-upload');
        const input = document.querySelector(`[data-ata-input="${eventId}"]`);
        if (input) input.click();
      });
    });

    document.querySelectorAll('[data-ata-input]').forEach((input) => {
      input.addEventListener('change', async () => {
        const eventId = input.getAttribute('data-ata-input');
        const form = input.closest('form');
        if (!form || !eventId) return;
        if (!input.files || !input.files.length) return;
        const formData = new FormData(form);
        try {
          const response = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
          });
          const payload = await response.json();
          if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'Não foi possível anexar a ata.');
          }
          const wrap = document.querySelector(`[data-ata-wrap="${eventId}"]`);
          if (wrap && payload.download_url) {
            let link = wrap.querySelector(`[data-ata-download="${eventId}"]`);
            if (!link) {
              link = document.createElement('a');
              link.className = 'icon-btn';
              link.setAttribute('title', 'Baixar ata');
              link.setAttribute('aria-label', 'Baixar ata');
              link.setAttribute('data-ata-download', eventId);
              link.innerHTML = '<span data-feather="file-text"></span>';
              wrap.appendChild(link);
              if (typeof feather !== 'undefined' && feather && typeof feather.replace === 'function') {
                feather.replace();
              }
            }
            link.setAttribute('href', payload.download_url);
          }
          alert(payload.message || 'Ata anexada com sucesso.');
        } catch (error) {
          alert(error.message || 'Erro ao anexar a ata.');
        } finally {
          input.value = '';
        }
      });
    });

    const occForm = document.getElementById('occFiltersForm');
    const occTable = document.getElementById('occTable');
    const occError = document.getElementById('occClientError');
    const occDataset = occForm ? occForm.querySelector('input[name="occ_dataset"]') : null;
    const isClientMode = occDataset && occDataset.value === 'client';
    const collator = typeof Intl !== 'undefined' ? new Intl.Collator('pt-BR', { sensitivity: 'base' }) : null;

    /**
     * Recupera o valor de um atributo data-* de uma linha da tabela.
     * @param {HTMLTableRowElement} row
     * @param {string} key
     * @returns {string}
     */
    const getRowValue = (row, key) => {
      return (row.dataset[key] || '').toString();
    };

    /**
     * Atualiza os ícones de ordenação de cabeçalhos com base no estado atual.
     * @param {string} activeColumn
     * @param {string} activeDir
     * @returns {void}
     */
    const updateSortIcons = (activeColumn, activeDir) => {
      if (!occTable) return;
      occTable.querySelectorAll('[data-sort-icon]').forEach((icon) => {
        const column = icon.getAttribute('data-sort-icon');
        if (column === activeColumn) {
          icon.textContent = activeDir === 'asc' ? '▲' : '▼';
          icon.classList.remove('text-gray-400');
          icon.classList.add('text-brand-red');
        } else {
          icon.textContent = '↕';
          icon.classList.add('text-gray-400');
          icon.classList.remove('text-brand-red');
        }
      });
    };

    /**
     * Ordena as linhas da tabela por coluna e direção.
     * @param {HTMLTableRowElement[]} rows
     * @param {string} column
     * @param {string} direction
     * @returns {HTMLTableRowElement[]}
     */
    const sortRows = (rows, column, direction) => {
      const dir = direction === 'desc' ? -1 : 1;
      return rows.sort((a, b) => {
        const left = getRowValue(a, `ev${column === 'data' ? 'Date' : column.charAt(0).toUpperCase() + column.slice(1)}`);
        const right = getRowValue(b, `ev${column === 'data' ? 'Date' : column.charAt(0).toUpperCase() + column.slice(1)}`);
        let result = 0;
        if (column === 'data') {
          result = left.localeCompare(right);
        } else if (collator) {
          result = collator.compare(left, right);
        } else {
          result = left.toLowerCase().localeCompare(right.toLowerCase());
        }
        if (result === 0) {
          result = getRowValue(a, 'evDate').localeCompare(getRowValue(b, 'evDate'));
        }
        return result * dir;
      });
    };

    /**
     * Aplica ordenação client-side no corpo da tabela.
     * @param {string} column
     * @param {string} direction
     * @returns {void}
     */
    const applyClientSort = (column, direction) => {
      if (!occTable) return;
      const tbody = occTable.querySelector('tbody');
      if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll('tr[data-event-row]'));
      const sorted = sortRows(rows, column, direction);
      sorted.forEach((row) => tbody.appendChild(row));
      updateSortIcons(column, direction);
    };

    /**
     * Verifica se uma linha atende aos filtros selecionados.
     * @param {HTMLTableRowElement} row
     * @param {{start:string,end:string,tipos:string[],status:string[],responsavel:string,local:string}} filters
     * @returns {boolean}
     */
    const matchesFilters = (row, filters) => {
      const date = getRowValue(row, 'evDate');
      if (filters.start && date < filters.start) return false;
      if (filters.end && date > filters.end) return false;
      if (filters.tipos.length && !filters.tipos.includes(getRowValue(row, 'evTopico'))) return false;
      if (filters.status.length && !filters.status.includes(getRowValue(row, 'evStatus'))) return false;
      if (filters.responsavel && !getRowValue(row, 'evResponsavel').toLowerCase().includes(filters.responsavel)) return false;
      if (filters.local && !getRowValue(row, 'evLocal').toLowerCase().includes(filters.local)) return false;
      return true;
    };

    /**
     * Aplica filtros client-side exibindo ou ocultando linhas da tabela.
     * @param {{start:string,end:string,tipos:string[],status:string[],responsavel:string,local:string}} filters
     * @returns {void}
     */
    const applyClientFilters = (filters) => {
      if (!occTable) return;
      const tbody = occTable.querySelector('tbody');
      if (!tbody) return;
      let visibleCount = 0;
      tbody.querySelectorAll('tr[data-event-row]').forEach((row) => {
        const visible = matchesFilters(row, filters);
        row.classList.toggle('hidden', !visible);
        if (visible) visibleCount++;
      });
      const emptyRow = tbody.querySelector('tr[data-empty-row]');
      if (emptyRow) {
        emptyRow.classList.toggle('hidden', visibleCount > 0);
      }
    };

    /**
     * Exibe mensagem amigável de erro no painel de filtros.
     * @param {string} message
     * @returns {void}
     */
    const showOccError = (message) => {
      if (!occError) return;
      occError.textContent = message;
      occError.classList.remove('hidden');
    };

    /**
     * Limpa mensagens de erro do painel de filtros.
     * @returns {void}
     */
    const clearOccError = () => {
      if (!occError) return;
      occError.textContent = '';
      occError.classList.add('hidden');
    };

    /**
     * Coleta filtros atuais do formulário de ocorrências.
     * @returns {{start:string,end:string,tipos:string[],status:string[],responsavel:string,local:string}|null}
     */
    const getFilterValues = () => {
      if (!occForm) return null;
      const data = new FormData(occForm);
      return {
        start: (data.get('occ_date_start') || '').toString(),
        end: (data.get('occ_date_end') || '').toString(),
        tipos: data.getAll('occ_tipo[]').map((v) => v.toString()),
        status: data.getAll('occ_status[]').map((v) => v.toString()),
        responsavel: (data.get('occ_responsavel') || '').toString().trim().toLowerCase(),
        local: (data.get('occ_local') || '').toString().trim().toLowerCase(),
      };
    };

    /**
     * Atualiza a URL com o estado atual de filtros e ordenação para preservar navegação.
     * @returns {void}
     */
    const updateUrlState = () => {
      if (!occForm) return;
      const params = new URLSearchParams(new FormData(occForm));
      const url = new URL(window.location.href);
      url.search = params.toString();
      window.history.replaceState({}, '', url.toString());
    };

    /**
     * Registra comportamento client-side para filtros e ordenação.
     * @returns {void}
     */
    const bindClientBehavior = () => {
      if (!occForm || !occTable) return;
      const currentSort = {
        column: (occForm.querySelector('input[name="occ_sort"]') || {}).value || 'data',
        direction: (occForm.querySelector('input[name="occ_dir"]') || {}).value || 'asc',
      };
      applyClientSort(currentSort.column, currentSort.direction);
      applyClientFilters(getFilterValues());
      occForm.addEventListener('submit', (event) => {
        event.preventDefault();
        clearOccError();
        const filters = getFilterValues();
        if (filters.start && filters.end && filters.start > filters.end) {
          showOccError('O período inicial não pode ser maior que o período final.');
          return;
        }
        applyClientFilters(filters);
        updateUrlState();
      });
      occTable.querySelectorAll('[data-occ-sort]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          const column = link.getAttribute('data-occ-sort');
          if (!column) return;
          const currentColumn = occForm.querySelector('input[name="occ_sort"]');
          const currentDir = occForm.querySelector('input[name="occ_dir"]');
          const isSame = currentColumn && currentColumn.value === column;
          const nextDir = isSame && currentDir && currentDir.value === 'asc' ? 'desc' : 'asc';
          if (currentColumn) currentColumn.value = column;
          if (currentDir) currentDir.value = nextDir;
          applyClientSort(column, nextDir);
          updateUrlState();
        });
      });
    };

    if (isClientMode) {
      bindClientBehavior();
    }
  })();
</script>
