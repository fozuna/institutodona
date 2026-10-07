<?php use App\Core\DateHelper; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $feedback */
/** @var array $colaborador */
$tipoLabels = PessoasGestaoConfig::feedbackTipoLabels();
$tipoClasses = PessoasGestaoConfig::feedbackTipoClasses();
?>
<div class="p-4 md:p-6 space-y-6 max-w-3xl mx-auto">
  <?php $pessoasSubnavAtivo = 'feedbacks'; require __DIR__ . '/../_subnav.php'; ?>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-brand-black"><?= htmlspecialchars($feedback['titulo']) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($colaborador['nome']) ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/feedbacks">Voltar à listagem</a>
      <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>">Central do Colaborador</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <div class="bg-white shadow rounded-xl p-4 md:p-6 space-y-4">
    <div class="flex flex-wrap items-center gap-2">
      <span class="px-2 py-1 rounded text-xs <?= $tipoClasses[$feedback['tipo']] ?? '' ?>"><?= htmlspecialchars($tipoLabels[$feedback['tipo']] ?? $feedback['tipo']) ?></span>
      <span class="text-xs text-gray-500">Registrado em <?= htmlspecialchars(DateHelper::formatDate((string)$feedback['data_feedback'])) ?></span>
      <?php if (!empty($feedback['registrado_por_nome'])): ?><span class="text-xs text-gray-500">· por <?= htmlspecialchars($feedback['registrado_por_nome']) ?></span><?php endif; ?>
    </div>

    <p class="text-sm text-gray-800 whitespace-pre-line"><?= htmlspecialchars($feedback['descricao']) ?></p>

    <?php if (!empty($feedback['gap_id']) || !empty($feedback['avaliacao_id'])): ?>
      <div class="pt-3 border-t space-y-1 text-sm">
        <?php if (!empty($feedback['gap_id'])): ?>
          <div>GAP relacionado: <a class="text-brand-pink hover:underline" href="index.php?route=pessoas/gapShow&id=<?= (int)$feedback['gap_id'] ?>"><?= htmlspecialchars((string)$feedback['gap_titulo']) ?></a></div>
        <?php endif; ?>
        <?php if (!empty($feedback['avaliacao_id'])): ?>
          <div>
            Avaliação relacionada<?= !empty($feedback['ciclo_nome']) ? ' — ' . htmlspecialchars($feedback['ciclo_nome']) : '' ?>
            <?php if (($feedback['avaliacao_status'] ?? '') === 'finalizada'): ?>
              · <a class="text-brand-pink hover:underline" href="index.php?route=pessoas/avaliacaoResultado&id=<?= (int)$feedback['avaliacao_id'] ?>">ver resultado</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
