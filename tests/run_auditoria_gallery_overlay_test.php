<?php

function assertTest($cond, $msg){ global $p,$t; $t++; if($cond){$p++; echo "[PASS] $msg\n";} else {echo "[FAIL] $msg\n";} }
$p=0; $t=0;

$show = file_get_contents(__DIR__ . '/../app/views/auditorias/show.php') ?: '';
$auditar = file_get_contents(__DIR__ . '/../app/views/auditorias/auditar.php') ?: '';

assertTest(strpos($show, 'id="imgOverlay"') !== false, 'Overlay presente na visualização de realizada');
assertTest(strpos($show, 'id="ovZoomIn"') !== false && strpos($show, 'id="ovZoomOut"') !== false, 'Controles de zoom presentes');
assertTest(strpos($show, "loading = 'lazy'") !== false || strpos($show, 'loading="lazy"') !== false, 'Lazy loading de thumbnails presente');
assertTest(strpos($show, 'api_list_anexos') !== false && strpos($show, 'thumb_anexo') !== false, 'Integração backend de galeria presente');
assertTest(strpos($auditar, 'accept=".jpg,.jpeg,.png,.gif,.webp') !== false, 'Validação frontend de formato no input de upload');
assertTest(strpos($auditar, 'maxPerFile = 10 * 1024 * 1024') !== false, 'Validação frontend de tamanho por arquivo');
assertTest(strpos($auditar, 'maxTotal = 50 * 1024 * 1024') !== false, 'Validação frontend de tamanho total');

echo "Tests Completed: $p/$t passed.\n";
exit($p===$t ? 0 : 1);

