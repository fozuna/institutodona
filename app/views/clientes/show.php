<?php /** @var array $item */ /** @var array $apps */ /** @var array $metodologias */ ?>
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
        <a class="px-3 py-2 rounded bg-brand-red text-white flex items-center gap-2" href="index.php?route=metodologias/create&cliente=<?= (int)$item['id'] ?>">
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
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=departamentos/index&cliente=<?= (int)$item['id'] ?>">Departamentos</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=setores/index&cliente=<?= (int)$item['id'] ?>">Setores</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=funcoes/index&cliente=<?= (int)$item['id'] ?>">Funções</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=colaboradores/index&cliente=<?= (int)$item['id'] ?>">Colaboradores</a>
        <a class="px-3 py-2 rounded bg-brand-red text-white" href="index.php?route=dashboard/index&cliente=<?= (int)$item['id'] ?>">Tarefas</a>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php
          $cronCount = count((new \App\Models\CronogramaModel())->byCliente((int)$item['id']));
          $planoTasks = (new \App\Models\PlanoAcaoTaskModel())->byCliente((int)$item['id']);
          $planoCount = count($planoTasks);
          $planoDone = array_reduce($planoTasks, function($acc,$t){ return $acc + ((($t['status'] ?? '') === 'Concluído') ? 1 : 0); }, 0);
        ?>
        <a class="card p-4 block" href="index.php?route=cronograma/index&id_cliente=<?= (int)$item['id'] ?>" data-loading>
          <div class="flex items-center justify-between">
            <div class="font-semibold">Cronograma</div>
            <span class="badge"><span data-feather="calendar"></span></span>
          </div>
          <div class="text-sm text-gray-600 mt-2">Cronogramas: <?= $cronCount ?></div>
          <div class="text-xs text-gray-500 mt-1">Clique para abrir cronogramas do cliente</div>
        </a>
        <a class="card p-4 block" href="index.php?route=planoacao/index&cliente=<?= (int)$item['id'] ?>" data-loading>
          <div class="flex items-center justify-between">
            <div class="font-semibold">Planos de Ação</div>
            <span class="badge"><span data-feather="activity"></span></span>
          </div>
          <div class="text-sm text-gray-600 mt-2">Planos: <?= $planoCount ?> · Concluídos: <?= $planoDone ?></div>
          <div class="text-xs text-gray-500 mt-1">Clique para abrir planos de ação do cliente</div>
        </a>
      </div>
      <script>
        document.querySelectorAll('[data-loading]').forEach(function(el){
          el.addEventListener('click', function(){
            el.setAttribute('aria-busy','true');
          });
        });
      </script>
    </div>

    <!-- Seção de Planos de Ação Detalhada -->
    <div class="bg-white shadow rounded mt-6 lg:col-span-2">
      <div class="px-4 py-3 border-b font-semibold flex justify-between items-center">
        <span>Planos de Ação</span>
        <a class="px-3 py-1 rounded bg-brand-red text-white text-sm" href="index.php?route=planoacao/create&cliente=<?= (int)$item['id'] ?>">Novo Plano</a>
      </div>
      <div class="p-4">
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
                  <th class="p-3 font-semibold text-gray-600">Progresso</th>
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
                    <td class="p-3">
                      <div class="flex items-center gap-2">
                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                          <div class="bg-brand-orange h-1.5 rounded-full" style="width: <?= (int)$pt['progresso'] ?>%"></div>
                        </div>
                        <span class="text-xs text-gray-500"><?= (int)$pt['progresso'] ?>%</span>
                      </div>
                    </td>
                    <td class="p-3 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <a class="text-brand-blue hover:bg-blue-50 p-1.5 rounded transition-colors" href="index.php?route=planoacao/show&id=<?= $pt['id'] ?>" title="Ver/Editar">
                          <span data-feather="edit-2" class="w-4 h-4"></span>
                        </a>
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
        <?php endif; ?>
      </div>
    </div>

    <div class="self-start">
      <!-- Seção de aplicar tarefa movida para o menu (aplicacoes/create) -->
      <div class="bg-white shadow rounded">
        <div class="px-4 py-3 border-b font-semibold">Criar nova tarefa</div>
        <div class="p-4">
          <form method="post" action="index.php?route=metodologias/store" class="space-y-3" enctype="multipart/form-data">
            <input type="hidden" name="id_cliente" value="<?= (int)$item['id'] ?>" />
            <label class="block text-sm">Pilar</label>
            <select name="id_pilar" class="w-full" required>
              <?php foreach ($pilares as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <label class="block text-sm">Item do Pilar</label>
            <input name="item_pilar" class="border rounded p-2 w-full" required />
            <label class="block text-sm">Tipo</label>
            <select name="tipo" class="w-full">
              <option value="tarefa">Tarefa</option>
              <option value="manual">Manual</option>
            </select>
            <label class="block text-sm">Arquivo (opcional)</label>
            <input type="file" name="arquivo" class="border rounded p-2 w-full" />
            <div class="text-xs text-gray-500">PDF, DOC, DOCX, XLS, XLSX ou TXT</div>
            <div class="flex items-center gap-3">
              <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">SALVAR</button>
              <button type="button" class="px-4 py-2 rounded bg-gray-200 text-brand-brown" onclick="history.back()">CANCELAR</button>
            </div>
          </form>
        </div>
      </div>
      <div class="bg-white shadow rounded mt-6">
        <div class="px-4 py-3 border-b font-semibold">Biblioteca de Arquivos</div>
        <div class="p-4">
          <?php if (empty($arquivosCliente)): ?>
            <div class="text-sm text-gray-600">Nenhum arquivo enviado ainda.</div>
          <?php else: ?>
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left border-b">
                  <th class="p-2">Arquivo</th>
                  <th class="p-2">Tarefa</th>
                  <th class="p-2">Pilar</th>
                  <th class="p-2">Tipo</th>
                  <th class="p-2">Tamanho</th>
                  <th class="p-2">Data</th>
                  <th class="p-2">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($arquivosCliente as $it): ?>
                  <tr class="border-b">
                    <td class="p-2"><?= htmlspecialchars($it['nome']) ?></td>
                    <td class="p-2"><?= htmlspecialchars($it['tarefa']) ?> (#<?= (int)$it['aplicacao_id'] ?>)</td>
                    <td class="p-2"><?= htmlspecialchars($it['pilar']) ?></td>
                    <td class="p-2"><?= htmlspecialchars($it['mime']) ?></td>
                    <td class="p-2"><?= number_format((int)$it['tamanho']/1024,1) ?> KB</td>
                    <td class="p-2"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($it['uploaded_at']))) ?></td>
                    <td class="p-2">
                      <a class="text-brand-red hover:underline" target="_blank" href="../<?= htmlspecialchars($it['path']) ?>">Abrir</a>
                      <a class="ml-2 hover:underline" href="index.php?route=aplicacoes/show&id=<?= (int)$it['aplicacao_id'] ?>">Tarefa</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
      <div class="bg-white shadow rounded mt-6">
        <div class="px-4 py-3 border-b font-semibold flex justify-between items-center">
          <span>Avaliações</span>
          <a class="px-3 py-1 rounded bg-brand-red text-white" href="index.php?route=avaliacoes/create&cliente=<?= (int)$item['id'] ?>">Nova Avaliação</a>
        </div>
        <div class="p-4">
          <?php if (empty($avaliacoes)): ?>
            <div class="text-sm text-gray-600">Nenhuma avaliação registrada.</div>
          <?php else: ?>
            <table class="min-w-full">
              <thead>
                <tr class="text-left border-b">
                  <th class="p-3">Data</th>
                  <th class="p-3">Financeiro</th>
                  <th class="p-3">Mercado</th>
                  <th class="p-3">Pessoas</th>
                  <th class="p-3">Processo</th>
                  <th class="p-3">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($avaliacoes as $av): ?>
                  <tr class="border-b">
                    <td class="p-3"><?= htmlspecialchars(date('d/m/Y', strtotime($av['created_at']))) ?></td>
                    <td class="p-3"><?= (int)$av['nota_financeiro'] ?>/7</td>
                    <td class="p-3"><?= (int)$av['nota_mercado'] ?>/7</td>
                    <td class="p-3"><?= (int)$av['nota_pessoas'] ?>/7</td>
                    <td class="p-3"><?= (int)$av['nota_processo'] ?>/7</td>
                    <td class="p-3"><a class="text-brand-pink icon-action" href="index.php?route=avaliacoes/show&id=<?= (int)$av['id'] ?>" title="Ver" aria-label="Ver"><span data-feather="bar-chart-2"></span></a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
      <?php if ((int)($item['is_matriz'] ?? 1) === 1): ?>
      <div class="bg-white shadow rounded mt-6">
        <div class="px-4 py-3 border-b font-semibold">Filiais</div>
        <div class="p-4">
          <?php if (empty($filiais)): ?>
            <div class="text-sm text-gray-600 mb-3">Nenhuma filial cadastrada.</div>
          <?php else: ?>
            <ul class="list-disc pl-5 mb-3">
              <?php foreach ($filiais as $f): ?>
                <li><a class="text-brand-red hover:underline" href="index.php?route=clientes/show&id=<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nome_empresa']) ?></a> — CNPJ: <?= htmlspecialchars($f['CNPJ']) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <form method="post" action="index.php?route=clientes/storeFilial" class="space-y-3">
            <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
            <input type="hidden" name="matriz_id" value="<?= (int)$item['id'] ?>" />
            <label class="block text-sm">Nome da Filial</label>
            <input name="nome_empresa" class="border rounded p-2 w-full" required />
            <label class="block text-sm">CNPJ</label>
            <input name="CNPJ" class="border rounded p-2 w-full" required />
            <label class="block text-sm">Contato</label>
            <input name="contato" class="border rounded p-2 w-full" />
            <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white">SALVAR</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
