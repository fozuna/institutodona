<?php use App\Core\Security; /** @var array $item */ /** @var array $respostas */ ?>
<div class="p-6 max-w-6xl">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-bold">Editar Observações (Auditoria Realizada)</h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($item['nome_auditoria'] ?? '') ?> · <?= htmlspecialchars($item['cliente_nome'] ?? '') ?> · <?= htmlspecialchars($item['setor_nome'] ?? '') ?></p>
    </div>
    <div class="flex items-center gap-2">
      <a href="index.php?route=auditorias/show&id=<?= (int)$item['id'] ?>" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Voltar</a>
      <button type="submit" form="editarRealizadaForm" class="px-4 py-2 rounded bg-brand-red text-white">Salvar</button>
    </div>
  </div>
  <form method="post" action="index.php?route=auditorias/atualizar_observacoes" id="editarRealizadaForm" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
    <input type="hidden" name="observacoes_json" id="observacoesJson" value="[]" />
    <?php foreach (($item['questoes'] ?? []) as $idx => $questao): ?>
      <?php $resp = $respostas[(int)$questao['id']] ?? []; ?>
      <div class="bg-white rounded shadow p-4">
        <div class="text-sm text-gray-500 mb-1">Questão <?= $idx + 1 ?></div>
        <div class="font-semibold mb-2"><?= nl2br(htmlspecialchars((string)$questao['pergunta'])) ?></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm">Conformidade</label>
            <div class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-sm inline-block"><?= htmlspecialchars((string)($resp['conformidade'] ?? '-')) ?></div>
          </div>
          <div>
            <label class="block text-sm">Observações</label>
            <textarea class="border rounded p-2 w-full obs-field" rows="3" maxlength="2000" data-qid="<?= (int)$questao['id'] ?>"><?= htmlspecialchars((string)($resp['observacoes'] ?? '')) ?></textarea>
            <div class="text-xs text-gray-500 text-right"><span class="count" data-qid="<?= (int)$questao['id'] ?>"><?= strlen((string)($resp['observacoes'] ?? '')) ?></span>/2000</div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="flex items-center justify-end gap-2">
      <a href="index.php?route=auditorias/show&id=<?= (int)$item['id'] ?>" class="px-4 py-2 rounded bg-gray-200 text-brand-brown">Cancelar</a>
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Salvar</button>
    </div>
  </form>
</div>
<script>
  (function(){
    const form = document.getElementById('editarRealizadaForm');
    const out = document.getElementById('observacoesJson');
    const collect = ()=>{
      const arr = [];
      document.querySelectorAll('.obs-field').forEach((el)=>{
        const qid = Number(el.getAttribute('data-qid'));
        arr.push({ questao_id: qid, observacoes: (el.value || '').trim() });
      });
      return arr;
    };
    form.addEventListener('submit', (e)=>{
      out.value = JSON.stringify(collect());
    });
    document.querySelectorAll('.obs-field').forEach((el)=>{
      el.addEventListener('input', ()=>{
        const qid = el.getAttribute('data-qid');
        const c = document.querySelector(`.count[data-qid="${qid}"]`);
        if (c) c.textContent = String((el.value || '').length);
      });
    });
  })();
</script>
