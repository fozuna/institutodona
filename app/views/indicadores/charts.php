<?php /** @var array $clientes */ /** @var int $cliente */ /** @var array $series */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Gráficos de Indicadores</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=indicadores/index&cliente=<?= (int)$cliente ?>">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4 mb-4">
    <form method="get" action="index.php" class="flex items-end gap-3">
      <input type="hidden" name="route" value="indicadores/charts" />
      <div class="flex-1">
        <label class="text-sm">Cliente</label>
        <select name="cliente" class="border rounded p-2 w-full">
          <option value="">— selecione —</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cliente === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="px-4 py-2 rounded bg-brand-red text-white">Filtrar</button>
    </form>
  </div>
  <?php if (!$cliente): ?>
    <div class="bg-white shadow rounded p-4">Selecione um cliente para visualizar os gráficos.</div>
  <?php elseif (empty($series)): ?>
    <div class="bg-white shadow rounded p-4">Nenhum indicador encontrado para o cliente.</div>
  <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <?php foreach ($series as $name => $points): ?>
        <div class="bg-white shadow rounded p-4">
          <div class="font-semibold mb-2"><?= htmlspecialchars($name) ?></div>
          <canvas width="600" height="300" data-points='<?= htmlspecialchars(json_encode($points, JSON_UNESCAPED_UNICODE)) ?>'></canvas>
          <div class="text-xs text-gray-600 mt-2">Linhas: Meta (azul) · Realizado (laranja)</div>
        </div>
      <?php endforeach; ?>
    </div>
    <script>
      (function(){
        function drawChart(canvas, points){
          const ctx = canvas.getContext('2d');
          const W = canvas.width, H = canvas.height;
          ctx.clearRect(0,0,W,H);
          const pad = 40;
          const xs = points.map(p=>p.month || 0).filter(m=>m>0);
          const metas = points.map(p=>p.meta||0);
          const reals = points.map(p=>p.realizado||0);
          const diffs = points.map((p,i)=> (reals[i]-metas[i]) );
          const maxVal = Math.max( ...metas, ...reals, 1 );
          // axes
          ctx.strokeStyle = '#ccc';
          ctx.beginPath();
          ctx.moveTo(pad, pad);
          ctx.lineTo(pad, H-pad);
          ctx.lineTo(W-pad, H-pad);
          ctx.stroke();
          const n = points.length;
          function x(i){ return pad + ((W-2*pad) * (i/(Math.max(n-1,1)))); }
          function y(v){ return H-pad - ((H-2*pad) * (v / maxVal)); }
          const barWidth = (W-2*pad) / Math.max(n,1) * 0.4;
          // grid and labels
          ctx.fillStyle = '#666';
          ctx.font = '10px sans-serif';
          for(let i=0;i<n;i++){
            const m = points[i].month || (i+1);
            const xi = x(i);
            ctx.fillText(String(m), xi-3, H-pad+12);
          }
          ctx.fillText(String(maxVal.toFixed(2)), 5, y(maxVal)+3);
          ctx.fillText('0', 15, H-pad+3);
          // diff bars (green for positive, red for negative)
          for(let i=0;i<n;i++){
            const d = diffs[i];
            const xi = x(i) - barWidth/2;
            const yi = y(Math.max(0, d));
            const y0 = y(0);
            const height = Math.abs(y0 - y(d));
            ctx.fillStyle = d >= 0 ? 'rgba(16,185,129,0.35)' : 'rgba(239,68,68,0.35)';
            ctx.fillRect(xi, d>=0 ? yi : y0, barWidth, height);
          }
          // meta line (blue)
          ctx.strokeStyle = '#1e3a8a';
          ctx.beginPath();
          ctx.moveTo(x(0), y(metas[0]));
          for(let i=1;i<n;i++){ ctx.lineTo(x(i), y(metas[i])); }
          ctx.stroke();
          // realizado line (orange)
          ctx.strokeStyle = '#fb923c';
          ctx.beginPath();
          ctx.moveTo(x(0), y(reals[0]));
          for(let i=1;i<n;i++){ ctx.lineTo(x(i), y(reals[i])); }
          ctx.stroke();
        }
        document.querySelectorAll('canvas[data-points]').forEach(cv=>{
          try {
            const pts = JSON.parse(cv.getAttribute('data-points')||'[]');
            if (pts.length) drawChart(cv, pts);
          } catch (e) {}
        });
      })();
    </script>
  <?php endif; ?>
</div>
