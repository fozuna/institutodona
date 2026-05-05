<?php /** @var array $item */ /** @var array $linked */ /** @var array $availableColaboradores */ /** @var array $eligibleRows */ /** @var array $eligibleFilters */ /** @var array $agendas */ /** @var array $pendingParticipants */ /** @var array $alerts */ /** @var array $unidades */ /** @var array $usuarios */ /** @var array $setores */ /** @var array $funcoes */ /** @var array $statusAtualOptions */ ?>
<div class="p-6 space-y-6">
  <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold"><?= htmlspecialchars($item['nome']) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars(($item['unidade_nome'] ?? '') . ' • ' . ($item['departamento_nome'] ?? '')) ?></p>
      <p class="text-xs text-gray-500 mt-1">Tipo: <?= htmlspecialchars((string)($item['tipo_treinamento'] ?? 'Não informado')) ?> • Periodicidade: <?= htmlspecialchars(\App\Models\TreinamentoModel::periodicidadeOptions()[$item['periodicidade'] ?? 'avulso'] ?? 'Avulso') ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/index">Voltar</a>
      <a class="px-4 py-2 rounded bg-brand-red text-white" href="index.php?route=treinamentos/edit&id=<?= (int)$item['id'] ?>">Editar</a>
      <a class="px-4 py-2 rounded bg-blue-100 text-blue-700" href="index.php?route=treinamentos/dashboard">Dashboard</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Carga horária</div><div class="text-2xl font-bold text-brand-black"><?= htmlspecialchars((string)($item['carga_horaria'] ?? '0')) ?>h</div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Elegíveis filtrados</div><div class="text-2xl font-bold text-brand-black"><?= count($eligibleRows) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Vínculos ativos</div><div class="text-2xl font-bold text-brand-black"><?= count($linked) ?></div></div>
    <div class="bg-white shadow rounded p-4"><div class="text-xs text-gray-500 uppercase">Agendamentos</div><div class="text-2xl font-bold text-brand-black"><?= count($agendas) ?></div></div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
      <div class="bg-white shadow rounded p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
          <div>
            <h2 class="text-lg font-semibold">Dados do Treinamento</h2>
            <p class="text-sm text-gray-500">Template de certificado, escopo do público e dados operacionais.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <a class="px-3 py-2 rounded bg-red-100 text-red-700" href="index.php?route=treinamentos/export_elegiveis&id=<?= (int)$item['id'] ?>&format=pdf&setor_id=<?= (int)($eligibleFilters['setor_id'] ?? 0) ?>&funcao_id=<?= (int)($eligibleFilters['funcao_id'] ?? 0) ?>&data_admissao_inicio=<?= urlencode((string)($eligibleFilters['data_admissao_inicio'] ?? '')) ?>&data_admissao_fim=<?= urlencode((string)($eligibleFilters['data_admissao_fim'] ?? '')) ?>&status_atual=<?= urlencode((string)($eligibleFilters['status_atual'] ?? '')) ?>&status_elegibilidade=<?= urlencode((string)($eligibleFilters['status_elegibilidade'] ?? '')) ?>">Exportar PDF</a>
            <a class="px-3 py-2 rounded bg-emerald-100 text-emerald-700" href="index.php?route=treinamentos/export_elegiveis&id=<?= (int)$item['id'] ?>&format=xlsx&setor_id=<?= (int)($eligibleFilters['setor_id'] ?? 0) ?>&funcao_id=<?= (int)($eligibleFilters['funcao_id'] ?? 0) ?>&data_admissao_inicio=<?= urlencode((string)($eligibleFilters['data_admissao_inicio'] ?? '')) ?>&data_admissao_fim=<?= urlencode((string)($eligibleFilters['data_admissao_fim'] ?? '')) ?>&status_atual=<?= urlencode((string)($eligibleFilters['status_atual'] ?? '')) ?>&status_elegibilidade=<?= urlencode((string)($eligibleFilters['status_elegibilidade'] ?? '')) ?>">Exportar XLSX</a>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div><span class="text-gray-500">Objetivo:</span><div><?= nl2br(htmlspecialchars((string)($item['objetivo'] ?? '—'))) ?></div></div>
          <div><span class="text-gray-500">Público:</span><div><?= htmlspecialchars((string)($item['publico'] ?? '—')) ?></div></div>
          <div><span class="text-gray-500">Fornecedor:</span><div><?= htmlspecialchars((string)($item['fornecedor'] ?? '—')) ?></div></div>
          <div><span class="text-gray-500">Assinatura:</span><div><?= htmlspecialchars((string)($item['assinatura_responsavel'] ?? '—')) ?></div></div>
          <div class="md:col-span-2"><span class="text-gray-500">Template do Certificado:</span><div><?= nl2br(htmlspecialchars((string)($item['template_certificado'] ?? 'Template padrão do sistema.'))) ?></div></div>
        </div>
      </div>

      <div class="bg-white shadow rounded p-5 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold">Lista de Elegíveis e Pré-Cadastro</h2>
            <p class="text-sm text-gray-500">Filtros avançados para exportação e seleção de participantes elegíveis.</p>
          </div>
        </div>

        <form method="get" action="index.php" class="grid grid-cols-1 md:grid-cols-6 gap-3">
          <input type="hidden" name="route" value="treinamentos/show" />
          <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
          <div>
            <label class="block text-sm">Setor</label>
            <select name="setor_id" class="border rounded p-2 w-full">
              <option value="0">Todos</option>
              <?php foreach ($setores as $setor): ?>
                <option value="<?= (int)$setor['id'] ?>" <?= ((int)($eligibleFilters['setor_id'] ?? 0) === (int)$setor['id']) ? 'selected' : '' ?>><?= htmlspecialchars($setor['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm">Cargo</label>
            <select name="funcao_id" class="border rounded p-2 w-full">
              <option value="0">Todos</option>
              <?php foreach ($funcoes as $funcao): ?>
                <option value="<?= (int)$funcao['id'] ?>" <?= ((int)($eligibleFilters['funcao_id'] ?? 0) === (int)$funcao['id']) ? 'selected' : '' ?>><?= htmlspecialchars($funcao['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm">Admissão Inicial</label>
            <input type="date" name="data_admissao_inicio" value="<?= htmlspecialchars((string)($eligibleFilters['data_admissao_inicio'] ?? '')) ?>" class="border rounded p-2 w-full" />
          </div>
          <div>
            <label class="block text-sm">Admissão Final</label>
            <input type="date" name="data_admissao_fim" value="<?= htmlspecialchars((string)($eligibleFilters['data_admissao_fim'] ?? '')) ?>" class="border rounded p-2 w-full" />
          </div>
          <div>
            <label class="block text-sm">Status Atual</label>
            <select name="status_atual" class="border rounded p-2 w-full">
              <option value="">Todos</option>
              <?php foreach ($statusAtualOptions as $status): ?>
                <option value="<?= htmlspecialchars($status) ?>" <?= (($eligibleFilters['status_atual'] ?? '') === $status) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($status)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm">Elegibilidade</label>
            <select name="status_elegibilidade" class="border rounded p-2 w-full">
              <option value="">Todos</option>
              <option value="Elegivel" <?= (($eligibleFilters['status_elegibilidade'] ?? '') === 'Elegivel') ? 'selected' : '' ?>>Elegível</option>
              <option value="Inelegivel" <?= (($eligibleFilters['status_elegibilidade'] ?? '') === 'Inelegivel') ? 'selected' : '' ?>>Inelegível</option>
            </select>
          </div>
          <div class="md:col-span-6 flex flex-wrap gap-2">
            <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Aplicar Filtros</button>
            <a class="px-4 py-2 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/show&id=<?= (int)$item['id'] ?>">Limpar</a>
          </div>
        </form>

        <form method="post" action="index.php?route=treinamentos/add_colaboradores" class="space-y-4">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="treinamento_id" value="<?= (int)$item['id'] ?>" />
          <div class="overflow-auto max-h-[420px] border rounded">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 sticky top-0">
                <tr class="border-b text-left">
                  <th class="p-2">Selecionar</th>
                  <th class="p-2">Nome</th>
                  <th class="p-2">Matrícula</th>
                  <th class="p-2">Setor</th>
                  <th class="p-2">Cargo</th>
                  <th class="p-2">CPF</th>
                  <th class="p-2">E-mail</th>
                  <th class="p-2">Status</th>
                  <th class="p-2">Elegibilidade</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($eligibleRows as $row): ?>
                  <tr class="border-b">
                    <td class="p-2"><input type="checkbox" name="colaborador_ids[]" value="<?= (int)$row['id'] ?>" <?= !empty($row['pre_cadastrado']) ? 'checked' : '' ?> /></td>
                    <td class="p-2"><?= htmlspecialchars((string)$row['nome']) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string)($row['matricula'] ?? '—')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string)($row['setor'] ?? '—')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string)($row['cargo'] ?? '—')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string)($row['cpf'] ?? '—')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string)($row['email_corporativo'] ?? '—')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string)($row['status_atual'] ?? '—')) ?></td>
                    <td class="p-2"><span class="px-2 py-1 rounded-full text-xs <?= ($row['status_elegibilidade'] ?? '') === 'Elegivel' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= htmlspecialchars((string)($row['status_elegibilidade'] ?? '—')) ?></span></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($eligibleRows)): ?>
                  <tr><td colspan="9" class="p-4 text-center text-gray-500">Nenhum colaborador encontrado para os filtros informados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="text-xs text-gray-500">A emissão antecipada de certificado só será possível depois que o colaborador for incluído na lista pré-cadastrada do agendamento.</div>
          <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Atualizar Pré-Cadastro de Colaboradores</button>
        </form>
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
            <label class="block text-sm">Participantes iniciais</label>
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
                <th class="p-2">Participantes</th>
                <th class="p-2">Presença</th>
                <th class="p-2">Certificados</th>
                <th class="p-2">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($agendas as $agenda): ?>
                <tr class="border-b">
                  <td class="p-2"><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)$agenda['data'])) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)$agenda['unidade_nome']) ?></td>
                  <td class="p-2"><?= htmlspecialchars((string)($agenda['instrutor'] ?: ($agenda['responsavel_nome'] ?? '—'))) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_participantes'] ?? 0) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_presentes'] ?? 0) ?></td>
                  <td class="p-2"><?= (int)($agenda['total_certificados'] ?? 0) ?></td>
                  <td class="p-2">
                    <div class="flex flex-wrap gap-2">
                      <a class="px-3 py-1 rounded bg-gray-200 text-brand-brown" href="index.php?route=treinamentos/presenca&agenda_id=<?= (int)$agenda['id'] ?>">Operar Lista</a>
                      <a class="px-3 py-1 rounded bg-red-100 text-red-700" href="index.php?route=treinamentos/presenca_pdf&agenda_id=<?= (int)$agenda['id'] ?>">PDF Presença</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($agendas)): ?>
                <tr><td colspan="7" class="p-4 text-center text-gray-500">Nenhum agendamento criado até o momento.</td></tr>
              <?php endif; ?>
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
        <h2 class="text-lg font-semibold mb-3">Status dos Vínculos</h2>
        <div class="space-y-3 max-h-[360px] overflow-auto">
          <?php foreach ($linked as $row): ?>
            <div class="rounded border p-3">
              <div class="font-medium"><?= htmlspecialchars($row['colaborador_nome']) ?></div>
              <div class="text-sm text-gray-600"><?= htmlspecialchars((string)($row['funcao_nome'] ?? '—')) ?> • <?= htmlspecialchars((string)($row['setor_nome'] ?? '—')) ?></div>
              <div class="flex items-center justify-between mt-2">
                <span class="px-2 py-1 rounded-full text-xs <?= ($row['status'] ?? '') === 'concluido' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' ?>"><?= htmlspecialchars(strtoupper((string)$row['status'])) ?></span>
                <span class="text-xs text-gray-500"><?= htmlspecialchars(\App\Core\DateHelper::formatDateTime((string)($row['ultima_conclusao'] ?? ''))) ?: 'Sem conclusão' ?></span>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($linked)): ?>
            <p class="text-sm text-gray-500">Nenhum colaborador vinculado ao treinamento.</p>
          <?php endif; ?>
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
