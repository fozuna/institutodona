<?php /** @var int $cliente */ /** @var array $clientes */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Nova Avaliação</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <form method="post" action="index.php?route=avaliacoes/store" class="space-y-6">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm">Empresa (prospect)</label>
        <?php if ($cliente): ?>
          <input type="hidden" name="cliente_id" value="<?= (int)$cliente ?>" />
          <input class="border rounded p-2 w-full bg-gray-100" value="<?php foreach ($clientes as $cl){ if ((int)$cl['id']===(int)$cliente){ echo htmlspecialchars($cl['nome_empresa']); break; } } ?>" readonly />
        <?php else: ?>
          <input name="empresa_nome" class="border rounded p-2 w-full" />
        <?php endif; ?>
      </div>
      <div>
        <label class="block text-sm">Contato</label>
        <input name="contato" class="border rounded p-2 w-full" />
      </div>
      <div>
        <label class="block text-sm">Ou vincule um cliente existente</label>
        <select name="cliente_id" class="border rounded p-2 w-full" <?= $cliente ? 'disabled' : '' ?>>
          <option value="">—</option>
          <?php foreach ($clientes as $cl): ?>
            <option value="<?= (int)$cl['id'] ?>" <?= $cliente === (int)$cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['nome_empresa']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php
      $qs = [
        'financeiro' => [
          'Planejamento estratégico?',
          'Quadro de indicadores estratégicos?',
          'Indicadores de desempenho por área?',
          'Informações gerenciais de fácil acesso?',
          'Reuniões de alinhamento estratégico?',
          'Gestão à vista?',
          'Registro dos planos de ação?',
        ],
        'mercado' => [
          'Missão, Visão e Valores?',
          'Processo comercial ativo e controlado?',
          'Relacionamento com fornecedores saudável?',
          'Pesquisa de satisfação de clientes?',
          'Canal de sugestões/fale conosco?',
          'Análise de concorrência/mercado?',
          'Práticas ambientais?',
        ],
        'pessoas' => [
          'Pesquisa de clima organizacional?',
          'Seleção adequada com teste de perfil?',
          'Integração com padrinhamento (onboarding)?',
          'Avaliação de gaps e feedback?',
          'Quadro de carreira e organograma?',
          'Desenvolvimento e treinamentos?',
          'PRG atualizado e implementado?',
        ],
        'processo' => [
          'Manual de processos?',
          'Treinamento/reciclagem de equipe nos manuais?',
          'Monitoramento da produção (indicadores)?',
          'Controle de ocorrências e erros?',
          'Garantia da qualidade de terceiros?',
          'Auditoria baseada nos manuais?',
          'Metodologia de incentivo à melhoria?',
        ],
      ];
    ?>
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
      <?php foreach ($qs as $pillar => $questions): ?>
        <div class="bg-white shadow rounded">
          <div class="px-4 py-3 border-b flex items-center justify-between">
            <div class="font-semibold uppercase"><?= $pillar ?></div>
            <span class="text-brand-brown">✔</span>
          </div>
          <div class="p-0">
            <table class="min-w-full text-sm">
              <tbody>
              <?php foreach ($questions as $idx => $q): ?>
                <tr class="border-b">
                  <td class="p-2 w-8"><input type="checkbox" name="<?= $pillar ?>[]" value="<?= $idx+1 ?>" data-pillar="<?= $pillar ?>" /></td>
                  <td class="p-2"><?= htmlspecialchars($q) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr>
                <td class="p-2 font-semibold">Nota:</td>
                <td class="p-2"><input type="number" name="nota_<?= $pillar ?>" id="nota_<?= $pillar ?>" min="0" max="7" class="border rounded p-2 w-24" /></td>
              </tr>
              <tr>
                <td class="p-2 font-semibold">Mundo ideal (100%)</td>
                <td class="p-2">7</td>
              </tr>
              <tr>
                <td class="p-2 font-semibold">Qual sua realidade? (%)</td>
                <td class="p-2"><input type="number" name="realidade_<?= $pillar ?>" id="realidade_<?= $pillar ?>" min="0" max="100" class="border rounded p-2 w-28" /></td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
      <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">Cancelar</button>
    </div>
    <script>
      (function(){
        const totals = {
          financeiro: 7,
          mercado: 7,
          pessoas: 7,
          processo: 7
        };
        function recalc(p) {
          const checks = document.querySelectorAll('input[type="checkbox"][data-pillar="'+p+'"]');
          let count = 0;
          checks.forEach(c=>{ if (c.checked) count++; });
          const nota = document.getElementById('nota_'+p);
          const real = document.getElementById('realidade_'+p);
          if (nota) nota.value = count;
          if (real) real.value = Math.round((count / (totals[p]||1)) * 100);
        }
        ['financeiro','mercado','pessoas','processo'].forEach(p=>{
          document.querySelectorAll('input[type="checkbox"][data-pillar="'+p+'"]').forEach(el=>{
            el.addEventListener('change', ()=>recalc(p));
          });
          const nota = document.getElementById('nota_'+p);
          const real = document.getElementById('realidade_'+p);
          if (nota) nota.addEventListener('input', ()=>{
            const v = Math.max(0, Math.min(7, parseInt(nota.value||0,10)));
            nota.value = v;
            if (real) real.value = Math.round((v / (totals[p]||1)) * 100);
          });
          if (real) real.addEventListener('input', ()=>{
            const v = Math.max(0, Math.min(100, parseInt(real.value||0,10)));
            real.value = v;
          });
          recalc(p);
        });
      })();
    </script>
  </form>
</div>
