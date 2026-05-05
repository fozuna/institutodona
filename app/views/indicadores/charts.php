<?php $t = static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace); ?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($t('indicadores.title.charts')) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.chart.limits')) ?> · <?= htmlspecialchars($t('indicadores.chart.current')) ?></p>
    </div>
    <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown text-center" href="index.php?route=indicadores/index<?= $cliente ? '&cliente=' . (int)$cliente : '' ?>">
      <?= htmlspecialchars($t('indicadores.action.back')) ?>
    </a>
  </div>

  <div class="bg-white shadow rounded-xl p-4">
    <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-4">
      <input type="hidden" name="route" value="indicadores/charts" />
      <div class="md:col-span-4">
        <label class="block text-sm font-medium text-gray-700 mb-1"><?= htmlspecialchars($t('indicadores.label.cliente')) ?></label>
        <select name="cliente" class="border border-gray-300 rounded-lg p-3 w-full">
          <option value=""><?= htmlspecialchars($t('indicadores.option.select')) ?></option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2 flex items-end">
        <button class="px-4 py-3 rounded-lg bg-brand-red text-white w-full" type="submit"><?= htmlspecialchars($t('indicadores.action.filter')) ?></button>
      </div>
    </form>
  </div>

  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.client')) ?></div>
  <?php elseif (empty($series)): ?>
    <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.charts')) ?></div>
  <?php else: ?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <?php foreach ($series as $name => $seriesItem): ?>
        <?php $payload = $seriesItem['points']; ?>
        <div class="bg-white shadow rounded-xl p-4">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <div class="font-semibold"><?= htmlspecialchars($name) ?></div>
              <div class="text-xs text-gray-500">
                <?= htmlspecialchars($t('indicadores.label.tendencia')) ?>:
                <?php
                  $trendKey = $seriesItem['trend']['trend'] === 'alta'
                    ? 'indicadores.meta.trend.up'
                    : ($seriesItem['trend']['trend'] === 'queda' ? 'indicadores.meta.trend.down' : 'indicadores.meta.trend.stable');
                ?>
                <?= htmlspecialchars($t($trendKey)) ?>
                ·
                <?= htmlspecialchars($t('indicadores.label.cumprimento')) ?> médio: <?= htmlspecialchars(\App\Core\ValueFormatter::percent($seriesItem['trend']['media_cumprimento'])) ?>
              </div>
            </div>
            <a class="text-brand-pink font-semibold text-sm" href="index.php?route=indicadores/historico&id=<?= (int)$seriesItem['indicador_id'] ?>">
              <?= htmlspecialchars($t('indicadores.action.history')) ?>
            </a>
          </div>
          <canvas width="600" height="240" data-indicador-chart='<?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE)) ?>'></canvas>
        </div>
      <?php endforeach; ?>
    </div>
    <script>
      (function () {
        const nf = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function draw(canvas, points) {
          const ctx = canvas.getContext('2d');
          const width = canvas.width;
          const height = canvas.height;
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
            }
          });

          ctx.fillStyle = '#6b7280';
          ctx.fillText('Meta', width - 88, pad);
          ctx.fillStyle = '#2563eb';
          ctx.fillRect(width - 116, pad - 8, 18, 2);
          ctx.fillStyle = '#6b7280';
          ctx.fillText('Atingido', width - 88, pad + 16);
          ctx.fillStyle = '#dc2626';
          ctx.fillRect(width - 116, pad + 8, 18, 2);
        }

        document.querySelectorAll('canvas[data-indicador-chart]').forEach((canvas) => {
          try {
            draw(canvas, JSON.parse(canvas.getAttribute('data-indicador-chart') || '[]'));
          } catch (error) {
            // no-op
          }
        });
      })();
    </script>
  <?php endif; ?>
</div>
