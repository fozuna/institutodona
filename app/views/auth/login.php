<?php /** @var string|null $error */ ?>
<?php use App\Core\Security; ?>
<?php $brandName = \App\Core\AppBrand::displayName(); ?>
<?php $brandTagline = \App\Core\AppBrand::TAGLINE; ?>
<div class="min-h-screen flex">
    <!-- Lado esquerdo: 70% com imagem de fundo -->
    <div class="relative basis-[70%] text-white flex items-center justify-center"
         style="background-image: url('assets/img/logobco.png'); background-size: cover; background-position: center; background-repeat: no-repeat; background-color: #102040; background-blend-mode: multiply;">
        <div class="p-10 max-w-2xl">
            <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($brandName) ?></h1>
            <?php if (!empty($brandTagline) && $brandTagline !== $brandName): ?>
                <p class="text-lg opacity-90"><?= htmlspecialchars($brandTagline) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lado direito: 30% cinza claro com formulário -->
    <div class="basis-[30%] bg-[#eeeeee] flex items-center justify-center">
        <div class="w-full max-w-sm bg-white shadow rounded p-6 border">
            <h2 class="text-2xl font-bold mb-4 text-brand-brown">Entrar</h2>
            <?php if (!empty($error)): ?>
                <div role="alert" class="mb-3 px-3 py-2 rounded border border-brand-brown bg-[#e8eef8] text-brand-brown text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="index.php?route=auth/doLogin" class="space-y-4">
                <input type="hidden" name="csrf" value="<?= Security::csrfToken() ?>" />
                <div>
                    <label class="block text-sm">E-mail</label>
                    <input class="border rounded p-2 w-full" name="email" type="email" required />
                </div>
                <div>
                    <label class="block text-sm">Senha</label>
                    <input class="border rounded p-2 w-full" name="senha" type="password" required />
                </div>
                <button class="px-4 py-2 rounded bg-brand-brown hover:bg-[#0b1630] focus:outline-none focus:ring-2 focus:ring-[#102040] focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed text-white w-full transition-colors" type="submit">Acessar</button>
            </form>
            <p class="text-xs text-gray-500 mt-3">Use um usuário previamente criado na tabela usuarios.</p>
        </div>
    </div>
</div>
