<?php
require __DIR__ . '/../autoload.php';

use App\Core\Security;
use App\Controllers\IndicadoresController;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

putenv('PDF_FORCE_MISSING=1');

$csrf = Security::csrfToken();
$fakePng = base64_encode("\x89PNG\r\n\x1a\n" . str_repeat("0", 128));
$charts = [[
    'title' => 'Teste',
    'trend' => 'Tendência: estável',
    'meta' => '10,00',
    'achieved' => '9,00',
    'image' => 'data:image/png;base64,' . $fakePng,
]];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf' => $csrf,
    'cliente_id' => 1,
    'cliente_nome' => 'Cliente',
    'charts' => json_encode($charts, JSON_UNESCAPED_UNICODE),
];
$_GET = ['route' => 'indicadores/chartsPdf'];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

ob_start();
(new IndicadoresController())->chartsPdf();
$out = ob_get_clean();

if (http_response_code() !== 503) {
    failFast('Esperado 503 quando PDF_FORCE_MISSING=1');
}
ok('HTTP 503');

$payload = json_decode((string)$out, true);
if (!is_array($payload) || empty($payload['message'])) {
    failFast('Resposta JSON inválida: ' . $out);
}
ok('Mensagem amigável retornada');

putenv('PDF_FORCE_MISSING');
echo "All indicadores chartsPdf force missing unit tests passed.\n";

