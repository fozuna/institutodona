<?php
/**
 * Campos do cadastro estruturado de Feedback no modelo SBI (Situação,
 * Comportamento, Impacto), do Center for Creative Leadership, com Orientação
 * e Próximo passo como extensões funcionais do Instituto Dona. Compartilhado
 * pelo cadastro standalone (Pessoas -> Feedbacks -> Novo Feedback) e pelo
 * contextual (Central do Colaborador) - mesmo markup, mesma validação no
 * Model, sem duplicar regra.
 *
 * @var string $tipo valor atual (para repopular em erro de validação)
 * @var array $valores titulo, situacao, comportamento, impacto, orientacao, proximo_passo, data_feedback
 */
$v = static fn(string $k): string => htmlspecialchars((string)($valores[$k] ?? ''));
$dataFeedback = $v('data_feedback') !== '' ? $v('data_feedback') : htmlspecialchars(date('Y-m-d'));
?>
<div class="space-y-4">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fbTitulo">Título <span class="text-red-600">*</span></label>
      <input id="fbTitulo" type="text" name="titulo" value="<?= $v('titulo') ?>" maxlength="255" required class="border border-gray-300 rounded-lg p-2 w-full" placeholder="Ex.: Condução da reunião de equipe" />
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fbData">Data do feedback <span class="text-red-600">*</span></label>
      <input id="fbData" type="date" name="data_feedback" value="<?= $dataFeedback ?>" required class="border border-gray-300 rounded-lg p-2 w-full" />
    </div>
  </div>

  <div>
    <span class="block text-sm font-medium text-gray-700 mb-2">Tipo <span class="text-red-600">*</span></span>
    <div class="flex gap-3" data-tipo-group>
      <label class="flex-1 flex items-center gap-2 border rounded-lg p-3 cursor-pointer <?= $tipo === 'positivo' ? 'border-brand-red ring-1 ring-brand-red' : 'border-gray-300' ?>">
        <input type="radio" name="tipo" value="positivo" <?= $tipo === 'positivo' ? 'checked' : '' ?> required />
        <span class="text-sm font-medium text-green-700">Positivo</span>
      </label>
      <label class="flex-1 flex items-center gap-2 border rounded-lg p-3 cursor-pointer <?= $tipo === 'melhoria' ? 'border-brand-red ring-1 ring-brand-red' : 'border-gray-300' ?>">
        <input type="radio" name="tipo" value="melhoria" <?= $tipo === 'melhoria' ? 'checked' : '' ?> required />
        <span class="text-sm font-medium text-amber-700">Melhoria</span>
      </label>
    </div>
  </div>

  <div class="bg-gray-50 rounded-lg p-3 md:p-4 space-y-3">
    <p class="text-xs text-gray-500">Modelo SBI (Situação, Comportamento, Impacto): descreva fatos observáveis, evitando avaliações vagas sobre a personalidade do colaborador.</p>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fbSituacao">Em qual situação ocorreu o fato? <span class="text-red-600">*</span></label>
      <textarea id="fbSituacao" name="situacao" rows="2" maxlength="1000" required class="border border-gray-300 rounded-lg p-2 w-full text-sm" placeholder="Ex.: Na reunião semanal de equipe do dia 03/10, ao apresentar o status do projeto..."><?= $v('situacao') ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fbComportamento">O que o colaborador fez, de forma objetiva? <span class="text-red-600">*</span></label>
      <textarea id="fbComportamento" name="comportamento" rows="2" maxlength="1000" required class="border border-gray-300 rounded-lg p-2 w-full text-sm" placeholder="Ex.: Apresentou os dados com exemplos claros e respondeu a todas as perguntas da equipe."><?= $v('comportamento') ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fbImpacto">Qual foi o impacto desse comportamento? <span class="text-red-600">*</span></label>
      <textarea id="fbImpacto" name="impacto" rows="2" maxlength="1000" required class="border border-gray-300 rounded-lg p-2 w-full text-sm" placeholder="Ex.: A equipe saiu da reunião alinhada, com os próximos passos claros."><?= $v('impacto') ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fbOrientacao">
        <span data-orientacao-texto><?= $tipo === 'melhoria' ? 'O que precisa ser melhorado?' : 'O que deve ser reconhecido e mantido?' ?></span> <span class="text-red-600">*</span>
      </label>
      <textarea id="fbOrientacao" name="orientacao" rows="2" maxlength="1000" required class="border border-gray-300 rounded-lg p-2 w-full text-sm"><?= $v('orientacao') ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1" for="fbProximo">Qual ação ou orientação prática é recomendada? <span class="text-xs text-gray-400">(opcional)</span></label>
      <textarea id="fbProximo" name="proximo_passo" rows="2" maxlength="1000" class="border border-gray-300 rounded-lg p-2 w-full text-sm" placeholder="Ex.: Repetir o formato na próxima apresentação."><?= $v('proximo_passo') ?></textarea>
    </div>
  </div>
</div>
<script>
  (function () {
    document.querySelectorAll('[data-tipo-group]').forEach(function (group) {
      var form = group.closest('form');
      if (!form) { return; }
      var label = form.querySelector('[data-orientacao-texto]');
      group.querySelectorAll('input[type="radio"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
          if (label) {
            label.textContent = radio.value === 'melhoria' ? 'O que precisa ser melhorado?' : 'O que deve ser reconhecido e mantido?';
          }
          group.querySelectorAll('label').forEach(function (l) {
            l.classList.remove('border-brand-red', 'ring-1', 'ring-brand-red');
            l.classList.add('border-gray-300');
          });
          var parentLabel = radio.closest('label');
          if (parentLabel) {
            parentLabel.classList.remove('border-gray-300');
            parentLabel.classList.add('border-brand-red', 'ring-1', 'ring-brand-red');
          }
        });
      });
    });
  })();
</script>
