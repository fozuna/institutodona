<?php
// Executa uma acao do AtasController num processo separado, para validar
// RBAC de ponta a ponta (rota + controller + model) sem sofrer o exit() de
// AtasController::download() (sucesso) nem o de BaseController::respondNotFound()
// no caminho JSON. Mesmo padrao de subprocesso + register_shutdown_function
// usado em cronograma_evento_probe.php.
//
// Uso: php atas_probe.php <method> <role> <userId> <action> <extraJsonBase64> <withCsrf:0|1>
// action: index|create|store|download|delete
// Stdout: conteudo echoado/streamado pelo controller, seguido do marcador
// ---PROBE_RESULT--- e uma linha JSON com {status, location}.

namespace App\Core {
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__probe_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace App\Controllers {
    // is_uploaded_file()/move_uploaded_file() so retornam true para arquivos
    // de fato submetidos via multipart real; num probe CLI isso sempre falharia,
    // entao substituimos apenas para as chamadas feitas de dentro do namespace
    // App\Controllers (ou seja, o proprio AtasController::store()) por
    // equivalentes que operam sobre um arquivo temporario preparado pelo teste.
    function is_uploaded_file(string $filename): bool
    {
        return is_file($filename);
    }

    function move_uploaded_file(string $from, string $to): bool
    {
        return @rename($from, $to) || (@copy($from, $to) && @unlink($from));
    }

    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__probe_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace {
    require_once __DIR__ . '/../../autoload.php';

    use App\Controllers\AtasController;
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
    $action = (string)($argv[4] ?? 'index');
    $extraJsonB64 = (string)($argv[5] ?? '');
    $withCsrf = (string)($argv[6] ?? '1') === '1';

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
        'nome' => 'Probe Atas',
        'email' => 'probe.atas@test.local',
        'tipo_acesso' => $role,
        'id_cliente' => null,
        'allowed_client_ids' => [],
    ];

    $controller = new AtasController();

    if ($action === 'store') {
        $_GET['route'] = 'atas/store';
        $_POST = [
            'nome' => (string)($extra['nome'] ?? ''),
            'descricao' => (string)($extra['descricao'] ?? ''),
        ];
        if ($withCsrf) {
            $_POST['csrf'] = Security::csrfToken();
        }
        $_FILES['arquivo'] = [
            'name' => (string)($extra['file_name'] ?? ''),
            'type' => (string)($extra['file_type'] ?? ''),
            'tmp_name' => (string)($extra['file_tmp'] ?? ''),
            'error' => (int)($extra['file_error'] ?? UPLOAD_ERR_OK),
            'size' => (int)($extra['file_size'] ?? 0),
        ];
        $controller->store();
    } elseif ($action === 'download') {
        $_GET = ['route' => 'atas/download', 'id' => (string)($extra['id'] ?? 0)];
        $controller->download();
    } elseif ($action === 'delete') {
        $_GET['route'] = 'atas/delete';
        $_POST = ['id' => (string)($extra['id'] ?? 0)];
        if ($withCsrf) {
            $_POST['csrf'] = Security::csrfToken();
        }
        $controller->delete();
    } elseif ($action === 'create') {
        $_GET = ['route' => 'atas/create'];
        $controller->create();
    } else {
        $_GET = array_merge(['route' => 'atas/index'], $extra);
        $controller->index();
    }
}
