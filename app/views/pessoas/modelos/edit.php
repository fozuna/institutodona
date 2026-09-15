<?php /** @var array $modelo */ /** @var array $estrutura */ /** @var bool $usadoPorCiclo */ ?>
<div class="p-4 md:p-6 space-y-6">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-brand-black"><?= htmlspecialchars($modelo['nome']) ?></h1>
      <p class="text-sm text-gray-600">Organize as perguntas em grupos. A ordem e os pesos definidos aqui valem para os próximos ciclos que usarem este modelo.</p>
    </div>
    <a class="px-4 py-3 rounded-lg bg-gray-200 text-brand-brown" href="index.php?route=pessoas/modelos&empresa_id=<?= (int)$modelo['empresa_id'] ?>">Voltar</a>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <?php if ($usadoPorCiclo): ?>
    <div class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Este modelo já é usado por pelo menos um ciclo. Grupos e perguntas existentes não podem ser removidos (as avaliações já iniciadas guardam sua própria cópia das perguntas), mas você ainda pode adicionar novos grupos/perguntas e editar nome/descrição.</div>
  <?php endif; ?>

  <div class="bg-white shadow rounded-xl p-6">
    <h2 class="font-semibold mb-3">Dados do modelo</h2>
    <form method="post" action="index.php?route=pessoas/modeloUpdate" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="id" value="<?= (int)$modelo['id'] ?>" />
      <div class="md:col-span-1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($modelo['nome']) ?>" class="border border-gray-300 rounded-lg p-3 w-full" required maxlength="180" />
      </div>
      <div class="md:col-span-1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
        <input type="text" name="descricao" value="<?= htmlspecialchars((string)($modelo['descricao'] ?? '')) ?>" class="border border-gray-300 rounded-lg p-3 w-full" maxlength="500" />
      </div>
      <div>
        <button type="submit" class="px-4 py-3 rounded-lg bg-brand-red text-white w-full">Salvar</button>
      </div>
    </form>
  </div>

  <div class="bg-white shadow rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold">Grupos e perguntas</h2>
    </div>

    <?php if (empty($estrutura)): ?>
      <div class="text-sm text-gray-600 mb-4">Nenhum grupo criado ainda.</div>
    <?php endif; ?>

    <div class="space-y-4">
      <?php foreach ($estrutura as $entry): $grupo = $entry['group']; $perguntas = $entry['perguntas']; ?>
        <div class="border rounded-lg p-4">
          <div class="flex items-center justify-between gap-3 mb-3">
            <div>
              <div class="font-semibold"><?= htmlspecialchars($grupo['nome']) ?> <span class="text-xs text-gray-400">(ordem <?= (int)$grupo['ordem'] ?>)</span></div>
              <?php if (!empty($grupo['descricao'])): ?><div class="text-xs text-gray-500"><?= htmlspecialchars($grupo['descricao']) ?></div><?php endif; ?>
            </div>
            <?php if (!$usadoPorCiclo): ?>
              <form method="post" action="index.php?route=pessoas/grupoDelete" onsubmit="return confirm('Remover este grupo e todas as suas perguntas?');">
                <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                <input type="hidden" name="id" value="<?= (int)$grupo['id'] ?>" />
                <button type="submit" class="text-xs text-brand-red">Remover grupo</button>
              </form>
            <?php endif; ?>
          </div>

          <table class="min-w-full text-sm mb-3">
            <thead>
              <tr class="text-left border-b bg-gray-50 text-xs text-gray-600">
                <th class="p-2">Pergunta</th>
                <th class="p-2 w-20">Peso</th>
                <th class="p-2 w-24">Obrigatória</th>
                <th class="p-2 w-16"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($perguntas as $p): ?>
                <tr class="border-b align-top">
                  <form method="post" action="index.php?route=pessoas/perguntaUpdate">
                    <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                    <td class="p-2">
                      <input type="text" name="pergunta" value="<?= htmlspecialchars($p['pergunta']) ?>" class="border rounded p-2 w-full text-sm" required maxlength="500" />
                      <input type="text" name="orientacao" value="<?= htmlspecialchars((string)($p['orientacao'] ?? '')) ?>" class="border rounded p-2 w-full text-xs mt-1" placeholder="Orientação (opcional)" maxlength="500" />
                    </td>
                    <td class="p-2"><input type="number" step="0.01" min="0.01" name="peso" value="<?= htmlspecialchars((string)$p['peso']) ?>" class="border rounded p-2 w-20 text-sm" /></td>
                    <td class="p-2"><input type="checkbox" name="obrigatoria" <?= (int)$p['obrigatoria'] === 1 ? 'checked' : '' ?> /></td>
                    <td class="p-2"><button type="submit" class="text-brand-pink text-xs">Salvar</button></td>
                  </form>
                  <?php if (!$usadoPorCiclo): ?>
                  <td class="p-0">
                    <form method="post" action="index.php?route=pessoas/perguntaDelete" onsubmit="return confirm('Remover esta pergunta?');">
                      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                      <button type="submit" class="text-brand-red text-xs">Remover</button>
                    </form>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <form method="post" action="index.php?route=pessoas/perguntaStore" class="grid grid-cols-1 md:grid-cols-6 gap-2 items-end bg-gray-50 rounded p-3">
            <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
            <input type="hidden" name="grupo_id" value="<?= (int)$grupo['id'] ?>" />
            <div class="md:col-span-3">
              <label class="block text-xs text-gray-600 mb-1">Nova pergunta</label>
              <input type="text" name="pergunta" class="border rounded p-2 w-full text-sm" required maxlength="500" placeholder="Ex.: Comunica informações de forma clara e objetiva?" />
            </div>
            <div class="md:col-span-1">
              <label class="block text-xs text-gray-600 mb-1">Peso</label>
              <input type="number" step="0.01" min="0.01" name="peso" value="1.00" class="border rounded p-2 w-full text-sm" />
            </div>
            <div class="md:col-span-1 flex items-center gap-1 pb-2">
              <input type="checkbox" name="obrigatoria" id="obrig_<?= (int)$grupo['id'] ?>" checked />
              <label for="obrig_<?= (int)$grupo['id'] ?>" class="text-xs text-gray-600">Obrigatória</label>
            </div>
            <div class="md:col-span-1">
              <button type="submit" class="px-3 py-2 rounded bg-brand-brown text-white text-sm w-full">Adicionar</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </div>

    <form method="post" action="index.php?route=pessoas/grupoStore" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-2 items-end border-t pt-4">
      <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
      <input type="hidden" name="modelo_id" value="<?= (int)$modelo['id'] ?>" />
      <div class="md:col-span-3">
        <label class="block text-xs text-gray-600 mb-1">Novo grupo</label>
        <input type="text" name="nome" class="border rounded p-2 w-full text-sm" required maxlength="180" placeholder="Ex.: Comunicação" />
      </div>
      <div class="md:col-span-2">
        <label class="block text-xs text-gray-600 mb-1">Descrição (opcional)</label>
        <input type="text" name="descricao" class="border rounded p-2 w-full text-sm" maxlength="500" />
      </div>
      <div class="md:col-span-1">
        <button type="submit" class="px-3 py-2 rounded bg-brand-red text-white text-sm w-full">Adicionar grupo</button>
      </div>
    </form>
  </div>
</div>
