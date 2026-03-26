<?php use App\Core\Security; /** @var array|null $item */ /** @var array $clientes */ /** @var array $consultores */ /** @var array $selectedClientes */ ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Editar Usuário') ?></h1>
    <?php if (!$item): ?>
        <p>Registro não encontrado.</p>
    <?php else: ?>
        <form method="post" action="index.php?route=usuarios/update" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
            <div>
                <label class="block text-sm">Nome</label>
                <input name="nome" class="border rounded p-2 w-full" value="<?= htmlspecialchars($item['nome']) ?>" />
            </div>
            <div>
                <label class="block text-sm">E-mail</label>
                <input name="email" class="border rounded p-2 w-full" type="email" value="<?= htmlspecialchars($item['email']) ?>" />
            </div>
            <div>
                <label class="block text-sm">Nova Senha (opcional)</label>
                <input name="senha" class="border rounded p-2 w-full" type="password" />
            </div>
            <div>
                <label class="block text-sm">Perfil</label>
                <select name="tipo_acesso" class="border rounded p-2 w-64">
                    <?php $tipo = $item['tipo_acesso']; ?>
                    <option value="cliente" <?= $tipo === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="consultor" <?= $tipo === 'consultor' ? 'selected' : '' ?>>Consultor</option>
                    <option value="instituto" <?= $tipo === 'instituto' ? 'selected' : '' ?>>Instituto</option>
                </select>
            </div>
            <div>
                <label class="block text-sm">Empresas vinculadas</label>
                <select name="id_clientes[]" class="border rounded p-2 w-full h-44" multiple>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'], $selectedClientes ?? [], true) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome_empresa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Matrizes herdam automaticamente filiais ativas e não restritas.</p>
            </div>
            <div>
                <label class="block text-sm">Vincular Consultor (opcional)</label>
                <select name="id_consultor" class="border rounded p-2 w-full">
                    <option value="">-- nenhum --</option>
                    <?php foreach ($consultores as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <button class="px-4 py-2 rounded bg-brand-red text-white" type="submit">Salvar</button>
                <button class="px-4 py-2 rounded bg-gray-200 text-brand-brown" type="button" onclick="history.back()">Cancelar</button>
            </div>
        </form>
    <?php endif; ?>
</div>
