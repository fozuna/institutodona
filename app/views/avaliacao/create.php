<?php /** @var array $clientes */ ?>
<?php use App\Core\Security; ?>
<div class="p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold text-brand-brown">Avaliação de Gestão</h1>
    <a class="px-3 py-2 rounded bg-brand-pink text-white" href="index.php?route=dashboard/index">Voltar</a>
  </div>
  <form method="post" action="index.php?route=avaliacao/store" class="space-y-6">
    <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />

    <div class="bg-white border rounded p-4">
      <div class="font-semibold mb-3 text-brand-brown">Cliente</div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm mb-1">Modo</label>
          <div class="flex items-center gap-6">
            <label class="flex items-center gap-2"><input type="radio" name="cliente_mode" value="existing" checked /> <span>Existente</span></label>
            <label class="flex items-center gap-2"><input type="radio" name="cliente_mode" value="new" /> <span>Novo prospect</span></label>
          </div>
        </div>
        <div id="existingBox">
          <label class="block text-sm mb-1">Selecionar cliente</label>
          <select name="cliente_id" class="border rounded p-2 w-full">
            <option value="">-- Selecione --</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="newBox" class="col-span-2 hidden">
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm">Nome da Empresa</label>
              <input name="novo_nome_empresa" class="border rounded p-2 w-full" />
            </div>
            <div>
              <label class="block text-sm">CNPJ</label>
              <input name="novo_cnpj" class="border rounded p-2 w-full" />
            </div>
            <div>
              <label class="block text-sm">Contato</label>
              <input name="novo_contato" class="border rounded p-2 w-full" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-4 gap-4">
      <div class="bg-white border rounded p-4">
        <div class="font-semibold mb-2">Financeiro</div>
        <div class="space-y-2">
          <?php $finPerg = ['Planejamento estratégico?','Quadro de indicadores estratégicos?','Indicadores de desempenho por área?','Informações gerenciais com fácil acesso/interpret.?','Reuniões de alinhamento estratégico?','Gestão à vista?','Registro dos planos de ação de gestão?']; foreach ($finPerg as $idx=>$q): ?>
            <div class="grid grid-cols-2 gap-3 items-center">
              <div class="text-sm"><?= htmlspecialchars($q) ?></div>
              <select name="fin[<?= $idx ?>]" class="border rounded p-2">
                <option value="0">Não</option>
                <option value="1">Sim</option>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="bg-white border rounded p-4">
        <div class="font-semibold mb-2">Mercado</div>
        <div class="space-y-2">
          <?php $merPerg = ['Missão, Visão e Valores?','Processo comercial ativo e controlado?','Relacionamento com fornecedores é saudável?','Pesquisa de satisfação clientes/pós-vendas?','Canal de sugestões/fale conosco?','Análise de concorrência/mercado?','Práticas ambientais?']; foreach ($merPerg as $idx=>$q): ?>
            <div class="grid grid-cols-2 gap-3 items-center">
              <div class="text-sm"><?= htmlspecialchars($q) ?></div>
              <select name="mer[<?= $idx ?>]" class="border rounded p-2">
                <option value="0">Não</option>
                <option value="1">Sim</option>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="bg-white border rounded p-4">
        <div class="font-semibold mb-2">Pessoas</div>
        <div class="space-y-2">
          <?php $pesPerg = ['Pesquisa de clima organizacional?','Seleção adequada com teste de perfil?','Integração (onboarding) de novos?','Avaliação de Gaps e feedback?','Plano de carreira? organograma?','Desenvolvimento de competências/treinamentos?','PRG implementado (levantamento psicossocial)?']; foreach ($pesPerg as $idx=>$q): ?>
            <div class="grid grid-cols-2 gap-3 items-center">
              <div class="text-sm"><?= htmlspecialchars($q) ?></div>
              <select name="pes[<?= $idx ?>]" class="border rounded p-2">
                <option value="0">Não</option>
                <option value="1">Sim</option>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="bg-white border rounded p-4">
        <div class="font-semibold mb-2">Processo</div>
        <div class="space-y-2">
          <?php $proPerg = ['Manual de processos?','Treinamento/reciclagem de equipe (não manuais)?','Monitoramento da produção por indicadores?','Controle de ocorrências/erros para evitar recorrências?','Garantia de qualidade de prestadores/terceiros?','Auditoria baseada nos processos?','Metodologia de incentivo à melhoria dos processos?']; foreach ($proPerg as $idx=>$q): ?>
            <div class="grid grid-cols-2 gap-3 items-center">
              <div class="text-sm"><?= htmlspecialchars($q) ?></div>
              <select name="pro[<?= $idx ?>]" class="border rounded p-2">
                <option value="0">Não</option>
                <option value="1">Sim</option>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="bg-white border rounded p-4">
      <div class="grid grid-cols-3 gap-4 items-end">
        <div>
          <div class="text-sm">Nota total dos 4 pilares</div>
          <div class="text-3xl font-bold" id="totalNota">0</div>
        </div>
        <div>
          <div class="text-sm">Mundo ideal (100%)</div>
          <div class="text-3xl font-bold">28</div>
        </div>
        <div>
          <div class="text-sm">Qual sua realidade (%)</div>
          <div class="text-3xl font-bold" id="realidadeMedia">0%</div>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">Salvar avaliação</button>
    </div>
  </form>

  <script>
    (function(){
      function calc(){
        var grupos = ['fin','mer','pes','pro'];
        var total = 0;
        grupos.forEach(function(g){
          var els = document.querySelectorAll('select[name^="'+g+'["]');
          els.forEach(function(el){ total += parseInt(el.value||'0',10); });
        });
        document.getElementById('totalNota').textContent = total;
        var med = Math.round((total/28)*100);
        document.getElementById('realidadeMedia').textContent = med+'%';
      }
      document.addEventListener('input', calc);
      document.addEventListener('change', calc);
      calc();
      var modes = document.querySelectorAll('input[name="cliente_mode"]');
      modes.forEach(function(m){ m.addEventListener('change', function(){
        var isNew = this.value === 'new';
        document.getElementById('existingBox').classList.toggle('hidden', isNew);
        document.getElementById('newBox').classList.toggle('hidden', !isNew);
      }); });
    })();
  </script>
</div>