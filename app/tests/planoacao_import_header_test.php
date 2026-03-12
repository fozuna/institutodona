<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\PlanoAcaoImportService;

function assert_true($cond, $msg) {
  if (!$cond) {
    echo "FAIL: $msg\n";
    exit(1);
  }
  echo "OK: $msg\n";
}

$svc = new PlanoAcaoImportService();
$ref = new \ReflectionClass(PlanoAcaoImportService::class);
$normalize = $ref->getMethod('normalizeHeader');
$normalize->setAccessible(true);
$mapColumns = $ref->getMethod('mapColumns');
$mapColumns->setAccessible(true);
$detectHeaderRow = $ref->getMethod('detectHeaderRow');
$detectHeaderRow->setAccessible(true);

function norm(ReflectionMethod $normalize, PlanoAcaoImportService $svc, string $v): string {
  return $normalize->invoke($svc, $v);
}

// Normalize variations of "titulo"
assert_true(norm($normalize, $svc, 'titulo') === 'titulo', 'normalize "titulo" básico');
assert_true(norm($normalize, $svc, ' TÍTULO ') === 'titulo', 'normalize " TÍTULO " com acento e espaços');
assert_true(norm($normalize, $svc, "\xEF\xBB\xBFtitulo") === 'titulo', 'normalize "titulo" com BOM');
assert_true(norm($normalize, $svc, "TITULO ") === 'titulo', 'normalize "TITULO " maiúsculo');

// Map columns for various header formats
$headers1 = ['cliente', 'titulo', 'por que?', 'como? (solução)', 'origem', 'prazo', 'responsavel', 'status'];
$norm1 = [];
foreach ($headers1 as $i => $h) {
  $norm1[$i] = norm($normalize, $svc, $h);
}
$map1 = $mapColumns->invoke($svc, $norm1);
assert_true($map1['titulo'] === 1, 'mapColumns encontra coluna "titulo" na posição correta');

$headers2 = ["  CLIENTE  ", "  TÍTULO  "];
$norm2 = [];
foreach ($headers2 as $i => $h) {
  $norm2[$i] = norm($normalize, $svc, $h);
}
$map2 = $mapColumns->invoke($svc, $norm2);
assert_true($map2['cliente'] === 0 && $map2['titulo'] === 1, 'mapColumns com espaços e acentos');

// detectHeaderRow deve encontrar a linha correta mesmo com linha inicial vazia
$rows = [
  ['', ''],
  $headers1,
  ['dados', 'dados'],
];
$info = $detectHeaderRow->invoke($svc, $rows);
assert_true($info['map']['titulo'] === 1, 'detectHeaderRow encontra coluna "titulo" em linha posterior');
assert_true($info['headers'][1] === 'titulo', 'detectHeaderRow mantém cabeçalhos originais');

echo "All planoacao import header tests passed.\n";

