<?php
require_once __DIR__ . '/../autoload.php';

$file = __DIR__ . '/../views/treinamentos/dashboard.php';
$source = file_get_contents($file);

function layout_assert(bool $condition, string $message): void
{
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

layout_assert($source !== false, 'Carrega a view do dashboard de treinamentos');
layout_assert(str_contains($source, 'data-panel-target="comparativoSetorPanel"'), 'Comparativo por setor possui controle de collapse');
layout_assert(str_contains($source, 'id="alertasSetorPanel" class="space-y-3 overflow-auto'), 'Alertas por setor possui contenção com rolagem');
layout_assert(str_contains($source, 'id="pendentesPanel" class="overflow-auto border rounded"'), 'Pendentes possui contenção com rolagem');
layout_assert(str_contains($source, 'id="concluidosPanel" class="overflow-auto border rounded"'), 'Concluídos possui contenção com rolagem');
layout_assert(str_contains($source, 'function bindCollapsiblePanels()'), 'View registra comportamento dinâmico de collapse/expand');
layout_assert(str_contains($source, 'Exibindo amostra adaptada à largura. Veja todos os setores na tabela.'), 'Gráfico por setor comunica adaptação dinâmica ao espaço disponível');

echo "treinamentos_dashboard_layout_smoke passed.\n";
