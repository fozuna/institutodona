<?php /** @var array $items */ ?>
<?php $t = $t ?? (static fn(string $key, array $replace = []): string => \App\Core\I18n::t($key, $replace)); ?>
<?php
$formatValue = $formatValue ?? static function ($value, array $item): string {
    return \App\Core\ValueFormatter::byUnit($value, [
        'simbolo' => $item['unidade_simbolo'] ?? '',
        'tipo' => $item['unidade_tipo'] ?? '',
    ]);
};
$viewMode = $viewMode ?? 'cards';
?>
<?php if (empty($items)): ?>
  <div class="bg-white shadow rounded-xl p-6 text-sm text-gray-600"><?= htmlspecialchars($t('indicadores.empty.index')) ?></div>
<?php else: ?>
  <?php if ($viewMode === 'list'): ?>
    <div class="space-y-3" data-indicadores-view="list">
      <?php foreach ($items as $item): ?>
        <div class="bg-white shadow rounded-xl p-4 border border-gray-100" data-indicador-id="<?= (int)$item['id'] ?>" data-unidade-tipo="<?= htmlspecialchars((string)($item['unidade_tipo'] ?? '')) ?>">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-start">
            <div class="md:col-span-5 min-w-0">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h2 class="text-base font-semibold text-brand-black truncate"><?= htmlspecialchars($item['indicador']) ?></h2>
                  <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars(($item['cliente_nome'] ?? '') . ' · ' . ($item['departamento_nome'] ?? '') . ' · ' . ($item['setor_nome'] ?? '')) ?></p>
                </div>
                <span class="inline-flex shrink-0 px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($item['control_status_class']) ?>">
                  <?= htmlspecialchars($item['control_status_label']) ?>
                </span>
              </div>
              <div class="mt-3 text-sm">
                <div class="text-gray-500 mb-1"><?= htmlspecialchars($t('indicadores.label.responsaveis_vinculados')) ?></div>
                <div class="flex flex-wrap gap-2">
                  <?php if (!empty($item['responsaveis'])): ?>
                    <?php foreach ($item['responsaveis'] as $responsavel): ?>
                      <span class="px-3 py-1 rounded-full bg-brand-red/10 text-brand-red text-xs"><?= htmlspecialchars($responsavel) ?></span>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="text-gray-400"><?= htmlspecialchars($t('indicadores.option.none')) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="md:col-span-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
              <div class="rounded-lg bg-gray-50 p-3 min-w-0">
                <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.valor')) ?></div>
                <div class="font-semibold text-base">
                  <span data-indicador-valor-view class="break-words"><?= htmlspecialchars($formatValue($item['valor'], $item)) ?></span>
                  <input data-indicador-valor-input class="hidden border border-gray-300 rounded-lg p-2 w-full mt-2" type="text" inputmode="decimal" value="<?= $item['valor'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::decimal($item['valor'], 2)) : '' ?>" />
                </div>
              </div>
              <div class="rounded-lg bg-gray-50 p-3 min-w-0">
                <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.periodicidade')) ?></div>
                <div class="font-semibold text-base truncate"><?= htmlspecialchars(\App\Core\I18n::t('indicadores.periodicidade.' . ($item['periodicidade_tipo'] ?? ''), [], null)) ?></div>
              </div>
              <div class="rounded-lg bg-gray-50 p-3 sm:col-span-2 min-w-0">
                <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.periodo')) ?></div>
                <div class="font-semibold text-base truncate"><?= htmlspecialchars(($item['data_inicial'] ?? '') . ' até ' . ($item['data_final'] ?? '')) ?></div>
              </div>
              <div class="rounded-lg bg-gray-50 p-3 sm:col-span-2 min-w-0">
                <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.unidade_medida')) ?></div>
                <div class="font-semibold text-base truncate"><?= htmlspecialchars(($item['unidade_nome'] ?? '') . ' (' . ($item['unidade_tipo'] ?? '') . ')') ?></div>
              </div>
            </div>

            <div class="md:col-span-3 flex md:flex-col items-start md:items-end justify-between gap-3">
              <div class="text-sm text-gray-600">
                <?= htmlspecialchars($t('indicadores.label.valor_minimo')) ?>: <?= $item['valor_minimo'] !== null ? htmlspecialchars($formatValue($item['valor_minimo'], $item)) : '—' ?>
                <span class="hidden md:inline">·</span>
                <span class="md:hidden"><br /></span>
                <?= htmlspecialchars($t('indicadores.label.valor_maximo')) ?>: <?= $item['valor_maximo'] !== null ? htmlspecialchars($formatValue($item['valor_maximo'], $item)) : '—' ?>
              </div>
              <div class="flex items-center gap-3">
                <a class="text-brand-brown icon-action" href="index.php?route=indicadores/historico&id=<?= (int)$item['id'] ?>" title="<?= htmlspecialchars($t('indicadores.action.history')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.history')) ?>">
                  <span data-feather="bar-chart-2"></span>
                </a>
                <button class="text-brand-pink icon-action" type="button" data-indicador-edit title="Editar valor" aria-label="Editar valor">
                  <span data-feather="edit"></span>
                </button>
                <button class="text-brand-brown icon-action opacity-50" type="button" data-indicador-save title="<?= htmlspecialchars($t('indicadores.action.save')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.save')) ?>" disabled>
                  <span data-feather="check"></span>
                </button>
                <button class="text-brand-brown icon-action" type="button" data-indicador-delete title="<?= htmlspecialchars($t('indicadores.action.delete')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.delete')) ?>">
                  <span data-feather="trash-2"></span>
                </button>
                <a class="text-brand-brown icon-action" href="index.php?route=indicadores/edit&id=<?= (int)$item['id'] ?>" title="<?= htmlspecialchars($t('indicadores.action.edit')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.edit')) ?>">
                  <span data-feather="sliders"></span>
                </a>
              </div>
            </div>
          </div>
          <div class="hidden mt-3 text-sm" data-indicador-msg></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4" data-indicadores-view="cards">
      <?php foreach ($items as $item): ?>
        <div class="bg-white shadow rounded-xl p-5 border border-gray-100" data-indicador-id="<?= (int)$item['id'] ?>" data-unidade-tipo="<?= htmlspecialchars((string)($item['unidade_tipo'] ?? '')) ?>">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-brand-black"><?= htmlspecialchars($item['indicador']) ?></h2>
              <p class="text-sm text-gray-600"><?= htmlspecialchars(($item['cliente_nome'] ?? '') . ' · ' . ($item['departamento_nome'] ?? '') . ' · ' . ($item['setor_nome'] ?? '')) ?></p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium <?= htmlspecialchars($item['control_status_class']) ?>">
              <?= htmlspecialchars($item['control_status_label']) ?>
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 text-sm">
            <div class="rounded-lg bg-gray-50 p-3">
              <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.valor')) ?></div>
              <div class="font-semibold text-base">
                <span data-indicador-valor-view><?= htmlspecialchars($formatValue($item['valor'], $item)) ?></span>
                <input data-indicador-valor-input class="hidden border border-gray-300 rounded-lg p-2 w-full mt-2" type="text" inputmode="decimal" value="<?= $item['valor'] !== null ? htmlspecialchars(\App\Core\ValueFormatter::decimal($item['valor'], 2)) : '' ?>" />
              </div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.periodicidade')) ?></div>
              <div class="font-semibold text-base"><?= htmlspecialchars(\App\Core\I18n::t('indicadores.periodicidade.' . ($item['periodicidade_tipo'] ?? ''), [], null)) ?></div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.periodo')) ?></div>
              <div class="font-semibold text-base"><?= htmlspecialchars(($item['data_inicial'] ?? '') . ' até ' . ($item['data_final'] ?? '')) ?></div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <div class="text-gray-500"><?= htmlspecialchars($t('indicadores.label.unidade_medida')) ?></div>
              <div class="font-semibold text-base"><?= htmlspecialchars(($item['unidade_nome'] ?? '') . ' (' . ($item['unidade_tipo'] ?? '') . ')') ?></div>
            </div>
          </div>

          <div class="mt-4 text-sm">
            <div class="text-gray-500 mb-1"><?= htmlspecialchars($t('indicadores.label.responsaveis_vinculados')) ?></div>
            <div class="flex flex-wrap gap-2">
              <?php if (!empty($item['responsaveis'])): ?>
                <?php foreach ($item['responsaveis'] as $responsavel): ?>
                  <span class="px-3 py-1 rounded-full bg-brand-red/10 text-brand-red text-xs"><?= htmlspecialchars($responsavel) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="text-gray-400"><?= htmlspecialchars($t('indicadores.option.none')) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-5 pt-4 border-t border-gray-100">
            <div class="text-sm text-gray-600">
              <?= htmlspecialchars($t('indicadores.label.valor_minimo')) ?>: <?= $item['valor_minimo'] !== null ? htmlspecialchars($formatValue($item['valor_minimo'], $item)) : '—' ?>
              ·
              <?= htmlspecialchars($t('indicadores.label.valor_maximo')) ?>: <?= $item['valor_maximo'] !== null ? htmlspecialchars($formatValue($item['valor_maximo'], $item)) : '—' ?>
            </div>
            <div class="flex items-center gap-3">
              <a class="text-brand-brown icon-action" href="index.php?route=indicadores/historico&id=<?= (int)$item['id'] ?>" title="<?= htmlspecialchars($t('indicadores.action.history')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.history')) ?>">
                <span data-feather="bar-chart-2"></span>
              </a>
              <button class="text-brand-pink icon-action" type="button" data-indicador-edit title="Editar valor" aria-label="Editar valor">
                <span data-feather="edit"></span>
              </button>
              <button class="text-brand-brown icon-action opacity-50" type="button" data-indicador-save title="<?= htmlspecialchars($t('indicadores.action.save')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.save')) ?>" disabled>
                <span data-feather="check"></span>
              </button>
              <button class="text-brand-brown icon-action" type="button" data-indicador-delete title="<?= htmlspecialchars($t('indicadores.action.delete')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.delete')) ?>">
                <span data-feather="trash-2"></span>
              </button>
              <a class="text-brand-brown icon-action" href="index.php?route=indicadores/edit&id=<?= (int)$item['id'] ?>" title="<?= htmlspecialchars($t('indicadores.action.edit')) ?>" aria-label="<?= htmlspecialchars($t('indicadores.action.edit')) ?>">
                <span data-feather="sliders"></span>
              </a>
            </div>
          </div>
          <div class="hidden mt-3 text-sm" data-indicador-msg></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
