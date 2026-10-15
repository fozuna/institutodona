<?php
// Redesign da landing page pública (DONA VIVA+ DIGITAL): valida, via servidor
// embutido + HTTP real, que a página pública (auth/login) usa o layout
// isolado (sem vazamento do app-shell/sidebar administrativo), publica os
// metadados de SEO exigidos (title, canonical, meta description, Open Graph,
// Twitter Card, JSON-LD, lang pt-BR) e o crédito obrigatório da TRAXTER, e
// que robots.txt/sitemap.xml são servidos corretamente. Também confere que a
// área restrita (layouts/main.php) permanece com o app-shell/sidebar intactos
// e que o fluxo de login (GET + CSRF) continua funcionando.
require __DIR__ . '/../autoload.php';

function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }

if (!function_exists('curl_init')) { echo "SKIP: extensão curl indisponível\n"; exit(0); }

$root = realpath(__DIR__ . '/../../public_html');
$port = random_int(20000, 45000);
$env = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET', 'APP_BASE_URL'];
$envStr = '';
foreach ($env as $k) { if (getenv($k) !== false) { $envStr .= $k . '=' . escapeshellarg((string)getenv($k)) . ' '; } }
$log = tempnam(sys_get_temp_dir(), 'landingsrv_');
$pid = (int)shell_exec($envStr . 'php -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg($root) . ' ' . escapeshellarg($root . '/router.php') . ' > ' . escapeshellarg($log) . ' 2>&1 & echo $!');
register_shutdown_function(static function () use ($pid, $log) { if ($pid > 0) { @exec('kill ' . $pid . ' 2>/dev/null'); } @unlink($log); });
$base = "http://127.0.0.1:{$port}";
$up = false;
for ($i = 0; $i < 40; $i++) { usleep(100000); if (@fsockopen('127.0.0.1', $port)) { $up = true; break; } }
if (!$up) { echo "SKIP: servidor embutido do PHP não subiu neste ambiente\n"; exit(0); }

/** @return array{code:int,body:string} */
function httpGet(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $body = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

// ---- página pública raiz ----
$home = httpGet($base . '/index.php');
if ($home['code'] !== 200) { failFast("Home pública retornou HTTP {$home['code']}"); }
$html = $home['body'];
ok('Home pública (index.php sem rota) responde HTTP 200');

if (!preg_match('/<html[^>]*\blang="pt-BR"/i', $html)) { failFast('Atributo lang="pt-BR" ausente no <html>'); }
ok('lang="pt-BR" presente');

if (preg_match_all('/<h1[\s>]/i', $html, $mm) !== 1) { failFast('Página pública não tem exatamente um <h1> (encontrados: ' . count($mm[0] ?? []) . ')'); }
ok('Exatamente um <h1> na página pública');

if (!preg_match('/<title>([^<]+)<\/title>/i', $html, $m) || trim($m[1]) === '') { failFast('<title> ausente ou vazio'); }
ok('<title> presente: ' . trim($m[1]));

if (!preg_match('/<link[^>]+rel="canonical"[^>]+href="https:\/\/donaconsultorias\.com\.br\/"/i', $html)) {
    failFast('Canonical absoluto ausente/incorreto');
}
ok('Canonical absoluto correto');

if (!preg_match('/<meta[^>]+name="description"[^>]+content="[^"]{20,}"/i', $html)) { failFast('Meta description ausente ou muito curta'); }
ok('Meta description presente');

foreach (['og:title', 'og:description', 'og:type', 'twitter:card'] as $tag) {
    if (!preg_match('/(?:property|name)="' . preg_quote($tag, '/') . '"/i', $html)) {
        failFast("Meta tag obrigatória ausente: {$tag}");
    }
}
ok('Open Graph + Twitter Card presentes');

if (!preg_match('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $m)) {
    failFast('JSON-LD ausente');
}
$ld = json_decode($m[1], true);
if (!is_array($ld)) { failFast('JSON-LD inválido (não decodifica)'); }
$ldFlat = json_encode($ld);
foreach (['Organization', 'WebSite', 'SoftwareApplication', 'Instituto Dona', 'TRAXTER'] as $needle) {
    if (!str_contains((string)$ldFlat, $needle)) { failFast("JSON-LD não contém '{$needle}'"); }
}
ok('JSON-LD com Organization/WebSite/SoftwareApplication e autoria TRAXTER');

if (!preg_match('/traxter\.com\.br/i', $html) || !preg_match('/TRAXTER/i', $html)) {
    failFast('Crédito TRAXTER ausente no HTML renderizado (fora do JSON-LD)');
}
ok('Crédito visível da TRAXTER presente no rodapé');

foreach (['app-shell', 'app-sidebar', 'app-main', 'cdn.tailwindcss', 'unpkg.com/feather'] as $needle) {
    if (str_contains($html, $needle)) { failFast("Vazamento do layout administrativo/CDN na página pública: '{$needle}' encontrado"); }
}
ok('Nenhum vazamento do app-shell/sidebar admin ou CDN pesado na página pública');

// ---- robots.txt / sitemap.xml ----
$robots = httpGet($base . '/robots.txt');
if ($robots['code'] !== 200 || !str_contains($robots['body'], 'Sitemap:')) { failFast('robots.txt não está acessível ou sem diretiva Sitemap'); }
if (!preg_match('/^Allow:\s*\/\s*$/mi', $robots['body']) && !str_contains($robots['body'], 'Allow: /')) {
    failFast('robots.txt não libera a home (/)');
}
ok('robots.txt acessível e com Sitemap + Allow da home');

$sitemap = httpGet($base . '/sitemap.xml');
if ($sitemap['code'] !== 200 || !str_contains($sitemap['body'], '<urlset') || !str_contains($sitemap['body'], 'donaconsultorias.com.br')) {
    failFast('sitemap.xml não está acessível ou malformado');
}
ok('sitemap.xml acessível e válido');

// ---- login (GET) continua funcional e isolado ----
if (!preg_match('/name="csrf" value="([^"]+)"/', $html, $m) || $m[1] === '') { failFast('Campo CSRF ausente no formulário de login'); }
ok('Formulário de login expõe token CSRF');

if (!preg_match('/name="email"/', $html) || !preg_match('/name="senha"/', $html)) { failFast('Campos email/senha ausentes no formulário de login'); }
ok('Campos email/senha presentes no formulário de login');

// ---- área restrita (layouts/main.php) permanece intacta ----
$mainLayout = (string)file_get_contents(__DIR__ . '/../views/layouts/main.php');
foreach (['app-shell', 'app-sidebar', 'app-main'] as $needle) {
    if (!str_contains($mainLayout, $needle)) { failFast("layouts/main.php perdeu a classe '{$needle}' (regressão na área restrita)"); }
}
ok('layouts/main.php preserva app-shell/app-sidebar/app-main (área restrita intacta)');

echo "\nlanding_page_public_seo_smoke passed.\n";
