<?php /** @var array $clientes */ ?>
<?php
  $alreadyRun = $alreadyRun ?? false;
  $report = $report ?? null;
  $uploadedFile = $uploadedFile ?? null;
?>
<div class="p-6 max-w-4xl">
  <div class="bg-white shadow rounded p-6 mb-6">
    <h1 class="text-2xl font-bold mb-2 text-gray-900">Importar planos de ação</h1>
    <p class="text-sm text-gray-700 mb-4">
      Ferramenta especial para migração única de planos de ação a partir de planilhas Excel.
    </p>
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded p-4 mb-4">
      <div class="font-semibold mb-1">Atenção</div>
      <ul class="list-disc pl-5 space-y-1">
        <li>Use esta importação apenas uma vez em produção.</li>
        <li>Recomenda-se testar antes em ambiente de homologação com cópia real dos dados.</li>
        <li>Faça backup do banco de dados antes da importação definitiva.</li>
      </ul>
    </div>
    <?php if ($alreadyRun && !$report): ?>
      <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded p-4">
        <div class="font-semibold mb-1">Importação marcada como já executada</div>
        <p>Esta ferramenta está bloqueada para evitar múltiplas importações. Caso precise repetir, avalie em ambiente de testes ou ajuste manualmente o sistema.</p>
      </div>
    <?php else: ?>
      <form method="post" action="index.php?route=planoacao/importRun" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrfToken() ?>" />
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cliente padrão (opcional)</label>
          <select name="id_cliente" class="border rounded p-2 w-full">
            <option value="">Sem cliente padrão (usar coluna de cliente na planilha)</option>
            <?php foreach ($clientes as $cli): ?>
              <option value="<?= (int)$cli['id'] ?>"><?= htmlspecialchars($cli['nome_empresa']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-500 mt-1">
            Caso a planilha não possua coluna de cliente, será usado este cliente para todos os registros.
          </p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo da planilha</label>
          <input type="file" name="arquivo" accept=".xlsx,.csv" required class="border rounded p-2 w-full" />
          <p class="text-xs text-gray-500 mt-1">
            Formatos aceitos: XLSX (Excel) ou CSV. A primeira linha deve conter os cabeçalhos.
          </p>
        </div>
        <div>
          <div class="text-sm font-medium text-gray-700 mb-1">Estrutura esperada</div>
          <p class="text-xs text-gray-600 mb-1">
            A ferramenta tenta reconhecer automaticamente os campos pelos nomes das colunas. Exemplos de cabeçalhos esperados:
          </p>
          <ul class="text-xs text-gray-600 list-disc pl-5 space-y-1">
            <li>Cliente: <span class="font-mono">cliente</span>, <span class="font-mono">empresa</span> ou <span class="font-mono">cliente_nome</span>;</li>
            <li>Ou ID do cliente: <span class="font-mono">id_cliente</span> ou <span class="font-mono">cliente_id</span>;</li>
            <li>Título do plano: <span class="font-mono">titulo</span>, <span class="font-mono">plano</span> ou similar;</li>
            <li>Descrição, solução, origem, prazo, responsável, status e progresso são opcionais.</li>
          </ul>
        </div>
        <div class="pt-2">
          <button type="submit" class="px-4 py-2 rounded bg-brand-red text-white font-semibold">
            Executar importação
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
  <?php if ($report): ?>
    <div class="bg-white shadow rounded p-6">
      <h2 class="text-xl font-semibold mb-3 text-gray-900">Relatório da importação</h2>
      <?php if ($uploadedFile): ?>
        <p class="text-sm text-gray-600 mb-2">Arquivo processado: <span class="font-mono"><?= htmlspecialchars($uploadedFile) ?></span></p>
      <?php endif; ?>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 text-sm">
        <div class="bg-gray-50 rounded p-3">
          <div class="text-xs text-gray-500">Linhas de dados</div>
          <div class="text-lg font-bold text-gray-900"><?= (int)($report['total_rows'] ?? 0) ?></div>
        </div>
        <div class="bg-green-50 rounded p-3">
          <div class="text-xs text-green-700">Importados</div>
          <div class="text-lg font-bold text-green-800"><?= (int)($report['imported'] ?? 0) ?></div>
        </div>
        <div class="bg-yellow-50 rounded p-3">
          <div class="text-xs text-yellow-700">Ignorados</div>
          <div class="text-lg font-bold text-yellow-800"><?= (int)($report['ignored'] ?? 0) ?></div>
        </div>
        <div class="bg-red-50 rounded p-3">
          <div class="text-xs text-red-700">Com erro</div>
          <div class="text-lg font-bold text-red-800"><?= (int)($report['errors'] ?? 0) ?></div>
        </div>
      </div>
      <?php if (!empty($report['error_rows'])): ?>
        <div class="mb-4">
          <div class="font-semibold text-sm text-red-800 mb-1">Erros encontrados</div>
          <div class="border border-red-100 rounded bg-red-50 max-h-64 overflow-auto text-xs">
            <table class="min-w-full text-xs">
              <thead class="bg-red-100">
                <tr>
                  <th class="p-2 text-left">Linha</th>
                  <th class="p-2 text-left">Mensagem</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($report['error_rows'] as $err): ?>
                  <tr class="border-t border-red-100">
                    <td class="p-2 whitespace-nowrap"><?= (int)($err['line'] ?? 0) ?></td>
                    <td class="p-2"><?= htmlspecialchars($err['message'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
      <?php if (!empty($report['duplicates'])): ?>
        <div class="mb-4">
          <div class="font-semibold text-sm text-yellow-800 mb-1">Registros ignorados por duplicidade</div>
          <div class="border border-yellow-100 rounded bg-yellow-50 max-h-64 overflow-auto text-xs">
            <table class="min-w-full text-xs">
              <thead class="bg-yellow-100">
                <tr>
                  <th class="p-2 text-left">Linha</th>
                  <th class="p-2 text-left">Cliente ID</th>
                  <th class="p-2 text-left">Título</th>
                  <th class="p-2 text-left">Motivo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($report['duplicates'] as $dup): ?>
                  <tr class="border-t border-yellow-100">
                    <td class="p-2 whitespace-nowrap"><?= (int)($dup['line'] ?? 0) ?></td>
                    <td class="p-2 whitespace-nowrap"><?= htmlspecialchars((string)($dup['cliente_id'] ?? '')) ?></td>
                    <td class="p-2"><?= htmlspecialchars($dup['titulo'] ?? '') ?></td>
                    <td class="p-2"><?= htmlspecialchars($dup['reason'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
      <?php if (!empty($report['headers'])): ?>
        <div class="mt-4 text-xs text-gray-600">
          <div class="font-semibold mb-1">Cabeçalhos detectados</div>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($report['headers'] as $h): ?>
              <span class="px-2 py-1 bg-gray-100 rounded border border-gray-200 font-mono"><?= htmlspecialchars((string)$h) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

