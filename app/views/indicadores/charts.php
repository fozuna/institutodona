<?php
$t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace);
$indicadorId = (int)($indicadorId ?? 0);
$periodoInicio = (string)($periodoInicio ?? '');
$periodoFim = (string)($periodoFim ?? '');
$indicadores = is_array($indicadores ?? null) ? $indicadores : [];
$departamentos = is_array($departamentos ?? null) ? $departamentos : [];
$departamentoId = (int)($departamentoId ?? 0);
?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.charts')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.chart.limits')) ?> · <?= htmlspecialchars($t('indicadores.chart.current')) ?></p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
      <?php if (!empty($series) && $cliente): ?>
        <button type="button" id="indicadoresChartsPdfBtn" class="px-4 py-3 rounded-lg bg-brand-red text-white text-center">
          Exportar PDF
        </button>
      <?php endif; ?>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center" href="index.php?route=indicadores/index<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>">
        <?= htmlspecialchars($t('indicadores.action.back')) ?>
      </a>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
      <input type="hidden" name="route" value="indicadores/charts" />
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <select name="cliente" id="indicadoresChartsClienteSelect" class="border border-gray-300 rounded-lg p-3 w-full">
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
        <select name="departamento_id" id="indicadoresChartsDepartamentoSelect" class="border border-gray-300 rounded-lg p-3 w-full" <?= $cliente ? '' : 'disabled' ?>>
          <option value="0">Todos os departamentos</option>
          <?php foreach ($departamentos as $dep): ?>
            <option value="<?= (int)$dep['id'] ?>" <?= $departamentoId === (int)$dep['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($dep['nome'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-4">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.indicador')) ?></label>
        <select name="indicador_id" id="indicadoresChartsIndicadorSelect" class="border border-gray-300 rounded-lg p-3 w-full" <?= $cliente ? '' : 'disabled' ?>>
          <option value="0"><?= htmlspecialchars($t('indicadores.option.none')) ?></option>
          <?php foreach ($indicadores as $ind): ?>
            <option value="<?= (int)$ind['id'] ?>" <?= $indicadorId === (int)$ind['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($ind['indicador'] ?? $ind['nome'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2 flex items-end">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full" type="submit"><?= htmlspecialchars($t('indicadores.action.filter')) ?></button>
      </div>
      <div class="md:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.periodo_apuracao')) ?></label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Início</label>
            <input type="date" name="periodo_inicio" id="indicadoresChartsPeriodoInicio" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= htmlspecialchars($periodoInicio) ?>" <?= $cliente ? '' : 'disabled' ?> />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fim</label>
            <input type="date" name="periodo_fim" id="indicadoresChartsPeriodoFim" class="border border-gray-300 rounded-lg p-3 w-full" value="<?= htmlspecialchars($periodoFim) ?>" <?= $cliente ? '' : 'disabled' ?> />
          </div>
        </div>
        <p class="mt-1 text-xs text-gray-500">Deixe em branco para ver todo o histórico.</p>
      </div>
      <div class="md:col-span-3 flex items-end">
        <button type="button" id="indicadoresChartsClear" class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown w-full"><?= htmlspecialchars($t('indicadores.action.clear')) ?></button>
      </div>
      <div class="md:col-span-6">
        <div id="indicadoresChartsErro" class="hidden rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
      </div>
    </form>
  </div>

  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.client')) ?></div>
  <?php elseif (empty($series)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.charts')) ?></div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl p-4">
      <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="text-sm font-semibold text-gray-800">Indicadores</div>
        <div class="flex flex-col sm:flex-row gap-2">
          <button type="button" id="indicadoresChartsSelectAll" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Selecionar todos</button>
          <button type="button" id="indicadoresChartsClearAll" class="px-3 py-2 rounded bg-gray-200 text-brand-brown">Limpar seleção</button>
        </div>
      </div>
      <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2" id="indicadoresChartsMultiSelect">
        <?php foreach ($series as $name => $seriesItem): ?>
          <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" class="form-checkbox h-4 w-4 text-brand-red" data-indicador-checkbox value="<?= (int)$seriesItem['indicador_id'] ?>" checked />
            <span class="truncate" title="<?= htmlspecialchars((string)$name) ?>"><?= htmlspecialchars((string)$name) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="mt-3 text-xs text-gray-500" id="indicadoresChartsCount"></div>
      <input type="hidden" id="indicadoresChartsCsrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <div id="indicadoresChartsMsg" class="hidden mt-3 rounded border px-4 py-3 text-sm"></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <?php foreach ($series as $name => $seriesItem): ?>
        <?php
          $payload = $seriesItem['points'] ?? [];
          $lastPoint = !empty($payload) ? $payload[count($payload) - 1] : null;
          $metaAtual = $lastPoint ? \App\Core\ValueFormatter::byUnit($lastPoint['meta'], $seriesItem['unit']) : '—';
          $percentuaisPeriodo = array_values(array_filter(array_map(static fn(array $p): ?float => $p['percentual'] === null ? null : (float)$p['percentual'], $payload), static fn($v): bool => $v !== null));
          $atingidoAtual = !empty($percentuaisPeriodo)
              ? \App\Core\ValueFormatter::percent(array_sum($percentuaisPeriodo) / count($percentuaisPeriodo))
              : '—';
          $trendKey = $seriesItem['trend']['trend'] === 'alta'
            ? 'indicadores.meta.trend.up'
            : ($seriesItem['trend']['trend'] === 'queda' ? 'indicadores.meta.trend.down' : 'indicadores.meta.trend.stable');
          $trendText = $t('indicadores.label.tendencia') . ': ' . $t($trendKey) . ' · ' . $t('indicadores.label.cumprimento') . ' médio: ' . \App\Core\ValueFormatter::percent($seriesItem['trend']['media_cumprimento']);
        ?>
        <div class="bg-white shadow rounded-xl p-4" data-indicador-card data-indicador-id="<?= (int)$seriesItem['indicador_id'] ?>" data-indicador-title="<?= htmlspecialchars((string)$name) ?>" data-indicador-meta="<?= htmlspecialchars($metaAtual) ?>" data-indicador-achieved="<?= htmlspecialchars($atingidoAtual) ?>" data-indicador-trend="<?= htmlspecialchars($trendText) ?>">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <div class="font-semibold"><?= htmlspecialchars($name) ?></div>
              <div class="text-xs text-gray-500"><?= htmlspecialchars($trendText) ?></div>
            </div>
            <a class="text-brand-pink font-semibold text-sm" href="index.php?route=indicadores/historico&id=<?= (int)$seriesItem['indicador_id'] ?>">
              <?= htmlspecialchars($t('indicadores.action.history')) ?>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-2 text-sm mb-3">
            <div class="rounded-lg bg-gray-50 p-3">
              <div class="text-gray-500">Meta</div>
              <div class="font-semibold text-base"><?= htmlspecialchars($metaAtual) ?></div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3" title="Média do percentual de cumprimento no período filtrado">
              <div class="text-gray-500">Atingido</div>
              <div class="font-semibold text-base"><?= htmlspecialchars($atingidoAtual) ?></div>
            </div>
          </div>
          <div class="w-full overflow-hidden">
            <canvas class="block w-full" data-indicador-chart='<?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE)) ?>'></canvas>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <script>
      (function () {
        const nf = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function prepareCanvas(canvas, logicalHeight) {
          if (!canvas) return null;
          const ratio = window.devicePixelRatio || 1;
          const parent = canvas.parentElement;
          const parentWidth = parent ? parent.clientWidth : 0;
          const logicalWidth = Math.max(280, parentWidth || 600);
          canvas.style.width = '100%';
          canvas.style.height = String(logicalHeight) + 'px';
          canvas.width = Math.round(logicalWidth * ratio);
          canvas.height = Math.round(logicalHeight * ratio);
          const ctx = canvas.getContext('2d');
          if (!ctx) return null;
          ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
          return { ctx, width: logicalWidth, height: logicalHeight };
        }

        function draw(canvas, points) {
          const prepared = prepareCanvas(canvas, 240);
          if (!prepared) return;
          const ctx = prepared.ctx;
          const width = prepared.width;
          const height = prepared.height;
          const pad = 34;
          const metas = points.map((point) => Number(point.meta || 0));
          const realizados = points.map((point) => point.achieved === null ? null : Number(point.achieved));
          const maxValue = Math.max(1, ...metas, ...realizados.filter((point) => point !== null));
          ctx.clearRect(0, 0, width, height);

          ctx.strokeStyle = '#d1d5db';
          ctx.beginPath();
          ctx.moveTo(pad, pad);
          ctx.lineTo(pad, height - pad);
          ctx.lineTo(width - pad, height - pad);
          ctx.stroke();

          const x = (index) => {
            if (points.length === 1) return width / 2;
            return pad + ((width - (pad * 2)) * (index / (points.length - 1)));
          };
          const y = (value) => height - pad - ((height - (pad * 2)) * (value / maxValue));

          ctx.fillStyle = '#6b7280';
          ctx.font = '11px sans-serif';
          ctx.fillText('0', pad - 12, height - pad + 4);
          ctx.fillText(nf.format(maxValue), pad - 12, pad + 4);

          ctx.strokeStyle = '#2563eb';
          ctx.lineWidth = 2;
          ctx.beginPath();
          metas.forEach((value, index) => {
            if (index === 0) ctx.moveTo(x(index), y(value));
            else ctx.lineTo(x(index), y(value));
          });
          ctx.stroke();

          ctx.strokeStyle = '#dc2626';
          ctx.beginPath();
          realizados.forEach((value, index) => {
            if (value === null) return;
            if (index === 0 || realizados.slice(0, index).every((entry) => entry === null)) ctx.moveTo(x(index), y(value));
            else ctx.lineTo(x(index), y(value));
          });
          ctx.stroke();

          points.forEach((point, index) => {
            const label = String(point.date || '').slice(5);
            ctx.fillStyle = '#6b7280';
            ctx.fillText(label, x(index) - 14, height - pad + 16);
            if (point.achieved !== null) {
              ctx.fillStyle = point.status === 'atingida' ? '#16a34a' : (point.status === 'parcial' ? '#d97706' : '#dc2626');
              ctx.beginPath();
              ctx.arc(x(index), y(Number(point.achieved)), 4, 0, Math.PI * 2);
              ctx.fill();
              ctx.fillStyle = '#111827';
              ctx.font = '11px sans-serif';
              const txt = nf.format(Number(point.achieved));
              ctx.fillText(txt, x(index) - 12, y(Number(point.achieved)) - 10);
            }
          });

          const legendWidth = 120;
          const legendX = Math.max(pad + 4, width - pad - legendWidth);
          const legendY = pad + 2;
          ctx.fillStyle = '#6b7280';
          ctx.fillText('Meta', legendX + 28, legendY);
          ctx.fillStyle = '#2563eb';
          ctx.fillRect(legendX, legendY - 8, 18, 2);
          ctx.fillStyle = '#6b7280';
          ctx.fillText('Atingido', legendX + 28, legendY + 16);
          ctx.fillStyle = '#dc2626';
          ctx.fillRect(legendX, legendY + 8, 18, 2);
        }

        function redrawVisibleCharts() {
          document.querySelectorAll('canvas[data-indicador-chart]').forEach((canvas) => {
            const card = canvas.closest('[data-indicador-card]');
            if (card && card.style.display === 'none') return;
            try {
              draw(canvas, JSON.parse(canvas.getAttribute('data-indicador-chart') || '[]'));
            } catch (error) {
            }
          });
        }

        redrawVisibleCharts();
        let resizeTimer = null;
        window.addEventListener('resize', () => {
          if (resizeTimer) clearTimeout(resizeTimer);
          resizeTimer = setTimeout(redrawVisibleCharts, 120);
        });

        const wrap = document.getElementById('indicadoresChartsMultiSelect');
        const count = document.getElementById('indicadoresChartsCount');
        const selectAll = document.getElementById('indicadoresChartsSelectAll');
        const clearAll = document.getElementById('indicadoresChartsClearAll');
        const msg = document.getElementById('indicadoresChartsMsg');
        const pdfBtn = document.getElementById('indicadoresChartsPdfBtn');
        const csrf = document.getElementById('indicadoresChartsCsrf');

        function setMsg(type, text) {
          if (!msg) return;
          if (!text) {
            msg.classList.add('hidden');
            msg.textContent = '';
            return;
          }
          msg.classList.remove('hidden');
          msg.textContent = text;
          msg.classList.remove('border-red-200', 'bg-red-50', 'text-red-700', 'border-green-200', 'bg-green-50', 'text-green-700');
          if (type === 'error') {
            msg.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
          } else {
            msg.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
          }
        }

        function selectedIds() {
          if (!wrap) return [];
          const ids = [];
          wrap.querySelectorAll('input[data-indicador-checkbox]').forEach((el) => {
            if (el.checked) ids.push(Number(el.value));
          });
          return ids.filter((n) => Number.isFinite(n));
        }

        function updateCards() {
          const ids = new Set(selectedIds());
          let visible = 0;
          document.querySelectorAll('[data-indicador-card]').forEach((card) => {
            const id = Number(card.getAttribute('data-indicador-id') || 0);
            const show = ids.has(id);
            card.style.display = show ? '' : 'none';
            if (show) visible++;
          });
          if (count) {
            count.textContent = 'Exibindo ' + visible + ' de ' + document.querySelectorAll('[data-indicador-card]').length + ' gráficos.';
          }
          if (pdfBtn) {
            pdfBtn.disabled = visible === 0;
            pdfBtn.classList.toggle('opacity-75', visible === 0);
            pdfBtn.classList.toggle('cursor-not-allowed', visible === 0);
          }
          redrawVisibleCharts();
        }

        if (wrap) {
          wrap.addEventListener('change', () => {
            setMsg('', '');
            updateCards();
          });
        }
        if (selectAll && wrap) {
          selectAll.addEventListener('click', () => {
            wrap.querySelectorAll('input[data-indicador-checkbox]').forEach((el) => { el.checked = true; });
            setMsg('', '');
            updateCards();
          });
        }
        if (clearAll && wrap) {
          clearAll.addEventListener('click', () => {
            wrap.querySelectorAll('input[data-indicador-checkbox]').forEach((el) => { el.checked = false; });
            setMsg('', '');
            updateCards();
          });
        }

        async function exportPdf() {
          setMsg('', '');
          const cards = Array.from(document.querySelectorAll('[data-indicador-card]')).filter((c) => c.style.display !== 'none');
          if (!cards.length) {
            setMsg('error', 'Selecione ao menos um indicador.');
            return;
          }
          if (!pdfBtn) return;
          pdfBtn.disabled = true;
          pdfBtn.classList.add('opacity-75', 'cursor-not-allowed');
          try {
            const charts = cards.map((card) => {
              const canvas = card.querySelector('canvas');
              const image = canvas ? canvas.toDataURL('image/png') : '';
              return {
                title: card.getAttribute('data-indicador-title') || '',
                trend: card.getAttribute('data-indicador-trend') || '',
                meta: card.getAttribute('data-indicador-meta') || '',
                achieved: card.getAttribute('data-indicador-achieved') || '',
                image,
              };
            });
            const form = new FormData();
            form.append('csrf', csrf ? (csrf.value || '') : '');
            form.append('cliente_id', String(<?= (int)$cliente ?>));
            const clienteName = document.querySelector('select[name="cliente"] option:checked')?.textContent?.trim() || '';
            form.append('cliente_nome', clienteName);
            form.append('charts', JSON.stringify(charts));
            const res = await fetch('index.php?route=indicadores/chartsPdf', {
              method: 'POST',
              body: form,
              headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const ct = (res.headers.get('content-type') || '').toLowerCase();
            if (!res.ok) {
              if (ct.includes('application/json')) {
                const payload = await res.json();
                throw new Error(payload.message || 'Falha ao exportar PDF.');
              }
              throw new Error('Falha ao exportar PDF.');
            }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'graficos_indicadores.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
          } catch (e) {
            setMsg('error', e.message || 'Não foi possível exportar o PDF.');
          } finally {
            pdfBtn.disabled = false;
            pdfBtn.classList.remove('opacity-75', 'cursor-not-allowed');
          }
        }

        if (pdfBtn) {
          pdfBtn.addEventListener('click', exportPdf);
        }
        updateCards();
      })();
    </script>
  <?php endif; ?>
</div>
<script>
  (function () {
    const periodoInicioInput = document.getElementById('indicadoresChartsPeriodoInicio');
    const periodoFimInput = document.getElementById('indicadoresChartsPeriodoFim');
    const msg = document.getElementById('indicadoresChartsErro');
    const clearBtn = document.getElementById('indicadoresChartsClear');
    const form = document.querySelector('form[action="index.php"][method="get"]');
    const indicadorSelect = form?.querySelector('select[name="indicador_id"]');
    const clienteSelect = document.getElementById('indicadoresChartsClienteSelect');
    const departamentoSelect = document.getElementById('indicadoresChartsDepartamentoSelect');

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    async function refreshDepartamentos() {
      if (!departamentoSelect || !clienteSelect) return;
      const cliente = clienteSelect.value || '';
      const current = departamentoSelect.value;
      if (!cliente) {
        departamentoSelect.innerHTML = '<option value="0">Todos os departamentos</option>';
        departamentoSelect.disabled = true;
        return;
      }
      try {
        const params = new URLSearchParams({ route: 'indicadores/apiDepartamentos', cliente_id: cliente });
        const response = await fetch(`index.php?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await response.json().catch(() => null);
        const items = (payload && payload.success && Array.isArray(payload.items)) ? payload.items : [];
        const options = ['<option value="0">Todos os departamentos</option>'].concat(
          items.map((item) => `<option value="${escapeHtml(item.id)}">${escapeHtml(item.nome)}</option>`)
        );
        departamentoSelect.innerHTML = options.join('');
        departamentoSelect.value = items.some((item) => String(item.id) === current) ? current : '0';
        departamentoSelect.disabled = false;
      } catch (e) {
      }
    }

    async function refreshIndicadores() {
      if (!indicadorSelect || !clienteSelect) return;
      const cliente = clienteSelect.value || '';
      const current = indicadorSelect.value;
      if (!cliente) {
        indicadorSelect.innerHTML = '<option value="0">Nenhum</option>';
        indicadorSelect.disabled = true;
        return;
      }
      try {
        const params = new URLSearchParams({ route: 'indicadores/apiIndicadoresByCliente', cliente_id: cliente, departamento_id: departamentoSelect?.value || '0' });
        const response = await fetch(`index.php?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await response.json().catch(() => null);
        const items = (payload && payload.success && Array.isArray(payload.items)) ? payload.items : [];
        const options = ['<option value="0">Nenhum</option>'].concat(
          items.map((item) => `<option value="${escapeHtml(item.id)}">${escapeHtml(item.nome)}</option>`)
        );
        indicadorSelect.innerHTML = options.join('');
        indicadorSelect.value = items.some((item) => String(item.id) === current) ? current : '0';
        indicadorSelect.disabled = false;
      } catch (e) {
      }
    }

    function setMsg(text) {
      if (!msg) return;
      if (!text) {
        msg.classList.add('hidden');
        msg.textContent = '';
        return;
      }
      msg.classList.remove('hidden');
      msg.textContent = String(text);
    }

    function validatePeriodo() {
      const ini = String(periodoInicioInput?.value || '').trim();
      const fim = String(periodoFimInput?.value || '').trim();
      if (!ini && !fim) {
        setMsg('');
        return true;
      }
      if (!ini || !fim) {
        setMsg('Informe as duas datas do período, ou deixe ambas em branco para ver todo o histórico.');
        return false;
      }
      if (fim < ini) {
        setMsg('A data final do período não pode ser anterior à data inicial.');
        return false;
      }
      setMsg('');
      return true;
    }

    if (form) {
      form.addEventListener('submit', (e) => {
        if (!validatePeriodo()) {
          e.preventDefault();
        }
      });
    }

    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        if (indicadorSelect) indicadorSelect.value = '0';
        if (!validatePeriodo()) return;
        form?.submit();
      });
    }

    clienteSelect?.addEventListener('change', () => {
      if (departamentoSelect) departamentoSelect.value = '0';
      refreshDepartamentos();
      refreshIndicadores();
    });
    departamentoSelect?.addEventListener('change', refreshIndicadores);
  })();
</script>
