<?php use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $colaborador */ /** @var int $gapId */ /** @var array $gapsDisponiveis */ /** @var string $tituloSugerido */ /** @var ?array $pdiAtivo */
$csrf = \App\Core\Security::csrfToken();
$gapStatusLabels = PessoasGestaoConfig::gapStatusLabels();
?>
<div class="p-4 md:p-6 space-y-6 max-w-3xl mx-auto">
  <div>
    <h1 class="text-xl md:text-2xl font-bold text-brand-black">Novo PDI</h1>
    <p class="text-sm text-gray-600"><?= htmlspecialchars($colaborador['nome']) ?></p>
  </div>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>
  <?php if (!empty($pdiAtivo)): ?>
    <div class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      Este colaborador já possui um PDI ativo (<a class="underline" href="index.php?route=pessoas/pdiShow&id=<?= (int)$pdiAtivo['id'] ?>"><?= htmlspecialchars($pdiAtivo['titulo']) ?></a>). Você pode criar este PDI como rascunho, mas só poderá ativá-lo depois que o atual for concluído ou cancelado.
    </div>
  <?php endif; ?>
  <form method="post" action="index.php?route=pessoas/pdiStore" class="bg-white shadow rounded-xl p-4 md:p-6 space-y-4">
    <input type="hidden" name="csrf" value="<?= $csrf ?>" />
    <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
      <input type="text" name="titulo" value="<?= htmlspecialchars($tituloSugerido) ?>" maxlength="255" required class="border border-gray-300 rounded-lg p-3 w-full" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
      <textarea name="descricao" rows="3" maxlength="2000" class="border border-gray-300 rounded-lg p-3 w-full"></textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Data de início</label>
        <input type="date" name="data_inicio" value="<?= htmlspecialchars(date('Y-m-d')) ?>" class="border border-gray-300 rounded-lg p-3 w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fim previsto</label>
        <input type="date" name="data_fim_prevista" class="border border-gray-300 rounded-lg p-3 w-full" />
      </div>
    </div>
    <?php if (!empty($gapsDisponiveis)): ?>
      <fieldset>
        <legend class="block text-sm font-medium text-gray-700 mb-1">GAPs a incluir (opcional)</legend>
        <div class="space-y-1">
          <?php foreach ($gapsDisponiveis as $g): ?>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" name="gap_ids[]" value="<?= (int)$g['id'] ?>" <?= $gapId === (int)$g['id'] ? 'checked' : '' ?> />
              <?= htmlspecialchars($g['titulo']) ?> <span class="text-xs text-gray-500">(<?= htmlspecialchars($gapStatusLabels[$g['status']] ?? $g['status']) ?>)</span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endif; ?>
    <div class="flex gap-2">
      <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white">Criar PDI (rascunho)</button>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/colaboradorHistorico&id=<?= (int)$colaborador['id'] ?>">Cancelar</a>
    </div>
  </form>
</div>
