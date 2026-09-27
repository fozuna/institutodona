<?php
// Pilar de Pessoas - Sprint 05: fluxo HTTP real (servidor embutido do PHP +
// login de verdade + cookies). Valida 200/redirects, CSRF em mutação,
// escaping, IDs inválidos, cross-tenant, sem sessão e sem permissão.
// Pula (SKIP) apenas se o ambiente não permitir subir o servidor embutido.
require __DIR__ . '/../autoload.php';
require __DIR__ . '/helpers/pessoas_sprint05_fixtures.php';

use App\Database\Database;

function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }

if (!function_exists('curl_init')) { echo "SKIP: extensão curl indisponível\n"; exit(0); }

$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$F = s05_fixtures('A1 "><img src=x onerror=alert(88)> ' . bin2hex(random_bytes(2)));
$pdo = Database::getConnection();
$eA = $F['eA']; $eB = $F['B']['eid'];
$senha = 'S05-Teste!' . bin2hex(random_bytes(3));
$mkUser = static function (string $tipo, ?int $cliente) use ($pdo, $senha, $F): string {
    $email = "s05.{$tipo}." . bin2hex(random_bytes(3)) . '@test.local';
    $id = s05_ins($pdo, 'usuarios', ['nome' => "S05 {$tipo}", 'email' => $email, 'senha_hash' => password_hash($senha, PASSWORD_DEFAULT), 'tipo_acesso' => $tipo, 'id_cliente' => $cliente]);
    $GLOBALS['__s05_cleanup']['users'][] = $id;
    return $email;
};
$uInst = $mkUser('instituto', null);
$uAdmA = $mkUser('cliente_admin', $eA);
$uLeitorA = $mkUser('cliente', $eA);

// ---- servidor embutido ----
$root = realpath(__DIR__ . '/../../public_html');
$port = random_int(20000, 45000);
$env = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET', 'APP_BASE_URL'];
$envStr = '';
foreach ($env as $k) { if (getenv($k) !== false) { $envStr .= $k . '=' . escapeshellarg((string)getenv($k)) . ' '; } }
$log = tempnam(sys_get_temp_dir(), 's05srv_');
$pid = (int)shell_exec($envStr . 'php -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg($root) . ' ' . escapeshellarg($root . '/router.php') . ' > ' . escapeshellarg($log) . ' 2>&1 & echo $!');
register_shutdown_function(static function () use ($pid, $log) { if ($pid > 0) { @exec('kill ' . $pid . ' 2>/dev/null'); } @unlink($log); });
$base = "http://127.0.0.1:{$port}/index.php";
$up = false;
for ($i = 0; $i < 40; $i++) { usleep(100000); if (@fsockopen('127.0.0.1', $port)) { $up = true; break; } }
if (!$up) { echo "SKIP: servidor embutido do PHP não subiu neste ambiente\n"; exit(0); }

/** @return array{code:int,location:string,body:string} */
function http(string $url, ?string $jar, array $post = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20]);
    if ($jar !== null) { curl_setopt($ch, CURLOPT_COOKIEJAR, $jar); curl_setopt($ch, CURLOPT_COOKIEFILE, $jar); }
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $raw = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hsize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headers = substr($raw, 0, $hsize);
    preg_match('/^Location:\s*(.+)$/mi', $headers, $mm);
    return ['code' => $code, 'location' => trim($mm[1] ?? ''), 'body' => substr($raw, $hsize)];
}
function csrfDe(string $html): string { preg_match('/name="csrf" value="([^"]+)"/', $html, $m); return $m[1] ?? ''; }
function login(string $base, string $email, string $senha): string
{
    $jar = tempnam(sys_get_temp_dir(), 's05jar_');
    $page = http($base . '?route=auth/login', $jar);
    $r = http($base . '?route=auth/doLogin', $jar, ['csrf' => csrfDe($page['body']), 'email' => $email, 'senha' => $senha]);
    if ($r['code'] !== 302 || str_contains($r['location'], 'auth/login')) { failFast("Login falhou para $email (HTTP {$r['code']} {$r['location']})"); }
    return $jar;
}
$naoEncontrado = static fn(array $r): bool => $r['code'] === 404 || str_contains($r['body'], 'Conteúdo não encontrado');

// ---- Sem sessão ----
foreach (['pessoas/gaps', 'pessoas/acoes', 'pessoas/necessidades', 'pessoas/colaboradorHistorico&id=' . $F['a1']] as $rota) {
    $r = http($base . '?route=' . $rota, null);
    if ($r['code'] !== 302 || !str_contains($r['location'], 'auth/login')) { failFast("Sem sessão, $rota deveria redirecionar para o login (HTTP {$r['code']})"); }
}
ok('Sem sessão: todas as rotas novas redirecionam para o login');

