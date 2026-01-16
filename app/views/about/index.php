<?php /** @var string $version */ ?>
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
      <div class="font-semibold mb-2">Dashboard (Kanban)</div>
      <p class="text-sm text-gray-700">Arraste e solte tarefas entre colunas para alterar o status. Clique no card para abrir a edição.</p>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Aplicar/Editar Tarefa</div>
      <ul class="text-sm text-gray-700 list-disc pl-5">
        <li>Defina data prevista, consultor e status.</li>
        <li>Selecione colaboradores na lista à direita.</li>
        <li>Registre observações e acompanhe o histórico de atualizações.</li>
        <li>Anexe arquivos (drag & drop ou seleção múltipla), com Preview integrado.</li>
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
      <div class="font-semibold mb-2">Biblioteca de Arquivos</div>
      <p class="text-sm text-gray-700">No perfil do cliente, lista arquivos anexados nas tarefas do cliente, com links Abrir e acesso à tarefa.</p>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-2">Logs de Auditoria</div>
      <p class="text-sm text-gray-700">Admins podem acessar: registros de criação, edição, exclusão e uploads com data/hora e usuário.</p>
    </div>
    <div class="bg-white shadow rounded p-4 md:col-span-2">
      <div class="font-semibold mb-2">Sobre</div>
      <p class="text-sm text-gray-700">Desenvolvido por Traxter — Sistemas e Automações.</p>
    </div>
  </div>
</div>
