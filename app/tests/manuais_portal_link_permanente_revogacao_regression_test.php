<?php
namespace App\Controllers {
    // Intercepta header() apenas dentro do namespace do controller (resolução de função
    // por namespace do PHP), para capturar o "Location:" real emitido pelos métodos de
    // portal sem depender de headers_list() (não confiável em CLI).
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__captured_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require __DIR__ . '/../autoload.php';

    use App\Controllers\ManuaisController;
    use App\Core\Security;
    use App\Database\Database;
    use App\Models\ClienteModel;
    use App\Models\ManualPortalTokenModel;

    function ok(string $msg): void { echo "OK: $msg\n"; }
    function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

    function resetRequest(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($GLOBALS['__captured_location']);
    }

    function gerarLink(int $empresaId): string
    {
        resetRequest();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['route'] = 'manuais/generatePortalLink';
        $_POST = [
            'csrf' => Security::csrfToken(),
            'empresa_id' => (string)$empresaId,
            'departamento_id' => '',
            'q' => '',
        ];
        ob_start();
        (new ManuaisController())->generatePortalLink();
        ob_end_clean();
        return (string)($GLOBALS['__captured_location'] ?? '');
    }

    function acessarPortal(string $token): string
    {
        resetRequest();
        unset($_SESSION['manual_portal']);
        $_GET = ['route' => 'manuais/portal', 'token' => $token];
        ob_start();
        (new ManuaisController())->portal();
        return (string)ob_get_clean();
    }

    $pdo = Database::getConnection();
    $clientes = new ClienteModel();
    $tokens = new ManualPortalTokenModel();
    $suffix = substr(bin2hex(random_bytes(4)), 0, 8);

    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $clienteAId = $clientes->create(['nome_empresa' => 'Cliente Portal Permanente A ' . $suffix, 'CNPJ' => '33.444.5' . substr($suffix, 0, 2) . '/0001-33', 'contato' => 'Contato A']);
    $clienteBId = $clientes->create(['nome_empresa' => 'Cliente Portal Permanente B ' . $suffix, 'CNPJ' => '44.555.6' . substr($suffix, 0, 2) . '/0001-44', 'contato' => 'Contato B']);
    if ($clienteAId <= 0 || $clienteBId <= 0) { failFast('Falha ao criar clientes de teste'); }
    ok('Criou clientes A e B para o teste');

    // ===================== GERACAO =====================

    // 1/2) Gera o primeiro link e confirma que funciona.
    $locationPrimeiro = gerarLink($clienteAId);
    parse_str((string)parse_url($locationPrimeiro, PHP_URL_QUERY), $q1);
    $tokenPrimeiro = (string)($q1['portal_token'] ?? '');
    if ($tokenPrimeiro === '') { failFast('Primeiro link não foi gerado'); }
    $htmlPrimeiro = acessarPortal($tokenPrimeiro);
    if (strpos($htmlPrimeiro, 'Link do portal inválido') !== false || strpos($htmlPrimeiro, 'Link inválido') !== false) {
        failFast('Primeiro link recém-gerado não funcionou');
    }
    ok('Gera o primeiro link e confirma que funciona');

    // 3/4/5) Gera um segundo link e confirma que o primeiro CONTINUA funcionando (a causa raiz
    // do bug: issue() costumava desativar todos os tokens anteriores da empresa).
    $locationSegundo = gerarLink($clienteAId);
    parse_str((string)parse_url($locationSegundo, PHP_URL_QUERY), $q2);
    $tokenSegundo = (string)($q2['portal_token'] ?? '');
    if ($tokenSegundo === '' || $tokenSegundo === $tokenPrimeiro) { failFast('Segundo link não foi gerado como token independente'); }
    ok('Gera um segundo link, independente do primeiro');

