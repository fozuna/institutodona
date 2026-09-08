<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\DashboardController;
use App\Database\Database;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Item 04: DashboardController::scopeClienteIds() reduzia Cliente Admin sem
 * filtro explícito a apenas Auth::allowedClientIds()[0] (a primeira empresa),
 * em vez de consolidar a carteira inteira. Este teste usa uma carteira real
 * de 3 empresas (A/B/C) com dados distinguíveis em cada uma, mais uma quarta
 * empresa (D) fora do tenant, para provar que:
 * - sem filtro, o Dashboard consolida A+B+C (nunca só a primeira);
 * - com filtro, mostra só a empresa escolhida;
 * - D nunca aparece, nem por manipulação de `clientes[]`, nem por `cliente=`
 *   direto (bloqueado antes disso pelo 404 oculto do RBAC de rota).
 */

function readFilters(DashboardController $controller): array
{
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('readDashboardFilters');
    $m->setAccessible(true);
    return $m->invoke($controller);
}

function computeMetrics(DashboardController $controller, array $filters): array
{
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('computeMetrics');
    $m->setAccessible(true);
    return $m->invoke($controller, $filters);
}

function resumoMesData(DashboardController $controller, array $filters): array
{
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('resumoMesData');
    $m->setAccessible(true);
    return $m->invoke($controller, $filters);
}

$pdo = Database::getConnection();
$suffix = 'dashscope_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];

$makeCnpj = static function (): string {
    $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
};

