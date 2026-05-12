<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Services\ColaboradorImportService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();

function cpfFromBase(string $base9): string
{
    $base9 = preg_replace('/\D+/', '', $base9) ?: '';
    $base9 = str_pad(substr($base9, 0, 9), 9, '1');
    $sum = 0;
    for ($i = 0, $w = 10; $i < 9; $i++, $w--) { $sum += ((int)$base9[$i]) * $w; }
    $d1 = 11 - ($sum % 11); $d1 = $d1 >= 10 ? 0 : $d1;
    $base10 = $base9 . $d1;
    $sum = 0;
    for ($i = 0, $w = 11; $i < 10; $i++, $w--) { $sum += ((int)$base10[$i]) * $w; }
    $d2 = 11 - ($sum % 11); $d2 = $d2 >= 10 ? 0 : $d2;
    return $base10 . $d2;
}

$start = random_int(100000, 900000);

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$empresaNome = 'Unidade Import ' . $suffix;
$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute([
    'nome' => $empresaNome,
    'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
    'contato' => 'Import',
]);
$empresaId = (int)$pdo->lastInsertId();
if ($empresaId <= 0) { failFast('Falha ao criar cliente'); }
ok('Cliente criado');

$emails = [];
$filesToDelete = [];
$createdDept = 'Departamento Import ' . $suffix;
$createdSetor = 'Setor Import ' . $suffix;
$createdFuncao = 'Funcao Import ' . $suffix;

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colab_import_' . $suffix;
@mkdir($tmpDir, 0775, true);

register_shutdown_function(function() use (&$pdo, &$emails, &$createdFuncao, &$createdSetor, &$createdDept, &$empresaId, &$filesToDelete, &$tmpDir) {
    try {
        if (!empty($emails)) {
            $inList = implode(',', array_fill(0, count($emails), '?'));
            $del = $pdo->prepare("DELETE FROM colaboradores WHERE email IN ($inList)");
            $del->execute($emails);
        }
        if (!empty($createdFuncao)) { $pdo->prepare('DELETE FROM funcoes WHERE nome = :n')->execute(['n' => $createdFuncao]); }
        if (!empty($createdSetor)) { $pdo->prepare('DELETE FROM setores WHERE nome = :n')->execute(['n' => $createdSetor]); }
        if (!empty($createdDept) && !empty($empresaId)) { $pdo->prepare('DELETE FROM departamentos WHERE nome = :n AND cliente_id = :cid')->execute(['n' => $createdDept, 'cid' => $empresaId]); }
        if (!empty($empresaId)) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $empresaId]); }
    } catch (\Throwable $e) {}
    foreach ($filesToDelete as $p) { @unlink($p); }
    if (!empty($tmpDir)) { @rmdir($tmpDir); }
});

$csvPath = $tmpDir . DIRECTORY_SEPARATOR . 'import.csv';
$doc1 = cpfFromBase((string)($start . '123'));
$doc2 = cpfFromBase((string)(($start + 1) . '456'));
$email1 = 'import_' . $suffix . '_1@example.com';
$email2 = 'import_' . $suffix . '_2@example.com';
$emails[] = $email1;
$emails[] = $email2;
$csv = "Nome;Documento;DN;Celular;Email;Unidade;Função;Setor;Departamento\n";
$csv .= "Pessoa 1;$doc1;01/01/1990;67999990001;$email1;$empresaNome;$createdFuncao;$createdSetor;$createdDept\n";
$csv .= "Pessoa 2;$doc2;02/02/1991;67999990002;$email2;$empresaNome;$createdFuncao;$createdSetor;$createdDept\n";
file_put_contents($csvPath, $csv);
$filesToDelete[] = $csvPath;

