<?php /** @var array $item */ /** @var array $respostas */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Plano de Ação</h1>
    <a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
  </div>
  <div class="bg-white shadow rounded p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <div class="text-sm text-gray-500">Empresa</div>
        <div class="font-semibold">
          <?php
          $empresa = $item['cliente_id'] ? ($item['cliente_nome'] ?? '') : ($item['empresa_nome'] ?? '');
          echo htmlspecialchars($empresa ?: '—');
          ?>
        </div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Data da avaliação</div>
        <div class="font-semibold">
          <?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['created_at']))) ?>
        </div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Descrição</div>
        <div class="font-semibold">Planos de ação derivados da avaliação</div>
      </div>
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
    $labels = [
      'financeiro' => 'Financeiro',
      'mercado' => 'Mercado',
      'pessoas' => 'Pessoas',
      'processo' => 'Processo',
    ];
  ?>
  <div class="bg-white shadow rounded p-4">
    <div class="font-semibold mb-3">Planos de ação</div>
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left border-b">
          <th class="p-2">Pilar</th>
          <th class="p-2">Item avaliado</th>
          <th class="p-2">Plano de ação</th>
          <th class="p-2">Responsável</th>
          <th class="p-2">Data de término</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($qs as $key => $questions): ?>
          <?php
            $selected = array_map('intval', $respostas[$key] ?? []);
          ?>
          <?php foreach ($questions as $idx => $q): ?>
            <?php if (in_array($idx + 1, $selected, true)) continue; ?>
            <tr class="border-b align-top">
              <td class="p-2 whitespace-nowrap"><?= htmlspecialchars($labels[$key] ?? $key) ?></td>
              <td class="p-2"><?= htmlspecialchars($q) ?></td>
              <td class="p-2"></td>
              <td class="p-2"></td>
              <td class="p-2"></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
