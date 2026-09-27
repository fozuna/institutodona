<?php
/**
 * Paginação das listagens operacionais: preserva todos os filtros ativos
 * (query string montada só com parâmetros já validados pelo Controller).
 *
 * @var string $listaRota @var array $qs @var int $total @var int $page @var int $perPage @var string $listaRotulo
 */
$ppPaginas = max(1, (int)ceil($total / max(1, $perPage)));
$ppUrl = static fn(int $ppPagina): string => 'index.php?' . http_build_query(array_merge(['route' => $listaRota], $qs, ['page' => $ppPagina]));
?>
<div class="flex flex-wrap items-center justify-between gap-2 text-sm">
  <span class="text-gray-600"><?= (int)$total ?> <?= htmlspecialchars($listaRotulo) ?> — página <?= (int)$page ?> de <?= (int)$ppPaginas ?></span>
  <?php if ($ppPaginas > 1): ?>
    <div class="flex gap-2">
      <?php if ($page > 1): ?><a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($ppUrl($page - 1)) ?>">Anterior</a><?php endif; ?>
      <?php if ($page < $ppPaginas): ?><a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="<?= htmlspecialchars($ppUrl($page + 1)) ?>">Próxima</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