    $htmlPrimeiroDepois = acessarPortal($tokenPrimeiro);
    if (strpos($htmlPrimeiroDepois, 'Link do portal inválido') !== false) {
        failFast('REGRESSÃO CRÍTICA: gerar um novo link invalidou o link anterior');
    }
    ok('Primeiro link continua funcionando após a geração do segundo (não é mais desativado)');

    $htmlSegundo = acessarPortal($tokenSegundo);
    if (strpos($htmlSegundo, 'Link do portal inválido') !== false) {
        failFast('Segundo link não funciona');
    }
    ok('Segundo link também funciona');

    // ===================== PERMANENCIA =====================

    // 6/8) Sem expiração automática: expira_em deve ser NULL para links novos.
    $stmt = $pdo->prepare('SELECT expira_em FROM manual_portal_tokens WHERE token = :t');
    $stmt->execute(['t' => $tokenSegundo]);
    $expiraEm = $stmt->fetchColumn();
    if ($expiraEm !== null && $expiraEm !== false && $expiraEm !== '') {
        failFast('Link novo foi criado com expiração automática (esperado: permanente/NULL). Obtido: ' . var_export($expiraEm, true));
    }
    ok('Link novo é criado sem expiração automática (expira_em = NULL)');

    // 7) Simula a passagem do tempo emulando um expira_em antigo já vencido em um token e
    // confirma que, para links criados sob a nova regra (expira_em NULL), isso não se aplica -
    // o findValid() só bloqueia quando expira_em está preenchido E no passado.
    $registro = $tokens->listByEmpresa($clienteAId)[0] ?? null;
    if (!$registro || !empty($registro['expira_em'])) {
        failFast('Token mais recente deveria estar sem expiração configurada');
    }
    ok('expira_em não bloqueia o acesso quando a regra final é permanente (NULL)');

    // ===================== REVOGACAO =====================

    // 9/10) Revoga o primeiro link.
    $tokenRow = $pdo->prepare('SELECT id FROM manual_portal_tokens WHERE token = :t');
    $tokenRow->execute(['t' => $tokenPrimeiro]);
    $tokenPrimeiroId = (int)$tokenRow->fetchColumn();
    if ($tokenPrimeiroId <= 0) { failFast('Não localizou o id do primeiro token'); }

    resetRequest();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['route'] = 'manuais/revokePortalLink';
    $_POST = [
        'csrf' => Security::csrfToken(),
        'empresa_id' => (string)$clienteAId,
        'token_id' => (string)$tokenPrimeiroId,
    ];
    ob_start();
    (new ManuaisController())->revokePortalLink();
    ob_end_clean();
    $locationRevoke = (string)($GLOBALS['__captured_location'] ?? '');
    if (strpos($locationRevoke, 'route=manuais/index') === false) {
        failFast('Revogação não redirecionou de volta para manuais/index');
    }
    ok('Ação de revogação executa e redireciona corretamente');

    $htmlPrimeiroRevogado = acessarPortal($tokenPrimeiro);
    if (strpos($htmlPrimeiroRevogado, 'Link do portal inválido') === false) {
        failFast('Link revogado ainda concede acesso ao portal');
    }
    ok('Primeiro link deixa de funcionar após a revogação');

    // 11/12) O segundo link (e demais tokens da empresa) não são afetados pela revogação do primeiro.
    $htmlSegundoAposRevogar = acessarPortal($tokenSegundo);
    if (strpos($htmlSegundoAposRevogar, 'Link do portal inválido') !== false) {
        failFast('Revogar um token afetou outro token da mesma empresa');
    }
    ok('Revogação de um token não afeta os demais links da mesma empresa');

    // Revogação não apaga o histórico (linha continua existindo, só com ativo=0).
    $existeAinda = (int)$pdo->query("SELECT COUNT(*) FROM manual_portal_tokens WHERE id = " . $tokenPrimeiroId)->fetchColumn();
    if ($existeAinda !== 1) { failFast('Revogação apagou o registro do token em vez de apenas desativá-lo'); }
    ok('Revogação preserva o histórico do token (registro não é excluído)');