try {
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:ct)');

    $insCli->execute(['n' => 'Empresa A ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato A']);
    $empresaA = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Empresa B ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato B']);
    $empresaB = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Empresa C ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato C']);
    $empresaC = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Empresa Vazia ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato Vazia']);
    $empresaVazia = (int)$pdo->lastInsertId();
    $insCli->execute(['n' => 'Empresa D Fora Tenant ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato D']);
    $empresaD = (int)$pdo->lastInsertId();
    $clienteIds = [$empresaA, $empresaB, $empresaC, $empresaVazia, $empresaD];
    ok('Criou 5 empresas: A, B, C, Vazia (na carteira do Cliente Admin) e D (fora do tenant)');

    // Dado distinguível por empresa: 1 tarefa de Plano de Ação "Concluído" cada
    // (created_at = agora, cai no mês corrente por padrão), exceto na Empresa Vazia.
    $insTask = $pdo->prepare("INSERT INTO pdca_tasks (id_cliente, titulo, fase, status, progresso) VALUES (:cid, :t, 'DO', 'Concluído', 100)");
    $insTask->execute(['cid' => $empresaA, 't' => 'Tarefa A ' . $suffix]);
    $insTask->execute(['cid' => $empresaB, 't' => 'Tarefa B ' . $suffix]);
    $insTask->execute(['cid' => $empresaC, 't' => 'Tarefa C ' . $suffix]);
    $insTask->execute(['cid' => $empresaD, 't' => 'Tarefa D ' . $suffix]);
    ok('Criou 1 tarefa "Concluído" em A, B, C e D (Vazia fica sem nenhuma, de propósito)');

    $carteira = [$empresaA, $empresaB, $empresaC, $empresaVazia];
    sort($carteira);
    $adminUser = static fn(): array => [
        'id' => 501, 'nome' => 'Cliente Admin Multi ' . $suffix, 'email' => 'admin.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente_admin', 'allowed_client_ids' => $carteira,
    ];

    // ===================== CENÁRIO: Cliente Admin sem filtro consolida A+B+C+Vazia =====================
    $_SESSION['user'] = $adminUser();
    unset($_SESSION['__dashboard_filters']); // isola cada cenario do filtro "lembrado" da sessao anterior
    $_GET = ['route' => 'dashboard/index'];
    $controller = new DashboardController();
    $filters = readFilters($controller);
    $got = $filters['cliente_ids'];
    sort($got);
    if ($got !== $carteira) {
        failFast('Sem filtro, cliente_ids deveria ser toda a carteira (' . implode(',', $carteira) . '). Obtido: ' . implode(',', $got));
    }
    ok('Cenário: Cliente Admin sem filtro → cliente_ids = carteira inteira (A+B+C+Vazia), nunca só a primeira');

    $metrics = computeMetrics($controller, $filters);
    if ((int)($metrics['planoacao']['total'] ?? -1) !== 3) {
        failFast('Sem filtro, computeMetrics()["planoacao"]["total"] deveria ser 3 (A+B+C). Obtido: ' . json_encode($metrics['planoacao'] ?? null));
    }
    ok('Cenário: computeMetrics() sem filtro soma exatamente A+B+C (3), nunca inclui D nem duplica');

    $resumo = resumoMesData($controller, $filters);
    if ((int)($resumo['planoacao']['total'] ?? -1) !== 3) {
        failFast('Sem filtro, resumoMesData()["planoacao"]["total"] deveria ser 3. Obtido: ' . json_encode($resumo['planoacao'] ?? null));
    }
    $itemClientes = array_map(static fn(array $i): string => (string)($i['cliente_nome'] ?? ''), $resumo['planoacao']['items'] ?? []);
    if (in_array('Empresa D Fora Tenant ' . $suffix, $itemClientes, true)) {
        failFast('Resumo do Mês sem filtro não pode listar item da Empresa D (fora do tenant)');
    }
    ok('Cenário: Resumo do Mês sem filtro soma A+B+C e nunca lista item da Empresa D');

    // ===================== CENÁRIOS: filtro por empresa própria (A, depois B, depois C) =====================
    foreach ([$empresaA => 'A', $empresaB => 'B', $empresaC => 'C'] as $empresaId => $label) {
        $_SESSION['user'] = $adminUser();
    unset($_SESSION['__dashboard_filters']); // isola cada cenario do filtro "lembrado" da sessao anterior
        $_GET = ['route' => 'dashboard/index', 'cliente' => (string)$empresaId];
        $controllerX = new DashboardController();
        $filtersX = readFilters($controllerX);
        if ($filtersX['cliente_ids'] !== [$empresaId]) {
            failFast("Cenário Empresa $label: cliente_ids deveria ser [$empresaId]. Obtido: " . implode(',', $filtersX['cliente_ids']));
        }
        $metricsX = computeMetrics($controllerX, $filtersX);
        if ((int)($metricsX['planoacao']['total'] ?? -1) !== 1) {
            failFast("Cenário Empresa $label: computeMetrics() deveria contar 1 tarefa isolada. Obtido: " . json_encode($metricsX['planoacao'] ?? null));
        }
    }
    ok('Cenários: filtro por Empresa A, B e C isoladamente mostram somente 1 tarefa cada (nunca a carteira inteira, nunca outra empresa)');

    // ===================== CENÁRIO: empresa sem registros (zero dados, sem erro) =====================
    $_SESSION['user'] = $adminUser();
    unset($_SESSION['__dashboard_filters']); // isola cada cenario do filtro "lembrado" da sessao anterior
    $_GET = ['route' => 'dashboard/index', 'cliente' => (string)$empresaVazia];
    $controllerVazia = new DashboardController();
    $filtersVazia = readFilters($controllerVazia);
    $metricsVazia = computeMetrics($controllerVazia, $filtersVazia);
    if (($metricsVazia['ok'] ?? false) !== true || (int)($metricsVazia['planoacao']['total'] ?? -1) !== 0) {
        failFast('Empresa sem registros deveria retornar ok=true e total=0, sem erro. Obtido: ' . json_encode($metricsVazia['planoacao'] ?? null));
    }
    ok('Cenário: empresa sem registros retorna zero/coleção vazia, sem erro 500');

    // ===================== CENÁRIO: cross-tenant via clientes[] (array, sem passar pelo 404 de rota) =====================
    $_SESSION['user'] = $adminUser();
    unset($_SESSION['__dashboard_filters']); // isola cada cenario do filtro "lembrado" da sessao anterior
    $_GET = ['route' => 'dashboard/index', 'clientes' => [(string)$empresaD]];
    $controllerCross = new DashboardController();
    $filtersCross = readFilters($controllerCross);
    $gotCross = $filtersCross['cliente_ids'];
    sort($gotCross);
    if (in_array($empresaD, $gotCross, true)) {
        failFast('Tentativa de filtrar só a Empresa D (fora do tenant) via clientes[] não pode incluir D no resultado');
    }
    if ($gotCross !== $carteira) {
        failFast('Tentativa cross-tenant sem nenhum id válido deveria cair de volta na carteira inteira (nunca vazio, nunca D). Obtido: ' . implode(',', $gotCross));
    }
    $metricsCross = computeMetrics($controllerCross, $filtersCross);
    if ((int)($metricsCross['planoacao']['total'] ?? -1) !== 3) {
        failFast('Tentativa cross-tenant via clientes[] não pode vazar dado de D nem perder A+B+C. Obtido total=' . (int)($metricsCross['planoacao']['total'] ?? -1));
    }
    ok('Cenário: manipular clientes[]=EmpresaD nunca inclui D - cai de volta na carteira própria inteira (A+B+C), sem vazamento');

    // ===================== CENÁRIO: cross-tenant via ?cliente= direto (bloqueado no RBAC de rota, 404 oculto) =====================
    // BaseController::respondNotFound() da exit() incondicional - precisa rodar em subprocesso
    // (mesmo padrao de cronograma_evento_probe.php/atas_probe.php).
    $probe = __DIR__ . '/helpers/dashboard_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' '
        . escapeshellarg('cliente_admin') . ' '
        . escapeshellarg('501') . ' '
        . escapeshellarg(implode(',', $carteira)) . ' '
        . escapeshellarg((string)$empresaD);
    $out = [];
    exec($cmd . ' 2>&1', $out);
    $raw = implode("\n", $out);
    $marker = '---PROBE_RESULT---';
    $pos = strpos($raw, $marker);
    $resultLine = $pos !== false ? trim(substr($raw, $pos + strlen($marker))) : '';
    $decoded = json_decode($resultLine, true);
    $status = is_array($decoded) ? (int)($decoded['status'] ?? 0) : 0;
    if ($status !== 404) {
        failFast('Cliente Admin pedindo ?cliente=EmpresaD diretamente deveria ser bloqueado com 404 oculto (RBAC de rota). Status: ' . $status . ' raw=' . $raw);
    }
    ok('Cenário: ?cliente=EmpresaD (fora do tenant) direto na rota continua bloqueado com 404 oculto - proteção existente preservada');

    // ===================== CENÁRIO: Instituto sem filtro consolida A+B+C+D (via filtro explícito das 4 para isolar de resíduo de outras massas de teste) =====================
    $institutoUser = ['id' => 1, 'nome' => 'Instituto ' . $suffix, 'email' => 'instituto.' . $suffix . '@test.local', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
    $_SESSION['user'] = $institutoUser;
    unset($_SESSION['__dashboard_filters']); // isola cada cenario do filtro "lembrado" da sessao anterior
    $_GET = ['route' => 'dashboard/index', 'clientes' => array_map('strval', [$empresaA, $empresaB, $empresaC, $empresaD])];
    $controllerInst = new DashboardController();
    $filtersInst = readFilters($controllerInst);
    $gotInst = $filtersInst['cliente_ids'];
    sort($gotInst);
    $expectedInst = [$empresaA, $empresaB, $empresaC, $empresaD];
    sort($expectedInst);
    if ($gotInst !== $expectedInst) {
        failFast('Instituto filtrando explicitamente A+B+C+D deveria ver as 4. Obtido: ' . implode(',', $gotInst));
    }
    $metricsInst = computeMetrics($controllerInst, $filtersInst);
    if ((int)($metricsInst['planoacao']['total'] ?? -1) !== 4) {
        failFast('Instituto vendo A+B+C+D deveria contar 4 tarefas (inclusive D, que só o Instituto pode ver). Obtido: ' . json_encode($metricsInst['planoacao'] ?? null));
    }
    ok('Cenário: Instituto continua vendo qualquer empresa, inclusive D (fora da carteira do Cliente Admin) - sem regressão de escopo');

    // ===================== CENÁRIO: Instituto filtrando só Empresa B =====================
    $_SESSION['user'] = $institutoUser;
    unset($_SESSION['__dashboard_filters']); // isola cada cenario do filtro "lembrado" da sessao anterior
    $_GET = ['route' => 'dashboard/index', 'cliente' => (string)$empresaB];
    $controllerInstB = new DashboardController();
    $filtersInstB = readFilters($controllerInstB);
    if ($filtersInstB['cliente_ids'] !== [$empresaB]) {
        failFast('Instituto filtrando Empresa B deveria ver só B. Obtido: ' . implode(',', $filtersInstB['cliente_ids']));
    }
    ok('Cenário: Instituto filtrando uma única empresa continua vendo só ela (sem regressão)');

    // ===================== CENÁRIO: período (multiempresa + período que exclui os dados) =====================
    $_SESSION['user'] = $adminUser();
    unset($_SESSION['__dashboard_filters']); // isola cada cenario do filtro "lembrado" da sessao anterior
    $_GET = ['route' => 'dashboard/index', 'month_start' => '2020-01', 'month_end' => '2020-01'];
    $controllerPeriodo = new DashboardController();
    $filtersPeriodo = readFilters($controllerPeriodo);
    if (empty($filtersPeriodo['period_ok'])) {
        failFast('Período válido (2020-01) não deveria ser rejeitado');
    }
    $metricsPeriodo = computeMetrics($controllerPeriodo, $filtersPeriodo);
    if ((int)($metricsPeriodo['planoacao']['total'] ?? -1) !== 0) {
        failFast('Período que não cobre as datas das fixtures deveria zerar o total, sem quebrar a correção de escopo. Obtido: ' . json_encode($metricsPeriodo['planoacao'] ?? null));
    }
    // E confirma que a carteira multiempresa continua correta mesmo com período explícito.
    $gotPeriodo = $filtersPeriodo['cliente_ids'];
    sort($gotPeriodo);
    if ($gotPeriodo !== $carteira) {
        failFast('Filtro de período não pode alterar o escopo multiempresa (carteira inteira sem filtro de cliente)');
    }
    ok('Cenário: filtro de período combinado com multiempresa funciona - zera contagem fora do período, mantém a carteira inteira no escopo');

    echo "dashboard_scope_multiempresa_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
} finally {
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM pdca_tasks WHERE id_cliente IN ($in)");
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    unset($_SESSION['user']);
}
