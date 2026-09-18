<?php use App\Core\DateHelper; use App\Core\PessoasAvaliacaoScale; use App\Core\PessoasGestaoConfig; ?>
<?php
/** @var array $colaborador */
/** @var array $avaliacoes */
/** @var array $gaps */
/** @var array $feedbacks */
/** @var array $acoes */
/** @var array $usuariosResponsaveis */
$gapStatusLabels = PessoasGestaoConfig::gapStatusLabels();
$gapStatusClasses = PessoasGestaoConfig::gapStatusClasses();
$prioridadeLabels = PessoasGestaoConfig::gapPrioridadeLabels();
$feedbackTipoLabels = PessoasGestaoConfig::feedbackTipoLabels();
$feedbackTipoClasses = PessoasGestaoConfig::feedbackTipoClasses();
$acaoStatusLabels = PessoasGestaoConfig::acaoStatusLabels();
$acaoStatusClasses = PessoasGestaoConfig::acaoStatusClasses();
$csrf = \App\Core\Security::csrfToken();
?>
<div class="p-4 md:p-6 space-y-6 max-w-5xl mx-auto">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl md:text-2xl font-bold text-brand-black">Histórico de Desenvolvimento</h1>
      <p class="text-sm text-gray-600"><?= htmlspecialchars($colaborador['nome']) ?></p>
    </div>
    <a class="px-4 py-2 rounded-lg bg-gray-200 text-brand-brown text-sm" href="index.php?route=colaboradores/index">Voltar a Colaboradores</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <!-- Avaliações -->
  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h2 class="font-semibold mb-3">Avaliações</h2>
    <?php if (empty($avaliacoes)): ?>
      <div class="text-sm text-gray-500">Nenhuma avaliação registrada ainda.</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left border-b bg-gray-50 text-xs text-gray-600">
              <th class="p-2">Ciclo</th>
              <th class="p-2">Modelo</th>
              <th class="p-2">Status</th>
              <th class="p-2">Resultado</th>
              <th class="p-2">Data</th>
              <th class="p-2"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($avaliacoes as $a): ?>
              <tr class="border-b">
                <td class="p-2"><?= htmlspecialchars($a['ciclo_nome']) ?></td>
                <td class="p-2"><?= htmlspecialchars($a['modelo_nome']) ?></td>
                <td class="p-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$a['status']))) ?></td>
                <td class="p-2">
                  <?php if ($a['resultado'] !== null): ?>
                    <?= number_format((float)$a['resultado'], 2, ',', '.') ?> / 5,00 — <?= htmlspecialchars(PessoasAvaliacaoScale::classification((float)$a['resultado'])) ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="p-2"><?= !empty($a['finalizado_em']) ? htmlspecialchars(DateHelper::formatDate((string)$a['finalizado_em'])) : '—' ?></td>
                <td class="p-2 text-right">
                  <?php if ($a['status'] === 'finalizada'): ?>
                    <a class="text-brand-pink font-semibold" href="index.php?route=pessoas/avaliacaoResultado&id=<?= (int)$a['id'] ?>">Ver resultado</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- GAPs -->
  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <div class="flex items-center justify-between mb-3">
      <h2 class="font-semibold">GAPs</h2>
    </div>
    <?php if (empty($gaps)): ?>
      <div class="text-sm text-gray-500 mb-3">Nenhum GAP registrado ainda.</div>
    <?php else: ?>
      <div class="space-y-2 mb-3">
        <?php foreach ($gaps as $g): ?>
          <a href="index.php?route=pessoas/gapShow&id=<?= (int)$g['id'] ?>" class="block border rounded p-3 text-sm hover:bg-gray-50">
            <div class="flex items-center justify-between gap-2">
              <span class="font-medium"><?= htmlspecialchars($g['titulo']) ?></span>
              <span class="px-2 py-0.5 rounded text-xs <?= $gapStatusClasses[$g['status']] ?? '' ?>"><?= htmlspecialchars($gapStatusLabels[$g['status']] ?? $g['status']) ?></span>
            </div>
            <div class="text-xs text-gray-500 mt-1">Prioridade: <?= htmlspecialchars($prioridadeLabels[$g['prioridade']] ?? $g['prioridade']) ?> · <?= $g['origem'] === 'avaliacao' ? 'De avaliação' : 'Manual' ?> · <?= htmlspecialchars(DateHelper::formatDate((string)$g['created_at'])) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <details>
      <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Novo GAP</summary>
      <form method="post" action="index.php?route=pessoas/gapCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
        <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Título" required maxlength="255" />
        <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição" maxlength="2000"></textarea>
        <select name="prioridade" class="border rounded p-2 w-full text-sm">
          <option value="baixa">Baixa</option>
          <option value="media" selected>Média</option>
          <option value="alta">Alta</option>
        </select>
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Registrar GAP manual</button>
      </form>
    </details>
  </div>

  <!-- Feedbacks -->
  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h2 class="font-semibold mb-3">Feedbacks</h2>
    <?php if (empty($feedbacks)): ?>
      <div class="text-sm text-gray-500 mb-3">Nenhum feedback registrado ainda.</div>
    <?php else: ?>
      <div class="space-y-2 mb-3">
        <?php foreach ($feedbacks as $f): ?>
          <div class="border rounded p-3 text-sm">
            <div class="flex items-center justify-between gap-2 mb-1">
              <span class="font-medium"><?= htmlspecialchars($f['titulo']) ?></span>
              <span class="px-2 py-0.5 rounded text-xs <?= $feedbackTipoClasses[$f['tipo']] ?? '' ?>"><?= htmlspecialchars($feedbackTipoLabels[$f['tipo']] ?? $f['tipo']) ?></span>
            </div>
            <p class="text-gray-600"><?= htmlspecialchars(mb_strimwidth($f['descricao'], 0, 180, '…')) ?></p>
            <div class="text-xs text-gray-400 mt-1"><?= htmlspecialchars(DateHelper::formatDate((string)$f['data_feedback'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <details>
      <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Novo Feedback</summary>
      <form method="post" action="index.php?route=pessoas/feedbackCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
        <select name="tipo" class="border rounded p-2 w-full text-sm" required>
          <option value="positivo">Feedback Positivo</option>
          <option value="melhoria">Feedback de Melhoria</option>
        </select>
        <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Título" required maxlength="255" />
        <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição" required maxlength="2000"></textarea>
        <input type="date" name="data_feedback" class="border rounded p-2 w-full text-sm" value="<?= htmlspecialchars(date('Y-m-d')) ?>" />
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Registrar feedback</button>
      </form>
    </details>
  </div>

  <!-- Ações de Melhoria -->
  <div class="bg-white shadow rounded-xl p-4 md:p-6">
    <h2 class="font-semibold mb-3">Ações de Melhoria</h2>
    <?php if (empty($acoes)): ?>
      <div class="text-sm text-gray-500 mb-3">Nenhuma ação de melhoria criada ainda.</div>
    <?php else: ?>
      <div class="space-y-2 mb-3">
        <?php foreach ($acoes as $a): ?>
          <div class="border rounded p-3 text-sm">
            <div class="flex items-center justify-between gap-2 mb-1">
              <span class="font-medium"><?= htmlspecialchars($a['titulo']) ?></span>
              <span class="px-2 py-0.5 rounded text-xs <?= $acaoStatusClasses[$a['status']] ?? '' ?>"><?= htmlspecialchars($acaoStatusLabels[$a['status']] ?? $a['status']) ?></span>
            </div>
            <div class="text-xs text-gray-500">
              Responsável: <?= htmlspecialchars((string)($a['responsavel_nome'] ?? 'Não definido')) ?>
              <?php if (!empty($a['prazo'])): ?> · Prazo: <?= htmlspecialchars(DateHelper::formatDate((string)$a['prazo'])) ?><?php endif; ?>
              <?php if (!empty($a['gap_id'])): ?> · <a class="text-brand-pink" href="index.php?route=pessoas/gapShow&id=<?= (int)$a['gap_id'] ?>">Ver GAP relacionado</a><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <details>
      <summary class="cursor-pointer text-sm text-brand-pink font-semibold">Nova Ação de Melhoria</summary>
      <form method="post" action="index.php?route=pessoas/acaoCreate" class="mt-3 space-y-2 bg-gray-50 rounded p-3">
        <input type="hidden" name="csrf" value="<?= $csrf ?>" />
        <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
        <input type="text" name="titulo" class="border rounded p-2 w-full text-sm" placeholder="Título" required maxlength="255" />
        <textarea name="descricao" class="border rounded p-2 w-full text-sm" rows="2" placeholder="Descrição" maxlength="2000"></textarea>
        <?php if (!empty($usuariosResponsaveis)): ?>
          <select name="responsavel_usuario_id" class="border rounded p-2 w-full text-sm">
            <option value="">Sem responsável definido</option>
            <?php foreach ($usuariosResponsaveis as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs text-gray-600 mb-1">Início</label>
            <input type="date" name="data_inicio" class="border rounded p-2 w-full text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Prazo</label>
            <input type="date" name="prazo" class="border rounded p-2 w-full text-sm" />
          </div>
        </div>
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Criar ação</button>
      </form>
    </details>
  </div>
</div>
