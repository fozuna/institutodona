<?php /** @var array $calendar */ /** @var array $events */ /** @var string $eventType */ ?>
<?php
$eventsByDate = \App\Services\AgendaEventService::groupByDate($events ?? []);
$days = $calendar['days'] ?? [];
$prev = $calendar['prev'] ?? ['year' => date('Y'), 'month' => date('n'), 'label' => ''];
$next = $calendar['next'] ?? ['year' => date('Y'), 'month' => date('n'), 'label' => ''];
$currentTitle = (string)($calendar['title'] ?? 'Agenda');
$filter = $eventType ?? 'all';
?>
<div class="p-6" id="agendaApp"
     data-start="<?= htmlspecialchars((string)($calendar['start'] ?? '')) ?>"
     data-end="<?= htmlspecialchars((string)($calendar['end'] ?? '')) ?>"
     data-filter="<?= htmlspecialchars($filter) ?>"
     data-api="index.php?route=agenda/api_events">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black">Agenda Integrada</h1>
      <p class="text-sm text-gray-600">Calendário visual com eventos sincronizados de Planos de Ação, Auditorias, Treinamentos e Cronograma.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=agenda/index&year=<?= (int)$prev['year'] ?>&month=<?= (int)$prev['month'] ?>&type=<?= urlencode($filter) ?>">◀ <?= htmlspecialchars((string)$prev['label']) ?></a>
      <div class="px-3 py-2 font-semibold"><?= htmlspecialchars($currentTitle) ?></div>
      <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=agenda/index&year=<?= (int)$next['year'] ?>&month=<?= (int)$next['month'] ?>&type=<?= urlencode($filter) ?>"><?= htmlspecialchars((string)$next['label']) ?> ▶</a>
    </div>
  </div>

  <div class="bg-white shadow rounded p-4 mb-4">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <div class="flex flex-wrap gap-2" id="agendaFilters">
        <?php foreach (['all' => 'Todos', 'planoacao' => 'Planos de Acao', 'auditoria' => 'Auditorias', 'treinamento' => 'Treinamentos', 'cronograma' => 'Cronograma'] as $key => $label): ?>
          <button type="button"
                  class="agenda-filter px-3 py-2 rounded border text-sm <?= $filter === $key ? 'bg-brand-pink text-white border-brand-pink' : 'bg-white text-brand-brown border-gray-300' ?>"
                  data-filter="<?= htmlspecialchars($key) ?>">
            <?= htmlspecialchars($label) ?>
          </button>
        <?php endforeach; ?>
      </div>
      <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span> Planos de Acao</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span> Auditorias</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-violet-600 inline-block"></span> Treinamentos</span>
        <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-teal-700 inline-block"></span> Cronograma</span>
        <span id="agendaRefreshStatus" class="text-xs text-gray-500">Atualizado agora</span>
      </div>
    </div>
  </div>

  <div class="bg-white shadow rounded p-4">
    <style>
      .agenda-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
      .agenda-head { font-weight: 600; text-align: center; padding: 10px 0; color: #555; }
      .agenda-day { border: 1px solid #e5e7eb; border-radius: 8px; min-height: 146px; padding: 8px; text-align: left; background: #fff; transition: border-color .15s ease, box-shadow .15s ease; }
      .agenda-day:hover { border-color: #d6a35d; box-shadow: 0 0 0 1px rgba(214,163,93,.18); }
      .agenda-day.muted { opacity: .55; background: #fafafa; }
      .agenda-day.today { box-shadow: 0 0 0 2px rgba(230,96,76,.22); border-color: #e6604c; }
      .agenda-date { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: #4b5563; margin-bottom: 8px; }
      .agenda-count { font-size: 11px; padding: 2px 6px; border-radius: 999px; background: #f3f4f6; }
      .agenda-list { display: flex; flex-direction: column; gap: 4px; }
      .agenda-chip { display: block; width: 100%; border-radius: 6px; padding: 6px 8px; font-size: 12px; color: #111827; border: 1px solid transparent; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .agenda-chip.planoacao { background: #dbeafe; border-color: #bfdbfe; }
      .agenda-chip.auditoria { background: #fef3c7; border-color: #fde68a; }
      .agenda-chip.treinamento { background: #ede9fe; border-color: #ddd6fe; }
      .agenda-chip.cronograma { background: #ccfbf1; border-color: #99f6e4; }
      .agenda-empty { color: #9ca3af; font-size: 12px; }
      .agenda-more { font-size: 11px; color: #6b7280; }
      .agenda-modal-backdrop { position: fixed; inset: 0; background: rgba(17,24,39,.55); display: none; align-items: center; justify-content: center; z-index: 60; }
      .agenda-modal { width: min(920px, calc(100vw - 32px)); max-height: 85vh; overflow: hidden; background: #fff; border-radius: 14px; box-shadow: 0 18px 50px rgba(0,0,0,.2); display: flex; flex-direction: column; }
      .agenda-modal-body { overflow: auto; padding: 16px; }
      .agenda-event-card { border: 1px solid #e5e7eb; border-left-width: 4px; border-radius: 10px; padding: 12px; margin-bottom: 10px; }
      .agenda-event-card.planoacao { border-left-color: #2563eb; }
      .agenda-event-card.auditoria { border-left-color: #d97706; }
      .agenda-event-card.treinamento { border-left-color: #7c3aed; }
      .agenda-event-card.cronograma { border-left-color: #0f766e; }
      .agenda-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; margin-right: 6px; }
      .agenda-badge.planoacao { background: #dbeafe; color: #1d4ed8; }
      .agenda-badge.auditoria { background: #fef3c7; color: #b45309; }
      .agenda-badge.treinamento { background: #ede9fe; color: #6d28d9; }
      .agenda-badge.cronograma { background: #ccfbf1; color: #0f766e; }
      @media (max-width: 768px) {
        .agenda-grid { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .agenda-head { display: none; }
        .agenda-day { min-height: 110px; }
      }
    </style>
    <div class="agenda-grid mb-2">
      <?php foreach (['Dom','Seg','Ter','Qua','Qui','Sex','Sab'] as $wd): ?>
        <div class="agenda-head"><?= htmlspecialchars($wd) ?></div>
      <?php endforeach; ?>
    </div>
    <div class="agenda-grid" id="agendaGrid">
      <?php foreach ($days as $day): ?>
        <?php $date = (string)$day['date']; $dayEvents = $eventsByDate[$date] ?? []; ?>
        <button type="button"
                class="agenda-day <?= !empty($day['is_current_month']) ? '' : 'muted' ?> <?= !empty($day['is_today']) ? 'today' : '' ?>"
                data-calendar-day="<?= htmlspecialchars($date) ?>">
          <div class="agenda-date">
            <span><?= (int)($day['day'] ?? 0) ?></span>
            <span class="agenda-count"><?= count($dayEvents) ?> evento(s)</span>
          </div>
          <div class="agenda-list">
            <?php if (empty($dayEvents)): ?>
              <span class="agenda-empty">Nenhum evento</span>
            <?php else: ?>
              <?php foreach (array_slice($dayEvents, 0, 3) as $event): ?>
                <span class="agenda-chip <?= htmlspecialchars((string)$event['type']) ?>"><?= htmlspecialchars((string)$event['title']) ?></span>
              <?php endforeach; ?>
              <?php if (count($dayEvents) > 3): ?>
                <span class="agenda-more">+<?= count($dayEvents) - 3 ?> evento(s)</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="agenda-modal-backdrop" id="agendaModalBackdrop" aria-hidden="true">
  <div class="agenda-modal" role="dialog" aria-modal="true" aria-labelledby="agendaModalTitle">
    <div class="flex items-center justify-between border-b px-4 py-3">
      <div>
        <h2 id="agendaModalTitle" class="text-lg font-semibold text-brand-black">Eventos do dia</h2>
        <div id="agendaModalSubtitle" class="text-sm text-gray-500"></div>
      </div>
      <button type="button" id="agendaModalClose" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Fechar</button>
    </div>
    <div class="agenda-modal-body">
      <div id="agendaModalList"></div>
    </div>
  </div>
</div>

<script>
  (function(){
    const app = document.getElementById('agendaApp');
    if (!app) return;
    const apiUrl = app.dataset.api;
    const start = app.dataset.start;
    const end = app.dataset.end;
    let activeFilter = app.dataset.filter || 'all';
    let grouped = <?= json_encode($eventsByDate, JSON_UNESCAPED_UNICODE) ?>;
    const refreshStatus = document.getElementById('agendaRefreshStatus');
    const grid = document.getElementById('agendaGrid');
    const modal = document.getElementById('agendaModalBackdrop');
    const modalTitle = document.getElementById('agendaModalTitle');
    const modalSubtitle = document.getElementById('agendaModalSubtitle');
    const modalList = document.getElementById('agendaModalList');
    const modalClose = document.getElementById('agendaModalClose');

    const updateRefreshStatus = (text)=>{ if (refreshStatus) refreshStatus.textContent = text; };
    const escapeHtml = (value)=>String(value ?? '').replace(/[&<>"']/g, (char)=>({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const humanFilter = (type)=> {
      if (type === 'planoacao') return 'Planos de Acao';
      if (type === 'auditoria') return 'Auditorias';
      if (type === 'treinamento') return 'Treinamentos';
      if (type === 'cronograma') return 'Cronograma';
      return 'Todos';
    };

    const renderDayCards = ()=>{
      grid.querySelectorAll('[data-calendar-day]').forEach((el)=>{
        const date = el.getAttribute('data-calendar-day');
        const items = Array.isArray(grouped[date]) ? grouped[date] : [];
        const countEl = el.querySelector('.agenda-count');
        const listEl = el.querySelector('.agenda-list');
        if (countEl) countEl.textContent = items.length + ' evento(s)';
        if (!listEl) return;
        if (!items.length) {
          listEl.innerHTML = '<span class="agenda-empty">Nenhum evento</span>';
          return;
        }
        const lines = items.slice(0, 3).map((event)=>(
          '<span class="agenda-chip ' + escapeHtml(event.type) + '">' + escapeHtml(event.title) + '</span>'
        ));
        if (items.length > 3) {
          lines.push('<span class="agenda-more">+' + (items.length - 3) + ' evento(s)</span>');
        }
        listEl.innerHTML = lines.join('');
      });
    };

    const openDayModal = (date)=>{
      const items = Array.isArray(grouped[date]) ? grouped[date] : [];
      modalTitle.textContent = 'Eventos do dia';
      modalSubtitle.textContent = date + ' • Filtro: ' + humanFilter(activeFilter);
      if (!items.length) {
        modalList.innerHTML = '<div class="text-sm text-gray-500">Nenhum evento encontrado para esta data.</div>';
      } else {
        modalList.innerHTML = items.map((event)=>(
          '<div class="agenda-event-card ' + escapeHtml(event.type) + '">' +
            '<div class="flex flex-wrap items-center gap-2 mb-2">' +
              '<span class="agenda-badge ' + escapeHtml(event.type) + '">' + escapeHtml(event.type_label) + '</span>' +
              '<span class="text-xs text-gray-500">' + escapeHtml(event.time || 'Dia todo') + '</span>' +
              (event.status ? '<span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">' + escapeHtml(event.status) + '</span>' : '') +
            '</div>' +
            '<div class="font-semibold text-brand-black mb-1">' + escapeHtml(event.title) + '</div>' +
            (event.client ? '<div class="text-sm text-gray-600 mb-1">Cliente: ' + escapeHtml(event.client) + '</div>' : '') +
            (event.description ? '<div class="text-sm text-gray-700 mb-2">' + escapeHtml(event.description) + '</div>' : '') +
            '<div class="flex items-center justify-between gap-3">' +
              '<div class="text-xs text-gray-500">' + escapeHtml(date) + '</div>' +
              '<a class="text-sm text-brand-pink font-semibold" href="' + escapeHtml(event.link) + '">Abrir</a>' +
            '</div>' +
          '</div>'
        )).join('');
      }
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
    };

    const closeDayModal = ()=>{
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    };

    const fetchEvents = ()=>{
      updateRefreshStatus('Atualizando...');
      fetch(apiUrl + '&start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end) + '&type=' + encodeURIComponent(activeFilter), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then((r)=>r.json())
        .then((json)=>{
          grouped = (json && typeof json.grouped === 'object' && json.grouped) ? json.grouped : {};
          renderDayCards();
          updateRefreshStatus('Atualizado em ' + new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }));
        })
        .catch((err)=>{
          console.error('[agenda] erro ao carregar eventos', err);
          updateRefreshStatus('Falha ao atualizar');
        });
    };

    document.querySelectorAll('.agenda-filter').forEach((button)=>{
      button.addEventListener('click', ()=>{
        activeFilter = button.dataset.filter || 'all';
        app.dataset.filter = activeFilter;
        document.querySelectorAll('.agenda-filter').forEach((el)=>{
          el.className = 'agenda-filter px-3 py-2 rounded border text-sm ' + (el === button
            ? 'bg-brand-pink text-white border-brand-pink'
            : 'bg-white text-brand-brown border-gray-300');
        });
        const url = new URL(window.location.href);
        url.searchParams.set('route', 'agenda/index');
        url.searchParams.set('type', activeFilter);
        window.history.replaceState({}, '', url.toString());
        fetchEvents();
      });
    });

    grid.addEventListener('click', (e)=>{
      const target = e.target.closest('[data-calendar-day]');
      if (!target) return;
      openDayModal(target.getAttribute('data-calendar-day') || '');
    });

    modalClose?.addEventListener('click', closeDayModal);
    modal?.addEventListener('click', (e)=>{ if (e.target === modal) closeDayModal(); });
    document.addEventListener('keydown', (e)=>{
      if (e.key === 'Escape' && modal.style.display === 'flex') closeDayModal();
    });

    setInterval(fetchEvents, 60000);
  })();
</script>
