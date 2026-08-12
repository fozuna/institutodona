<?php
// Executa exportPlanos()/export() num processo PHP separado, pois esses metodos
// terminam com exit() (tanto no download com sucesso quanto no 404 oculto do
// RBAC de rota) - inviavel de chamar diretamente dentro do processo do teste.
// Uso: php export_planos_probe.php <route> <role> <allowedClientIds csv> <querystring>
// Stdout: corpo bruto da resposta (bytes do XLSX em caso de sucesso, ou texto de erro).
// Stderr: uma linha por header emitido via header(), prefixada com "HEADER:".

namespace App\Controllers {
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        fwrite(STDERR, "HEADER:" . $value . "\n");
    }
}

namespace {
    require_once __DIR__ . '/../../autoload.php';

    use App\Controllers\ClientesController;
    use App\Controllers\PlanoAcaoController;

    session_start();

    $route = (string)($argv[1] ?? '');
    $role = (string)($argv[2] ?? 'instituto');
    $allowedClientIds = array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', (string)($argv[3] ?? '')) ?: [])));
    $queryString = (string)($argv[4] ?? '');

    $_GET['route'] = $route;
    if ($queryString !== '') {
        parse_str($queryString, $extra);
        if (is_array($extra)) {
            foreach ($extra as $k => $v) {
                $_GET[$k] = $v;
            }
        }
    }
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION['user'] = [
        'id' => 9999,
        'nome' => 'Probe',
        'email' => 'probe@test.local',
        'tipo_acesso' => $role,
        'allowed_client_ids' => $allowedClientIds,
    ];

    if ($route === 'clientes/exportPlanos') {
        (new ClientesController())->exportPlanos();
    } elseif ($route === 'planoacao/export') {
        (new PlanoAcaoController())->export();
    } else {
        fwrite(STDERR, "PROBE_ERROR: rota desconhecida\n");
        exit(2);
    }
}
