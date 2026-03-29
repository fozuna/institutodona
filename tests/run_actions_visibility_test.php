<?php
namespace App\Core {
    class Security { public static function csrfToken(){ return 'test'; } }
}
namespace {
// Renderiza a view de index de auditorias com dados simulados e verifica a presença dos botões

ob_start();
$items = [
    ['id'=>1,'nome_auditoria'=>'A1','cliente_nome'=>'C','setor_nome'=>'S','data_auditoria'=>date('Y-m-d'),'status'=>'Rascunho','conformidade_pct'=>null,'total_questoes'=>1],
    ['id'=>2,'nome_auditoria'=>'A2','cliente_nome'=>'C','setor_nome'=>'S','data_auditoria'=>date('Y-m-d'),'status'=>'Agendada','conformidade_pct'=>null,'total_questoes'=>2],
    ['id'=>3,'nome_auditoria'=>'A3','cliente_nome'=>'C','setor_nome'=>'S','data_auditoria'=>date('Y-m-d'),'status'=>'Realizada','conformidade_pct'=>67,'total_questoes'=>3],
];
$filters = []; $clientes=[]; $setores=[]; $canManage = true; $total=3; $totalPages=1; $page=1;
include __DIR__ . '/../app/views/auditorias/index.php';
$html = ob_get_clean();

function assertTest($cond, $msg){ global $passed,$totalT; $totalT++; if($cond){$passed++; echo "[PASS] $msg\n";} else {echo "[FAIL] $msg\n";}}
$passed=0; $totalT=0;

assertTest(strpos($html, 'index.php?route=auditorias/edit&id=1') !== false, 'Exibe Editar para Rascunho');
assertTest(strpos($html, 'index.php?route=auditorias/edit&id=2') !== false, 'Exibe Editar para Agendada');
assertTest(strpos($html, 'index.php?route=auditorias/edit&id=3') !== false, 'Exibe Editar para Realizada');

echo "Tests Completed: $passed/$totalT passed.\n";
exit($passed===$totalT?0:1);

}
