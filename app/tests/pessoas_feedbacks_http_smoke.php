<?php
// Pilar de Pessoas - Sprint 05.1: fluxo HTTP real de Feedbacks (servidor
// embutido + login de verdade + cookies). login -> Pessoas -> Feedbacks ->
// filtros -> Central do Colaborador -> Registrar Feedback -> salvar ->
// voltar -> histórico atualizado -> localizar na listagem operacional.
// Também cobre CSRF, ID inexistente, cross-tenant, XSS e RBAC.
// Pula (SKIP) apenas se o ambiente não permitir subir o servidor embutido.
require __DIR__ . '/../autoload.php';
require __DIR__ . '/helpers/pessoas_sprint05_fixtures.php';

use App\Database\Database;

function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }

if (!function_exists('curl_init')) { echo "SKIP: extensão curl indisponível\n"; exit(0); }

$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];
$F = s05_fixtures();
$pdo = Database::getConnection();
$eA = $F['eA']; $eB = $F['B']['eid'];
$senha = 'S051-Teste!' . bin2hex(random_bytes(3));
$mkUser = static function (string $tipo, ?int $cliente) use ($pdo, $senha): string {
    $email = "s051.{$tipo}." . bin2hex(random_bytes(3)) . '@test.local';
    $id = s05_ins($pdo, 'usuarios', ['nome' => "S051 {$tipo}", 'email' => $email, 'senha_hash' => password_hash($senha, PASSWORD_DEFAULT), 'tipo_acesso' => $tipo, 'id_cliente' => $cliente]);
    $GLOBALS['__s05_cleanup']['users'][] = $id;
    return $email;
};
$uInst = $mkUser('instituto', null);
$uAdmA = $mkUser('cliente_admin', $eA);
$uLeitorA = $mkUser('cliente', $eA);
$gapB = s05_ins($pdo, 'pessoas_gaps', ['empresa_id' => $eB, 'colaborador_id' => $F['b1'], 'titulo' => 'gap de B para tentativa cross-tenant', 'status' => 'aberto']);

// ---- servidor embutido ----
$root = realpath(__DIR__ . '/../../public_html');
$port = random_int(20000, 45000);
$env = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET', 'APP_BASE_URL'];
$envStr = '';
foreach ($env as $k) { if (getenv($k) !== false) { $envStr .= $k . '=' . escapeshellarg((string)getenv($k)) . ' '; } }
$log = tempnam(sys_get_temp_dir(), 's051srv_');
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
    $jar = tempnam(sys_get_temp_dir(), 's051jar_');
    $page = http($base . '?route=auth/login', $jar);
    $r = http($base . '?route=auth/doLogin', $jar, ['csrf' => csrfDe($page['body']), 'email' => $email, 'senha' => $senha]);
    if ($r['code'] !== 302 || str_contains($r['location'], 'auth/login')) { failFast("Login falhou para $email (HTTP {$r['code']} {$r['location']})"); }
    return $jar;
}
$naoEncontrado = static fn(array $r): bool => $r['code'] === 404 || str_contains($r['body'], 'Conteúdo não encontrado');

// ---- Sem sessão: redireciona ----
foreach (['pessoas/feedbacks', 'pessoas/feedbackShow&id=1'] as $rota) {
    $r = http($base . '?route=' . $rota, null);
    if ($r['code'] !== 302 || !str_contains($r['location'], 'auth/login')) { failFast("Sem sessão, $rota deveria redirecionar para o login (HTTP {$r['code']})"); }
}
ok('Sem sessão: rotas de Feedbacks redirecionam para o login');

// ---- Instituto: fluxo completo login -> Pessoas -> Feedbacks -> filtros -> Central -> Registrar -> salvar -> voltar -> histórico -> localizar ----
$jar = login($base, $uInst, $senha);

$lista = http($base . '?route=pessoas/feedbacks&empresa_id=' . $eA, $jar);
if ($lista['code'] !== 200 || !str_contains($lista['body'], 'Feedback s05')) { failFast('Listagem de Feedbacks deveria mostrar o feedback de fixture'); }
if (!str_contains($lista['body'], 'aria-label="Navegação do Pilar de Pessoas"')) { failFast('Subnavegação ausente na listagem de Feedbacks'); }
ok('Pessoas → Feedbacks: listagem abre com a subnavegação e mostra os registros da empresa');

