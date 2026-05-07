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

  <div class="bg-white shadow rounded">
    <div class="px-4 py-3 border-b font-semibold">Ocorrências materializadas</div>
    <div class="p-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <th class="p-3">Data</th>
            <th class="p-3">Pilar / Departamento</th>
            <th class="p-3">Atividade</th>
            <th class="p-3">Responsável</th>
            <th class="p-3">Periodicidade</th>
            <th class="p-3">Status</th>
            <th class="p-3">Farol</th>
            <th class="p-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)): ?>
            <tr><td class="p-4 text-center text-gray-500" colspan="8">Nenhuma ocorrência cadastrada.</td></tr>
          <?php endif; ?>
          <?php foreach ($events as $ev): ?>
            <?php $isSeries = ($ev['periodicidade'] ?? 'unico') !== 'unico' || !empty($ev['evento_pai_id']); ?>
            <tr class="border-b align-top cronograma-traffic-row <?= htmlspecialchars($ev['traffic']['row_class'] ?? '') ?>" data-event-row="<?= (int)$ev['id'] ?>">
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
                <form method="post" action="index.php?route=cronograma/updateEvento" class="space-y-2">
                  <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                  <input type="hidden" name="id_evento" value="<?= (int)$ev['id'] ?>" />
                  <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                  <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
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
                    <input type="hidden" name="escopo" value="evento" />
                    <button class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-xs" type="submit">Excluir este evento</button>
                  </form>
                  <?php if ($isSeries): ?>
                    <form method="post" action="index.php?route=cronograma/deleteEvento" onsubmit="return confirm('Excluir toda a série deste evento?');">
                      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                      <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>" />
                      <input type="hidden" name="id_cronograma" value="<?= (int)$crono['id'] ?>" />
                      <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>" />
                      <input type="hidden" name="escopo" value="serie" />
                      <button class="px-3 py-2 rounded bg-red-100 text-red-700 text-xs" type="submit">Excluir série</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
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
  })();
</script>