// ---- Instituto: fluxo completo ----
$jar = login($base, $uInst, $senha);
$fluxo = [
    'pessoas/visaoGeral&empresa_id=' . $eA => 'Pontos de Atenção',
    'pessoas/colaboradorHistorico&id=' . $F['a1'] => 'Central do Colaborador',
    'pessoas/gaps&empresa_id=' . $eA . '&status=aberto&tratamento=sem_acao' => 'gA1 sem acao',
    'pessoas/acoes&empresa_id=' . $eA . '&atrasadas=1' => 'ac4 vencida treinamento',
    'pessoas/necessidades&empresa_id=' . $eA . '&status=pendente' => 'n1 pendente alta',
    'pessoas/pdiIndex&empresa_id=' . $eA => 'PDI a1',
    'pessoas/gapShow&id=' . $F['g']['gA1'] => 'gA1 sem acao',
    'pessoas/acaoShow&id=' . $F['ac']['ac1'] => 'ac1 vencida plano',
];
foreach ($fluxo as $rota => $texto) {
    $r = http($base . '?route=' . $rota, $jar);
    if ($r['code'] !== 200) { failFast("Instituto: $rota deveria responder 200 (obtido {$r['code']})"); }
    if (!str_contains($r['body'], $texto)) { failFast("Instituto: $rota deveria conter \"$texto\""); }
    if (!str_contains($r['body'], 'aria-label="Navegação do Pilar de Pessoas"')) { failFast("Subnavegação ausente em $rota"); }
    if (str_contains($r['body'], '<img src=x onerror=alert(88)>')) { failFast("XSS não escapado em $rota"); }
}
$gaps = http($base . '?route=pessoas/gaps&empresa_id=' . $eA, $jar)['body'];
if (!str_contains($gaps, '&quot;&gt;&lt;img src=x onerror=alert(88)&gt;')) { failFast('Nome do colaborador deveria aparecer escapado na listagem'); }
ok('Instituto: Visão Geral → Central → GAPs → Ações → Necessidades → PDI → GAP → Ação, todos 200, com subnavegação e escaping');

// IDs inválidos
$r = http($base . '?route=pessoas/colaboradorHistorico&id=999999999', $jar);
if ($r['code'] !== 302 || !str_contains($r['location'], 'colaboradores/index')) { failFast('Colaborador inexistente deveria redirecionar'); }
$r = http($base . '?route=pessoas/gaps&page=abc&colaborador_id=xyz&status=../../etc', $jar);
if ($r['code'] !== 200) { failFast('Parâmetros inválidos não podem gerar erro 500 (obtido ' . $r['code'] . ')'); }
ok('IDs e parâmetros inválidos tratados (redirect / 200 sem erro)');

// CSRF em mutação (rota existente usada pela Central)
$r = http($base . '?route=pessoas/gapCreate', $jar, ['colaborador_id' => $F['a1'], 'titulo' => 'sem csrf']);
if ($r['code'] !== 400) { failFast('POST sem CSRF deveria ser recusado com 400 (obtido ' . $r['code'] . ')'); }
$central = http($base . '?route=pessoas/colaboradorHistorico&id=' . $F['a1'], $jar)['body'];
$r = http($base . '?route=pessoas/gapCreate', $jar, ['csrf' => csrfDe($central), 'colaborador_id' => $F['a1'], 'titulo' => 'GAP via Central S05', 'prioridade' => 'alta']);
if ($r['code'] !== 302 || !str_contains($r['location'], 'pessoas/gapShow')) { failFast('POST com CSRF válido a partir da Central deveria criar o GAP'); }
$criado = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_gaps WHERE colaborador_id = " . (int)$F['a1'] . " AND titulo = 'GAP via Central S05'")->fetchColumn();
if ($criado !== 1) { failFast('GAP criado pela Central não encontrado'); }
ok('CSRF: mutação sem token recusada (400); com token da Central aceita');

// ---- Cliente Admin da empresa A ----
$jarA = login($base, $uAdmA, $senha);
if (http($base . '?route=pessoas/gaps', $jarA)['code'] !== 200) { failFast('Cliente Admin deveria acessar GAPs'); }
$r = http($base . '?route=pessoas/gaps', $jarA);
if (str_contains($r['body'], 'gB1')) { failFast('Cliente Admin de A não pode ver GAP de B'); }
if (!$naoEncontrado(http($base . '?route=pessoas/gaps&empresa_id=' . $eB, $jarA))) { failFast('empresa_id de outro tenant deveria resultar em 404 oculto'); }
if (!$naoEncontrado(http($base . '?route=pessoas/acoes&empresa_id=' . $eB, $jarA))) { failFast('empresa_id de outro tenant (ações) deveria resultar em 404 oculto'); }
$r = http($base . '?route=pessoas/colaboradorHistorico&id=' . $F['b1'], $jarA);
if ($r['code'] !== 302 || !str_contains($r['location'], 'colaboradores/index')) { failFast('Central de colaborador de outro tenant deveria ser bloqueada'); }
$r = http($base . '?route=pessoas/necessidades&colaborador_id=' . $F['b1'], $jarA);
if ($r['code'] !== 200 || str_contains($r['body'], 'nB') && str_contains($r['body'], 'B1 ' . $F['sfx'])) { failFast('colaborador_id de outro tenant não pode vazar dados'); }
if (!str_contains($r['body'], 'n1 pendente alta')) { failFast('colaborador_id de outro tenant deveria ser descartado (listagem normal do próprio tenant)'); }
ok('Cliente Admin: acesso ao próprio tenant; empresa/colaborador de outro tenant bloqueados');

// ---- Usuário sem permissão (perfil cliente comum) ----
$jarL = login($base, $uLeitorA, $senha);
foreach (['pessoas/gaps', 'pessoas/acoes', 'pessoas/necessidades', 'pessoas/colaboradorHistorico&id=' . $F['a1']] as $rota) {
    if (!$naoEncontrado(http($base . '?route=' . $rota, $jarL))) { failFast("Perfil sem permissão deveria receber 404 oculto em $rota"); }
}
ok('Usuário sem permissão: 404 oculto nas rotas do Pilar');

foreach ([$jar, $jarA, $jarL] as $j) { @unlink($j); }
echo "\nFluxo HTTP da Sprint 05 aprovado.\n";
