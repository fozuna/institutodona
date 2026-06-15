<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Services\ColaboradorImportService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$empresaNome = 'Unidade Opt ' . $suffix;

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Cliente Admin',
    'email' => 'cliente_admin@example.com',
    'tipo_acesso' => 'cliente_admin',
    'allowed_client_ids' => [],
];

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute([
    'nome' => $empresaNome,
    'cnpj' => '98.888.888/0001-' . substr($suffix, 0, 2),
    'contato' => 'Opt Import',
]);
$empresaId = (int)$pdo->lastInsertId();
if ($empresaId <= 0) {
    failFast('Falha ao criar cliente');
}

$_SESSION['user']['allowed_client_ids'] = [$empresaId];

$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colab_optional_' . $suffix . '.csv';
$names = [
    'Colab Opt 1 ' . $suffix,
    'Colab Opt 2 ' . $suffix,
];

register_shutdown_function(function() use ($pdo, $empresaId, $names, $tmpFile) {
    try {
        $in = implode(',', array_fill(0, count($names), '?'));
        $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE nome IN ($in)");
        $stmt->execute($names);
        $pdo->prepare('DELETE FROM funcoes WHERE nome = :n')->execute(['n' => 'Funcao Opt ' . $GLOBALS['suffix_for_colab_opt_test']]);
        $pdo->prepare('DELETE FROM setores WHERE nome = :n')->execute(['n' => 'Setor Opt ' . $GLOBALS['suffix_for_colab_opt_test']]);
        $pdo->prepare('DELETE FROM departamentos WHERE nome = :n AND cliente_id = :cid')->execute(['n' => 'Departamento Opt ' . $GLOBALS['suffix_for_colab_opt_test'], 'cid' => $empresaId]);
        $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $empresaId]);
    } catch (\Throwable $e) {}
    @unlink($tmpFile);
});
$GLOBALS['suffix_for_colab_opt_test'] = $suffix;

$fh = fopen($tmpFile, 'wb');
if ($fh === false) {
    failFast('Falha ao criar CSV temporário');
}
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['Nome','Documento','DN','Celular','Email','Unidade','Função','Setor','Departamento'], ';', '"', '\\');
fputcsv($fh, [$names[0], '', '01/01/1990', '', '', $empresaNome, 'Funcao Opt ' . $suffix, 'Setor Opt ' . $suffix, 'Departamento Opt ' . $suffix], ';', '"', '\\');
fputcsv($fh, [$names[1], '', '02/02/1992', '', '', $empresaNome, 'Funcao Opt ' . $suffix, 'Setor Opt ' . $suffix, 'Departamento Opt ' . $suffix], ';', '"', '\\');
fclose($fh);

$svc = new ColaboradorImportService($pdo);
$res = $svc->import($tmpFile, 'optional.csv', 1);
if (empty($res['ok']) || (int)($res['inserted'] ?? 0) !== 2) {
    failFast('Import com Documento/Celular/Email vazios deveria funcionar: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
}
ok('Import aceita Documento, Celular e Email vazios');

$stmt = $pdo->prepare('SELECT nome, email, documento, celular FROM colaboradores WHERE nome IN (?, ?) ORDER BY nome');
$stmt->execute($names);
$rows = $stmt->fetchAll();
if (count($rows) !== 2) {
    failFast('Registros importados não encontrados.');
}
foreach ($rows as $row) {
    if (($row['email'] ?? null) !== null || ($row['documento'] ?? null) !== null || ($row['celular'] ?? null) !== null) {
        failFast('Campos opcionais vazios deveriam ser persistidos como NULL: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
    }
}
ok('Campos opcionais vazios persistidos corretamente');

$badFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'colab_optional_bad_' . $suffix . '.csv';
register_shutdown_function(static function() use ($badFile) { @unlink($badFile); });
$fh2 = fopen($badFile, 'wb');
fwrite($fh2, "\xEF\xBB\xBF");
fputcsv($fh2, ['Nome','Documento','DN','Celular','Email','Unidade','Função','Setor','Departamento'], ';', '"', '\\');
fputcsv($fh2, ['Colab Sem DN ' . $suffix, '', '', '', '', $empresaNome, 'Funcao Opt ' . $suffix, 'Setor Opt ' . $suffix, 'Departamento Opt ' . $suffix], ';', '"', '\\');
fclose($fh2);
$resBad = $svc->import($badFile, 'optional_bad.csv', 1);
if (!empty($resBad['ok'])) {
    failFast('Import sem DN deveria continuar falhando.');
}
$jsonErr = json_encode($resBad, JSON_UNESCAPED_UNICODE);
if (!str_contains($jsonErr, 'Data de nascimento é obrigatória')) {
    failFast('Erro esperado para DN obrigatório não encontrado: ' . $jsonErr);
}
ok('Campos obrigatórios não destacados permanecem validados');

echo "All colaboradores optional import integration tests passed.\n";

