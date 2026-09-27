<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\XlsxExport;
use App\Models\PlanoAcaoTaskModel;

function ok($msg){ echo "OK: $msg\n"; }
function fail($msg){ echo "FAIL: $msg\n"; exit(1); }

try {
  if (!class_exists('ZipArchive')) {
    ok('ZipArchive indisponível; teste de exportação XLSX ignorado neste ambiente');
    exit(0);
  }
  $model = new PlanoAcaoTaskModel();
  $dbRows = $model->filterForExport(1, []);
  $sampleRows = [[
    'id' => 1,
    'id_cliente' => 1,
    'cliente_nome' => 'Cliente Exemplo',
    'titulo' => 'Exemplo Plano',
    'descricao' => 'Descrição de teste',
    'meta_valor' => 'Implementar ação',
    'meta_unidade' => 'Origem teste',
    'prazo' => date('Y-m-d', strtotime('+7 days')),
    'responsavel' => 'Responsável A',
    'fase' => 'DO',
    'status' => 'Planejado',
    'progresso' => 0,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'campo_customizado_alpha' => 'Alpha',
    'campo_customizado_beta' => 'Beta',
  ]];

  if (!empty($dbRows)) {
    $file = 'export_planos_db_test_' . date('Ymd_His') . '.xlsx';
    $path = XlsxExport::exportPlanos($dbRows, $file);
    if (!is_file($path)) fail('Arquivo XLSX real não foi gerado');
    if (filesize($path) <= 0) fail('Arquivo XLSX real está vazio');
    @unlink($path);
    ok('Geração de XLSX com dados reais');
  }

  $file = 'export_planos_test_' . date('Ymd_His') . '.xlsx';
  $path = XlsxExport::exportPlanos($sampleRows, $file);
  if (!is_file($path)) fail('Arquivo não foi gerado');
  if (filesize($path) <= 0) fail('Arquivo vazio');
  ok('Geração de XLSX');
  $zip = new ZipArchive();
  if ($zip->open($path) !== true) fail('Não foi possível abrir o XLSX gerado');
  $sheet = (string)$zip->getFromName('xl/worksheets/sheet1.xml');
  $zip->close();
  foreach ([
    'O Quê? (Problema)',
    'Por que?',
    'Meta / Objetivo',
    'Origem',
    'Responsável',
    'Status',
    'Prazo',
    'Exemplo Plano',
    'Descrição de teste',
    'Implementar ação',
    'Origem teste',
    'Responsável A',
    'Planejado',
    'Exportação padronizada do sistema',
  ] as $needle) {
    if (!str_contains($sheet, $needle)) {
      fail('Coluna ou valor ausente na exportação: ' . $needle);
    }
  }
  foreach (['ID Cliente', 'Cliente Exemplo', 'Fase', 'Progresso', 'Data de Cria', 'Data de Atualiza', 'Campo Customizado', 'Alpha', 'Beta', 'Ã', 'â€'] as $needle) {
    if (str_contains($sheet, $needle)) {
      fail('Planilha não deveria conter: ' . $needle);
    }
  }
  ok('Planilha contém somente as 7 colunas aprovadas, sem erro de acentuação');
  @unlink($path);
  ok('Limpeza de arquivo temporário');
  echo "All export tests passed.\n";
} catch (Throwable $e) {
  fail('Exceção: ' . $e->getMessage());
}

