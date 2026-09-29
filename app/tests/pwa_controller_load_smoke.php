<?php
// Regressão: PwaController não carregava ("A void method must not return a
// value") por usar `fn(): void => ...` - arrow function sempre retorna o valor
// da expressão, então o tipo void é erro de COMPILAÇÃO e derrubava TODAS as
// rotas /pwa/*. O pwa_route_mapping_unit_test não pegava porque nunca carrega
// o controller. Este teste:
//  1) valida a sintaxe de todos os controllers (php -l em subprocesso, para
//     que um erro de compilação vire FAIL em vez de derrubar o teste);
//  2) carrega o PwaController e confere os métodos usados pelas rotas PWA;
//  3) impede o padrão `fn(...): void =>` em qualquer arquivo de app/.
require __DIR__ . '/../autoload.php';

function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }

$php = escapeshellarg(PHP_BINARY);
$erros = [];
foreach (glob(__DIR__ . '/../controllers/*.php') ?: [] as $file) {
    $out = [];
    $rc = 0;
    exec($php . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $rc);
    if ($rc !== 0) {
        $erros[] = basename($file) . ': ' . trim(implode(' ', $out));
    }
}
if ($erros) {
    failFast("Controllers com erro de sintaxe/compilação:\n  " . implode("\n  ", $erros));
}
ok('Todos os controllers passam em php -l');

// Roda como arquivo (não `php -r`): no Windows, escapeshellarg() não escapa
// aspas duplas de forma compatível com cmd.exe, e aspas aninhadas dentro do
// código de `-r` chegam corrompidas ao PHP (ex.: "LOADED" vira LOADED, uma
// constante indefinida - erro de execução do teste, não do PwaController).
$probe = tempnam(sys_get_temp_dir(), 'pwa_probe_') . '.php';
file_put_contents($probe, '<?php require ' . var_export(__DIR__ . '/../autoload.php', true) . '; echo class_exists(\App\Controllers\PwaController::class) ? "LOADED" : "MISSING";');
$rc = 0;
$out = [];
exec($php . ' ' . escapeshellarg($probe) . ' 2>&1', $out, $rc);
@unlink($probe);
if ($rc !== 0 || trim(implode('', $out)) !== 'LOADED') {
    failFast('PwaController não carrega: ' . implode(' ', $out));
}
ok('PwaController carrega sem erro');

$metodos = ['login', 'doLogin', 'logout', 'dashboard', 'indicadores', 'indicadoresHistorico', 'indicadoresGraficos', 'agenda', 'tarefas', 'cronogramas', 'planoAcao', 'auditorias', 'avaliacoes', 'tratamentos', 'balancos', 'oficinas'];
foreach ($metodos as $m) {
    if (!method_exists(\App\Controllers\PwaController::class, $m)) {
        failFast("PwaController::{$m}() ausente (rota PWA quebrada)");
    }
}
ok('Métodos das rotas PWA presentes (' . count($metodos) . ')');

$ocorrencias = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/..', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php' || str_contains($f->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    if (preg_match('/\bfn\s*\([^)]*\)\s*:\s*void\s*=>/', (string)file_get_contents($f->getPathname()))) {
        $ocorrencias[] = $f->getPathname();
    }
}
if ($ocorrencias) {
    failFast("Arrow function com retorno void (erro de compilação):\n  " . implode("\n  ", $ocorrencias));
}
ok('Nenhuma arrow function com retorno void em app/');

echo "\nPwaController load smoke passed.\n";
