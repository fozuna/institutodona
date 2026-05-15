<?php
$clientes = is_array($clientes ?? null) ? $clientes : [];
$filters = is_array($filters ?? null) ? $filters : [];
$monthStart = (string)($filters['month_start'] ?? '');
$monthEnd = (string)($filters['month_end'] ?? '');
$clienteIds = is_array($filters['cliente_ids'] ?? null) ? array_values(array_map('intval', $filters['cliente_ids'])) : [];
$selectedLabel = $clienteIds ? (count($clienteIds) . ' empresa(s) selecionada(s)') : 'Todas as empresas';
?>

<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold">Dashboard</h1>
      <p class="text-sm text-gray-600">Visão <?= htmlspecialchars((string)($user['tipo_acesso'] ?? '')) ?></p>
      <p class="text-xs text-gray-500 mt-1"><span id="dashboardPeriodLabel"></span> • <span id="dashboardEmpresaLabel"><?= htmlspecialchars($selectedLabel) ?></span></p>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <form method="get" action="index.php" class="grid grid-cols-1 lg:grid-cols-12 gap-4" id="dashboardFiltersForm">
      <input type="hidden" name="route" value="dashboard/index" />
      <div class="lg:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Mês inicial</label>
        <input type="month" name="month_start" id="dashboardMonthStart" class="border border-gray-300 rounded-lg p-3 w-full" required value="<?= htmlspecialchars($monthStart) ?>" />
      </div>
      <div class="lg:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Mês final</label>
        <input type="month" name="month_end" id="dashboardMonthEnd" class="border border-gray-300 rounded-lg p-3 w-full" required value="<?= htmlspecialchars($monthEnd) ?>" />
      </div>
      <div class="lg:col-span-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Empresas</label>
        <details class="border border-gray-300 rounded-lg">
          <summary class="cursor-pointer select-none p-3 text-sm text-brand-brown">
            <span id="dashboardEmpresasSummary"><?= htmlspecialchars($selectedLabel) ?></span>
          </summary>
          <div class="p-3 border-t bg-white">
            <div class="max-h-56 overflow-auto space-y-2 pr-1">
              <?php foreach ($clientes as $c): ?>
                <?php $id = (int)($c['id'] ?? 0); ?>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" name="clientes[]" value="<?= $id ?>" class="rounded border-gray-300" <?= in_array($id, $clienteIds, true) ? 'checked' : '' ?> />
                  <span><?= htmlspecialchars((string)($c['nome_empresa'] ?? '')) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
              <button type="button" class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm" id="dashboardEmpresasAll">Selecionar todas</button>
              <button type="button" class="px-3 py-2 rounded bg-gray-200 text-brand-brown text-sm" id="dashboardEmpresasNone">Limpar</button>
            </div>
          </div>
        </details>
      </div>
      <div class="lg:col-span-2 flex items-end gap-2">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full" type="submit" id="dashboardApplyBtn">Aplicar</button>
      </div>
      <div class="lg:col-span-12">
        <div id="dashboardFilterError" class="hidden rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
      </div>
    </form>
  </div>

  <div id="dashboardLoading" class="hidden items-center justify-center rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600">
    Carregando dados...
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div class="bg-white shadow rounded-xl p-5 border-l-4 border-brand-red">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-xs text-gray-500">Cumprimento do Cronograma</div>
          <div class="text-3xl font-bold text-brand-brown mt-1"><span id="dashCronogramaPct">—</span>%</div>
          <div class="text-sm text-gray-600 mt-1"><span id="dashCronogramaDone">—</span> finalizados / <span id="dashCronogramaTotal">—</span> no período</div>
        </div>
        <canvas id="dashCronogramaChart" height="120" class="w-40"></canvas>
      </div>
    </div>

    <div class="bg-white shadow rounded-xl p-5 border-l-4 border-brand-orange">
      <div class="text-xs text-gray-500">Biblioteca</div>
      <div class="text-3xl font-bold text-brand-brown mt-1" id="dashBibliotecaTotal">—</div>
      <div class="text-sm text-gray-600 mt-1">Itens cadastrados no período</div>
    </div>

    <div class="bg-white shadow rounded-xl p-5 border-l-4 border-green-600">
      <div class="text-xs text-gray-500">Auditorias</div>
      <div class="text-3xl font-bold text-brand-brown mt-1"><span id="dashAuditoriasMedia">—</span>%</div>
      <div class="text-sm text-gray-600 mt-1">Média geral • <span id="dashAuditoriasTotal">—</span> realizada(s)</div>
    </div>

    <div class="bg-white shadow rounded-xl p-5 border-l-4 border-blue-600">
      <div class="text-xs text-gray-500">Indicadores Estratégicos</div>
      <div class="text-3xl font-bold text-brand-brown mt-1"><span id="dashIndicadoresMedia">—</span>%</div>
      <div class="text-sm text-gray-600 mt-1"><span id="dashIndicadoresTotal">—</span> evento(s) no período</div>
      <div class="h-3 bg-gray-100 rounded mt-4 overflow-hidden">
        <div id="dashIndicadoresBar" class="h-3 bg-brand-red" style="width:0%"></div>
      </div>
    </div>

    <div class="bg-white shadow rounded-xl p-5 border-l-4 border-amber-600">
      <div class="text-xs text-gray-500">Planos de Ação</div>
      <div class="text-3xl font-bold text-brand-brown mt-1" id="dashPlanoAcaoTotal">—</div>
      <div class="text-sm text-gray-600 mt-1">Total no período</div>
      <div class="mt-4">
        <canvas id="dashPlanoAcaoChart" height="140" class="w-full"></canvas>
      </div>
    </div>

    <div class="bg-white shadow rounded-xl p-5 border-l-4 border-brand-red">
      <div class="text-xs text-gray-500">Treinamentos</div>
      <div class="grid grid-cols-2 gap-4 mt-2">
        <div>
          <div class="text-sm text-gray-600">Planejados</div>
          <div class="text-2xl font-bold text-brand-brown" id="dashTreinamentosPlanejados">—</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Realizados</div>
          <div class="text-2xl font-bold text-brand-brown" id="dashTreinamentosRealizados">—</div>
        </div>
      </div>
      <div class="text-sm text-gray-600 mt-3">Participação: <span id="dashTreinamentosParticipacao">—</span>%</div>
      <div class="mt-4">
        <canvas id="dashTreinamentosChart" height="160" class="w-full"></canvas>
      </div>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
      <div class="font-semibold text-brand-brown">Detalhamento do Cronograma</div>
      <div class="text-sm text-gray-600" id="dashCronogramaLegend"></div>
    </div>
    <div class="mt-3">
      <div id="dashCronogramaBars" class="grid grid-cols-1 md:grid-cols-5 gap-3"></div>
    </div>
  </div>
