<?php
/** @var string $backUrl */
$backUrl = $backUrl ?? 'index.php?route=dashboard/index';
$user = $_SESSION['user'] ?? null;
$secondaryUrl = $user ? 'index.php?route=dashboard/index' : 'index.php?route=auth/login';
$secondaryLabel = $user ? 'Ir para o Dashboard' : 'Ir para o login';
?>
<div class="flex items-center justify-center py-16 px-4">
  <div class="w-full max-w-lg bg-white shadow rounded-2xl p-10 text-center">
    <div class="mx-auto mb-6 flex items-center justify-center w-16 h-16 rounded-full bg-brand-brown/5 text-brand-brown">
      <span data-feather="compass" style="width:28px;height:28px;" aria-hidden="true"></span>
    </div>
    <div class="text-sm font-semibold tracking-widest text-gray-400 mb-2">404</div>
    <h1 class="text-2xl font-bold text-brand-black mb-3">Conteúdo não encontrado</h1>
    <p class="text-gray-600 leading-relaxed mb-8">
      Não foi possível localizar ou acessar o conteúdo solicitado.<br />
      Ele pode não estar disponível para o seu perfil ou o endereço pode estar incorreto.
    </p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="<?= htmlspecialchars($backUrl) ?>" class="px-5 py-3 rounded-lg bg-brand-red text-white font-semibold text-center">
        Voltar para a página anterior
      </a>
      <a href="<?= htmlspecialchars($secondaryUrl) ?>" class="px-5 py-3 rounded-lg bg-gray-100 text-brand-brown font-semibold text-center">
        <?= htmlspecialchars($secondaryLabel) ?>
      </a>
    </div>
  </div>
</div>
