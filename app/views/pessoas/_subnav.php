<?php
/**
 * Subnavegação contextual do Pilar de Pessoas (Sprint 05): uma faixa
 * horizontal compacta, sem nova sidebar. Cada item só aparece se o usuário
 * puder acessar a rota (RBAC real via AccessControl).
 *
 * @var string $pessoasSubnavAtivo chave do item ativo
 * Variáveis locais usam o prefixo $pessoasSubnav* para não sobrescrever as da view que inclui o parcial.
 */
$pessoasSubnavUser = $_SESSION['user'] ?? null;
$pessoasSubnavAtivo = (string)($pessoasSubnavAtivo ?? '');
$pessoasSubnavItens = [
    'visao' => ['pessoas/visaoGeral', 'Visão Geral'],
    'colaboradores' => ['colaboradores/index', 'Colaboradores'],
    'avaliacoes' => ['pessoas/index', 'Avaliações'],
    'gaps' => ['pessoas/gaps', 'GAPs'],
    'acoes' => ['pessoas/acoes', 'Ações'],
    'pdi' => ['pessoas/pdiIndex', 'PDI'],
    'necessidades' => ['pessoas/necessidades', 'Necessidades de Treinamento'],
];
?>
<nav aria-label="Navegação do Pilar de Pessoas" class="-mx-1 overflow-x-auto">
  <ul class="flex items-center gap-1 px-1 whitespace-nowrap text-sm border-b border-gray-200">
    <?php foreach ($pessoasSubnavItens as $pessoasSubnavChave => [$pessoasSubnavRota, $pessoasSubnavRotulo]): ?>
      <?php if (!\App\Core\AccessControl::canAccessRoute($pessoasSubnavRota, 'GET', $pessoasSubnavUser)) { continue; } ?>
      <?php $pessoasSubnavItemAtivo = $pessoasSubnavChave === $pessoasSubnavAtivo; ?>
      <li>
        <a href="index.php?route=<?= htmlspecialchars($pessoasSubnavRota) ?>"
           class="inline-block px-3 py-2 -mb-px border-b-2 <?= $pessoasSubnavItemAtivo ? 'border-brand-red text-brand-black font-semibold' : 'border-transparent text-gray-600 hover:text-brand-black hover:border-gray-300' ?>"
           <?= $pessoasSubnavItemAtivo ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($pessoasSubnavRotulo) ?></a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
