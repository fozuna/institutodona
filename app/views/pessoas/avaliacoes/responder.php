<?php
/** @var array $avaliacao */
/** @var array $grupos */
/** @var array $escala */
?>
<div class="p-4 md:p-6 space-y-6 max-w-4xl mx-auto">
  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold text-brand-black"><?= htmlspecialchars($avaliacao['colaborador_nome']) ?></h1>
    <div class="text-sm text-gray-600 mt-1">
      <?= htmlspecialchars($avaliacao['ciclo_nome']) ?> · <?= htmlspecialchars($avaliacao['modelo_nome']) ?>
    </div>
    <div class="text-xs text-gray-500 mt-1">
      <?= htmlspecialchars((string)($avaliacao['departamento'] ?? '')) ?> / <?= htmlspecialchars((string)($avaliacao['setor'] ?? '')) ?> / <?= htmlspecialchars((string)($avaliacao['funcao'] ?? '')) ?>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <div class="bg-white shadow rounded-xl p-4 text-xs text-gray-600">
    <span class="font-semibold">Escala:</span>
    <?php foreach ($escala as $nota => $label): ?>
      <span class="inline-block mr-3"><strong><?= (int)$nota ?></strong> — <?= htmlspecialchars($label) ?></span>
    <?php endforeach; ?>
  </div>

  <form method="post" action="index.php?route=pessoas/avaliacaoSalvar" id="pessoasAvaliacaoForm">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <input type="hidden" name="id" value="<?= (int)$avaliacao['id'] ?>" />

    <?php foreach ($grupos as $grupoNome => $itens): ?>
      <div class="bg-white shadow rounded-xl p-4 md:p-6 mb-4">
        <h2 class="font-semibold text-brand-brown mb-4"><?= htmlspecialchars($grupoNome) ?></h2>
        <div class="space-y-5">
          <?php foreach ($itens as $item): ?>
            <div class="border-b pb-4 last:border-b-0 last:pb-0">
              <div class="text-sm font-medium mb-1">
                <?= htmlspecialchars($item['pergunta_snapshot']) ?>
                <?= (int)$item['obrigatoria_snapshot'] === 1 ? '<span class="text-brand-red">*</span>' : '<span class="text-xs text-gray-400">(opcional)</span>' ?>
              </div>
              <div class="flex gap-3 mb-2">
                <?php foreach ($escala as $nota => $label): ?>
                  <label class="flex flex-col items-center gap-1 cursor-pointer">
                    <input type="radio" name="respostas[<?= (int)$item['id'] ?>]" value="<?= (int)$nota ?>" <?= (int)($item['resposta'] ?? 0) === (int)$nota ? 'checked' : '' ?> class="h-5 w-5 text-brand-red" title="<?= htmlspecialchars($label) ?>" />
                    <span class="text-xs text-gray-500"><?= (int)$nota ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <textarea name="observacoes[<?= (int)$item['id'] ?>]" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Observação (opcional)" maxlength="1000"><?= htmlspecialchars((string)($item['observacao'] ?? '')) ?></textarea>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="bg-white shadow rounded-xl p-4 flex flex-col sm:flex-row gap-2">
      <button type="submit" class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown">Salvar e continuar depois</button>
      <button type="submit" formaction="index.php?route=pessoas/avaliacaoFinalizar" formnovalidate onclick="return confirm('Finalizar a avaliação? Depois de finalizada, as respostas não podem mais ser editadas.');" class="px-4 py-3 rounded-lg bg-brand-red text-white">Finalizar Avaliação</button>
    </div>
  </form>
</div>
