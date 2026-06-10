<?php use App\Core\Security; ?>
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow p-5">
        <h1 class="text-xl font-bold mb-1">Acesso</h1>
        <p class="text-sm text-gray-600 mb-4">Entre com suas credenciais para continuar.</p>
        <?php if (!empty($error)): ?>
            <div class="p-3 rounded bg-red-50 text-red-800 text-sm mb-4"><?= htmlspecialchars((string)$error) ?></div>
        <?php endif; ?>
        <form method="post" action="dologin" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
            <div>
                <label class="block text-sm mb-1">E-mail</label>
                <input name="email"
                       type="email"
                       autocomplete="email"
                       inputmode="email"
                       class="border rounded-lg p-3 w-full"
                       required />
            </div>
            <div>
                <label class="block text-sm mb-1">Senha</label>
                <input name="senha"
                       type="password"
                       autocomplete="current-password"
                       class="border rounded-lg p-3 w-full"
                       required />
            </div>
            <button class="w-full px-4 py-3 rounded-lg bg-brand-red text-white font-semibold" type="submit">Entrar</button>
            <div class="text-xs text-gray-500">Se você nao possui acesso ao aplicativo, solicite liberacao ao administrador.</div>
        </form>
    </div>
</div>
