<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\TenantScopeResolver;
use App\Database\Database;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'tsr_' . date('YmdHis') . '_' . random_int(100, 999);
$ids = [];

try {
    if (!\App\Database\Database::columnExists('clientes', 'ativo')) {
        $pdo->exec('ALTER TABLE clientes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
    }
    if (!\App\Database\Database::columnExists('clientes', 'acesso_restrito')) {
        $pdo->exec('ALTER TABLE clientes ADD COLUMN acesso_restrito TINYINT(1) NOT NULL DEFAULT 0');
    }
    $ins = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n, :c, :ct, :m, :mid)');
    $ins->execute(['n' => 'Matriz ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-1', 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $matriz = (int)$pdo->lastInsertId();
    $ids[] = $matriz;

    $ins->execute(['n' => 'Filial 1 ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-2', 'ct' => 'contato', 'm' => 0, 'mid' => $matriz]);
    $filial1 = (int)$pdo->lastInsertId();
    $ids[] = $filial1;

    $ins->execute(['n' => 'Filial 2 ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-3', 'ct' => 'contato', 'm' => 0, 'mid' => $filial1]);
    $filial2 = (int)$pdo->lastInsertId();
    $ids[] = $filial2;
    $ins->execute(['n' => 'Filial Inativa ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-4', 'ct' => 'contato', 'm' => 0, 'mid' => $matriz]);
    $filialInativa = (int)$pdo->lastInsertId();
    $ids[] = $filialInativa;
    $ins->execute(['n' => 'Filial Restrita ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-5', 'ct' => 'contato', 'm' => 0, 'mid' => $matriz]);
    $filialRestrita = (int)$pdo->lastInsertId();
    $ids[] = $filialRestrita;
    $upd = $pdo->prepare('UPDATE clientes SET ativo = :ativo, acesso_restrito = :restrito WHERE id = :id');
    $upd->execute(['ativo' => 0, 'restrito' => 0, 'id' => $filialInativa]);
    $upd->execute(['ativo' => 1, 'restrito' => 1, 'id' => $filialRestrita]);

    $scope = TenantScopeResolver::descendantsInclusive($matriz);
    sort($scope);
    $expected = [$matriz, $filial1, $filial2];
    sort($expected);

    if ($scope !== $expected) {
        failFast('Escopo recursivo não corresponde ao esperado');
    }
    ok('Escopo recursivo cliente/filiais');
    echo "All tenant scope resolver tests passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($ids)) {
        $in = implode(',', array_map('intval', $ids));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
}
