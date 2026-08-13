<?php
// Executa uma acao do CronogramaController num processo separado, para
// validar RBAC/tenant de ponta a ponta sem sofrer o exit() de
// BaseController::denyAccess()/respondNotFound() (que mata o processo PHP
// do teste principal antes da limpeza de fixtures). Mesmo padrao de
// subprocesso + register_shutdown_function usado em
// auditorias_consultor_action_probe.php.
//
// Uso: php cronograma_evento_probe.php <method> <role> <userId> <idCliente|""> <action> <eventoId> <idCronograma> <escopo> <extraGetOrPostJsonBase64> <withCsrf:0|1>
// action: eventoDrawer|updateEvento
// Stdout: conteudo echoado pelo controller (se houver) seguido do marcador
// ---PROBE_RESULT--- e uma linha JSON com {status, location}.

namespace App\Core {
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__probe_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require_once __DIR__ . '/../../autoload.php';

    use App\Controllers\CronogramaController;
    use App\Core\Security;

    session_start();

    register_shutdown_function(function () {
        echo "\n---PROBE_RESULT---\n";
        echo json_encode([
            'status' => http_response_code(),
            'location' => $GLOBALS['__probe_location'] ?? '',
        ], JSON_UNESCAPED_UNICODE) . "\n";
    });

    $method = (string)($argv[1] ?? 'GET');
    $role = (string)($argv[2] ?? 'instituto');
    $userId = (int)($argv[3] ?? 9001);
    $idClienteRaw = (string)($argv[4] ?? '');
    $idCliente = $idClienteRaw !== '' ? (int)$idClienteRaw : null;
    $action = (string)($argv[5] ?? 'eventoDrawer');
    $eventoId = (int)($argv[6] ?? 0);
    $idCronograma = (int)($argv[7] ?? 0);
    $escopo = (string)($argv[8] ?? 'evento');
    $extraJsonB64 = (string)($argv[9] ?? '');
    $withCsrf = (string)($argv[10] ?? '1') === '1';

    $extra = [];
    if ($extraJsonB64 !== '') {
        $decoded = json_decode((string)base64_decode($extraJsonB64), true);
        if (is_array($decoded)) {
            $extra = $decoded;
        }
    }

    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_SESSION['user'] = [
        'id' => $userId,
        'nome' => 'Probe Cronograma',
        'email' => 'probe.cronograma@test.local',
        'tipo_acesso' => $role,
        'id_cliente' => $idCliente,
        'allowed_client_ids' => $idCliente !== null ? [$idCliente] : [],
    ];

    $controller = new CronogramaController();
    if ($action === 'updateEvento') {
        $_GET['route'] = 'cronograma/updateEvento';
        $_POST = array_merge([
            'id_evento' => (string)$eventoId,
            'id_cronograma' => (string)$idCronograma,
            'escopo' => $escopo,
        ], $extra);
        if ($withCsrf) {
            $_POST['csrf'] = Security::csrfToken();
        }
        $controller->updateEvento();
    } else {
        $_GET = array_merge([
            'route' => 'cronograma/eventoDrawer',
            'id_evento' => (string)$eventoId,
            'id_cronograma' => (string)$idCronograma,
            'escopo' => $escopo,
            'mode' => 'edit',
        ], $extra);
        $controller->eventoDrawer();
    }
}
