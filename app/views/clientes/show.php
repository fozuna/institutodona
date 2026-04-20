<?php /** @var array $item */ ?>
<?php
  $importEnabled = getenv('PLANOACAO_IMPORT_ENABLED') === '1';
  $importAlreadyRun = is_file(__DIR__ . '/../../storage/imports/planoacao_import_done.flag');
  $canExportPlanos = \App\Core\Auth::canExportPlanosAcao();
  $selectedFilialId = (int)($selectedFilialId ?? 0);
  $clienteAlvoId = (int)($clienteAlvoId ?? (int)($item['id'] ?? 0));
  $filiais = $filiais ?? [];
  $filialQuery = $selectedFilialId > 0 ? '&filial_id=' . $selectedFilialId : '';
  $planoSearch = trim((string)($planoSearch ?? ''));
?>
<div class="p-6">
  <div class="mb-4">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-brand-black">Perfil do Cliente</h1>
      <div class="flex items-center gap-2">
        <?php if (($_SESSION['user']['tipo_acesso'] ?? '') === 'instituto'): ?>
          <a class="px-3 py-2 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors flex items-center gap-2" href="index.php?route=clientes/edit&id=<?= (int)$item['id'] ?>">
            <span data-feather="edit" class="w-4 h-4"></span> Editar
          </a>
        <?php endif; ?>
        <a class="px-3 py-2 rounded bg-brand-red text-white flex items-center gap-2" href="index.php?route=metodologias/create&cliente=<?= $clienteAlvoId ?>">
          <span data-feather="plus" class="w-4 h-4"></span> Criar Tarefa
        </a>
      </div>
    </div>
    <div class="mt-2 bg-white shadow rounded p-4">
      <?php if (!empty($item['logo_path'])): ?>
        <div class="mb-3">
          <img src="<?= htmlspecialchars($item['logo_path']) ?>" alt="Logo do cliente" class="h-12 w-auto" />
        </div>
      <?php endif; ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-500">Empresa</div>
          <div class="font-semibold"><?= htmlspecialchars($item['nome_empresa'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">CNPJ</div>
          <div class="font-semibold"><?= htmlspecialchars($item['CNPJ'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Contato</div>
          <div class="font-semibold"><?= htmlspecialchars($item['contato'] ?? '') ?></div>
        </div>
        <div>
          <div class="text-sm text-gray-500">Tipo</div>
          <div class="font-semibold"><?= ((int)($item['is_matriz'] ?? 1) === 1) ? 'Matriz' : 'Filial' ?></div>
        </div>
        <?php if (!empty($filiais)): ?>
        <div>
          <div class="text-sm text-gray-500">Filial</div>
          <form method="get" action="index.php">
            <input type="hidden" name="route" value="clientes/show" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <select name="filial_id" class="border rounded p-2 w-full text-sm" onchange="this.form.submit()">
              <option value="0">Todas as filiais</option>
              <?php foreach ($filiais as $filial): ?>
                <option value="<?= (int)$filial['id'] ?>" <?= $selectedFilialId === (int)$filial['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($filial['nome_empresa'] ?? '') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <?php endif; ?>
        <?php if (!empty($item['matriz_id'])): ?>
        <div>
          <div class="text-sm text-gray-500">Matriz</div>
          <div class="font-semibold">
            <?php foreach ($matrizes as $m): if ((int)$m['id'] === (int)$item['matriz_id']) { echo htmlspecialchars($m['nome_empresa']); break; } endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="mb-6">
    <div class="bg-white shadow rounded p-4">
      <div class="font-semibold mb-3">Estrutura Organizacional</div>
      <div class="flex flex-wrap gap-3">
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=departamentos/index&cliente=<?= $clienteAlvoId ?>">Departamentos</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=manuais/index&empresa_id=<?= $clienteAlvoId ?>">Ver Manuais</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=setores/index&cliente=<?= $clienteAlvoId ?>">Setores</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=funcoes/index&cliente=<?= $clienteAlvoId ?>">Funções</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=colaboradores/index&cliente=<?= $clienteAlvoId ?>">Colaboradores</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=dashboard/index&cliente=<?= $clienteAlvoId ?>">Tarefas</a>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-6">
    <div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php
          $cronCount = count((new \App\Models\CronogramaModel())->byClientes($scopeClienteIds ?? [(int)$item['id']]));
          $planoCount = (int)(($planoResumoBase['total_planos'] ?? null) ?? ($planoResumo['total_planos'] ?? 0));
          $planoDone = (int)(($planoResumoBase['total_concluidos'] ?? null) ?? ($planoResumo['total_concluidos'] ?? 0));
          $planoNotDone = (int)(($planoResumoBase['total_nao_concluidos'] ?? null) ?? ($planoResumo['total_nao_concluidos'] ?? 0));
        ?>
        <a class="bg-white shadow rounded p-4 block hover:shadow-md transition-shadow" href="index.php?route=cronograma/index&id_cliente=<?= $clienteAlvoId ?>" data-loading>
          <div class="flex items-center justify-between">
            <div class="font-semibold">Cronograma</div>
            <span class="badge"><span data-feather="calendar"></span></span>
          </div>
          <div class="text-sm text-gray-600 mt-2">Cronogramas: <?= $cronCount ?></div>
          <div class="text-xs text-gray-500 mt-1">Clique para abrir cronogramas do cliente</div>
        </a>
        <a class="bg-white shadow rounded p-4 block hover:shadow-md transition-shadow" href="index.php?route=planoacao/index&cliente=<?= $clienteAlvoId ?>" data-loading>
          <div class="flex items-center justify-between">
            <div class="font-semibold">Planos de Ação</div>
            <span class="badge"><span data-feather="activity"></span></span>
          </div>
          <div class="text-sm text-gray-600 mt-2">Total: <?= $planoCount ?> · Concluídos: <?= $planoDone ?> · Não concluídos: <?= $planoNotDone ?></div>
          <div class="text-xs text-gray-500 mt-1">Clique para abrir planos de ação do cliente</div>
        </a>
      </div>
      <script>
        document.querySelectorAll('[data-loading]').forEach(function(el){
          el.addEventListener('click', function(){
            el.setAttribute('aria-busy','true');
          });
        });
        (function(){
          const exportForm = document.getElementById('planoExportForm');
          const exportBtn = document.getElementById('planoExportBtn');
          if (exportForm && exportBtn) {
            exportForm.addEventListener('submit', function(){
              exportBtn.disabled = true;
              exportBtn.innerHTML = '<span class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span><span>Gerando...</span>';
              setTimeout(()=>{ 
                exportBtn.disabled = false; 
                exportBtn.innerHTML = '<span data-feather="download" class="w-4 h-4"></span><span>Exportar Excel</span>'; 
                if (typeof feather !== 'undefined') feather.replace();
              }, 8000);
            });
          }
          // Auto-submit on checkbox change + selecionar todos
          const form = document.getElementById('planoFilterForm');
          const all = document.getElementById('planoStAll');
          const items = document.querySelectorAll('#planoFilterForm .st-item');
          if (form) {
            items.forEach(cb => cb.addEventListener('change', () => form.submit()));
          }
          if (all) {
            all.addEventListener('change', () => {
              const checked = all.checked;
              items.forEach(cb => { cb.checked = checked; });
              form.submit();
            });
            // Define estado inicial do "Selecionar Todos"
            let allOn = true;
            items.forEach(cb => { if (!cb.checked) allOn = false; });
            all.checked = allOn;
          }
        })();
      </script>

      <!-- Seção de Planos de Ação Detalhada -->
      <div class="bg-white shadow rounded mt-6">
        <div class="px-4 py-3 border-b font-semibold flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <span>Planos de Ação</span>
          <div class="flex items-center gap-2">
            <?php if ($importEnabled && !$importAlreadyRun && (($_SESSION['user']['tipo_acesso'] ?? '') === 'instituto')): ?>
              <button type="button" id="clienteImportOpenBtn" class="px-3 py-1 rounded bg-brand-brown text-white text-sm flex items-center gap-2 hover:bg-gray-800">
                <span data-feather="upload-cloud" class="w-4 h-4"></span>
                <span>Importar planos</span>
              </button>
            <?php endif; ?>
            <?php if ($canExportPlanos): ?>
            <form method="get" action="index.php" id="planoExportForm" class="inline">
              <input type="hidden" name="route" value="clientes/exportPlanos" />
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
              <input type="hidden" name="filial_id" value="<?= $selectedFilialId ?>" />
              <?php if ($planoSearch !== ''): ?>
                <input type="hidden" name="plano_q" value="<?= htmlspecialchars($planoSearch) ?>" />
              <?php endif; ?>
              <?php foreach (($planoStatusFilters ?? []) as $st): ?>
                <input type="hidden" name="plano_status[]" value="<?= htmlspecialchars($st) ?>" />
              <?php endforeach; ?>
              <button type="submit" aria-label="Exportar planos para Excel" class="px-3 py-1 rounded bg-brand-brown text-white text-sm flex items-center gap-2 hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange shadow-sm" id="planoExportBtn">
                <span data-feather="download" class="w-4 h-4"></span>
                <span>Exportar Excel</span>
              </button>
            </form>
            <?php endif; ?>
            <a class="px-3 py-1 rounded bg-brand-red text-white text-sm" href="index.php?route=planoacao/create&cliente=<?= $clienteAlvoId ?>">Novo Plano</a>
          </div>
        </div>
        <div class="p-4">
          <form method="get" class="flex flex-wrap items-center gap-3 mb-3" id="planoFilterForm">
            <input type="hidden" name="route" value="clientes/show" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <input type="hidden" name="filial_id" value="<?= $selectedFilialId ?>" />
            <?php if ($planoSearch !== ''): ?>
              <input type="hidden" name="plano_q" value="<?= htmlspecialchars($planoSearch) ?>" />
            <?php endif; ?>
            <div class="flex flex-wrap items-center gap-3 text-xs">
              <?php
                $allStatusOptions = ['Planejado','Em Andamento','Concluído','Pendente','Atrasado'];
                $selStatuses = $planoStatusFilters ?? [];
              ?>
              <label class="inline-flex items-center gap-1 cursor-pointer">
                <input type="checkbox" id="planoStAll" class="form-checkbox">
                <span>Selecionar Todos</span>
              </label>
              <?php foreach ($allStatusOptions as $opt): ?>
                <label class="inline-flex items-center gap-1 cursor-pointer">
                  <input type="checkbox" name="plano_status[]" value="<?= $opt ?>" class="form-checkbox st-item"
                    <?= in_array($opt, $selStatuses, true) ? 'checked' : '' ?>>
                  <span><?= $opt ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <select name="plano_per" class="text-xs border rounded px-2 py-1">
              <?php foreach ([10,20,25,50,100] as $opt): ?>
                <option value="<?= $opt ?>" <?= ((string)($planoPer ?? '20') === (string)$opt) ? 'selected' : '' ?>><?= $opt ?> por página</option>
              <?php endforeach; ?>
              <option value="all" <?= (string)($planoPer ?? '20') === 'all' ? 'selected' : '' ?>>Todos os itens</option>
            </select>
            <button type="submit" class="px-2 py-1 rounded bg-gray-200 text-xs text-brand-brown">Aplicar</button>
          </form>
          <div class="mb-3 text-xs text-gray-600">
            Total disponível: <?= (int)($planoDatasetTotal ?? 0) ?>
            · Filtrados: <?= (int)($planoFilteredTotal ?? 0) ?>
            · Exibindo: <?= (int)($planoDisplayedCount ?? 0) ?>
            <?php if ((string)($planoPer ?? '20') === 'all'): ?>
              · Modo: Todos os itens
            <?php else: ?>
              · Página: <?= (int)($planoPage ?? 1) ?> de <?= (int)($planoTotalPages ?? 1) ?>
            <?php endif; ?>
          </div>
          <?php if (empty($planoTasks)): ?>
            <div class="text-sm text-gray-600 text-center py-4">Nenhum plano de ação registrado.</div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="text-left border-b bg-gray-50">
                    <th class="p-3 font-semibold text-gray-600">Título</th>
                    <th class="p-3 font-semibold text-gray-600">Responsável</th>
                    <th class="p-3 font-semibold text-gray-600">Prazo</th>
                    <th class="p-3 font-semibold text-gray-600">Status</th>
                    <th class="p-3 font-semibold text-gray-600 text-right">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $taskModel = new \App\Models\PlanoAcaoTaskModel();
                  foreach ($planoTasks as $pt): 
                    $prazoStatus = $taskModel->getPrazoStatus($pt['prazo'] ?? null, $pt['status'] ?? '');
                    $colorClass = match($prazoStatus) {
                        'red' => 'bg-red-500',
                        'yellow' => 'bg-yellow-400',
                        'green' => 'bg-green-500',
                        default => 'bg-gray-300'
                    };
                    $prazoDisplay = (!empty($pt['prazo']) && $pt['prazo'] !== '0000-00-00') ? date('d/m/Y', strtotime($pt['prazo'])) : '—';
                  ?>
                    <tr class="border-b hover:bg-gray-50 transition-colors">
                      <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($pt['titulo']) ?></td>
                      <td class="p-3 text-gray-600"><?= htmlspecialchars($pt['responsavel'] ?? '—') ?></td>
                      <td class="p-3 flex items-center gap-2 text-gray-600">
                          <span class="w-3 h-3 rounded-full <?= $colorClass ?>" title="Status do Prazo"></span>
                          <?= $prazoDisplay ?>
                      </td>
                      <td class="p-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                          <?= $pt['status'] === 'Concluído' ? 'bg-green-100 text-green-800' :
                             ($pt['status'] === 'Em Andamento' ? 'bg-blue-100 text-blue-800' :
                             ($pt['status'] === 'Atrasado' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) ?>">
                          <?= htmlspecialchars($pt['status']) ?>
                        </span>
                      </td>
                      <td class="p-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                          <a class="text-brand-blue hover:bg-blue-50 p-1.5 rounded transition-colors" href="index.php?route=planoacao/show&id=<?= $pt['id'] ?>" title="Visualizar Detalhes">
                            <span data-feather="eye" class="w-4 h-4"></span>
                          </a>
                          <?php if (($_SESSION['user']['tipo_acesso'] ?? '') === 'instituto'): ?>
                          <button type="button" onclick='openQuickEdit(<?= htmlspecialchars(json_encode($pt), ENT_QUOTES, "UTF-8") ?>)' class="text-brand-orange hover:bg-orange-50 p-1.5 rounded transition-colors" title="Edição Rápida">
                            <span data-feather="edit" class="w-4 h-4"></span>
                          </button>
                          <?php endif; ?>
        <form method="post" action="index.php?route=planoacao/delete" onsubmit="return confirm('Tem certeza que deseja excluir este plano de ação?');" style="display:inline;">
                              <input type="hidden" name="id" value="<?= $pt['id'] ?>">
                              <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>">
                              <button type="submit" class="text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors" title="Excluir">
                                  <span data-feather="trash-2" class="w-4 h-4"></span>
                              </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if (($planoTotalPages ?? 1) > 1): ?>
            <div class="mt-4 flex items-center justify-between text-xs text-gray-600">
              <div>Total filtrado: <?= (int)($planoFilteredTotal ?? 0) ?> • Página <?= (int)($planoPage ?? 1) ?> de <?= (int)($planoTotalPages ?? 1) ?></div>
              <div class="flex items-center gap-2">
                <?php
                  $paginationParams = [
                    'route' => 'clientes/show',
                    'id' => (int)$item['id'],
                    'filial_id' => $selectedFilialId,
                    'plano_per' => (string)($planoPer ?? '20'),
                  ];
                  if ($planoSearch !== '') {
                    $paginationParams['plano_q'] = $planoSearch;
                  }
                  foreach (($planoStatusFilters ?? []) as $status) {
                    $paginationParams['plano_status'][] = $status;
                  }
                  $prev = max(1, (int)($planoPage ?? 1) - 1);
                  $next = min((int)($planoTotalPages ?? 1), (int)($planoPage ?? 1) + 1);
                  $prevParams = $paginationParams;
                  $nextParams = $paginationParams;
                  $prevParams['plano_page'] = $prev;
                  $nextParams['plano_page'] = $next;
                ?>
                <a href="<?= ($planoPage ?? 1) > 1 ? 'index.php?' . htmlspecialchars(http_build_query($prevParams)) : '#' ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= ($planoPage ?? 1) <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">Anterior</a>
                <a href="<?= ($planoPage ?? 1) < ($planoTotalPages ?? 1) ? 'index.php?' . htmlspecialchars(http_build_query($nextParams)) : '#' ?>" class="px-2 py-1 rounded bg-gray-200 text-brand-brown <?= ($planoPage ?? 1) >= ($planoTotalPages ?? 1) ? 'opacity-50 pointer-events-none' : '' ?>">Próximo</a>
              </div>
            </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($importEnabled && !$importAlreadyRun && (($_SESSION['user']['tipo_acesso'] ?? '') === 'instituto')): ?>
    <div id="clienteImportModal" class="fixed inset-0 z-40 hidden items-center justify-center">
      <div id="clienteImportOverlay" class="absolute inset-0 bg-black bg-opacity-40"></div>
      <div class="relative bg-white rounded shadow-lg max-w-lg w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="text-lg font-semibold text-gray-900">Importar planos de ação</div>
            <div class="text-xs text-gray-500">Selecione a planilha para importar planos de ação para este cliente.</div>
          </div>
          <button type="button" id="clienteImportCloseBtn" class="text-gray-500 hover:text-gray-800">
            <span data-feather="x" class="w-4 h-4"></span>
          </button>
        </div>
        <form id="clienteImportForm" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="id_cliente" value="<?= (int)$item['id'] ?>" />
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo da planilha</label>
            <input type="file" name="arquivo" id="clienteImportFile" accept=".xlsx,.csv" class="border rounded p-2 w-full text-sm" />
            <div class="text-xs text-gray-500 mt-1">
              Formatos aceitos: XLSX ou CSV. A primeira linha deve conter os cabeçalhos.
            </div>
          </div>
          <div id="clienteImportStatus" class="text-xs mt-1 min-h-[1.25rem]"></div>
          <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" id="clienteImportCancelBtn" class="px-3 py-1.5 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-100">Cancelar</button>
            <button type="submit" id="clienteImportSubmitBtn" class="px-4 py-1.5 rounded bg-brand-red text-white text-sm font-semibold hover:bg-red-700 flex items-center gap-2">
              <span data-feather="play" class="w-4 h-4"></span>
              <span>Executar importação</span>
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>

<!-- Quick Edit Modal -->
<div id="quickEditModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Edição Rápida</h3>
            <button onclick="closeQuickEdit()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        <form id="quickEditForm" method="post" action="index.php?route=planoacao/updateTask" class="p-6 space-y-4">
            <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>">
            <input type="hidden" name="id" id="qe_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">O Quê? (Problema) <span class="text-red-500">*</span></label>
                <input type="text" name="titulo" id="qe_titulo" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Por que?</label>
                <textarea name="descricao" id="qe_descricao" rows="3" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Como (Solução)</label>
                <textarea name="meta_valor" id="qe_meta_valor" rows="3" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
                    <input type="text" name="meta_unidade" id="qe_meta_unidade" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prazo <span class="text-red-500">*</span></label>
                    <input type="date" name="prazo" id="qe_prazo" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" required>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsável <span class="text-red-500">*</span></label>
                    <input type="text" name="responsavel" id="qe_responsavel" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50" required>
                </div>
                <div class="flex items-end pb-2">
                     <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="concluido" id="qe_concluido" value="1" class="form-checkbox h-5 w-5 text-brand-orange">
                        <span class="text-sm font-medium text-gray-700">Marcar como Concluído</span>
                    </label>
                </div>
            </div>

             <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="qe_status" class="w-full rounded border-gray-300 shadow-sm focus:border-brand-orange focus:ring focus:ring-brand-orange focus:ring-opacity-50">
                    <option value="Planejado">Planejado</option>
                    <option value="Em Andamento">Em Andamento</option>
                    <option value="Concluído">Concluído</option>
                    <option value="Pendente">Pendente</option>
                </select>
            </div>

            <div class="flex justify-end pt-4 gap-2">
                <button type="button" onclick="closeQuickEdit()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-brand-orange text-white rounded hover:bg-orange-700 transition-colors flex items-center gap-2">
                    <span data-feather="save" class="w-4 h-4"></span>
                    <span>Salvar Alterações</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($importEnabled && !$importAlreadyRun && (($_SESSION['user']['tipo_acesso'] ?? '') === 'instituto')): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var openBtn = document.getElementById('clienteImportOpenBtn');
  var modal = document.getElementById('clienteImportModal');
  var overlay = document.getElementById('clienteImportOverlay');
  var closeBtn = document.getElementById('clienteImportCloseBtn');
  var cancelBtn = document.getElementById('clienteImportCancelBtn');
  var form = document.getElementById('clienteImportForm');
  var fileInput = document.getElementById('clienteImportFile');
  var statusEl = document.getElementById('clienteImportStatus');
  var submitBtn = document.getElementById('clienteImportSubmitBtn');

  function openModal() {
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (statusEl) {
      statusEl.textContent = '';
      statusEl.className = 'text-xs mt-1 min-h-[1.25rem]';
    }
    if (fileInput) {
      fileInput.value = '';
    }
    if (submitBtn) {
      submitBtn.disabled = false;
    }
    if (window.feather) {
      window.feather.replace();
    }
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!form || !fileInput || !submitBtn || !statusEl) return;
    if (!fileInput.files || !fileInput.files.length) {
      statusEl.textContent = 'Selecione um arquivo para importação.';
      statusEl.className = 'text-xs mt-1 text-red-700';
      return;
    }
    var file = fileInput.files[0];
    var parts = file.name.split('.');
    var ext = parts.length > 1 ? parts.pop().toLowerCase() : '';
    if (ext !== 'xlsx' && ext !== 'csv') {
      statusEl.textContent = 'Apenas arquivos XLSX ou CSV são aceitos.';
      statusEl.className = 'text-xs mt-1 text-red-700';
      return;
    }
    var fd = new FormData(form);
    submitBtn.disabled = true;
    statusEl.innerHTML = '<span class="inline-flex items-center gap-2 text-gray-700"><span class="animate-spin rounded-full h-4 w-4 border-b-2 border-brand-orange"></span><span>Processando importação, aguarde...</span></span>';
    statusEl.className = 'text-xs mt-1';
    try {
      var resp = await fetch('index.php?route=planoacao/importRun&ajax=1', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      var ct = resp.headers.get('Content-Type') || '';
      var isJson = ct.indexOf('application/json') !== -1;
      var data = null;
      if (isJson) {
        data = await resp.json();
      } else {
        var text = await resp.text();
        data = { success: resp.ok, message: text };
      }
      if (!data.success) {
        statusEl.textContent = data.message || 'Falha ao realizar importação.';
        statusEl.className = 'text-xs mt-1 text-red-700';
        submitBtn.disabled = false;
        return;
      }
      var report = data.report || {};
      var imported = report.imported || 0;
      var ignored = report.ignored || 0;
      var errors = report.errors || 0;
      statusEl.textContent = 'Importação concluída. Importados: ' + imported + ', ignorados: ' + ignored + ', com erro: ' + errors + '.';
      statusEl.className = 'text-xs mt-1 text-green-700';
      setTimeout(function () {
        window.location.reload();
      }, 2000);
    } catch (err) {
      statusEl.textContent = 'Erro inesperado ao realizar importação.';
      statusEl.className = 'text-xs mt-1 text-red-700';
      submitBtn.disabled = false;
    }
  }

  if (openBtn) {
    openBtn.addEventListener('click', openModal);
  }
  if (overlay) {
    overlay.addEventListener('click', closeModal);
  }
  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', closeModal);
  }
  if (form) {
    form.addEventListener('submit', handleSubmit);
  }
});
</script>
<?php endif; ?>

<script>
function openQuickEdit(task) {
    document.getElementById('qe_id').value = task.id;
    document.getElementById('qe_titulo').value = task.titulo || '';
    document.getElementById('qe_descricao').value = task.descricao || '';
    document.getElementById('qe_meta_valor').value = task.meta_valor || '';
    document.getElementById('qe_meta_unidade').value = task.meta_unidade || '';
    document.getElementById('qe_prazo').value = task.prazo || '';
    document.getElementById('qe_responsavel').value = task.responsavel || '';
    document.getElementById('qe_status').value = task.status || 'Planejado';
    
    // Checkbox logic
    const isDone = (parseInt(task.progresso) || 0) >= 100 || task.status === 'Concluído';
    document.getElementById('qe_concluido').checked = isDone;

    document.getElementById('quickEditModal').classList.remove('hidden');
    if (typeof feather !== 'undefined') feather.replace();
}

function closeQuickEdit() {
    document.getElementById('quickEditModal').classList.add('hidden');
}

document.getElementById('quickEditForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-spin mr-2">⟳</span> Salvando...';

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => { throw new Error(data.message || 'Erro HTTP ' + response.status); });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar alterações');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocorreu um erro ao salvar as alterações: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>

</div>
