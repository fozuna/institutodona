<?php /** @var array $crono */ /** @var array $events */ /** @var array $grid */ ?>
<?php
  $months = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];
  $statusFilter = $statusFilter ?? 'todos';
  $gridOrder = $gridOrder ?? ['column' => 'topico', 'direction' => 'asc'];
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
<div class="p-6 space-y-6" data-cronograma-page>
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Cronograma</h1>
      <div class="text-sm text-gray-600"><?= htmlspecialchars($crono['cliente'] ?? '') ?> · Ano <?= (int)($crono['ano'] ?? date('Y')) ?></div>
      <?php if (!empty($crono['nome'])): ?><div class="text-sm text-gray-600">Nome: <?= htmlspecialchars($crono['nome']) ?></div><?php endif; ?>
    </div>
    <div class="flex items-center gap-2">
      <?php
        $gridPdfQuery = http_build_query(array_filter([
            'route' => 'cronograma/grid_pdf',
            'id' => (int)$crono['id'],
            'status_filter' => $statusFilter !== 'todos' ? $statusFilter : null,
            'grid_sort' => $gridOrder['column'] ?? null,
            'grid_dir' => $gridOrder['direction'] ?? null,
        ], static fn($v): bool => $v !== null && $v !== ''));
      ?>
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?<?= $gridPdfQuery ?>" target="_blank">Imprimir / PDF</a>
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
      <table class="min-w-full text-sm" id="cronogramaGridTable">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <?php
              $gridBase = $_GET;
              $gridBase['route'] = 'cronograma/show';
              $gridBase['id'] = (int)$crono['id'];
              $renderGridHeader = static function (string $label, string $column) use ($gridBase, $gridOrder): void {
                  $active = ($gridOrder['column'] ?? 'topico') === $column;
                  $nextDir = $active && (($gridOrder['direction'] ?? 'asc') === 'asc') ? 'desc' : 'asc';
                  $params = array_merge($gridBase, ['grid_sort' => $column, 'grid_dir' => $nextDir]);
                  ?>
                  <th class="p-3">
                    <a class="inline-flex items-center gap-1 hover:underline"
                       data-grid-sort="<?= htmlspecialchars($column) ?>"
                       href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                      <?= htmlspecialchars($label) ?>
                      <span class="text-xs cronograma-sort-icon <?= $active ? 'text-brand-red' : 'text-gray-400' ?>" data-grid-sort-icon="<?= htmlspecialchars($column) ?>" aria-hidden="true">
                        <?= $active && (($gridOrder['direction'] ?? 'asc') === 'asc') ? '▲' : ($active ? '▼' : '↕') ?>
                      </span>
                    </a>
                  </th>
                  <?php
              };
              $renderGridHeader('Pilar', 'topico');
              $renderGridHeader('Departamento', 'unidade');
              $renderGridHeader('Atividade', 'atividade');
              $renderGridHeader('Responsável', 'responsavel');
              $renderGridHeader('Status', 'status');
            ?>
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
            <tr class="border-b cronograma-traffic-row"
                data-grid-row
                data-serie-id="<?= (int)$row['serie_id'] ?>"
                data-grid-topico="<?= htmlspecialchars($row['topico']) ?>"
                data-grid-unidade="<?= htmlspecialchars($row['unidade']) ?>"
                data-grid-atividade="<?= htmlspecialchars($row['atividade']) ?>"
                data-grid-responsavel="<?= htmlspecialchars($row['responsavel']) ?>"
                data-grid-status="<?= htmlspecialchars($row['status']) ?>">
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
            <th class="p-3">Tipo</th>
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
            <th class="p-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)): ?>
            <tr data-empty-row><td class="p-4 text-center text-gray-500" colspan="7">Nenhuma ocorrência encontrada com os filtros aplicados.</td></tr>
          <?php endif; ?>
          <?php foreach ($events as $ev): ?>
            <?php $isSeries = ($ev['periodicidade'] ?? 'unico') !== 'unico' || !empty($ev['evento_pai_id']); ?>
            <tr class="border-b align-top cronograma-traffic-row <?= htmlspecialchars($ev['traffic']['row_class'] ?? '') ?>"
                data-event-row="<?= (int)$ev['id'] ?>"
                data-ev-date="<?= htmlspecialchars($ev['data']) ?>"
                data-ev-topico="<?= htmlspecialchars($ev['topico']) ?>"
                data-ev-atividade="<?= htmlspecialchars($ev['atividade']) ?>"
                data-ev-responsavel="<?= htmlspecialchars($ev['responsavel'] ?? '') ?>"
                data-ev-tipo-evento="<?= htmlspecialchars((string)($ev['tipo_evento'] ?? '')) ?>"
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
              <td class="p-3" data-ev-tipo-evento="<?= htmlspecialchars((string)($ev['tipo_evento'] ?? '')) ?>"><?= htmlspecialchars((string)($ev['tipo_evento'] ?? '')) ?></td>
              <td class="p-3">
                <span class="text-xs px-2 py-1 rounded cronograma-traffic-badge <?= htmlspecialchars($ev['traffic']['badge_class'] ?? 'bg-gray-200 text-gray-700') ?>" data-event-badge="<?= (int)$ev['id'] ?>"><?= htmlspecialchars($ev['traffic']['label'] ?? $ev['status']) ?></span>
              </td>
              <td class="p-3">
                <?php
                  $eventId = (int)($ev['id'] ?? 0);
                  $serieId = (int)($ev['serie_id'] ?? $eventId);
                  $isReuniao = ($ev['tipo_evento'] ?? '') === 'Reunião';
                  $isFinalizado = (string)($ev['status'] ?? '') === 'Finalizado';
                ?>

                <div class="flex flex-wrap items-center gap-2">
                  <button type="button"
                          class="px-3 py-2 rounded bg-brand-brown text-white text-xs"
                          data-cronograma-drawer-open
                          data-event-id="<?= $eventId ?>"
                          data-serie-id="<?= $serieId ?>"
                          data-scope="evento"
                          data-mode="edit">Editar</button>
                  <?php if ($isSeries): ?>
                    <button type="button"
                            class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-xs"
                            data-cronograma-drawer-open
                            data-event-id="<?= $eventId ?>"
                            data-serie-id="<?= $serieId ?>"
                            data-scope="serie"
                            data-mode="edit">Editar série</button>
                  <?php endif; ?>
                  <button type="button"
                          class="px-3 py-2 rounded bg-green-600 text-white text-xs <?= $isFinalizado ? 'opacity-50 cursor-not-allowed' : '' ?>"
                          <?= $isFinalizado ? 'disabled' : '' ?>
                          data-cronograma-drawer-close-open
                          data-event-id="<?= $eventId ?>"
                          data-serie-id="<?= $serieId ?>"
                          data-is-reuniao="<?= $isReuniao ? '1' : '0' ?>">Encerrar</button>
                  <button type="button"
                          class="px-3 py-2 rounded bg-gray-100 text-brand-brown text-xs <?= $isReuniao ? '' : 'hidden' ?>"
                          data-cronograma-drawer-open
                          data-event-id="<?= $eventId ?>"
                          data-serie-id="<?= $serieId ?>"
                          data-scope="evento"
                          data-mode="docs">Documentos</button>
                  <button type="button"
                          class="px-3 py-2 rounded bg-red-100 text-red-700 text-xs"
                          data-cronograma-delete-open
                          data-event-id="<?= $eventId ?>"
                          data-serie-id="<?= $serieId ?>"
                          data-scope="evento"
                          data-label="Excluir evento">
                    Excluir evento
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!empty($events)): ?>
            <tr data-empty-row class="hidden"><td class="p-4 text-center text-gray-500" colspan="7">Nenhuma ocorrência encontrada com os filtros aplicados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="cronogramaDrawerBackdrop" class="fixed inset-0 bg-black/40 hidden z-40" aria-hidden="true"></div>
