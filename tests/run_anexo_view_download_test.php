<?php

function assertTest($cond, $msg){ global $p,$t; $t++; echo ($cond ? "[PASS] " : "[FAIL] ") . $msg . "\n"; if($cond)$p++; }
$p=0; $t=0;

$c = file_get_contents(__DIR__ . '/../app/controllers/AuditoriasController.php') ?: '';
$show = file_get_contents(__DIR__ . '/../app/views/auditorias/show.php') ?: '';
$router = file_get_contents(__DIR__ . '/../public_html/index.php') ?: '';

assertTest(strpos($c, 'public function viewAnexo()') !== false, 'Endpoint viewAnexo existe');
assertTest(strpos($router, "case 'auditorias/view_anexo'") !== false, 'Rota view_anexo registrada');
assertTest(strpos($c, "path = \$file['path']") !== false, 'Download/View usam arquivo original');
assertTest(strpos($c, "'.gz'") === false || strpos($c, "filename=\"' . \$name . (substr(\$path, -3) === '.gz'") === false, 'Não força extensão .gz no download');
assertTest(strpos($show, 'route=auditorias/view_anexo') !== false, 'Show abre visualização inline da imagem original');

echo "Tests Completed: $p/$t passed.\n";
exit($p===$t?0:1);