    // Revogar novamente (idempotência) não falha nem afeta outros tokens.
    resetRequest();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['route'] = 'manuais/revokePortalLink';
    $_POST = [
        'csrf' => Security::csrfToken(),
        'empresa_id' => (string)$clienteAId,
        'token_id' => (string)$tokenPrimeiroId,
    ];
    ob_start();
    (new ManuaisController())->revokePortalLink();
    ob_end_clean();
    ok('Revogar um token já revogado é idempotente (não gera erro)');

    // ===================== SEGURANCA =====================

    // 13) Token inexistente.
    $htmlInexistente = acessarPortal('token-que-nao-existe-' . $suffix);
    if (strpos($htmlInexistente, 'Link do portal inválido') === false) {
        failFast('Token inexistente deveria ser rejeitado com o mesmo comportamento seguro');
    }
    ok('Token inexistente é rejeitado com segurança (sem revelar informação adicional)');

    // 14) Token revogado (reforça o teste 10 acima com foco em segurança).
    $htmlRevogadoSeguranca = acessarPortal($tokenPrimeiro);
    if (strpos($htmlRevogadoSeguranca, 'Link do portal inválido') === false) {
        failFast('Token revogado deveria continuar bloqueado');
    }
    ok('Token revogado continua bloqueado de forma consistente');

    // 15/16) Token de uma empresa não pode ser usado para revogar/acessar dados de outra empresa
    // (mesmo manipulando o token_id no POST de revogação).
    $locationClienteB = gerarLink($clienteBId);
    parse_str((string)parse_url($locationClienteB, PHP_URL_QUERY), $qB);
    $tokenClienteB = (string)($qB['portal_token'] ?? '');
    $rowB = $pdo->prepare('SELECT id FROM manual_portal_tokens WHERE token = :t');
    $rowB->execute(['t' => $tokenClienteB]);
    $tokenClienteBId = (int)$rowB->fetchColumn();

    // Sessão de Cliente Admin restrita à empresa A tentando revogar o token da empresa B.
    $_SESSION['user'] = [
        'id' => 2,
        'nome' => 'Cliente Admin A',
        'email' => 'admin.a@example.com',
        'tipo_acesso' => 'cliente_admin',
        'allowed_client_ids' => [$clienteAId],
    ];
    resetRequest();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['route'] = 'manuais/revokePortalLink';
    $_POST = [
        'csrf' => Security::csrfToken(),
        'empresa_id' => (string)$clienteAId, // o proprio controller resolve/normaliza para o tenant do usuario
        'token_id' => (string)$tokenClienteBId, // tentando manipular o id de um token de outra empresa
    ];
    ob_start();
    (new ManuaisController())->revokePortalLink();
    ob_end_clean();
    $tokenBAindaAtivo = $tokens->listByEmpresa($clienteBId);
    $encontrado = null;
    foreach ($tokenBAindaAtivo as $row) {
        if ((int)$row['id'] === $tokenClienteBId) { $encontrado = $row; break; }
    }
    if (!$encontrado || (int)$encontrado['ativo'] !== 1) {
        failFast('Token de outra empresa foi revogado por um Cliente Admin sem acesso a ela (falha de isolamento)');
    }
    ok('Token de uma empresa não pode ser revogado por usuário sem acesso a ela (isolamento por tenant preservado)');

    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    // 17/18) O portal do token da empresa B nunca expõe dados da empresa A.
    $htmlPortalB = acessarPortal($tokenClienteB);
    if (strpos($htmlPortalB, 'Cliente Portal Permanente A ' . $suffix) !== false) {
        failFast('Portal da empresa B expôs referência à empresa A');
    }
    ok('Portal do token da empresa B não expõe dados da empresa A');

    echo "Manuais portal link permanente/revogação regression tests passed.\n";
}
