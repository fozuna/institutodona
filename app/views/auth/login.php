<?php /** @var string|null $error */ ?>
<?php use App\Core\Security; ?>
<div class="min-h-screen flex">
    <!-- Lado esquerdo: 70% vermelho com imagem de fundo -->
    <div class="relative basis-[70%] text-white flex items-center justify-center"
         style="background-image: url('assets/img/login-bg.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; background-color: var(--brand-red); background-blend-mode: multiply;">
        <div class="p-10 max-w-2xl">
            <h1 class="text-4xl font-bold mb-4">Instituto Dona</h1>
            <p class="text-lg opacity-90">Gestão de processos, pessoas e metodologias com simplicidade.</p>
        </div>
    </div>

    <!-- Lado direito: 30% cinza claro com formulário -->
    <div class="basis-[30%] bg-[#eeeeee] flex items-center justify-center">
        <div class="w-full max-w-sm bg-white shadow rounded p-6 border">
            <h2 class="text-2xl font-bold mb-4 text-brand-brown">Entrar</h2>
            <?php if (!empty($error)): ?>
                <div class="mb-3 px-3 py-2 rounded bg-red-100 text-red-700 text-sm"><?= htmlspecialchars($error) ?></div>
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
                <button class="px-4 py-2 rounded bg-brand-red text-white w-full" type="submit">Acessar</button>
            </form>
            <p class="text-xs text-gray-500 mt-3">Use um usuário previamente criado na tabela usuarios.</p>
        </div>
    </div>
</div>