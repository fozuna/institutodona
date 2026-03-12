<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\XlsxExport;
use App\Models\PlanoAcaoTaskModel;

function ok($msg){ echo "OK: $msg\n"; }
function fail($msg){ echo "FAIL: $msg\n"; exit(1); }

try {
  $model = new PlanoAcaoTaskModel();
  // Try fetch some rows from any cliente id=1; if empty, forge a sample
  $rows = $model->filterForExport(1, []);
  if (empty($rows)) {
    $rows = [
      [
        'id' => 1,
        'id_cliente' => 1,
        'titulo' => 'Exemplo Plano',
        'descricao' => 'Descrição de teste',
        'meta_valor' => 'Implementar ação',
        'meta_unidade' => 'Observação teste',
        'prazo' => date('Y-m-d', strtotime('+7 days')),
        'responsavel' => 'Responsável A',
        'status' => 'Planejado',
        'progresso' => 0,
        'created_at' => date('Y-m-d H:i:s'),
      ],
    ];
  }
  $file = 'export_planos_test_' . date('Ymd_His') . '.xlsx';
  $path = XlsxExport::exportPlanos($rows, $file);
  if (!is_file($path)) fail('Arquivo não foi gerado');
  if (filesize($path) <= 0) fail('Arquivo vazio');
  ok('Geração de XLSX');
  @unlink($path);
  ok('Limpeza de arquivo temporário');
  echo "All export tests passed.\n";
} catch (Throwable $e) {
  fail('Exceção: ' . $e->getMessage());
}