$filtroTipo = http($base . '?route=pessoas/feedbacks&empresa_id=' . $eA . '&tipo=positivo', $jar);
if ($filtroTipo['code'] !== 200 || !str_contains($filtroTipo['body'], 'Feedback s05')) { failFast('Filtro tipo=positivo deveria manter o feedback positivo de fixture'); }
$filtroColab = http($base . '?route=pessoas/feedbacks&empresa_id=' . $eA . '&colaborador_id=' . $F['a1'], $jar);
if ($filtroColab['code'] !== 200 || !str_contains($filtroColab['body'], 'Feedback s05')) { failFast('Filtro por colaborador deveria manter o feedback de a1'); }
ok('Filtros (tipo, colaborador) preservados na query string e aplicados');

$central = http($base . '?route=pessoas/colaboradorHistorico&id=' . $F['a1'], $jar);
if ($central['code'] !== 200 || !str_contains($central['body'], 'Registrar feedback')) { failFast('Central do Colaborador deveria oferecer "Registrar feedback"'); }
if (!str_contains($central['body'], 'Feedback s05')) { failFast('Central deveria listar o feedback já existente no histórico'); }
$csrf = csrfDe($central['body']);

// CSRF inválido primeiro (não deve criar nada)
$antes = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_feedbacks WHERE colaborador_id = " . (int)$F['a1'])->fetchColumn();
$semCsrf = http($base . '?route=pessoas/feedbackCreate', $jar, ['csrf' => 'invalido', 'colaborador_id' => $F['a1'], 'tipo' => 'positivo', 'titulo' => 'x', 'descricao' => 'x']);
if ($semCsrf['code'] !== 400) { failFast('POST sem CSRF válido deveria ser recusado com 400'); }
$depoisSemCsrf = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_feedbacks WHERE colaborador_id = " . (int)$F['a1'])->fetchColumn();
if ($depoisSemCsrf !== $antes) { failFast('CSRF inválido não deveria criar registro'); }
ok('CSRF inválido: 400, nada criado');

// Registrar Feedback a partir da Central (contextual: colaborador já vem preenchido).
// Payload com XSS no título para provar escaping na exibição.
$tituloXss = 'Excelente entrega <script>alert(1)</script> ' . bin2hex(random_bytes(3));
$store = http($base . '?route=pessoas/feedbackCreate', $jar, [
    'csrf' => $csrf, 'colaborador_id' => $F['a1'], 'tipo' => 'melhoria', 'titulo' => $tituloXss,
    'descricao' => 'Registrado via fluxo HTTP', 'data_feedback' => '2026-09-27',
]);
if ($store['code'] !== 302 || !str_contains($store['location'], 'pessoas/colaboradorHistorico')) { failFast('Registrar Feedback deveria voltar para a Central do Colaborador (destino contextual)'); }
$novoId = (int)$pdo->query("SELECT id FROM pessoas_feedbacks WHERE colaborador_id = " . (int)$F['a1'] . " ORDER BY id DESC LIMIT 1")->fetchColumn();
if ($novoId <= 0) { failFast('Feedback não foi persistido'); }
ok('Registrar Feedback pela Central: colaborador contextual, redireciona de volta (não pede para selecionar o colaborador de novo)');

// Volta para a Central: feedback aparece no histórico, escapado.
$centralDepois = http($base . '?route=pessoas/colaboradorHistorico&id=' . $F['a1'], $jar);
if (!str_contains($centralDepois['body'], 'Feedback registrado.')) { failFast('Mensagem de sucesso deveria aparecer após registrar'); }
if (str_contains($centralDepois['body'], '<script>alert(1)</script>')) { failFast('XSS não escapado no histórico da Central'); }
if (!str_contains($centralDepois['body'], htmlspecialchars($tituloXss))) { failFast('Feedback registrado deveria aparecer no histórico (escapado)'); }
ok('Histórico da Central atualizado com o novo feedback; XSS escapado');

// Localizar o mesmo registro na listagem operacional (e no detalhe).
$listaDepois = http($base . '?route=pessoas/feedbacks&empresa_id=' . $eA . '&colaborador_id=' . $F['a1'], $jar);
if (!str_contains($listaDepois['body'], htmlspecialchars($tituloXss))) { failFast('Feedback registrado deveria aparecer na listagem operacional (escapado)'); }
if (str_contains($listaDepois['body'], '<script>alert(1)</script>')) { failFast('XSS não escapado na listagem operacional'); }
ok('Mesmo registro localizado na listagem operacional de Feedbacks');

$detalhe = http($base . '?route=pessoas/feedbackShow&id=' . $novoId, $jar);
if ($detalhe['code'] !== 200 || !str_contains($detalhe['body'], 'Registrado via fluxo HTTP')) { failFast('Detalhe do Feedback deveria mostrar o conteúdo completo'); }
if (str_contains($detalhe['body'], '<script>alert(1)</script>')) { failFast('XSS não escapado no detalhe do Feedback'); }
ok('Detalhe do Feedback: conteúdo completo acessível, escapado');

