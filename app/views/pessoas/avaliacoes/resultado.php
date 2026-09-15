<?php use App\Core\DateHelper; ?>
<?php
/** @var array $avaliacao */
/** @var array $grupos */
/** @var array $resultadoPorGrupo */
/** @var string $classificacaoGeral */
$resultadoGeral = (float)$avaliacao['resultado'];
?>
<div class="p-4 md:p-6 space-y-6 max-w-4xl mx-auto">
  <div class="flex items-center justify-between gap-3">
    <h1 class="text-xl md:text-2xl font-bold text-brand-black">Resultado da Avaliação</h1>
    <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/cicloShow&id=<?= (int)$avaliacao['ciclo_id'] ?>">Voltar ao ciclo</a>
  </div>

  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
      <div><span class="text-gray-500">Colaborador:</span> <span class="font-semibold"><?= htmlspecialchars($avaliacao['colaborador_nome']) ?></span></div>
      <div><span class="text-gray-500">Ciclo:</span> <?= htmlspecialchars($avaliacao['ciclo_nome']) ?></div>
      <div><span class="text-gray-500">Modelo:</span> <?= htmlspecialchars($avaliacao['modelo_nome']) ?></div>
      <div><span class="text-gray-500">Departamento/Setor/Função:</span> <?= htmlspecialchars((string)($avaliacao['departamento'] ?? '')) ?> / <?= htmlspecialchars((string)($avaliacao['setor'] ?? '')) ?> / <?= htmlspecialchars((string)($avaliacao['funcao'] ?? '')) ?></div>
      <div><span class="text-gray-500">Avaliador:</span> <?= htmlspecialchars((string)($avaliacao['avaliador_nome'] ?? '—')) ?></div>
      <div><span class="text-gray-500">Finalizada em:</span> <?= !empty($avaliacao['finalizado_em']) ? htmlspecialchars(DateHelper::formatDateTime((string)$avaliacao['finalizado_em'])) : '—' ?></div>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-6 text-center">
    <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Resultado Geral</div>
    <div class="text-4xl font-bold text-brand-red"><?= number_format($resultadoGeral, 2, ',', '.') ?> <span class="text-lg text-gray-400">/ 5,00</span></div>
    <div class="text-sm font-semibold text-brand-brown mt-2"><?= htmlspecialchars($classificacaoGeral) ?></div>
    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden mt-4 max-w-md mx-auto">
      <div class="bg-brand-red h-3" style="width: <?= min(100, round(($resultadoGeral / 5) * 100)) ?>%"></div>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-6">
    <h2 class="font-semibold mb-4">Resultado por grupo</h2>
    <div class="space-y-3">
      <?php foreach ($resultadoPorGrupo as $g): ?>
        <div>
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="font-medium"><?= htmlspecialchars($g['grupo']) ?></span>
            <span class="text-gray-600"><?= $g['resultado'] !== null ? number_format((float)$g['resultado'], 2, ',', '.') . ' / 5,00 — ' . htmlspecialchars((string)$g['classificacao']) : '—' ?></span>
          </div>
          <?php if ($g['resultado'] !== null): ?>
            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
              <div class="bg-brand-brown h-2" style="width: <?= min(100, round(((float)$g['resultado'] / 5) * 100)) ?>%"></div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="bg-white shadow rounded-xl p-6">
    <h2 class="font-semibold mb-4">Detalhamento das perguntas</h2>
    <?php foreach ($grupos as $grupoNome => $itens): ?>
      <div class="mb-4">
        <div class="text-sm font-semibold text-brand-brown mb-2"><?= htmlspecialchars($grupoNome) ?></div>
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b bg-gray-50 text-xs text-gray-600">
              <th class="p-2">Pergunta</th>
              <th class="p-2 w-16">Peso</th>
              <th class="p-2 w-16">Nota</th>
              <th class="p-2">Observação</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($itens as $item): ?>
              <tr class="border-b align-top">
                <td class="p-2"><?= htmlspecialchars($item['pergunta_snapshot']) ?></td>
                <td class="p-2"><?= htmlspecialchars((string)$item['peso_snapshot']) ?></td>
                <td class="p-2 font-semibold"><?= $item['resposta'] !== null ? (int)$item['resposta'] : '—' ?></td>
                <td class="p-2 text-gray-600"><?= htmlspecialchars((string)($item['observacao'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</div>
