<?php use App\Core\Security; /** @var array $clientes */ /** @var array $consultores */ ?>
<div class="p-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle ?? 'Novo Usuário') ?></h1>
    <form method="post" action="index.php?route=usuarios/store" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
        <div>
            <label class="block text-sm">Nome</label>
            <input name="nome" class="border rounded p-2 w-full" required />
        </div>
        <div>
            <label class="block text-sm">E-mail</label>
            <input name="email" class="border rounded p-2 w-full" type="email" required />
        </div>
        <div>
            <label class="block text-sm">Senha</label>
            <input name="senha" class="border rounded p-2 w-full" type="password" required />
        </div>
        <div>
            <label class="block text-sm">Perfil</label>
            <select name="tipo_acesso" class="border rounded p-2 w-64">
                <option value="cliente">Cliente</option>
                <option value="consultor">Consultor</option>
                <option value="instituto">Instituto</option>
            </select>
        </div>
        <div>
            <label class="block text-sm">Vincular Cliente (opcional)</label>
            <select name="id_cliente" class="border rounded p-2 w-full">
                <option value="">-- nenhum --</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome_empresa']) ?></option>
                <?php endforeach; ?>
            </select>
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
        <button class="icon-btn icon-btn--primary" type="submit" title="Salvar" aria-label="Salvar"><span data-feather="check"></span></button>
    </form>
</div>