$svc = new ColaboradorImportService($pdo);
$res = $svc->import($csvPath, 'import.csv', 1);
if (empty($res['ok']) || (int)($res['inserted'] ?? 0) !== 2) {
    failFast('Import CSV falhou: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
}
ok('Import CSV');

$xlsxPath = $tmpDir . DIRECTORY_SEPARATOR . 'import.xlsx';
$doc3 = cpfFromBase((string)(($start + 2) . '789'));
$email3 = 'import_' . $suffix . '_3@example.com';
$emails[] = $email3;

function zipWriteStored(string $path, array $entries): void
{
    $offset = 0;
    $cd = '';
    $out = '';
    $now = getdate();
    $dosTime = (($now['hours'] ?? 0) << 11) | (($now['minutes'] ?? 0) << 5) | (int)(($now['seconds'] ?? 0) / 2);
    $dosDate = ((($now['year'] ?? 1980) - 1980) << 9) | (($now['mon'] ?? 1) << 5) | ($now['mday'] ?? 1);

    foreach ($entries as $name => $data) {
        $name = str_replace('\\', '/', (string)$name);
        $data = (string)$data;
        $crc = crc32($data);
        $size = strlen($data);
        $nameLen = strlen($name);

        $lh = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLen, 0);
        $out .= $lh . $name . $data;

        $cdh = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 0, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLen, 0, 0, 0, 0, 0, $offset);
        $cd .= $cdh . $name;

        $offset += strlen($lh) + $nameLen + $size;
    }

    $out .= $cd;
    $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, count($entries), count($entries), strlen($cd), $offset, 0);
    $out .= $eocd;
    file_put_contents($path, $out);
}

$strings = [
    'Nome','Documento','DN','Celular','Email','Unidade','Função','Setor','Departamento',
    'Pessoa 3', $doc3, '03/03/1992', '67999990003', $email3, $empresaNome, $createdFuncao, $createdSetor, $createdDept,
];
$si = '';
foreach ($strings as $s) {
    $s = htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    $si .= '<si><t>' . $s . '</t></si>';
}
$sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">' . $si . '</sst>';

$sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c><c r="E1" t="s"><v>4</v></c><c r="F1" t="s"><v>5</v></c><c r="G1" t="s"><v>6</v></c><c r="H1" t="s"><v>7</v></c><c r="I1" t="s"><v>8</v></c>
    </row>
    <row r="2">
      <c r="A2" t="s"><v>9</v></c><c r="B2" t="s"><v>10</v></c><c r="C2" t="s"><v>11</v></c><c r="D2" t="s"><v>12</v></c><c r="E2" t="s"><v>13</v></c><c r="F2" t="s"><v>14</v></c><c r="G2" t="s"><v>15</v></c><c r="H2" t="s"><v>16</v></c><c r="I2" t="s"><v>17</v></c>
    </row>
  </sheetData>
</worksheet>';
$entries = [
    '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>',
    '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>',
    'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>',
    'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Sheet1" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>',
    'xl/sharedStrings.xml' => $sharedStringsXml,
    'xl/worksheets/sheet1.xml' => $sheet,
];
zipWriteStored($xlsxPath, $entries);
$filesToDelete[] = $xlsxPath;

$res2 = $svc->import($xlsxPath, 'import.xlsx', 1);
if (empty($res2['ok']) || (int)($res2['inserted'] ?? 0) !== 1) {
    failFast('Import XLSX falhou: ' . json_encode($res2, JSON_UNESCAPED_UNICODE));
}
ok('Import XLSX');

$xlsPath = $tmpDir . DIRECTORY_SEPARATOR . 'import.xls';
$doc4 = cpfFromBase((string)(($start + 3) . '012'));
$email4 = 'import_' . $suffix . '_4@example.com';
$emails[] = $email4;
$html = '<html><head><meta charset="UTF-8"></head><body><table><tr><th>Nome</th><th>Documento</th><th>DN</th><th>Celular</th><th>Email</th><th>Unidade</th><th>Função</th><th>Setor</th><th>Departamento</th></tr>'
      . '<tr><td>Pessoa 4</td><td>' . $doc4 . '</td><td>04/04/1993</td><td>67999990004</td><td>' . $email4 . '</td><td>' . htmlspecialchars($empresaNome) . '</td><td>' . htmlspecialchars($createdFuncao) . '</td><td>' . htmlspecialchars($createdSetor) . '</td><td>' . htmlspecialchars($createdDept) . '</td></tr>'
      . '</table></body></html>';
file_put_contents($xlsPath, $html);
$filesToDelete[] = $xlsPath;

$res3 = $svc->import($xlsPath, 'import.xls', 1);
if (empty($res3['ok']) || (int)($res3['inserted'] ?? 0) !== 1) {
    failFast('Import XLS (HTML) falhou: ' . json_encode($res3, JSON_UNESCAPED_UNICODE));
}
ok('Import XLS (HTML)');

ok('Cleanup');
echo "All colaboradores import small integration tests passed.\n";
