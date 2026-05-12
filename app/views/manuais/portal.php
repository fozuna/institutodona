<?php /** @var array|null $empresa */ /** @var array $items */ /** @var array $departamentos */ ?>
<?php /** @var int $selectedDepartamento */ /** @var string $q */ /** @var string $dataDe */ /** @var string $dataAte */ ?>
<?php /** @var int $page */ /** @var int $totalPages */ /** @var int $total */ ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portal da Biblioteca</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
  <div class="max-w-6xl mx-auto p-4 md:p-6">
    <?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); if ($basePath === '.' || $basePath === '/' || $basePath === '\\') { $basePath = ''; } ?>
    <div class="bg-white shadow rounded p-5 mb-4">
      <h1 class="text-2xl font-bold">Portal da Biblioteca</h1>
      <p class="text-sm text-gray-600 mt-1">
        Empresa: <strong><?= htmlspecialchars((string)($empresa['nome_empresa'] ?? 'Cliente')) ?></strong>
      </p>
      <p class="text-sm text-gray-600">Acesso restrito aos itens autorizados para esta empresa.</p>
    </div>

    <form method="get" action="index.php" class="bg-white shadow rounded p-4 mb-4">
      <input type="hidden" name="route" value="manuais/portal" />
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm mb-1">Departamento</label>
          <select name="departamento_id" class="border rounded p-2 w-full">
            <option value="">Todos</option>
            <?php foreach ($departamentos as $dep): ?>
              <option value="<?= (int)$dep['id'] ?>" <?= $selectedDepartamento === (int)$dep['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($dep['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm mb-1">Palavra-chave</label>
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="border rounded p-2 w-full" />
        </div>
        <div>
          <label class="block text-sm mb-1">Data inicial</label>
          <input type="date" name="data_de" value="<?= htmlspecialchars($dataDe) ?>" class="border rounded p-2 w-full" />
        </div>
        <div>
          <label class="block text-sm mb-1">Data final</label>
          <input type="date" name="data_ate" value="<?= htmlspecialchars($dataAte) ?>" class="border rounded p-2 w-full" />
        </div>
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white">Filtrar</button>
        <a href="index.php?route=manuais/portal" class="px-4 py-2 rounded bg-gray-200 text-gray-800">Limpar</a>
      </div>
    </form>

    <div class="bg-white shadow rounded overflow-x-auto">
      <div class="px-4 py-3 border-b text-sm text-gray-600">Total de itens: <?= (int)$total ?></div>
      <table class="min-w-full">
        <thead>
          <tr class="text-left border-b">
            <th class="p-3">Nome</th>
            <th class="p-3">Descrição</th>
            <th class="p-3">Empresa</th>
            <th class="p-3">Departamento</th>
            <th class="p-3">Data</th>
            <th class="p-3">Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr><td colspan="6" class="p-4 text-sm text-gray-600">Nenhum item encontrado.</td></tr>
          <?php else: ?>
            <?php foreach ($items as $manual): ?>
              <tr class="border-b">
                <td class="p-3 font-medium"><?= htmlspecialchars($manual['nome']) ?></td>
                <td class="p-3"><?= htmlspecialchars((string)($manual['descricao'] ?? '—')) ?></td>
                <td class="p-3"><?= htmlspecialchars($manual['empresa_nome']) ?></td>
                <td class="p-3"><?= htmlspecialchars($manual['departamento_nome']) ?></td>
                <td class="p-3"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$manual['created_at']))) ?></td>
                <td class="p-3">
                  <a class="px-3 py-2 rounded bg-red-600 text-white inline-block" href="<?= $basePath ?>/biblioteca/download/<?= (int)$manual['id'] ?>">Baixar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="mt-4 flex flex-wrap gap-2">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php
            $query = http_build_query([
              'route' => 'manuais/portal',
              'page' => $p,
              'departamento_id' => $selectedDepartamento ?: null,
              'q' => $q !== '' ? $q : null,
              'data_de' => $dataDe !== '' ? $dataDe : null,
              'data_ate' => $dataAte !== '' ? $dataAte : null,
            ]);
          ?>
          <a href="index.php?<?= $query ?>" class="px-3 py-2 rounded <?= $p === $page ? 'bg-red-600 text-white' : 'bg-white text-gray-800 shadow' ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
