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

function cpfFromInt(int $n): string
{
    $base9 = str_pad((string)($n % 1000000000), 9, '0', STR_PAD_LEFT);
    if (preg_match('/^(\d)\1{8}$/', $base9)) {
        $base9 = substr($base9, 0, 8) . '1';
    }
    $sum = 0;
    for ($i = 0, $w = 10; $i < 9; $i++, $w--) { $sum += ((int)$base9[$i]) * $w; }
    $d1 = 11 - ($sum % 11); $d1 = $d1 >= 10 ? 0 : $d1;
    $base10 = $base9 . $d1;
    $sum = 0;
    for ($i = 0, $w = 11; $i < 10; $i++, $w--) { $sum += ((int)$base10[$i]) * $w; }
    $d2 = 11 - ($sum % 11); $d2 = $d2 >= 10 ? 0 : $d2;
    return $base10 . $d2;
}

$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$empresaNome = 'Unidade Import 10k ' . $suffix;
$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute([
    'nome' => $empresaNome,
    'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
    'contato' => 'Import',
]);
$empresaId = (int)$pdo->lastInsertId();
if ($empresaId <= 0) { failFast('Falha ao criar cliente'); }
ok('Cliente criado');

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colab_import_10000_' . $suffix;
@mkdir($tmpDir, 0775, true);
$csvPath = $tmpDir . DIRECTORY_SEPARATOR . 'import_10000.csv';

$createdDept = 'Departamento Import 10k ' . $suffix;
$createdSetor = 'Setor Import 10k ' . $suffix;
$createdFuncao = 'Funcao Import 10k ' . $suffix;

$fh = fopen($csvPath, 'wb');
if (!$fh) { failFast('Falha ao criar CSV'); }
fwrite($fh, "Nome;Documento;DN;Celular;Email;Unidade;Função;Setor;Departamento\n");

$prefixEmail = 'import10k_' . $suffix . '_';
$start = random_int(100000, 900000);
$total = 10000;
for ($i = 0; $i < $total; $i++) {
    $cpf = cpfFromInt($start + $i);
    $email = $prefixEmail . $i . '@example.com';
    $nome = 'Pessoa ' . $i;
    $dn = '01/01/1990';
    $cel = '6799999' . str_pad((string)($i % 10000), 4, '0', STR_PAD_LEFT);
    $line = $nome . ';' . $cpf . ';' . $dn . ';' . $cel . ';' . $email . ';' . $empresaNome . ';' . $createdFuncao . ';' . $createdSetor . ';' . $createdDept . "\n";
    fwrite($fh, $line);
}
fclose($fh);
ok('CSV 10k gerado');

$svc = new ColaboradorImportService($pdo);
$res = $svc->import($csvPath, basename($csvPath), 1);
if (empty($res['ok']) || (int)($res['inserted'] ?? 0) !== $total) {
    failFast('Import 10k falhou: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
}
ok('Import 10k');

$del = $pdo->prepare('DELETE FROM colaboradores WHERE email LIKE :p');
$del->execute(['p' => $prefixEmail . '%']);

$pdo->prepare('DELETE FROM funcoes WHERE nome = :n')->execute(['n' => $createdFuncao]);
$pdo->prepare('DELETE FROM setores WHERE nome = :n')->execute(['n' => $createdSetor]);
$pdo->prepare('DELETE FROM departamentos WHERE nome = :n AND cliente_id = :cid')->execute(['n' => $createdDept, 'cid' => $empresaId]);
$pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $empresaId]);

@unlink($csvPath);
@rmdir($tmpDir);

ok('Cleanup');
echo "All colaboradores import 10000 integration tests passed.\n";

