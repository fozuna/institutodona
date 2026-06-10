<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

function setUser(string $tipo, string $platform): void
{
    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Test',
        'email' => 'test@test.local',
        'tipo_acesso' => $tipo,
        'id_cliente' => null,
        'platform_access' => $platform,
        'allowed_client_ids' => [],
        'selected_client_ids' => [],
        'unrestricted_access' => ($tipo === 'instituto'),
    ];
}

@session_start();

setUser('cliente_admin', 'WEB');
if (!Auth::canUseWeb() || Auth::canUsePwa()) {
    failFast('WEB deveria permitir somente WEB');
}
ok('WEB permite somente WEB');

setUser('cliente_admin', 'PWA');
if (Auth::canUseWeb() || !Auth::canUsePwa()) {
    failFast('PWA deveria permitir somente PWA');
}
ok('PWA permite somente PWA');

setUser('cliente_admin', 'WEB_PWA');
if (!Auth::canUseWeb() || !Auth::canUsePwa()) {
    failFast('WEB_PWA deveria permitir WEB e PWA');
}
ok('WEB_PWA permite WEB e PWA');

setUser('instituto', 'PWA');
if (!Auth::canUseWeb() || !Auth::canUsePwa()) {
    failFast('Instituto deveria permitir WEB e PWA independentemente do campo');
}
ok('Instituto permite ambos');

echo "All platform access unit tests passed.\n";

