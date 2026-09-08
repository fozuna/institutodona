<?php
// Executa ManuaisController::update() num processo separado, para validar o
// fluxo real (RBAC + Controller + Model) sem sofrer o exit() incondicional de
// BaseController::redirect() (chamado no caminho de sucesso) - mesmo padrao
// de subprocesso ja usado em cronograma_evento_probe.php / atas_probe.php /
// dashboard_probe.php.
//
// Uso: php manuais_update_probe.php <role> <userId> <allowedClientIdsCsv> <id>
//      <empresaId> <departamentoId> <nome> <descricao> <filiaisIdsCsv>
//      <withFile:0|1> <withCsrf:0|1> <pdfPath>
// filiaisIdsCsv: "__NONE__" = nao envia o campo; "" = envia [] vazio; "1,2" = envia [1,2].
// Stdout: ---PROBE_RESULT--- seguido de uma linha JSON com {status, location}.

namespace App\Core {
    function header(string $value, bool $replace = true, int $responseCode = 0): void
    {
        if (stripos($value, 'Location:') === 0) {
            $GLOBALS['__probe_location'] = trim(substr($value, strlen('Location:')));
        }
    }
}

namespace App\Controllers {
    // Mesma tecnica de is_uploaded_file()/move_uploaded_file() usada em
    // atas_probe.php: um upload real via multipart nao existe num probe CLI,
    // entao essas duas funcoes (chamadas de dentro do namespace
    // App\Controllers, i.e., o proprio ManuaisController::update()) sao
    // substituidas para operar sobre o arquivo temporario preparado pelo teste.
    function is_uploaded_file(string $filename): bool
    {
        return is_file($filename);
    }

    function move_uploaded_file(string $from, string $to): bool
    {
        return @rename($from, $to) || (@copy($from, $to) && @unlink($from));
    }
}

namespace {
    require_once __DIR__ . '/../../autoload.php';

    use App\Controllers\ManuaisController;
    use App\Core\Security;

    session_start();

    register_shutdown_function(function () {
        echo "\n---PROBE_RESULT---\n";
        echo json_encode([
            'status' => http_response_code(),
            'location' => $GLOBALS['__probe_location'] ?? '',
        ], JSON_UNESCAPED_UNICODE) . "\n";
    });

    $role = (string)($argv[1] ?? 'instituto');
    $userId = (int)($argv[2] ?? 9001);
    $allowedCsv = (string)($argv[3] ?? '');
    $allowed = $allowedCsv !== '' ? array_map('intval', explode(',', $allowedCsv)) : [];
    $id = (string)($argv[4] ?? '0');
    $empresaId = (string)($argv[5] ?? '0');
    $departamentoId = (string)($argv[6] ?? '0');
    $nome = (string)($argv[7] ?? '');
    $descricao = (string)($argv[8] ?? '');
    $filiaisCsv = (string)($argv[9] ?? '__NONE__');
    $withFile = (string)($argv[10] ?? '0') === '1';
    $withCsrf = (string)($argv[11] ?? '1') === '1';
    $pdfPath = (string)($argv[12] ?? '');

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION['user'] = [
        'id' => $userId,
        'nome' => 'Probe Manuais',
        'email' => 'probe.manuais@test.local',
        'tipo_acesso' => $role,
        'id_cliente' => null,
        'allowed_client_ids' => $allowed,
    ];

    $_GET = ['route' => 'manuais/update'];
    $_POST = [
        'id' => $id,
        'empresa_id' => $empresaId,
        'departamento_id' => $departamentoId,
        'nome' => $nome,
        'descricao' => $descricao,
    ];
    if ($filiaisCsv !== '__NONE__') {
        $_POST['filiais_ids'] = $filiaisCsv !== '' ? array_map('intval', explode(',', $filiaisCsv)) : [];
    }
    if ($withCsrf) {
        $_POST['csrf'] = Security::csrfToken();
    }
    if ($withFile) {
        $_FILES['arquivo'] = [
            'name' => 'novo.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $pdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => is_file($pdfPath) ? filesize($pdfPath) : 0,
        ];
    }

    (new ManuaisController())->update();
}
