<?php
namespace App\Controllers {
    // Intercepta header() apenas dentro do namespace do controller (resolução de função
    // por namespace do PHP), para capturar o "Location:" real gerado por generatePortalLink()
    // sem depender de headers_list() (que não funciona de forma confiável em CLI).
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__captured_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require __DIR__ . '/../autoload.php';

    use App\Core\Security;
    use App\Database\Database;
    use App\Controllers\ManuaisController;
    use App\Models\ClienteModel;
    use App\Models\DepartamentoModel;

    function ok(string $msg): void { echo "OK: $msg\n"; }
    function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $pdo = Database::getConnection();
    $clientes = new ClienteModel();
    $departamentos = new DepartamentoModel();
    $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
    $cleanup = ['cliente_id' => 0, 'departamento_ids' => []];

    register_shutdown_function(function () use ($pdo, &$cleanup) {
        try {
            if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM manual_portal_tokens WHERE empresa_id = :id')->execute(['id' => $cleanup['cliente_id']]); }
            foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
            if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
        } catch (\Throwable $e) {}
    });

    $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
    $stmt->execute(['nome' => 'Cliente Portal Dep ' . $suffix, 'cnpj' => '88.888.888/0001-' . substr($suffix, 0, 2), 'contato' => 'Test']);
    $clienteId = (int)$pdo->lastInsertId();
    if ($clienteId <= 0) failFast('Falha ao criar cliente');
    $cleanup['cliente_id'] = $clienteId;

    $departamentoIdA = $departamentos->create(['nome' => 'Departamento A ' . $suffix, 'cliente_id' => $clienteId]);
    $departamentoIdB = $departamentos->create(['nome' => 'Departamento B ' . $suffix, 'cliente_id' => $clienteId]);
    if ($departamentoIdA <= 0 || $departamentoIdB <= 0) failFast('Falha ao criar departamentos');
    $cleanup['departamento_ids'] = [$departamentoIdA, $departamentoIdB];
    ok('Cliente e departamentos criados');

    // 1) Gera o link do portal filtrando por departamento A.
    $GLOBALS['__captured_location'] = null;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = ['route' => 'manuais/generatePortalLink'];
    $_POST = [
        'csrf' => Security::csrfToken(),
        'empresa_id' => (string)$clienteId,
        'departamento_id' => (string)$departamentoIdA,
        'q' => '',
    ];
    ob_start();
    (new ManuaisController())->generatePortalLink();
    ob_get_clean();

    $location = (string)($GLOBALS['__captured_location'] ?? '');
    if ($location === '') {
        failFast('generatePortalLink() não emitiu redirecionamento (Location)');
    }
    ok('Link gerado com filtro de departamento, redirecionamento capturado');

    // 2) A causa raiz do bug: o redirect precisa preservar departamento_id, senão a tela
    //    de gerenciamento perde o filtro e monta o link exibido ao admin sem esse parâmetro,
    //    divergindo do filtro travado no token (gerando "Link inválido" ao usar o link).
    parse_str((string)parse_url($location, PHP_URL_QUERY), $redirectQuery);
    if ((int)($redirectQuery['departamento_id'] ?? 0) !== (int)$departamentoIdA) {
        failFast('Redirecionamento de generatePortalLink() não preservou departamento_id: ' . $location);
    }
    ok('Redirecionamento preserva departamento_id, permitindo reconstruir o filtro correto');

    // 3) Simula o reload de index() com os parâmetros reais do redirect e confere que o
    //    link exibido ao admin (portalLink) contém o departamento_id correto.
    $stmt = $pdo->prepare('SELECT token, filters_json FROM manual_portal_tokens WHERE empresa_id = :id ORDER BY id DESC LIMIT 1');
    $stmt->execute(['id' => $clienteId]);
    $tokenRow = $stmt->fetch();
    if (!$tokenRow) failFast('Token do portal não foi persistido');
    $lockedFilters = json_decode((string)$tokenRow['filters_json'], true);
    if ((int)($lockedFilters['departamento_id'] ?? 0) !== (int)$departamentoIdA) {
        failFast('Token não travou o departamento_id esperado: ' . $tokenRow['filters_json']);
    }
    ok('Token do portal trava o departamento_id correto (filtro seguro no servidor)');

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $redirectQuery;
    ob_start();
    (new ManuaisController())->index();
    $indexHtml = ob_get_clean();
    // manuais/index agora lista todos os links da empresa (não só o recém-gerado); o mais
    // recente (o que acabamos de gerar) aparece primeiro, ordenado por created_at DESC.
    if (!preg_match('/class="portal-link-input[^"]*"\s+value="([^"]+)"/', $indexHtml, $m)) {
        failFast('Lista de links do portal não foi renderizada em manuais/index');
    }
    $displayedLink = htmlspecialchars_decode($m[1]);
    if (strpos($displayedLink, 'departamento_id=' . $departamentoIdA) === false) {
        failFast('Link exibido ao admin não contém departamento_id, divergindo do token travado: ' . $displayedLink);
    }
    ok('Link exibido ao admin contém o mesmo departamento_id travado no token');

    // 4) Usa o link exibido de fato (como o destinatário faria) e confirma que NÃO
    //    aparece mais "Link inválido".
    unset($_SESSION['manual_portal']);
    $linkParts = parse_url($displayedLink);
    parse_str((string)($linkParts['query'] ?? ''), $linkQuery);
    preg_match('#/biblioteca/portal/([a-f0-9]+)#', (string)($linkParts['path'] ?? ''), $tokenMatch);
    $_GET = array_merge(['route' => 'manuais/portal', 'token' => $tokenMatch[1] ?? ''], $linkQuery);
    ob_start();
    (new ManuaisController())->portal();
    $portalHtml = ob_get_clean();
    if (strpos($portalHtml, 'Link inválido') !== false) {
        failFast('Link exibido ao admin ainda dispara "Link inválido" ao ser acessado');
    }
    ok('Link do portal com filtro de departamento funciona sem "Link inválido"');

    // 5) Segurança: adulterar o departamento_id na URL para um valor fora do link travado
    //    ainda deve ser bloqueado.
    unset($_SESSION['manual_portal']);
    $_GET = ['route' => 'manuais/portal', 'token' => $tokenMatch[1] ?? '', 'departamento_id' => (string)$departamentoIdB];
    ob_start();
    (new ManuaisController())->portal();
    $tamperedHtml = ob_get_clean();
    if (strpos($tamperedHtml, 'Link inválido') === false) {
        failFast('Adulterar departamento_id na URL deveria continuar bloqueado com "Link inválido" (regressão de segurança)');
    }
    ok('Segurança preservada: adulterar departamento_id na URL ainda é bloqueado');

    echo "Manuais portal link departamento regression test passed.\n";
}
