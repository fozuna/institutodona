<?php /** @var array $dashboard */ /** @var array $filters */ /** @var array $setores */ /** @var array $tipoTreinamentoOptions */ ?>
<div class="p-6 space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Dashboard de Treinamentos') ?></h1>
      <p class="text-sm text-gray-600">Percentuais de participação, totalizadores por setor, acumuladores e alertas automáticos.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/index">Voltar</a>
      <a class="px-4 py-2 rounded bg-red-100 text-red-700" href="index.php?route=treinamentos/dashboard_pdf&periodo_inicio=<?= urlencode((string)($filters['periodo_inicio'] ?? '')) ?>&periodo_fim=<?= urlencode((string)($filters['periodo_fim'] ?? '')) ?>&setor_id=<?= (int)($filters['setor_id'] ?? 0) ?>&tipo_treinamento=<?= urlencode((string)($filters['tipo_treinamento'] ?? '')) ?>&instrutor=<?= urlencode((string)($filters['instrutor'] ?? '')) ?>">Exportar PDF</a>
    </div>
  </div>

  <form method="get" action="index.php" class="bg-white shadow rounded p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
    <input type="hidden" name="route" value="treinamentos/dashboard" />
    <div>
      <label class="block text-sm">Período Inicial</label>
      <input type="date" name="periodo_inicio" value="<?= htmlspecialchars((string)($filters['periodo_inicio'] ?? '')) ?>" class="border rounded p-2 w-full" />
    </div>
    <div>
      <label class="block text-sm">Período Final</label>
      <input type="date" name="periodo_fim" value="<?= htmlspecialchars((string)($filters['periodo_fim'] ?? '')) ?>" class="border rounded p-2 w-full" />
    </div>
    <div>
      <label class="block text-sm">Setor</label>
      <select name="setor_id" class="border rounded p-2 w-full">
        <option value="0">Todos</option>
        <?php foreach ($setores as $setor): ?>
          <option value="<?= (int)$setor['id'] ?>" <?= ((int)($filters['setor_id'] ?? 0) === (int)$setor['id']) ? 'selected' : '' ?>><?= htmlspecialchars($setor['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Tipo de Treinamento</label>
      <select name="tipo_treinamento" class="border rounded p-2 w-full">
        <option value="">Todos</option>
        <?php foreach ($tipoTreinamentoOptions as $tipo): ?>
          <option value="<?= htmlspecialchars($tipo) ?>" <?= (($filters['tipo_treinamento'] ?? '') === $tipo) ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Instrutor</label>
      <input type="text" name="instrutor" value="<?= htmlspecialchars((string)($filters['instrutor'] ?? '')) ?>" class="border rounded p-2 w-full" placeholder="Buscar instrutor" />
    </div>
    <div class="md:col-span-5 flex flex-wrap gap-2">
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Aplicar Filtros</button>
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/dashboard">Limpar</a>
    </div>
  </form>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Treinamentos</div><div class="text-3xl font-bold"><?= (int)($dashboard['resumo']['treinamentos_monitorados'] ?? 0) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Inscritos</div><div class="text-3xl font-bold"><?= (int)($dashboard['resumo']['total_inscritos'] ?? 0) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Presentes</div><div class="text-3xl font-bold"><?= (int)($dashboard['resumo']['total_presentes'] ?? 0) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Certificados</div><div class="text-3xl font-bold"><?= (int)($dashboard['resumo']['total_certificados'] ?? 0) ?></div></div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Gráfico de Pizza por Participação</h2>
      <canvas id="treinamentosPizzaChart" height="260" data-chart='<?= htmlspecialchars(json_encode($dashboard['participacao_treinamento'] ?? [], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
    </div>
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Gráfico de Barras por Setor</h2>
      <canvas id="treinamentosBarChart" height="260" data-chart='<?= htmlspecialchars(json_encode($dashboard['setores'] ?? [], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Participação por Treinamento</h2>
      <div class="space-y-4">
        <?php foreach ($dashboard['participacao_treinamento'] ?? [] as $row): ?>
          <div>
            <div class="flex justify-between text-sm">
              <span><?= htmlspecialchars((string)$row['treinamento_nome']) ?></span>
              <span><?= number_format((float)$row['percentual_participacao'], 2, ',', '.') ?>%</span>
            </div>
            <div class="h-3 bg-gray-100 rounded mt-1 overflow-hidden">
              <div class="h-3 rounded <?= (float)$row['percentual_participacao'] >= 80 ? 'bg-green-500' : ((float)$row['percentual_participacao'] >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= max(0, min(100, (float)$row['percentual_participacao'])) ?>%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1"><?= (int)($row['total_presentes'] ?? 0) ?> presentes / <?= (int)($row['total_inscritos'] ?? 0) ?> inscritos • <?= htmlspecialchars((string)($row['instrutor'] ?? 'Não informado')) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($dashboard['participacao_treinamento'])): ?>
          <div class="text-sm text-gray-500">Nenhum dado de participação encontrado.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Comparativo por Setor</h2>
      <div class="space-y-4">
        <?php foreach ($dashboard['setores'] ?? [] as $row): ?>
          <div>
            <div class="flex justify-between text-sm">
              <span><?= htmlspecialchars((string)$row['setor_nome']) ?></span>
              <span><?= number_format((float)$row['percentual_participacao'], 2, ',', '.') ?>%</span>
            </div>
            <div class="h-3 bg-gray-100 rounded mt-1 overflow-hidden">
              <div class="h-3 rounded <?= (float)$row['percentual_participacao'] >= 80 ? 'bg-green-500' : ((float)$row['percentual_participacao'] >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width: <?= max(0, min(100, (float)$row['percentual_participacao'])) ?>%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1"><?= (int)($row['total_treinados'] ?? 0) ?> treinados • <?= number_format((float)($row['total_horas_treinamento'] ?? 0), 2, ',', '.') ?>h • média <?= number_format((float)($row['media_horas_por_colaborador'] ?? 0), 2, ',', '.') ?>h</div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($dashboard['setores'])): ?>
          <div class="text-sm text-gray-500">Nenhum totalizador por setor disponível.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Acumuladores Mensais / Trimestrais / Anuais</h2>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="border-b text-left"><th class="p-2">Mês</th><th class="p-2">Trimestre</th><th class="p-2">Ano</th><th class="p-2">Inscritos</th><th class="p-2">Presentes</th><th class="p-2">Horas</th></tr></thead>
          <tbody>
            <?php foreach ($dashboard['acumulados'] ?? [] as $row): ?>
              <tr class="border-b">
                <td class="p-2"><?= htmlspecialchars((string)($row['mensal'] ?? '—')) ?></td>
                <td class="p-2"><?= htmlspecialchars((string)($row['trimestral'] ?? '—')) ?></td>
                <td class="p-2"><?= htmlspecialchars((string)($row['anual'] ?? '—')) ?></td>
                <td class="p-2"><?= (int)($row['total_inscritos'] ?? 0) ?></td>
                <td class="p-2"><?= (int)($row['total_presentes'] ?? 0) ?></td>
                <td class="p-2"><?= number_format((float)($row['total_horas'] ?? 0), 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($dashboard['acumulados'])): ?>
              <tr><td colspan="6" class="p-4 text-center text-gray-500">Nenhum acumulador encontrado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Alertas Automáticos por Setor</h2>
      <?php if (empty($dashboard['alertas_setor'])): ?>
        <div class="text-sm text-gray-500">Nenhum setor com baixo índice de participação no filtro atual.</div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($dashboard['alertas_setor'] as $row): ?>
            <div class="rounded border border-red-200 bg-red-50 p-3">
              <div class="font-medium"><?= htmlspecialchars((string)$row['setor_nome']) ?></div>
              <div class="text-sm text-gray-700">Participação de <?= number_format((float)$row['percentual_participacao'], 2, ',', '.') ?>%</div>
              <div class="text-xs text-gray-600 mt-1">Total de colaboradores: <?= (int)($row['total_colaboradores_setor'] ?? 0) ?> • treinados: <?= (int)($row['total_treinados'] ?? 0) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Pendentes</h2>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="border-b text-left"><th class="p-2">Treinamento</th><th class="p-2">Colaborador</th><th class="p-2">Unidade</th></tr></thead>
          <tbody>
            <?php foreach ($dashboard['pendentes'] ?? [] as $row): ?>
              <tr class="border-b"><td class="p-2"><?= htmlspecialchars($row['treinamento_nome']) ?></td><td class="p-2"><?= htmlspecialchars($row['colaborador_nome']) ?></td><td class="p-2"><?= htmlspecialchars((string)$row['unidade_nome']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($dashboard['pendentes'])): ?>
              <tr><td colspan="3" class="p-4 text-center text-gray-500">Nenhuma pendência ativa.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">Concluídos</h2>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="border-b text-left"><th class="p-2">Treinamento</th><th class="p-2">Colaborador</th><th class="p-2">Unidade</th></tr></thead>
          <tbody>
            <?php foreach ($dashboard['concluidos'] ?? [] as $row): ?>
              <tr class="border-b"><td class="p-2"><?= htmlspecialchars($row['treinamento_nome']) ?></td><td class="p-2"><?= htmlspecialchars($row['colaborador_nome']) ?></td><td class="p-2"><?= htmlspecialchars((string)$row['unidade_nome']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($dashboard['concluidos'])): ?>
              <tr><td colspan="3" class="p-4 text-center text-gray-500">Nenhuma conclusão registrada.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  (function () {
    const pieCanvas = document.getElementById('treinamentosPizzaChart');
    const barCanvas = document.getElementById('treinamentosBarChart');

    function prepareCanvas(canvas, height) {
      if (!canvas) return;
      const ratio = window.devicePixelRatio || 1;
      const width = Math.max(320, canvas.parentElement ? canvas.parentElement.clientWidth - 24 : 520);
      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      canvas.width = Math.round(width * ratio);
      canvas.height = Math.round(height * ratio);
      const ctx = canvas.getContext('2d');
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      return ctx;
    }

    function drawPie(canvas, rows) {
      if (!canvas || !rows.length) return;
      const ctx = prepareCanvas(canvas, 320);
      if (!ctx) return;
      const total = rows.reduce((sum, row) => sum + Number(row.total_presentes || 0), 0);
      const colors = ['#dc2626', '#2563eb', '#16a34a', '#d97706', '#7c3aed', '#0891b2'];
      let start = -Math.PI / 2;
      const logicalWidth = canvas.width / (window.devicePixelRatio || 1);
      const cx = logicalWidth / 2;
      const cy = 120;
      const radius = 88;
      rows.slice(0, 6).forEach((row, index) => {
        const value = Number(row.total_presentes || 0);
        const angle = total > 0 ? (value / total) * Math.PI * 2 : 0;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, radius, start, start + angle);
        ctx.closePath();
        ctx.fillStyle = colors[index % colors.length];
        ctx.fill();
        start += angle;
      });
      ctx.font = '12px sans-serif';
      rows.slice(0, 6).forEach((row, index) => {
        ctx.fillStyle = colors[index % colors.length];
        ctx.fillRect(24, 220 + (index * 18), 12, 12);
        ctx.fillStyle = '#374151';
        ctx.fillText(String(row.treinamento_nome || '').slice(0, 36), 44, 230 + (index * 18));
      });
    }

    function drawBars(canvas, rows) {
      if (!canvas || !rows.length) return;
      const ctx = prepareCanvas(canvas, 320);
      if (!ctx) return;
      const max = Math.max(1, ...rows.map((row) => Number(row.percentual_participacao || 0)));
      const baseY = 210;
      const left = 30;
      const width = 42;
      const gap = 22;
      rows.slice(0, 6).forEach((row, index) => {
        const x = left + index * (width + gap);
        const value = Number(row.percentual_participacao || 0);
        const barHeight = (value / max) * 150;
        ctx.fillStyle = value >= 80 ? '#16a34a' : (value >= 50 ? '#d97706' : '#dc2626');
        ctx.fillRect(x, baseY - barHeight, width, barHeight);
        ctx.fillStyle = '#374151';
        ctx.font = '11px sans-serif';
        ctx.fillText(String(Math.round(value)) + '%', x, baseY - barHeight - 6);
        ctx.save();
        ctx.translate(x + 4, baseY + 14);
        ctx.rotate(-0.35);
        ctx.fillText(String(row.setor_nome || '').slice(0, 18), 0, 0);
        ctx.restore();
      });
    }

    try {
      if (pieCanvas) drawPie(pieCanvas, JSON.parse(pieCanvas.dataset.chart || '[]'));
      if (barCanvas) drawBars(barCanvas, JSON.parse(barCanvas.dataset.chart || '[]'));
    } catch (error) {
      // no-op
    }
  })();
</script>
