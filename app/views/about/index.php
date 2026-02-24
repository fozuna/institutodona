<?php /** @var string $version */ /** @var array $changelog */ ?>
<div class="p-6">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Sobre e Manual de Uso</h1>
    <span class="text-sm text-gray-600">Versão: <?= htmlspecialchars($version) ?></span>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Visão Geral</div>
      <p class="text-sm text-gray-700">Sistema de gestão de processos com cadastro de clientes, aplicação de tarefas, avaliação e biblioteca de arquivos.</p>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Painel (Kanban)</div>
      <p class="text-sm text-gray-700">Arrastar e soltar tarefas entre colunas para alterar o status. Clique no cartão para abrir a edição.</p>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Aplicar/Editar Tarefa</div>
      <ul class="text-sm text-gray-700 list-disc pl-5">
        <li>Defina data prevista, consultor e status.</li>
        <li>Selecione colaboradores na lista à direita.</li>
        <li>Registre observações e acompanhe o histórico de atualizações.</li>
        <li>Anexe arquivos (arrastar e soltar ou seleção múltipla), com pré-visualização integrada.</li>
      </ul>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Avaliações</div>
      <ul class="text-sm text-gray-700 list-disc pl-5">
        <li>Matriz em 4 colunas (Financeiro, Mercado, Pessoas, Processo).</li>
        <li>Cálculo automático de Nota e Realidade (%).</li>
        <li>Visualização com gráficos e lista de itens pontuados/não pontuados.</li>
      </ul>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Planos de Ação</div>
      <ul class="text-sm text-gray-700 list-disc pl-5">
        <li>Cadastro de planos de ação diretos.</li>
        <li>Lista de planos abaixo do formulário com filtros por Status e Busca.</li>
        <li>Acompanhamento de progresso e status.</li>
      </ul>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Cronograma</div>
      <ul class="text-sm text-gray-700 list-disc pl-5">
        <li>Seleção prévia do cliente para criar cronogramas.</li>
        <li>Eventos mensais por tópico/unidade/atividade com marcação no grid (J–D).</li>
        <li>Botão “Voltar” retorna ao perfil do cliente mantendo o contexto.</li>
      </ul>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Perfil do Cliente</div>
      <ul class="text-sm text-gray-700 list-disc pl-5">
        <li>Dois cartões: “Cronograma” e “Planos de Ação”, com contadores e acesso rápido.</li>
        <li>Navegação contextual e estados de carregamento para melhor usabilidade.</li>
      </ul>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Biblioteca de Arquivos</div>
      <p class="text-sm text-gray-700">No perfil do cliente, lista arquivos anexados nas tarefas do cliente, com links Abrir e acesso à tarefa.</p>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Logs de Auditoria</div>
      <p class="text-sm text-gray-700">Administradores podem acessar: registros de criação, edição, exclusão e uploads com data/hora e usuário. Inclui logs de depuração para eventos do cronograma e tentativas de login.</p>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Segurança e Sessão</div>
      <ul class="text-sm text-gray-700 list-disc pl-5">
        <li>Proteção contra CSRF em formulários.</li>
        <li>Acesso direto à ação de login redireciona para a tela de autenticação.</li>
      </ul>
    </div>
    <div class="bg-white shadow rounded p-4 md:col-span-2">
      <div class="font-semibold mb-2">Sobre</div>
      <p class="text-sm text-gray-700">Desenvolvido por Traxter — Sistemas e Automações.</p>
    </div>
    <div class="bg-white shadow rounded p-4 md:col-span-2">
      <div class="font-semibold mb-2">Novidades</div>
      <?php if (!empty($changelog)): ?>
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b">
              <th class="p-2">Data</th>
              <th class="p-2">Resumo</th>
              <th class="p-2">Commit</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($changelog as $c): ?>
              <tr class="border-b">
                <td class="p-2 whitespace-nowrap"><?= htmlspecialchars($c['date']) ?></td>
                <td class="p-2"><?= htmlspecialchars($c['subject']) ?></td>
                <td class="p-2 text-gray-500"><?= htmlspecialchars($c['hash']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="text-sm text-gray-600">Sem novidades disponíveis no momento.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
