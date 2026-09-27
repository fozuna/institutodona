<?php
/**
 * Campos de filtro comuns às listagens operacionais (Sprint 05): empresa,
 * cascata Departamento/Setor/Função (só com empresa em foco), colaborador e
 * período de registro. As opções vêm do Controller já escopadas por tenant.
 *
 * @var array $clientes @var int $selectedEmpresa @var array $departamentos @var array $setores
 * @var array $funcoes @var array $f @var ?array $colaborador
 */
$pfSel = static fn(int $pfA, int $pfB): string => $pfA === $pfB ? 'selected' : '';
?>
<?php if (count($clientes) > 1): ?>
<div class="md:col-span-2">
  <label class="block text-sm font-medium text-gray-700 mb-1" for="fEmpresa">Empresa</label>
  <select id="fEmpresa" name="empresa_id" class="border border-gray-300 rounded-lg p-2 w-full">
    <option value="">Todas</option>
    <?php foreach ($clientes as $pfCliente): ?>
      <option value="<?= (int)$pfCliente['id'] ?>" <?= $pfSel((int)$selectedEmpresa, (int)$pfCliente['id']) ?>><?= htmlspecialchars($pfCliente['nome_empresa']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>
<?php if (!empty($departamentos)): ?>
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1" for="fDep">Departamento</label>
  <select id="fDep" name="departamento_id" class="border border-gray-300 rounded-lg p-2 w-full">
    <option value="">Todos</option>
    <?php foreach ($departamentos as $pfDep): ?>
      <option value="<?= (int)$pfDep['id'] ?>" <?= $pfSel((int)($f['departamento_id'] ?? 0), (int)$pfDep['id']) ?>><?= htmlspecialchars($pfDep['nome']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>
<?php if (!empty($setores)): ?>
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1" for="fSetor">Setor</label>
  <select id="fSetor" name="setor_id" class="border border-gray-300 rounded-lg p-2 w-full">
    <option value="">Todos</option>
    <?php foreach ($setores as $pfSetor): ?>
      <option value="<?= (int)$pfSetor['id'] ?>" <?= $pfSel((int)($f['setor_id'] ?? 0), (int)$pfSetor['id']) ?>><?= htmlspecialchars($pfSetor['nome']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>
<?php if (!empty($funcoes)): ?>
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1" for="fFuncao">Função</label>
  <select id="fFuncao" name="funcao_id" class="border border-gray-300 rounded-lg p-2 w-full">
    <option value="">Todas</option>
    <?php foreach ($funcoes as $pfFuncao): ?>
      <option value="<?= (int)$pfFuncao['id'] ?>" <?= $pfSel((int)($f['funcao_id'] ?? 0), (int)$pfFuncao['id']) ?>><?= htmlspecialchars($pfFuncao['nome']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<?php endif; ?>
<?php if (!empty($colaborador)): ?>
  <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>" />
<?php else: ?>
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1" for="fQ">Colaborador</label>
  <input id="fQ" type="text" name="q" value="<?= htmlspecialchars((string)($f['q'] ?? '')) ?>" maxlength="100" class="border border-gray-300 rounded-lg p-2 w-full" placeholder="Nome" />
</div>
<?php endif; ?>
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1" for="fIni">Registrado de</label>
  <input id="fIni" type="date" name="inicio" value="<?= htmlspecialchars((string)($f['inicio'] ?? '')) ?>" class="border border-gray-300 rounded-lg p-2 w-full" />
</div>
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1" for="fFim">até</label>
  <input id="fFim" type="date" name="fim" value="<?= htmlspecialchars((string)($f['fim'] ?? '')) ?>" class="border border-gray-300 rounded-lg p-2 w-full" />
</div>
