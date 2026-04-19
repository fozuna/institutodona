<?php /** @var array $item */ /** @var array $linked */ /** @var array $availableColaboradores */ /** @var array $agendas */ /** @var array $pendingParticipants */ /** @var array $alerts */ /** @var array $unidades */ /** @var array $usuarios */ ?>
<div class="p-6 space-y-6">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($item['nome']) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars(($item['unidade_nome'] ?? '') . ' • ' . ($item['departamento_nome'] ?? '')) ?></p>
    </div>
    <div class="flex items-center gap-2">
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/index">Voltar</a>
      <a class="px-4 py-2 rounded bg-brand-red text-white" href="index.php?route=treinamentos/edit&id=<?= (int)$item['id'] ?>">Editar</a>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-2">Dados do Treinamento</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div><span class="text-gray-500">Objetivo:</span><div><?= nl2br(htmlspecialchars((string)($item['objetivo'] ?? '—'))) ?></div></div>
          <div><span class="text-gray-500">Público:</span><div><?= htmlspecialchars((string)($item['publico'] ?? '—')) ?></div></div>
          <div><span class="text-gray-500">Carga Horária:</span><div><?= htmlspecialchars((string)($item['carga_horaria'] ?? '—')) ?></div></div>
          <div><span class="text-gray-500">Periodicidade:</span><div><?= htmlspecialchars(\App\Models\TreinamentoModel::periodicidadeOptions()[$item['periodicidade'] ?? 'avulso'] ?? 'Avulso') ?></div></div>
          <div><span class="text-gray-500">Fornecedor:</span><div><?= htmlspecialchars((string)($item['fornecedor'] ?? '—')) ?></div></div>
          <div><span class="text-gray-500">Criado em:</span><div><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($item['created_at'] ?? ''))) ?></div></div>
        </div>
      </div>

      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-4">Vincular Colaboradores</h2>
        <form method="post" action="index.php?route=treinamentos/add_colaboradores" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="treinamento_id" value="<?= (int)$item['id'] ?>" />
          <select name="colaborador_ids[]" multiple size="8" class="border rounded p-2 w-full">
            <?php foreach ($availableColaboradores as $colaborador): ?>
              <option value="<?= (int)$colaborador['id'] ?>">
                <?= htmlspecialchars($colaborador['nome'] . ' • ' . ($colaborador['funcao_nome'] ?? 'Sem função') . ' • ' . ($colaborador['setor_nome'] ?? 'Sem setor')) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="text-xs text-gray-500">A vinculação é livre e não restringe por função ou setor.</div>
          <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Vincular Selecionados</button>
        </form>

        <div class="mt-5 overflow-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b text-left">
                <th class="p-2">Colaborador</th>
                <th class="p-2">Função</th>
                <th class="p-2">Unidade</th>
                <th class="p-2">Status</th>
                <th class="p-2">Última Conclusão</th>
                <th class="p-2">Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($linked as $row): ?>
                <tr class="border-b">
                  <td class="p-2"><?= htmlspecialchars($row['colaborador_nome']) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)($row['funcao_nome'] ?? '—')) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)($row['unidade_nome'] ?? '—')) ?></td>
                  <td class="p-2"><span class="px-2 py-1 rounded-full text-xs <?= ($row['status'] ?? '') === 'concluido' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>"><?= htmlspecialchars(strtoupper((string)$row['status'])) ?></span></td>
                  <td class="p-2"><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($row['ultima_conclusao'] ?? ''))) ?: '—' ?></td>
                  <td class="p-2">
                    <?php if (($row['status'] ?? '') === 'pendente'): ?>
                      <form method="post" action="index.php?route=treinamentos/remove_colaborador" onsubmit="return confirm('Remover vínculo pendente?');">
                        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                        <input type="hidden" name="treinamento_id" value="<?= (int)$item['id'] ?>" />
                        <input type="hidden" name="colaborador_id" value="<?= (int)$row['colaborador_id'] ?>" />
                        <button class="px-3 py-1 rounded bg-red-100 text-red-700" type="submit">Remover</button>
                      </form>
                    <?php else: ?>
                      <span class="text-xs text-gray-500">Histórico preservado</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-4">Agendar Treinamento</h2>
        <form method="post" action="index.php?route=treinamentos/store_agenda" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="treinamento_id" value="<?= (int)$item['id'] ?>" />
          <div>
            <label class="block text-sm">Data / Hora</label>
            <input type="datetime-local" name="data" class="border rounded p-2 w-full" required />
          </div>
          <div>
            <label class="block text-sm">Unidade</label>
            <select name="unidade_id" class="border rounded p-2 w-full" required>
              <?php foreach ($unidades as $unidade): ?>
                <option value="<?= (int)$unidade['id'] ?>" <?= ((int)$item['cliente_id'] === (int)$unidade['id']) ? 'selected' : '' ?>><?= htmlspecialchars($unidade['nome_empresa']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm">Responsável</label>
            <select name="responsavel_id" class="border rounded p-2 w-full">
              <option value="0">—</option>
              <?php foreach ($usuarios as $usuario): ?>
                <option value="<?= (int)$usuario['id'] ?>"><?= htmlspecialchars($usuario['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm">Instrutor</label>
            <input type="text" name="instrutor" class="border rounded p-2 w-full" placeholder="Texto livre" />
          </div>
          <div>
            <label class="block text-sm">Local</label>
            <input type="text" name="local" class="border rounded p-2 w-full" />
          </div>
          <div>
            <label class="block text-sm">Participantes iniciais (pendentes sugeridos)</label>
            <select name="participante_ids[]" multiple size="5" class="border rounded p-2 w-full">
              <?php foreach ($pendingParticipants as $pending): ?>
                <option value="<?= (int)$pending['colaborador_id'] ?>"><?= htmlspecialchars($pending['colaborador_nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm">Observações</label>
            <textarea name="observacoes" class="border rounded p-2 w-full" rows="3"></textarea>
          </div>
          <div class="md:col-span-2">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Criar Agendamento</button>
          </div>
        </form>

        <div class="mt-5 overflow-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b text-left">
                <th class="p-2">Data</th>
                <th class="p-2">Unidade</th>
                <th class="p-2">Instrutor</th>
                <th class="p-2">Local</th>
                <th class="p-2">Participantes</th>
                <th class="p-2">Presença</th>
                <th class="p-2">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($agendas as $agenda): ?>
                <tr class="border-b">
                  <td class="p-2"><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)$agenda['data'])) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)$agenda['unidade_nome']) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)($agenda['instrutor'] ?: ($agenda['responsavel_nome'] ?? '—'))) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)($agenda['local'] ?? '—')) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_participantes'] ?? 0) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_presentes'] ?? 0) ?></td>
                  <td class="p-2">
                    <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/presenca&agenda_id=<?= (int)$agenda['id'] ?>">Lista de Presença</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="space-y-6">
      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-3">Relacionamentos</h2>
        <div class="text-sm">
          <div class="mb-3">
            <div class="text-gray-500">Setores</div>
            <div><?= empty($item['setores']) ? '—' : htmlspecialchars(implode(', ', array_map(static fn($row) => $row['nome'], $item['setores']))) ?></div>
          </div>
          <div>
            <div class="text-gray-500">Funções</div>
            <div><?= empty($item['funcoes']) ? '—' : htmlspecialchars(implode(', ', array_map(static fn($row) => $row['nome'], $item['funcoes']))) ?></div>
          </div>
        </div>
      </div>

      <div class="bg-white shadow rounded p-5">
        <h2 class="text-lg font-semibold mb-3">Alertas de Vencimento</h2>
        <?php if (empty($alerts)): ?>
          <p class="text-sm text-gray-500">Nenhum alerta ativo.</p>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($alerts as $alert): ?>
              <div class="rounded border p-3 <?= ($alert['alerta'] ?? '') === 'vencido' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' ?>">
                <div class="font-medium"><?= htmlspecialchars($alert['colaborador_nome']) ?></div>
                <div class="text-sm text-gray-700"><?= htmlspecialchars($alert['unidade_nome'] ?? '') ?></div>
                <div class="text-xs text-gray-600 mt-1">
                  <?= ($alert['alerta'] ?? '') === 'vencido'
                    ? 'Treinamento vencido'
                    : 'Próximo do vencimento em ' . (int)($alert['dias_restantes'] ?? 0) . ' dia(s)' ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
