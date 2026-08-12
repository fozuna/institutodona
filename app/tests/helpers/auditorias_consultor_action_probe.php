<?php
// Executa uma acao do AuditoriasController num processo separado, para
// validar de ponta a ponta (RBAC + controller) as restricoes especificas
// do Consultor introduzidas na Sprint B / Achado B: delete(), update()
// sobre auditoria Realizada, editarRealizada() e atualizarObservacoes().
// Mesmo padrao de subprocesso + override de header() por namespace usado
// pelos probes de reabertura/correcao de classificacao (necessario porque
// varios desses caminhos chamam BaseController::redirect() -> exit(), e
// porque headers_list() nao funciona no SAPI cli).
//
// Uso: php auditorias_consultor_action_probe.php <method> <role> <userId> <idCliente|""> <controllerAction> <auditoriaId> <postJsonBase64> <withCsrf:0|1>
// controllerAction: store|update|delete|editarRealizada|atualizarObservacoes|auditar|finalizar
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

    use App\Controllers\AuditoriasController;
    use App\Core\Security;

    session_start();

    register_shutdown_function(function () {
        echo "\n---PROBE_RESULT---\n";
        echo json_encode([
            'status' => http_response_code(),
            'location' => $GLOBALS['__probe_location'] ?? '',
        ], JSON_UNESCAPED_UNICODE) . "\n";
    });

    $method = (string)($argv[1] ?? 'POST');
    $role = (string)($argv[2] ?? 'instituto');
    $userId = (int)($argv[3] ?? 9001);
    $idClienteRaw = (string)($argv[4] ?? '');
    $idCliente = $idClienteRaw !== '' ? (int)$idClienteRaw : null;
    $controllerAction = (string)($argv[5] ?? 'delete');
    $auditoriaId = (int)($argv[6] ?? 0);
    $postJsonB64 = (string)($argv[7] ?? '');
    $withCsrf = (string)($argv[8] ?? '1') === '1';

    $extraPost = [];
    if ($postJsonB64 !== '') {
        $decoded = json_decode((string)base64_decode($postJsonB64), true);
        if (is_array($decoded)) {
            $extraPost = $decoded;
        }
    }

    $routeByAction = [
        'store' => 'auditorias/store',
        'update' => 'auditorias/update',
        'delete' => 'auditorias/delete',
        'editarRealizada' => 'auditorias/editar_realizada',
        'atualizarObservacoes' => 'auditorias/atualizar_observacoes',
        'auditar' => 'auditorias/auditar',
        'finalizar' => 'auditorias/finalizar',
    ];

    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_GET['route'] = $routeByAction[$controllerAction] ?? 'auditorias/index';
    $_SESSION['user'] = [
        'id' => $userId,
        'nome' => 'Probe Consultor Acoes',
        'email' => 'probe.consultor.acoes@test.local',
        'tipo_acesso' => $role,
        'id_cliente' => $idCliente,
        'allowed_client_ids' => $idCliente !== null ? [$idCliente] : [],
    ];
    $_POST = array_merge(['id' => (string)$auditoriaId], $extraPost);
    $_GET['id'] = (string)$auditoriaId;
    if ($withCsrf) {
        $_POST['csrf'] = Security::csrfToken();
    }

    $controller = new AuditoriasController();
    switch ($controllerAction) {
        case 'store':
            $controller->store();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'editarRealizada':
            $controller->editarRealizada();
            break;
        case 'atualizarObservacoes':
            $controller->atualizarObservacoes();
            break;
        case 'auditar':
            $controller->auditar();
            break;
        case 'finalizar':
            $controller->finalizar();
            break;
        default:
            echo 'Acao de probe desconhecida: ' . $controllerAction;
    }
}
