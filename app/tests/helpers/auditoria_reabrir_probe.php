<?php
// Executa AuditoriasController::reabrir() num processo separado, pois os
// caminhos de sucesso/erro usam BaseController::redirect() (App\Core), que
// termina com exit() - inviavel de chamar repetidamente no mesmo processo de
// teste (exit() encerraria o script de teste no meio, pulando a limpeza no
// finally). headers_list() nao funciona no SAPI cli (sempre retorna vazio),
// entao capturamos o header() real via override de funcao por namespace -
// BaseController::redirect() esta em App\Core, entao o override precisa
// estar nesse namespace (nao em App\Controllers).
//
// Uso: php auditoria_reabrir_probe.php <method> <role> <userId> <idCliente|""> <auditoriaId> <motivo> <withCsrf:0|1>
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
    $auditoriaId = (int)($argv[5] ?? 0);
    $motivo = (string)($argv[6] ?? '');
    $withCsrf = (string)($argv[7] ?? '1') === '1';

    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_GET['route'] = 'auditorias/reabrir';
    $_SESSION['user'] = [
        'id' => $userId,
        'nome' => 'Probe Reabrir',
        'email' => 'probe.reabrir@test.local',
        'tipo_acesso' => $role,
        'id_cliente' => $idCliente,
        'allowed_client_ids' => $idCliente !== null ? [$idCliente] : [],
    ];
    $_POST = ['id' => (string)$auditoriaId, 'motivo' => $motivo];
    if ($withCsrf) {
        $_POST['csrf'] = Security::csrfToken();
    }

    (new AuditoriasController())->reabrir();
}
