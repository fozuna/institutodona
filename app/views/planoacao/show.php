<?php /** @var array $task */ /** @var array $metrics */ /** @var array $checks */ /** @var array $actions */ ?>
<div class="p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($task['titulo'] ?? 'Plano de Ação') ?></h1>
        <button onclick="openTaskModal()" class="text-gray-400 hover:text-brand-orange" title="Editar Título/Descrição">
           <span data-feather="edit-3" class="w-5 h-5"></span>
        </button>
      </div>
      <?php 
          $taskModel = new \App\Models\PlanoAcaoTaskModel();
          $prazoStatus = $taskModel->getPrazoStatus($task['prazo'] ?? null, $task['status'] ?? '');
          $colorClass = match($prazoStatus) {
              'red' => 'bg-red-500',
              'yellow' => 'bg-yellow-400',
              'green' => 'bg-green-500',
              default => 'bg-gray-300'
          };
          $prazoDisplay = (!empty($task['prazo']) && $task['prazo'] !== '0000-00-00') ? date('d/m/Y', strtotime($task['prazo'])) : '—';
      ?>
      <div class="flex items-center gap-2 mt-1">
          <span class="w-3 h-3 rounded-full <?= $colorClass ?>" title="Status do Prazo"></span>
          <p class="text-sm text-gray-600">Prazo: <?= htmlspecialchars($prazoDisplay) ?> · Resp.: <?= htmlspecialchars($task['responsavel'] ?? '—') ?></p>
      </div>
    </div>
    <a class="px-3 py-2 rounded bg-brand-brown text-white" href="index.php?route=clientes/show&id=<?= (int)($task['id_cliente'] ?? 0) ?>">Voltar</a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-3 bg-white shadow rounded p-4 space-y-4">
      <div>
        <div class="font-semibold mb-1 text-gray-900">Título do Plano</div>
        <div class="text-gray-700 text-lg"><?= htmlspecialchars($task['titulo'] ?? '') ?></div>
      </div>
      <div>
        <div class="font-semibold mb-1 text-gray-900">Descrição/Justificativa</div>
        <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded"><?= nl2br(htmlspecialchars($task['descricao'] ?? '—')) ?></div>
      </div>
      <div>
        <div class="font-semibold mb-1 text-gray-900">Meta/Objetivo</div>
        <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded"><?= nl2br(htmlspecialchars($task['meta_valor'] ?? '—')) ?></div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="font-semibold mb-1 text-gray-900">Origem</div>
            <div class="text-sm text-gray-700"><?= htmlspecialchars($task['meta_unidade'] ?? '—') ?></div>
          </div>
          <div>
             <div class="font-semibold mb-1 text-gray-900">Concluído?</div>
             <div class="text-sm text-gray-700">
                <?= ((int)($task['progresso'] ?? 0) >= 100) ? '<span class="text-green-600 font-bold">Sim</span>' : '<span class="text-gray-500">Não</span>' ?>
             </div>
          </div>
      </div>
    </div>
  </div>

  <!-- Histórico de Alterações -->
  <div class="bg-white shadow rounded p-4 mt-6">
      <h3 class="font-semibold mb-4 text-lg border-b pb-2">Histórico de Alterações</h3>
      
      <!-- Filtros do Histórico -->
      <form method="get" class="mb-4 bg-gray-50 p-3 rounded flex flex-wrap gap-3 items-end">
        <input type="hidden" name="route" value="planoacao/show">
        <input type="hidden" name="id" value="<?= $task['id'] ?>">
        
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Usuário</label>
            <select name="h_user" class="text-sm border-gray-300 rounded shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
                <option value="">Todos</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($historyFilters['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tipo de Ação</label>
            <select name="h_action" class="text-sm border-gray-300 rounded shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
                <option value="">Todos</option>
                <option value="create" <?= ($historyFilters['action_type'] ?? '') == 'create' ? 'selected' : '' ?>>Criação</option>
                <option value="update" <?= ($historyFilters['action_type'] ?? '') == 'update' ? 'selected' : '' ?>>Atualização</option>
                <option value="delete" <?= ($historyFilters['action_type'] ?? '') == 'delete' ? 'selected' : '' ?>>Remoção</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Data Início</label>
            <input type="date" name="h_start" value="<?= $historyFilters['date_start'] ?? '' ?>" class="text-sm border-gray-300 rounded shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Data Fim</label>
            <input type="date" name="h_end" value="<?= $historyFilters['date_end'] ?? '' ?>" class="text-sm border-gray-300 rounded shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
        </div>

        <button type="submit" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm font-medium transition-colors">Filtrar</button>
        <?php if (!empty(array_filter($historyFilters))): ?>
            <a href="index.php?route=planoacao/show&id=<?= $task['id'] ?>" class="px-3 py-2 text-red-600 hover:text-red-800 text-sm font-medium">Limpar</a>
        <?php endif; ?>
      </form>

      <div class="overflow-x-auto max-h-96 overflow-y-auto">
          <table class="min-w-full text-sm">
              <thead class="bg-gray-50 sticky top-0">
                  <tr class="text-left text-gray-600">
                      <th class="p-3">Data/Hora</th>
                      <th class="p-3">Usuário</th>
                      <th class="p-3">Ação</th>
                      <th class="p-3">Detalhes da Modificação</th>
                  </tr>
              </thead>
              <tbody class="divide-y">
                  <?php if (empty($history)): ?>
                      <tr><td colspan="4" class="p-4 text-center text-gray-500">Nenhum histórico registrado ainda.</td></tr>
                  <?php else: ?>
                      <?php foreach ($history as $h): ?>
                          <?php if ($h['item_type'] !== 'task') continue; // Hide sub-action history ?>
                          <tr class="hover:bg-gray-50">
                              <td class="p-3 whitespace-nowrap text-gray-600"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                              <td class="p-3 font-medium"><?= htmlspecialchars($h['user_name'] ?? 'Sistema') ?></td>
                              <td class="p-3">
                                  <?php
                                  $actMap = ['create' => 'Criou', 'update' => 'Alterou', 'delete' => 'Removeu'];
                                  $act = $actMap[$h['action_type']] ?? $h['action_type'];
                                  echo "<span class='font-semibold'>$act</span>";
                                  ?>
                              </td>
                              <td class="p-3">
                                  <?php
                                  $changes = json_decode($h['changes_json'], true);
                                  if ($h['action_type'] === 'create') {
                                      echo '<span class="text-green-600">Item criado inicialmente</span>';
                                  } elseif ($changes && is_array($changes)) {
                                      echo '<div class="space-y-1">';
                                      foreach ($changes as $field => $vals) {
                                          $fieldLabel = ucfirst(str_replace('_', ' ', $field));
                                          $old = $vals['old'] ? htmlspecialchars($vals['old']) : '<em class="text-gray-400">vazio</em>';
                                          $new = $vals['new'] ? htmlspecialchars($vals['new']) : '<em class="text-gray-400">vazio</em>';
                                          echo "<div class='flex items-center gap-2'><span class='font-medium text-gray-700'>$fieldLabel:</span> <span class='line-through text-red-400 text-xs'>$old</span> <span class='text-gray-400'>&rarr;</span> <span class='text-green-600'>$new</span></div>";
                                      }
                                      echo '</div>';
                                  } else {
                                      echo '<span class="text-gray-400">Sem detalhes registrados</span>';
                                  }
                                  ?>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>
  </div>
</div>

<script>
// Handle Task Submit with Loading State and Validation
function handleTaskSubmit(form) {
    const titulo = document.getElementById('modal_task_titulo');
    const tituloError = document.getElementById('task_titulo_error');
    const responsavel = document.getElementById('modal_task_responsavel');
    const responsavelError = document.getElementById('task_responsavel_error');
    const prazo = document.getElementById('modal_task_prazo');
    const prazoError = document.getElementById('task_prazo_error');
    
    const btnSave = document.getElementById('btn_save_task');
    const btnCancel = document.getElementById('btn_cancel_task');
    
    let isValid = true;

    // Validate Titulo
    if (!titulo.value.trim()) {
        titulo.classList.add('border-red-500');
        tituloError.classList.remove('hidden');
        if (isValid) titulo.focus();
        isValid = false;
    } else {
        titulo.classList.remove('border-red-500');
        tituloError.classList.add('hidden');
    }

    // Validate Responsavel
    if (!responsavel.value.trim()) {
        responsavel.classList.add('border-red-500');
        responsavelError.classList.remove('hidden');
        if (isValid) responsavel.focus();
        isValid = false;
    } else {
        responsavel.classList.remove('border-red-500');
        responsavelError.classList.add('hidden');
    }

    // Validate Prazo
    if (!prazo.value) {
        prazo.classList.add('border-red-500');
        prazoError.classList.remove('hidden');
        if (isValid) prazo.focus();
        isValid = false;
    } else {
        prazo.classList.remove('border-red-500');
        prazoError.classList.add('hidden');
    }

    if (!isValid) return false;
    
    // Loading State
    btnSave.disabled = true;
    btnCancel.disabled = true;
    const originalText = btnSave.innerHTML;
    btnSave.innerHTML = `<span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2"></span> Salvando...`;
    btnSave.classList.add('opacity-75', 'cursor-not-allowed');
    
    // Allow form submission
    return true;
}

// Task Modal Logic
function openTaskModal() {
    document.getElementById('editTaskModal').classList.remove('hidden');
}
function closeTaskModal() {
    document.getElementById('editTaskModal').classList.add('hidden');
}

// Close on outside click
document.getElementById('editTaskModal').addEventListener('click', function(e) {
    if (e.target === this) closeTaskModal();
});
</script>

<!-- Modal Edit Task -->
<div id="editTaskModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Editar Detalhes do Plano</h3>
            <button onclick="closeTaskModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="post" action="index.php?route=planoacao/updateTask" class="p-6 space-y-4" onsubmit="return handleTaskSubmit(this)">
            <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>">
            <input type="hidden" name="id" value="<?= $task['id'] ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">O Quê? (Problema) <span class="text-red-500">*</span></label>
                <input type="text" name="titulo" id="modal_task_titulo" value="<?= htmlspecialchars($task['titulo'] ?? '') ?>" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" required placeholder="Título do Plano">
                <p class="text-xs text-red-500 mt-1 hidden" id="task_titulo_error">Este campo é obrigatório.</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Por que?</label>
                <textarea name="descricao" rows="3" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" placeholder="Descrição detalhada"><?= htmlspecialchars($task['descricao'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Como (Solução)</label>
                <textarea name="meta_valor" rows="3" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" placeholder="Objetivo a alcançar"><?= htmlspecialchars($task['meta_valor'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
                    <input type="text" name="meta_unidade" value="<?= htmlspecialchars($task['meta_unidade'] ?? '') ?>" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" placeholder="Origem da demanda">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prazo <span class="text-red-500">*</span></label>
                    <input type="date" name="prazo" id="modal_task_prazo" value="<?= htmlspecialchars($task['prazo'] ?? '') ?>" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" required>
                    <p class="text-xs text-red-500 mt-1 hidden" id="task_prazo_error">Informe o prazo.</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsável <span class="text-red-500">*</span></label>
                    <input type="text" name="responsavel" id="modal_task_responsavel" value="<?= htmlspecialchars($task['responsavel'] ?? '') ?>" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" placeholder="Nome do responsável" required>
                    <p class="text-xs text-red-500 mt-1 hidden" id="task_responsavel_error">Informe o responsável.</p>
                </div>
                <div class="flex items-end pb-2">
                     <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="concluido" value="1" <?= ((int)($task['progresso']??0) >= 100) ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-brand-orange">
                        <span class="text-sm font-medium text-gray-700">Marcar como Concluído</span>
                    </label>
                </div>
            </div>

             <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
                    <option value="A Fazer" <?= ($task['status']??'') == 'A Fazer' ? 'selected' : '' ?>>A Fazer</option>
                    <option value="Em Andamento" <?= ($task['status']??'') == 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="Concluído" <?= ($task['status']??'') == 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                    <option value="Pendente" <?= ($task['status']??'') == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                </select>
            </div>

            <div class="flex justify-end pt-4 gap-2">
                <button type="button" onclick="closeTaskModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors" id="btn_cancel_task">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-brand-orange text-white rounded hover:bg-orange-700 transition-colors flex items-center gap-2" id="btn_save_task">
                    <span data-feather="save" class="w-4 h-4"></span>
                    <span>Salvar Alterações</span>
                </button>
            </div>
        </form>
    </div>
</div>