</div>

<script>
  (function () {
    const form = document.getElementById('dashboardFiltersForm');
    const monthStart = document.getElementById('dashboardMonthStart');
    const monthEnd = document.getElementById('dashboardMonthEnd');
    const err = document.getElementById('dashboardFilterError');
    const loading = document.getElementById('dashboardLoading');
    const summary = document.getElementById('dashboardEmpresasSummary');
    const empresaLabel = document.getElementById('dashboardEmpresaLabel');
    const periodLabel = document.getElementById('dashboardPeriodLabel');
    const btnAll = document.getElementById('dashboardEmpresasAll');
    const btnNone = document.getElementById('dashboardEmpresasNone');

    const elCronPct = document.getElementById('dashCronogramaPct');
    const elCronDone = document.getElementById('dashCronogramaDone');
    const elCronTot = document.getElementById('dashCronogramaTotal');
    const elCronBars = document.getElementById('dashCronogramaBars');
    const elCronLegend = document.getElementById('dashCronogramaLegend');

    const elBib = document.getElementById('dashBibliotecaTotal');

    const elAudMedia = document.getElementById('dashAuditoriasMedia');
    const elAudTot = document.getElementById('dashAuditoriasTotal');

    const elIndMedia = document.getElementById('dashIndicadoresMedia');
    const elIndTot = document.getElementById('dashIndicadoresTotal');
    const elIndBar = document.getElementById('dashIndicadoresBar');

    const elPlanoTot = document.getElementById('dashPlanoAcaoTotal');

    const elTrPlan = document.getElementById('dashTreinamentosPlanejados');
    const elTrReal = document.getElementById('dashTreinamentosRealizados');
    const elTrPct = document.getElementById('dashTreinamentosParticipacao');

    const cronCanvas = document.getElementById('dashCronogramaChart');
    const planoCanvas = document.getElementById('dashPlanoAcaoChart');
    const trCanvas = document.getElementById('dashTreinamentosChart');

    function setErr(text) {
      if (!err) return;
      if (!text) {
        err.classList.add('hidden');
        err.textContent = '';
        return;
      }
      err.classList.remove('hidden');
      err.textContent = String(text);
    }

    function selectedEmpresaCount() {
      return (form ? Array.from(form.querySelectorAll('input[name="clientes[]"]:checked')).length : 0);
    }

    function updateEmpresaLabels() {
      const n = selectedEmpresaCount();
      const label = n ? (n + ' empresa(s) selecionada(s)') : 'Todas as empresas';
      if (summary) summary.textContent = label;
      if (empresaLabel) empresaLabel.textContent = label;
    }

    function validateMonths() {
      const a = String(monthStart?.value || '').trim();
      const b = String(monthEnd?.value || '').trim();
      if (!a || !b) {
        setErr('Selecione o mês inicial e o mês final.');
        return false;
      }
      if (b < a) {
        setErr('O mês final não pode ser anterior ao mês inicial.');
        return false;
      }
      setErr('');
      return true;
    }

    function prepareCanvas(canvas, height) {
      if (!canvas) return null;
      const ratio = window.devicePixelRatio || 1;
      const width = Math.max(280, canvas.parentElement ? canvas.parentElement.clientWidth : 520);
      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      canvas.width = Math.round(width * ratio);
      canvas.height = Math.round(height * ratio);
      const ctx = canvas.getContext('2d');
      if (!ctx) return null;
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      return ctx;
    }

    function drawDonut(canvas, pct) {
      if (!canvas) return;
      const ctx = prepareCanvas(canvas, 120);
      if (!ctx) return;
      const logicalWidth = canvas.width / (window.devicePixelRatio || 1);
      const logicalHeight = canvas.height / (window.devicePixelRatio || 1);
      ctx.clearRect(0, 0, logicalWidth, logicalHeight);
      const cx = logicalWidth / 2;
      const cy = logicalHeight / 2;
      const r = Math.min(cx, cy) - 10;
      const start = -Math.PI / 2;
      const angle = Math.max(0, Math.min(100, pct)) / 100 * Math.PI * 2;

      ctx.lineWidth = 14;
      ctx.strokeStyle = '#e5e7eb';
      ctx.beginPath();
      ctx.arc(cx, cy, r, 0, Math.PI * 2);
      ctx.stroke();

      ctx.strokeStyle = pct >= 80 ? '#16a34a' : (pct >= 50 ? '#d97706' : '#dc2626');
      ctx.beginPath();
      ctx.arc(cx, cy, r, start, start + angle);
      ctx.stroke();

      ctx.fillStyle = '#111827';
      ctx.font = '12px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(String(Math.round(pct)) + '%', cx, cy + 4);
    }

    function drawPlanoBars(canvas, rows) {
      if (!canvas) return;
      const ctx = prepareCanvas(canvas, 140);
      if (!ctx) return;
      const logicalWidth = canvas.width / (window.devicePixelRatio || 1);
      const logicalHeight = canvas.height / (window.devicePixelRatio || 1);
      ctx.clearRect(0, 0, logicalWidth, logicalHeight);
      const data = Array.isArray(rows) ? rows : [];
      const labels = ['Planejado', 'Em Andamento', 'Pendente', 'Concluído'];
      const colors = {
        'Planejado': '#2563eb',
        'Em Andamento': '#d97706',
        'Pendente': '#dc2626',
        'Concluído': '#16a34a'
      };
      const map = {};
      data.forEach((r) => { map[String(r.status || '')] = Number(r.total || 0); });
      const values = labels.map((l) => map[l] || 0);
      const max = Math.max(1, ...values);
      const baseY = 110;
      const left = 18;
      const width = 36;
      const gap = 18;
      labels.forEach((label, idx) => {
        const x = left + idx * (width + gap);
        const v = values[idx];
        const h = (v / max) * 70;
        ctx.fillStyle = colors[label] || '#111827';
        ctx.fillRect(x, baseY - h, width, h);
        ctx.fillStyle = '#374151';
        ctx.font = '11px sans-serif';
        ctx.fillText(String(v), x + 10, baseY - h - 6);
        ctx.save();
        ctx.translate(x - 4, baseY + 18);
        ctx.rotate(-0.45);
        ctx.fillText(label, 0, 0);
        ctx.restore();
      });
    }

    function drawTreinamentos(canvas, rows) {
      if (!canvas) return;
      const ctx = prepareCanvas(canvas, 160);
      if (!ctx) return;
      const logicalWidth = canvas.width / (window.devicePixelRatio || 1);
      const logicalHeight = canvas.height / (window.devicePixelRatio || 1);
      ctx.clearRect(0, 0, logicalWidth, logicalHeight);
      const data = (Array.isArray(rows) ? rows : []).slice(0, 6);
      const max = Math.max(1, ...data.map((r) => Number(r.participacao_pct || 0)));
      const baseY = 130;
      const left = 16;
      const width = 42;
      const gap = 18;
      data.forEach((r, i) => {
        const x = left + i * (width + gap);
        const v = Number(r.participacao_pct || 0);
        const h = (v / max) * 90;
        ctx.fillStyle = v >= 80 ? '#16a34a' : (v >= 50 ? '#d97706' : '#dc2626');
        ctx.fillRect(x, baseY - h, width, h);
        ctx.fillStyle = '#374151';
        ctx.font = '11px sans-serif';
        ctx.fillText(String(Math.round(v)) + '%', x + 4, baseY - h - 6);
        ctx.save();
        ctx.translate(x - 2, baseY + 18);
        ctx.rotate(-0.45);
        ctx.fillText(String(r.treinamento_nome || '').slice(0, 14), 0, 0);
        ctx.restore();
      });
    }

    function renderCronogramaBars(byStatus) {
      if (!elCronBars) return;
      const entries = Object.entries(byStatus || {});
      const total = entries.reduce((sum, it) => sum + Number(it[1] || 0), 0) || 0;
      const order = ['Finalizado', 'Andamento', 'Pendente', 'Adiado', 'Planejado'];
      const color = {
        'Finalizado': 'bg-green-600',
        'Andamento': 'bg-amber-600',
        'Pendente': 'bg-brand-red',
        'Adiado': 'bg-gray-500',
        'Planejado': 'bg-blue-600'
      };
      const ordered = order.filter((k) => Object.prototype.hasOwnProperty.call(byStatus || {}, k)).map((k) => [k, byStatus[k]]);
      elCronBars.innerHTML = ordered.map(([st, ct]) => {
        const v = Number(ct || 0);
        const pct = total > 0 ? (v / total) * 100 : 0;
        return `
          <div class="rounded-lg border border-gray-200 p-3">
            <div class="flex justify-between text-sm">
              <span class="font-medium text-brand-brown">${st}</span>
              <span class="text-gray-600">${v}</span>
            </div>
            <div class="h-3 bg-gray-100 rounded mt-2 overflow-hidden">
              <div class="h-3 ${color[st] || 'bg-gray-700'}" style="width:${pct}%"></div>
            </div>
          </div>
        `;
      }).join('');
      if (elCronLegend) {
        elCronLegend.textContent = total ? ('Total no período: ' + total) : 'Nenhum evento no período';
      }
    }

    async function loadMetrics() {
      if (!validateMonths()) return;
      updateEmpresaLabels();
      const params = new URLSearchParams(new FormData(form));
      params.set('route', 'dashboard/metrics');
      const url = 'index.php?' + params.toString();
      if (loading) loading.classList.remove('hidden');
      try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json().catch(() => null);
        if (!res.ok) {
          setErr(json && json.message ? json.message : 'Não foi possível carregar os dados da Dashboard.');
          return;
        }
        if (!json || !json.ok) {
          setErr(json && json.message ? json.message : 'Não foi possível carregar os dados da Dashboard.');
          return;
        }
        const f = json.filters || {};
        if (periodLabel) {
          periodLabel.textContent = (f.start_date && f.end_date) ? ('Período: ' + f.start_date + ' a ' + f.end_date) : '';
        }
        if (empresaLabel) {
          const n = Array.isArray(f.cliente_ids) ? f.cliente_ids.length : 0;
          empresaLabel.textContent = n ? (n + ' empresa(s) selecionada(s)') : 'Todas as empresas';
        }
        const cron = json.cronograma || {};
        const pct = Number(cron.pct || 0);
        if (elCronPct) elCronPct.textContent = pct.toFixed(2).replace('.', ',');
        if (elCronDone) elCronDone.textContent = String(cron.finalizados || 0);
        if (elCronTot) elCronTot.textContent = String(cron.total || 0);
        drawDonut(cronCanvas, pct);
        renderCronogramaBars(cron.by_status || {});

        const bib = json.biblioteca || {};
        if (elBib) elBib.textContent = String(bib.total_itens || 0);

        const aud = json.auditorias || {};
        if (elAudMedia) elAudMedia.textContent = (aud.media_conformidade_pct === null || aud.media_conformidade_pct === undefined) ? '—' : String(Number(aud.media_conformidade_pct).toFixed(2)).replace('.', ',');
        if (elAudTot) elAudTot.textContent = String(aud.total_realizadas || 0);

        const ind = json.indicadores || {};
        const indPct = (ind.media_atingimento_pct === null || ind.media_atingimento_pct === undefined) ? null : Number(ind.media_atingimento_pct);
        if (elIndMedia) elIndMedia.textContent = indPct === null ? '—' : String(indPct.toFixed(2)).replace('.', ',');
        if (elIndTot) elIndTot.textContent = String(ind.total_eventos || 0);
        if (elIndBar) elIndBar.style.width = Math.max(0, Math.min(100, indPct || 0)) + '%';

        const pa = json.planoacao || {};
        if (elPlanoTot) elPlanoTot.textContent = String(pa.total || 0);
        drawPlanoBars(planoCanvas, pa.by_status || []);

        const tr = json.treinamentos || {};
        if (elTrPlan) elTrPlan.textContent = String(tr.planejados || 0);
        if (elTrReal) elTrReal.textContent = String(tr.realizados || 0);
        if (elTrPct) elTrPct.textContent = String(Number(tr.participacao_pct || 0).toFixed(2)).replace('.', ',');
        drawTreinamentos(trCanvas, tr.por_treinamento || []);
      } catch (e) {
        setErr('Não foi possível carregar os dados da Dashboard.');
      } finally {
        if (loading) loading.classList.add('hidden');
      }
    }

    if (btnAll) {
      btnAll.addEventListener('click', () => {
        form.querySelectorAll('input[name="clientes[]"]').forEach((el) => { el.checked = true; });
        updateEmpresaLabels();
      });
    }
    if (btnNone) {
      btnNone.addEventListener('click', () => {
        form.querySelectorAll('input[name="clientes[]"]').forEach((el) => { el.checked = false; });
        updateEmpresaLabels();
      });
    }

    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        loadMetrics();
        const params = new URLSearchParams(new FormData(form));
        params.set('route', 'dashboard/index');
        window.history.replaceState({}, '', 'index.php?' + params.toString());
      });
      form.querySelectorAll('input[name="clientes[]"]').forEach((el) => el.addEventListener('change', updateEmpresaLabels));
    }
    if (monthStart) monthStart.addEventListener('change', loadMetrics);
    if (monthEnd) monthEnd.addEventListener('change', loadMetrics);

    updateEmpresaLabels();
    loadMetrics();
  })();
</script>
