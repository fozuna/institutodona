<?php /** @var array $dashboard */ ?>
<div class="p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($pageTitle ?? 'Dashboard de Treinamentos') ?></h1>
      <p class="text-sm text-gray-600">Visão geral de pendências, concluídos, alertas e percentuais por dimensão.</p>
    </div>
    <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/index">Voltar</a>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">% por Treinamento</h2>
      <div class="space-y-3 text-sm">
        <?php foreach ($dashboard['por_treinamento'] ?? [] as $row): ?>
          <div>
            <div class="flex justify-between"><span><?= htmlspecialchars((string)$row['group_label']) ?></span><span><?= htmlspecialchars((string)$row['percentual']) ?>%</span></div>
            <div class="h-2 bg-gray-100 rounded mt-1"><div class="h-2 rounded bg-brand-red" style="width: <?= max(0, min(100, (float)$row['percentual'])) ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">% por Função</h2>
      <div class="space-y-3 text-sm">
        <?php foreach ($dashboard['por_funcao'] ?? [] as $row): ?>
          <div>
            <div class="flex justify-between"><span><?= htmlspecialchars((string)($row['group_label'] ?: 'Sem função')) ?></span><span><?= htmlspecialchars((string)$row['percentual']) ?>%</span></div>
            <div class="h-2 bg-gray-100 rounded mt-1"><div class="h-2 rounded bg-blue-500" style="width: <?= max(0, min(100, (float)$row['percentual'])) ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="bg-white shadow rounded p-5">
      <h2 class="font-semibold mb-3">% por Unidade</h2>
      <div class="space-y-3 text-sm">
        <?php foreach ($dashboard['por_unidade'] ?? [] as $row): ?>
          <div>
            <div class="flex justify-between"><span><?= htmlspecialchars((string)($row['group_label'] ?: 'Sem unidade')) ?></span><span><?= htmlspecialchars((string)$row['percentual']) ?>%</span></div>
            <div class="h-2 bg-gray-100 rounded mt-1"><div class="h-2 rounded bg-amber-500" style="width: <?= max(0, min(100, (float)$row['percentual'])) ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
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
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="bg-white shadow rounded p-5">
    <h2 class="font-semibold mb-3">Alertas de Vencimento</h2>
    <?php if (empty($dashboard['alertas'])): ?>
      <div class="text-sm text-gray-500">Nenhum alerta ativo.</div>
    <?php else: ?>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="border-b text-left"><th class="p-2">Treinamento</th><th class="p-2">Colaborador</th><th class="p-2">Última Conclusão</th><th class="p-2">Situação</th></tr></thead>
          <tbody>
            <?php foreach ($dashboard['alertas'] as $row): ?>
              <tr class="border-b">
                <td class="p-2"><?= htmlspecialchars($row['treinamento_nome']) ?></td>
                <td class="p-2"><?= htmlspecialchars($row['colaborador_nome']) ?></td>
                <td class="p-2"><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($row['ultima_conclusao'] ?? ''))) ?: '—' ?></td>
                <td class="p-2"><?= htmlspecialchars(($row['alerta'] ?? '') === 'vencido' ? 'Vencido' : ('Próximo do vencimento em ' . (int)($row['dias_restantes'] ?? 0) . ' dia(s)')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