// Tipo inválido / conteúdo vazio via HTTP.
$antesInvalidos = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_feedbacks WHERE colaborador_id = " . (int)$F['a1'])->fetchColumn();
http($base . '?route=pessoas/feedbackCreate', $jar, ['csrf' => $csrf, 'colaborador_id' => $F['a1'], 'tipo' => 'neutro', 'titulo' => 'x', 'descricao' => 'x']);
http($base . '?route=pessoas/feedbackCreate', $jar, ['csrf' => $csrf, 'colaborador_id' => $F['a1'], 'tipo' => 'positivo', 'titulo' => 'x', 'descricao' => '']);
$depoisInvalidos = (int)$pdo->query("SELECT COUNT(*) FROM pessoas_feedbacks WHERE colaborador_id = " . (int)$F['a1'])->fetchColumn();
if ($depoisInvalidos !== $antesInvalidos) { failFast('Tipo inválido / conteúdo vazio não deveriam criar registro'); }
ok('Tipo inválido e conteúdo vazio bloqueados via HTTP (nada criado)');

// ID inexistente.
$r = http($base . '?route=pessoas/feedbackShow&id=999999999', $jar);
if ($r['code'] !== 302 || !str_contains($r['location'], 'pessoas/feedbacks')) { failFast('Feedback inexistente deveria redirecionar para a listagem'); }
ok('ID inexistente: redireciona sem vazar informação');

// Auditoria: evento de criação registrado (mesmo mecanismo já existente - não duplicado).
$logPath = 'C:/laragon/www/institutodona/storage/logs/audit.log';
$log = @file_get_contents($logPath) ?: '';
if ($log !== '' && !str_contains($log, 'pessoas_feedback_criado')) { failFast('Evento de auditoria pessoas_feedback_criado deveria estar presente'); }
ok('Auditoria: pessoas_feedback_criado registrado pelo mecanismo já existente' . ($log === '' ? ' (log não localizado neste ambiente)' : ''));

// ---- Cliente Admin da empresa A ----
$jarA = login($base, $uAdmA, $senha);
$listaA = http($base . '?route=pessoas/feedbacks', $jarA);
if ($listaA['code'] !== 200 || str_contains($listaA['body'], 'fbB outra empresa')) { failFast('Cliente Admin de A não pode ver feedback de B'); }
if (!$naoEncontrado(http($base . '?route=pessoas/feedbacks&empresa_id=' . $eB, $jarA))) { failFast('empresa_id de outro tenant deveria resultar em 404 oculto'); }
$rB = http($base . '?route=pessoas/feedbackShow&id=' . (int)$pdo->query("SELECT id FROM pessoas_feedbacks WHERE empresa_id = {$eB} LIMIT 1")->fetchColumn(), $jarA);
if ($rB['code'] === 200) { failFast('Cliente Admin de A não pode abrir feedback de B pelo id direto'); }
ok('Cliente Admin: escopo respeitado; feedback de outro tenant bloqueado na listagem e no detalhe direto');

// Cliente Admin não pode vincular GAP de outra empresa via formulário manipulado.
$centralA = http($base . '?route=pessoas/colaboradorHistorico&id=' . $F['a1'], $jarA);
$csrfA = csrfDe($centralA['body']);
http($base . '?route=pessoas/feedbackCreate', $jarA, ['csrf' => $csrfA, 'colaborador_id' => $F['a1'], 'tipo' => 'positivo', 'titulo' => 'Tentativa cross-tenant', 'descricao' => 'x', 'gap_id' => $gapB]);
$feedbackCrossTenant = $pdo->query("SELECT gap_id FROM pessoas_feedbacks WHERE titulo = 'Tentativa cross-tenant' ORDER BY id DESC LIMIT 1")->fetchColumn();
if ($feedbackCrossTenant !== false && $feedbackCrossTenant !== null) { failFast('GAP de outra empresa não poderia ter sido vinculado via formulário manipulado'); }
ok('Cliente Admin: GAP de outra empresa manipulado no formulário é descartado (não vincula, não quebra)');

// ---- Perfil sem permissão ao Pilar de Pessoas ----
$jarL = login($base, $uLeitorA, $senha);
foreach (['pessoas/feedbacks', 'pessoas/feedbackShow&id=' . $novoId] as $rota) {
    if (!$naoEncontrado(http($base . '?route=' . $rota, $jarL))) { failFast("Perfil sem permissão deveria receber 404 oculto em $rota"); }
}
ok('Perfil sem acesso ao Pilar de Pessoas: 404 oculto nas rotas de Feedbacks');

foreach ([$jar, $jarA, $jarL] as $j) { @unlink($j); }
echo "\nFluxo HTTP de Feedbacks aprovado.\n";