<aside id="cronogramaDrawer"
       class="fixed inset-y-0 right-0 w-full max-w-[700px] bg-white shadow-xl translate-x-full transition-transform duration-200 z-50 outline-none"
       role="dialog"
       aria-modal="true"
       aria-labelledby="cronogramaDrawerTitle"
       tabindex="-1">
  <div class="h-full flex flex-col">
    <div id="cronogramaDrawerBody" class="flex-1 overflow-y-auto p-4"></div>
  </div>
</aside>

<div id="cronogramaToast" class="fixed bottom-4 right-4 z-50 hidden">
  <div class="px-4 py-3 rounded bg-green-600 text-white text-sm shadow-lg" data-toast-message></div>
</div>

<div id="cronogramaConfirm" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/40" data-confirm-backdrop></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-4">
      <div class="font-semibold text-sm mb-2" data-confirm-title>Atenção</div>
      <div class="text-sm text-gray-700 mb-4" data-confirm-message></div>
      <div class="flex items-center justify-end gap-2">
        <button type="button" class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm" data-confirm-cancel></button>
        <button type="button" class="px-3 py-2 rounded bg-brand-red text-white text-sm" data-confirm-ok></button>
      </div>
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
    const gridInitialOrder = <?= json_encode($gridOrder ?? ['column' => 'topico', 'direction' => 'asc'], JSON_UNESCAPED_UNICODE) ?>;
    const cronogramaDeleteCsrf = <?= json_encode(\App\Core\Security::csrfToken(), JSON_UNESCAPED_UNICODE) ?>;
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

    const drawerBackdrop = document.getElementById('cronogramaDrawerBackdrop');
    const drawer = document.getElementById('cronogramaDrawer');
    const drawerBody = document.getElementById('cronogramaDrawerBody');
    const toast = document.getElementById('cronogramaToast');
    const toastMsg = toast ? toast.querySelector('[data-toast-message]') : null;
    const confirmEl = document.getElementById('cronogramaConfirm');
    const confirmTitle = confirmEl ? confirmEl.querySelector('[data-confirm-title]') : null;
    const confirmMessage = confirmEl ? confirmEl.querySelector('[data-confirm-message]') : null;
    const confirmOk = confirmEl ? confirmEl.querySelector('[data-confirm-ok]') : null;
    const confirmCancel = confirmEl ? confirmEl.querySelector('[data-confirm-cancel]') : null;
    const confirmBackdrop = confirmEl ? confirmEl.querySelector('[data-confirm-backdrop]') : null;

    const showToast = (message) => {
      if (!toast || !toastMsg) return;
      toastMsg.textContent = message;
      toast.classList.remove('hidden');
      window.setTimeout(() => toast.classList.add('hidden'), 2500);
    };

    const askConfirm = (title, message, okLabel, cancelLabel) => {
      return new Promise((resolve) => {
        if (!confirmEl || !confirmOk || !confirmCancel || !confirmMessage || !confirmTitle) {
          resolve(window.confirm(message));
          return;
        }
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;
        confirmOk.textContent = okLabel;
        confirmCancel.textContent = cancelLabel;
        confirmEl.classList.remove('hidden');
        try { confirmOk.focus(); } catch (e) {}
        const cleanup = () => {
          confirmEl.classList.add('hidden');
          confirmOk.removeEventListener('click', onOk);
          confirmCancel.removeEventListener('click', onCancel);
          if (confirmBackdrop) confirmBackdrop.removeEventListener('click', onCancel);
          document.removeEventListener('keydown', onKey);
        };
        const onOk = () => { cleanup(); resolve(true); };
        const onCancel = () => { cleanup(); resolve(false); };
        const onKey = (ev) => {
          if (ev.key === 'Escape') onCancel();
        };
        confirmOk.addEventListener('click', onOk);
        confirmCancel.addEventListener('click', onCancel);
        if (confirmBackdrop) confirmBackdrop.addEventListener('click', onCancel);
        document.addEventListener('keydown', onKey);
      });
    };

    let drawerOpen = false;
    let drawerDirty = false;
    let drawerInitialSnapshot = '';
    let lastFocusedElement = null;
    let restoreBodyOverflow = '';

    const pageContainer = document.querySelector('[data-cronograma-page]');

    const getFocusable = (root) => {
      if (!root) return [];
      return Array.from(root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
        .filter((el) => !el.hasAttribute('disabled') && !el.getAttribute('aria-hidden'));
    };

    const trapFocus = (root, ev) => {
      if (ev.key !== 'Tab') return;
      const focusable = getFocusable(root);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      const active = document.activeElement;
      if (ev.shiftKey && active === first) {
        ev.preventDefault();
        last.focus();
        return;
      }
      if (!ev.shiftKey && active === last) {
        ev.preventDefault();
        first.focus();
      }
    };
    const onDrawerKeydown = (ev) => trapFocus(drawer, ev);

    const snapshotForm = (form) => {
      const params = new URLSearchParams();
      const fd = new FormData(form);
      for (const [k, v] of fd.entries()) {
        if (v instanceof File) continue;
        params.append(k, String(v));
      }
      return params.toString();
    };

    const setDrawerDirtyFromForm = (form) => {
      const current = snapshotForm(form);
      drawerDirty = current !== drawerInitialSnapshot;
    };

    const openDrawer = async ({ eventId, scope = 'evento', mode = 'edit' }) => {
      if (!drawer || !drawerBackdrop || !drawerBody) return;
      lastFocusedElement = document.activeElement;
      restoreBodyOverflow = document.body.style.overflow || '';
      document.body.style.overflow = 'hidden';
      if (pageContainer) {
        pageContainer.setAttribute('aria-hidden', 'true');
        if ('inert' in pageContainer) pageContainer.inert = true;
      }
      drawerBody.innerHTML = '<div class="text-sm text-gray-600">Carregando...</div>';
      drawerBackdrop.classList.remove('hidden');
      drawer.classList.remove('translate-x-full');
      drawerOpen = true;
      drawerDirty = false;
      drawerInitialSnapshot = '';
      drawer.focus();

      const url = new URL(window.location.href);
      url.searchParams.set('route', 'cronograma/eventoDrawer');
      url.searchParams.set('id_evento', String(eventId));
      url.searchParams.set('id_cronograma', String(<?= (int)$crono['id'] ?>));
      url.searchParams.set('escopo', scope);
      url.searchParams.set('mode', mode);
      try {
        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        if (!res.ok) throw new Error('Falha ao carregar o evento.');
        drawerBody.innerHTML = html;
        try { feather.replace(); } catch (e) {}

        const form = drawerBody.querySelector('form[data-cronograma-drawer-form="edit"]');
        if (form) {
          drawerInitialSnapshot = snapshotForm(form);
          form.querySelectorAll('input,select,textarea').forEach((el) => {
            el.addEventListener('input', () => setDrawerDirtyFromForm(form));
            el.addEventListener('change', () => setDrawerDirtyFromForm(form));
          });

          const statusSelect = form.querySelector('[data-cronograma-status-select]');
          const reuniaoBlock = form.querySelector('[data-cronograma-reuniao-anexos]');
          const syncReuniaoBlock = () => {
            if (!statusSelect || !reuniaoBlock) return;
            reuniaoBlock.classList.toggle('hidden', statusSelect.value !== 'Finalizado');
          };
          if (statusSelect) statusSelect.addEventListener('change', syncReuniaoBlock);
          syncReuniaoBlock();

          form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const scopeInput = form.querySelector('input[name="escopo"]');
            const periodicidadeInput = form.querySelector('[name="periodicidade"]');
            const initialPeriod = periodicidadeInput ? (periodicidadeInput.getAttribute('data-initial-periodicidade') || '') : '';
            const drawerContent = drawerBody.querySelector('[data-cronograma-drawer-content]');
            const eventId = drawerContent ? parseInt(drawerContent.getAttribute('data-event-id') || '0', 10) : 0;
            const serieId = drawerContent ? parseInt(drawerContent.getAttribute('data-serie-id') || '0', 10) : 0;
            const belongsToSeries = (eventId > 0 && serieId > 0 && eventId !== serieId) || initialPeriod !== 'unico';
            if (scopeInput && periodicidadeInput && scopeInput.value === 'evento' && periodicidadeInput.value !== initialPeriod) {
              const applySeries = await askConfirm(
                'Atualizar periodicidade',
                belongsToSeries
                  ? 'A periodicidade é uma classificação da série. Deseja aplicar essa alteração em toda a série?'
                  : 'Ao alterar a periodicidade, o sistema precisará materializar a recorrência. Deseja aplicar essa alteração como série?'
                ,
                'Aplicar à série',
                'Cancelar'
              );
              if (!applySeries) {
                return;
              }
              scopeInput.value = 'serie';
            }
            const existing = parseInt(form.getAttribute('data-anexos-count') || '0', 10) || 0;
            const isFinal = statusSelect && statusSelect.value === 'Finalizado';
            const fileInput = form.querySelector('input[type="file"][name="anexos[]"]');
            const filesCount = fileInput && fileInput.files ? fileInput.files.length : 0;
            if (reuniaoBlock && isFinal && existing <= 0 && filesCount <= 0) {
              showToast('Para encerrar uma reunião, anexe pelo menos um documento.');
              return;
            }
            try {
              const fd = new FormData(form);
              const response = await fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
              const payload = await response.json();
              if (!response.ok || !payload.ok) throw new Error(payload.message || 'Não foi possível salvar.');
              const occ = payload.occurrence || null;
              if (occ && occ.id) {
                const row = document.querySelector(`[data-event-row="${occ.id}"]`);
                if (row) {
                  row.querySelectorAll('td')[0].textContent = occ.data || row.querySelectorAll('td')[0].textContent;
                  const categoryCell = row.querySelectorAll('td')[1];
                  if (categoryCell) {
                    const titleEl = categoryCell.querySelector('.font-medium');
                    const subEl = categoryCell.querySelector('.text-xs');
                    if (titleEl) titleEl.textContent = occ.topico || titleEl.textContent;
                    if (subEl) subEl.textContent = occ.unidade || subEl.textContent;
                  }
                  const descCell = row.querySelectorAll('td')[2];
                  if (descCell) descCell.textContent = occ.atividade || descCell.textContent;
                  const respCell = row.querySelectorAll('td')[3];
                  if (respCell) respCell.textContent = occ.responsavel || '';
                  const tipoCell = row.querySelectorAll('td')[4];
                  if (tipoCell) tipoCell.textContent = occ.tipo_evento || '';
                  row.dataset.evDate = occ.data || row.dataset.evDate;
                  row.dataset.evTopico = occ.topico || row.dataset.evTopico;
                  row.dataset.evAtividade = occ.atividade || row.dataset.evAtividade;
                  row.dataset.evResponsavel = occ.responsavel || row.dataset.evResponsavel;
                  row.dataset.evTipoEvento = occ.tipo_evento || row.dataset.evTipoEvento || '';
                  row.dataset.evPeriodicidade = occ.periodicidade || row.dataset.evPeriodicidade;
                  row.dataset.evStatus = (occ.traffic && occ.traffic.label) ? occ.traffic.label : (occ.status || row.dataset.evStatus);
                  row.dataset.evLocal = occ.unidade || row.dataset.evLocal;
                  const badge = document.querySelector(`[data-event-badge="${occ.id}"]`);
                  if (badge && occ.traffic) {
                    badge.className = `text-xs px-2 py-1 rounded cronograma-traffic-badge ${occ.traffic.badge_class}`;
                    badge.textContent = occ.traffic.label;
                  }
                  if (occ.traffic) {
                    row.className = `border-b align-top cronograma-traffic-row ${occ.traffic.row_class}`;
                  }
                }
                if (payload.series && payload.series.serie_id) {
                  applySeriesState(payload.series.serie_id, payload.series);
                }
              }
              showToast(payload.message || 'Evento atualizado com sucesso.');
              drawerDirty = false;
              drawerInitialSnapshot = snapshotForm(form);
              closeDrawer(true);
            } catch (error) {
              showToast(error.message || 'Erro ao salvar o evento.');
            }
          });
        }

        const closeBtn = drawerBody.querySelectorAll('[data-cronograma-drawer-close]');
        closeBtn.forEach((btn) => btn.addEventListener('click', () => closeDrawer(false)));

        const deleteForm = drawerBody.querySelector('form[data-cronograma-delete-form]');
        const deleteSubmit = drawerBody.querySelector('[data-cronograma-delete-submit]');
        if (deleteForm && deleteSubmit) {
          deleteSubmit.addEventListener('click', async () => {
            const deleteScope = (deleteForm.querySelector('input[name="escopo"]') || {}).value || 'evento';
            const confirmed = await askConfirm(
              'Confirmar exclusão',
              deleteScope === 'serie'
                ? 'Deseja realmente excluir esta série e todas as suas ocorrências?'
                : 'Deseja realmente excluir este evento do cronograma?',
              'Excluir',
              'Cancelar'
            );
            if (!confirmed) return;
            const fd = new FormData(deleteForm);
            try {
              const response = await fetch(deleteForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
              });
              const payload = await response.json();
              if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Não foi possível excluir o evento.');
              }
              showToast(payload.message || 'Evento excluído com sucesso.');
              window.setTimeout(() => {
                window.location.href = payload.redirect_url || window.location.href;
              }, 250);
            } catch (error) {
              showToast(error.message || 'Erro ao excluir o evento.');
            }
          });
        }

        const closeEventBtn = drawerBody.querySelector('[data-cronograma-close-event]');
        const closeForm = drawerBody.querySelector('form[data-cronograma-close-form]');
        if (closeEventBtn && closeForm) {
          closeEventBtn.addEventListener('click', async () => {
            const ok = await askConfirm('Confirmação', 'Deseja realmente encerrar este evento?', 'Confirmar', 'Cancelar');
            if (!ok) return;
            const fd = new FormData(closeForm);
            fd.set('realizado', '1');
            try {
              const response = await fetch(closeForm.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
              const payload = await response.json();
              if (!response.ok || !payload.ok) throw new Error(payload.message || 'Não foi possível encerrar o evento.');
              const eventId = payload.occurrence ? payload.occurrence.id : null;
              if (eventId) {
                const badge = document.querySelector(`[data-event-badge="${eventId}"]`);
                const row = document.querySelector(`[data-event-row="${eventId}"]`);
                if (badge && payload.occurrence && payload.occurrence.traffic) {
                  badge.className = `text-xs px-2 py-1 rounded cronograma-traffic-badge ${payload.occurrence.traffic.badge_class}`;
                  badge.textContent = payload.occurrence.traffic.label;
                }
                if (row && payload.occurrence && payload.occurrence.traffic) {
                  row.className = `border-b align-top cronograma-traffic-row ${payload.occurrence.traffic.row_class}`;
                }
                if (payload.series) applySeriesState(payload.series.serie_id, payload.series);
              }
              showToast('Evento encerrado com sucesso.');
              closeDrawer(true);
            } catch (error) {
              showToast(error.message || 'Erro ao encerrar o evento.');
            }
          });
        }

        const closeReuniaoBtn = drawerBody.querySelector('[data-cronograma-close-reuniao]');
        if (closeReuniaoBtn && form) {
          closeReuniaoBtn.addEventListener('click', async () => {
            const ok = await askConfirm('Confirmação', 'Deseja realmente encerrar este evento?', 'Confirmar', 'Cancelar');
            if (!ok) return;
            const statusSelect = form.querySelector('[data-cronograma-status-select]');
            if (statusSelect) statusSelect.value = 'Finalizado';
            if (statusSelect) statusSelect.dispatchEvent(new Event('change', { bubbles: true }));
            form.requestSubmit();
          });
        }

        const modeDocs = drawerBody.querySelector('[data-cronograma-drawer-content]');
        if (mode === 'docs' && modeDocs) {
          const docsSection = drawerBody.querySelector('[data-cronograma-docs]');
          if (docsSection) docsSection.scrollIntoView({ block: 'start' });
        }
        if (mode === 'close') {
          const closeBtn = drawerBody.querySelector('[data-cronograma-close-event], [data-cronograma-close-reuniao]');
          if (closeBtn) closeBtn.scrollIntoView({ block: 'start' });
        }

        const firstField = drawerBody.querySelector('input,select,textarea,button');
        if (firstField && firstField.focus) firstField.focus();
        drawer.removeEventListener('keydown', onDrawerKeydown);
        drawer.addEventListener('keydown', onDrawerKeydown);
      } catch (error) {
        drawerBody.innerHTML = `<div class="text-sm text-red-700">${(error && error.message) ? error.message : 'Erro ao carregar.'}</div>`;
      }
    };

    const closeDrawer = async (force) => {
      if (!drawer || !drawerBackdrop || !drawerBody) return;
      if (!force && drawerDirty) {
        const discard = await askConfirm('Alterações não salvas', 'Deseja descartar as alterações realizadas?', 'Descartar alterações', 'Continuar editando');
        if (!discard) return;
      }
      drawerBody.innerHTML = '';
      drawer.removeEventListener('keydown', onDrawerKeydown);
      drawer.classList.add('translate-x-full');
      drawerBackdrop.classList.add('hidden');
      drawerOpen = false;
      drawerDirty = false;
      drawerInitialSnapshot = '';
      document.body.style.overflow = restoreBodyOverflow;
      if (pageContainer) {
        pageContainer.removeAttribute('aria-hidden');
        if ('inert' in pageContainer) pageContainer.inert = false;
      }
      if (lastFocusedElement && lastFocusedElement.focus) {
        try { lastFocusedElement.focus(); } catch (e) {}
      }
    };

    if (drawerBackdrop) {
      drawerBackdrop.addEventListener('click', () => closeDrawer(false));
    }

    document.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape' && drawerOpen) {
        closeDrawer(false);
      }
    });

    document.addEventListener('click', (ev) => {
      const openBtn = ev.target && ev.target.closest ? ev.target.closest('[data-cronograma-drawer-open]') : null;
      if (openBtn) {
        const eventId = openBtn.getAttribute('data-event-id');
        const scope = openBtn.getAttribute('data-scope') || 'evento';
        const mode = openBtn.getAttribute('data-mode') || 'edit';
        if (eventId) openDrawer({ eventId, scope, mode });
        return;
      }
      const closeOpenBtn = ev.target && ev.target.closest ? ev.target.closest('[data-cronograma-drawer-close-open]') : null;
      if (closeOpenBtn) {
        const eventId = closeOpenBtn.getAttribute('data-event-id');
        const isReuniao = closeOpenBtn.getAttribute('data-is-reuniao') === '1';
        if (!eventId) return;
        if (isReuniao) {
          openDrawer({ eventId, scope: 'evento', mode: 'close' });
          return;
        }
        openDrawer({ eventId, scope: 'evento', mode: 'close' });
        return;
      }
      const deleteBtn = ev.target && ev.target.closest ? ev.target.closest('[data-cronograma-delete-open]') : null;
      if (deleteBtn) {
        const eventId = deleteBtn.getAttribute('data-event-id');
        if (!eventId) return;
        askConfirm(
          'Confirmar exclusão',
          'Deseja realmente excluir este evento do cronograma?',
          'Excluir',
          'Cancelar'
        ).then((confirmed) => {
          if (!confirmed) return;
          const fd = new FormData();
          fd.set('csrf', cronogramaDeleteCsrf);
          fd.set('id', eventId);
          fd.set('id_cronograma', String(<?= (int)$crono['id'] ?>));
          fd.set('status_filter', activeFilter);
          fd.set('escopo', deleteBtn.getAttribute('data-scope') || 'evento');
          fetch('index.php?route=cronograma/deleteEvento', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
          })
            .then(async (response) => {
              const payload = await response.json();
              if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Não foi possível excluir o evento.');
              }
              showToast(payload.message || 'Evento excluído com sucesso.');
              window.setTimeout(() => {
                window.location.href = payload.redirect_url || window.location.href;
              }, 250);
            })
            .catch((error) => showToast(error.message || 'Erro ao excluir o evento.'));
        });
      }
    });

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

    const occForm = document.getElementById('occFiltersForm');
    const occTable = document.getElementById('occTable');
    const gridTable = document.getElementById('cronogramaGridTable');
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
      if (filters.tipos.length && !filters.tipos.includes(getRowValue(row, 'evTipoEvento'))) return false;
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

    const gridCollator = typeof Intl !== 'undefined' ? new Intl.Collator('pt-BR', { sensitivity: 'base' }) : null;
    const getGridValue = (row, column) => (row.dataset['grid' + column.charAt(0).toUpperCase() + column.slice(1)] || '').toString();
    const updateGridSortIcons = (activeColumn, activeDir) => {
      if (!gridTable) return;
      gridTable.querySelectorAll('[data-grid-sort-icon]').forEach((icon) => {
        const column = icon.getAttribute('data-grid-sort-icon');
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
    const applyGridSort = (column, direction) => {
      if (!gridTable) return;
      const tbody = gridTable.querySelector('tbody');
      if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll('tr[data-grid-row]'));
      const dir = direction === 'desc' ? -1 : 1;
      rows.sort((a, b) => {
        const left = getGridValue(a, column);
        const right = getGridValue(b, column);
        const result = gridCollator ? gridCollator.compare(left, right) : left.toLowerCase().localeCompare(right.toLowerCase());
        return result * dir;
      });
      rows.forEach((row) => tbody.appendChild(row));
      updateGridSortIcons(column, direction);
    };
    if (gridTable) {
      applyGridSort(gridInitialOrder.column || 'topico', gridInitialOrder.direction || 'asc');
      gridTable.querySelectorAll('[data-grid-sort]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          const column = link.getAttribute('data-grid-sort');
          if (!column) return;
          const currentColumn = link.closest('table') ? null : null;
          const activeColumn = gridTable.getAttribute('data-grid-active-column') || (gridInitialOrder.column || 'topico');
          const activeDir = gridTable.getAttribute('data-grid-active-dir') || (gridInitialOrder.direction || 'asc');
          const nextDir = activeColumn === column && activeDir === 'asc' ? 'desc' : 'asc';
          gridTable.setAttribute('data-grid-active-column', column);
          gridTable.setAttribute('data-grid-active-dir', nextDir);
          applyGridSort(column, nextDir);
          const url = new URL(window.location.href);
          url.searchParams.set('grid_sort', column);
          url.searchParams.set('grid_dir', nextDir);
          window.history.replaceState({}, '', url.toString());
        });
      });
      gridTable.setAttribute('data-grid-active-column', gridInitialOrder.column || 'topico');
      gridTable.setAttribute('data-grid-active-dir', gridInitialOrder.direction || 'asc');
    }
  })();
</script>
