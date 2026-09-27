<?php use App\Core\DateHelper; ?>
<?php
/** @var array $ciclo */
/** @var array $participantes */
/** @var array $participanteIds */
/** @var array $colaboradoresDisponiveis */
/** @var array $departamentos */
$statusLabels = \App\Models\PessoaCicloAvaliacaoModel::statusOptions();
$avaliacaoStatusLabels = [
    'pendente' => 'Pendente',
    'em_andamento' => 'Em andamento',
    'finalizada' => 'Finalizada',
];
$avaliacaoStatusClasses = [
    'pendente' => 'bg-gray-100 text-gray-600',
    'em_andamento' => 'bg-amber-100 text-amber-800',
    'finalizada' => 'bg-green-100 text-green-700',
];
?>
<div class="p-4 md:p-6 space-y-6">
  <?php $pessoasSubnavAtivo = 'avaliacoes'; require __DIR__ . '/../_subnav.php'; ?>
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($ciclo['nome']) ?></h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($ciclo['modelo_nome']) ?> · <?= htmlspecialchars($statusLabels[$ciclo['status']] ?? $ciclo['status']) ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if ($ciclo['status'] === 'rascunho'): ?>
        <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/cicloEdit&id=<?= (int)$ciclo['id'] ?>">Editar dados</a>
        <form method="post" action="index.php?route=pessoas/cicloAbrir" onsubmit="return confirm('Abrir este ciclo? Depois de aberto, os participantes não podem mais ser alterados.');">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="id" value="<?= (int)$ciclo['id'] ?>" />
          <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white" <?= empty($participantes) ? 'disabled title="Selecione ao menos 1 participante"' : '' ?>>Abrir ciclo</button>
        </form>
      <?php elseif ($ciclo['status'] === 'aberto'): ?>
        <form method="post" action="index.php?route=pessoas/cicloEncerrar" onsubmit="return confirm('Encerrar este ciclo? Avaliações não finalizadas continuarão como estão, mas o ciclo passa a ser somente consulta.');">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="id" value="<?= (int)$ciclo['id'] ?>" />
          <button type="submit" class="px-4 py-3 rounded-lg bg-brand-brown text-white">Encerrar ciclo</button>
        </form>
      <?php endif; ?>
      <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/index&empresa_id=<?= (int)$ciclo['empresa_id'] ?>">Voltar</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <?php if ($ciclo['status'] === 'rascunho'): ?>
    <div class="bg-white shadow rounded-xl p-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Selecionar participantes</h2>
        <div class="flex gap-2 text-xs">
          <button type="button" id="pessoasSelAll" class="px-2 py-1 rounded bg-gray-200 text-brand-brown">Selecionar todos</button>
          <button type="button" id="pessoasSelNone" class="px-2 py-1 rounded bg-gray-200 text-brand-brown">Limpar seleção</button>
        </div>
      </div>
      <?php if (empty($colaboradoresDisponiveis)): ?>
        <div class="text-sm text-gray-600">Nenhum colaborador ativo cadastrado para esta empresa.</div>
      <?php else: ?>
        <form method="post" action="index.php?route=pessoas/cicloParticipantesStore">
          <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
          <input type="hidden" name="id" value="<?= (int)$ciclo['id'] ?>" />
          <?php if (!empty($departamentos)): ?>
            <div class="mb-3">
              <label class="block text-xs text-gray-600 mb-1">Filtrar por departamento</label>
              <select id="pessoasDeptFilter" class="border rounded p-2 text-sm">
                <option value="">Todos</option>
                <?php foreach ($departamentos as $d): ?>
                  <option value="<?= htmlspecialchars($d['nome']) ?>"><?= htmlspecialchars($d['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-auto border rounded p-3" id="pessoasColabList">
            <?php foreach ($colaboradoresDisponiveis as $col): ?>
              <label class="flex items-center gap-2 p-2 rounded border bg-white text-sm" data-departamento="<?= htmlspecialchars((string)($col['departamento'] ?? '')) ?>">
                <input type="checkbox" name="colaborador_ids[]" value="<?= (int)$col['id'] ?>" class="pessoas-colab-checkbox" <?= in_array((int)$col['id'], $participanteIds, true) ? 'checked' : '' ?> />
                <span class="min-w-0">
                  <span class="block truncate"><?= htmlspecialchars($col['nome']) ?></span>
                  <span class="block text-xs text-gray-500 truncate"><?= htmlspecialchars((string)($col['departamento'] ?? '')) ?> · <?= htmlspecialchars((string)($col['setor'] ?? '')) ?> · <?= htmlspecialchars((string)($col['funcao'] ?? '')) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="mt-3">
            <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white">Salvar participantes</button>
          </div>
        </form>
        <script>
          (function () {
            document.getElementById('pessoasSelAll')?.addEventListener('click', () => {
              document.querySelectorAll('.pessoas-colab-checkbox').forEach((el) => { el.checked = true; });
            });
            document.getElementById('pessoasSelNone')?.addEventListener('click', () => {
              document.querySelectorAll('.pessoas-colab-checkbox').forEach((el) => { el.checked = false; });
            });
            document.getElementById('pessoasDeptFilter')?.addEventListener('change', (e) => {
              const value = e.target.value;
              document.querySelectorAll('#pessoasColabList > label').forEach((label) => {
                label.style.display = (!value || label.getAttribute('data-departamento') === value) ? '' : 'none';
              });
            });
          })();
        </script>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="bg-white shadow rounded-xl overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b bg-gray-50">
            <th class="p-3">Colaborador</th>
            <th class="p-3">Departamento / Setor / Função</th>
            <th class="p-3">Status</th>
            <th class="p-3">Resultado</th>
            <th class="p-3 text-right">Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($participantes as $p): ?>
            <tr class="border-b">
              <td class="p-3 font-medium"><?= htmlspecialchars($p['nome']) ?></td>
              <td class="p-3 text-xs text-gray-500"><?= htmlspecialchars((string)($p['departamento'] ?? '')) ?> / <?= htmlspecialchars((string)($p['setor'] ?? '')) ?> / <?= htmlspecialchars((string)($p['funcao'] ?? '')) ?></td>
              <td class="p-3">
                <span class="px-2 py-1 rounded text-xs <?= $avaliacaoStatusClasses[$p['avaliacao_status']] ?? 'bg-gray-100 text-gray-600' ?>"><?= htmlspecialchars($avaliacaoStatusLabels[$p['avaliacao_status']] ?? (string)$p['avaliacao_status']) ?></span>
              </td>
              <td class="p-3"><?= $p['resultado'] !== null ? number_format((float)$p['resultado'], 2, ',', '.') . ' / 5,00' : '—' ?></td>
              <td class="p-3 text-right">
                <?php if ($p['avaliacao_status'] === 'finalizada'): ?>
                  <a class="text-brand-pink font-semibold" href="index.php?route=pessoas/avaliacaoResultado&id=<?= (int)$p['avaliacao_id'] ?>">Ver resultado</a>
                <?php elseif ($p['avaliacao_status'] === 'em_andamento'): ?>
                  <a class="text-brand-pink font-semibold" href="index.php?route=pessoas/avaliacaoResponder&id=<?= (int)$p['avaliacao_id'] ?>">Continuar</a>
                <?php elseif ($ciclo['status'] === 'aberto'): ?>
                  <form method="post" action="index.php?route=pessoas/avaliacaoIniciar" class="inline">
                    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                    <input type="hidden" name="id" value="<?= (int)$p['avaliacao_id'] ?>" />
                    <button type="submit" class="text-brand-pink font-semibold">Iniciar avaliação</button>
                  </form>
                <?php else: ?>
                  <span class="text-gray-400 text-xs">Ciclo encerrado</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
