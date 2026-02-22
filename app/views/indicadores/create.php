<?php /** @var array $clientes */ ?>
<div class="p-6 max-w-2xl">
  <h1 class="text-2xl font-bold mb-4">Novo Indicador</h1>
  <form method="post" action="index.php?route=indicadores/store" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div>
      <label class="block text-sm">Cliente</label>
      <select name="cliente_id" required>
        <?php foreach ($clientes as $cli): ?>
          <option value="<?= (int)$cli['id'] ?>"><?= htmlspecialchars($cli['nome_empresa']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Indicador</label>
      <input type="text" name="nome" required />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
      <div>
        <label class="block text-sm">Referência (data)</label>
        <input type="date" name="referencia" />
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm">Meta R$</label>
        <input type="text" name="meta" placeholder="R$ 0,00" data-money required />
      </div>
    </div>
    <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
  </form>
  <script>
    (function(){
      function fmt(i){
        let v = i.value.replace(/\D/g,'');
        if (!v) { i.value = ''; return; }
        let n = parseInt(v,10);
        i.value = 'R$ ' + (n/100).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
      }
      function norm(s){
        s = String(s||'').replace(/[^\d,.-]/g,'').replace(/\./g,'').replace(',', '.');
        let n = parseFloat(s);
        if (isNaN(n) || n < 0) n = 0;
        return String(n);
      }
      document.querySelectorAll('input[data-money]').forEach(i=>{
        i.addEventListener('input', ()=>fmt(i));
        if (i.value) fmt(i);
      });
      const form = document.querySelector('form[action*="indicadores/store"]');
      if (form) {
        form.addEventListener('submit', ()=>{
          form.querySelectorAll('input[data-money]').forEach(i=>{ i.value = norm(i.value); });
        });
      }
    })();
  </script>
</div>
