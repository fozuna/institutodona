<?php /** @var int $cliente */ /** @var array $clientes */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Nova Avaliação</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <form method="post" action="index.php?route=avaliacoes/store" class="space-y-6">
    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
    <?php if ($cliente): ?>
      <input type="hidden" name="cliente_id" value="<?= (int)$cliente ?>" />
      <div class="text-xs text-gray-600 mb-2">Vinculada à empresa #<?= (int)$cliente ?></div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">Empresa (prospect)</label>
          <input name="empresa_nome" class="border rounded p-2 w-full" />
        </div>
        <div>
          <label class="block text-sm">Contato</label>
          <input name="contato" class="border rounded p-2 w-full" />
        </div>
        <div>
          <label class="block text-sm">Ou vincule um cliente existente</label>
          <select name="cliente_id" class="border rounded p-2 w-full">
            <option value="">—</option>
            <?php foreach ($clientes as $cl): ?>
              <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>
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
    <?php foreach ($qs as $pillar => $questions): ?>
      <div class="bg-white shadow rounded p-4">
        <div class="font-semibold mb-3 uppercase"><?= $pillar ?></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <?php foreach ($questions as $idx => $q): ?>
            <label class="flex items-center gap-2">
              <input type="checkbox" name="<?= $pillar ?>[]" value="<?= $idx+1 ?>" />
              <span class="text-sm"><?= htmlspecialchars($q) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="flex items-center gap-3">
      <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
      <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">Cancelar</button>
    </div>
  </form>
</div>
