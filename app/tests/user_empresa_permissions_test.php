<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Core\TenantScopeResolver;
use App\Database\Database;
use App\Models\UsuarioEmpresaModel;
use App\Models\UsuarioModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'uep_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$usuarioId = 0;

try {
    if (!\App\Database\Database::columnExists('clientes', 'ativo')) {
        $pdo->exec('ALTER TABLE clientes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
    }
    if (!\App\Database\Database::columnExists('clientes', 'acesso_restrito')) {
        $pdo->exec('ALTER TABLE clientes ADD COLUMN acesso_restrito TINYINT(1) NOT NULL DEFAULT 0');
    }

    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id, ativo, acesso_restrito) VALUES (:n, :c, :ct, :m, :mid, :ativo, :restrito)');
    $insCli->execute(['n' => 'Matriz A ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-1', 'ct' => 'contato', 'm' => 1, 'mid' => null, 'ativo' => 1, 'restrito' => 0]);
    $matrizA = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizA;
    $insCli->execute(['n' => 'Filial A1 ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-2', 'ct' => 'contato', 'm' => 0, 'mid' => $matrizA, 'ativo' => 1, 'restrito' => 0]);
    $filialA1 = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialA1;
    $insCli->execute(['n' => 'Filial A2 Restrita ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-3', 'ct' => 'contato', 'm' => 0, 'mid' => $matrizA, 'ativo' => 1, 'restrito' => 1]);
    $filialA2 = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialA2;
    $insCli->execute(['n' => 'Matriz B ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-4', 'ct' => 'contato', 'm' => 1, 'mid' => null, 'ativo' => 1, 'restrito' => 0]);
    $matrizB = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizB;
    $insCli->execute(['n' => 'Filial B1 Inativa ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-5', 'ct' => 'contato', 'm' => 0, 'mid' => $matrizB, 'ativo' => 0, 'restrito' => 0]);
    $filialB1 = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialB1;
    $insCli->execute(['n' => 'Filial B2 ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-6', 'ct' => 'contato', 'm' => 0, 'mid' => $matrizB, 'ativo' => 1, 'restrito' => 0]);
    $filialB2 = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialB2;

    $usuarios = new UsuarioModel();
    $usuarioId = $usuarios->create([
        'nome' => 'Usuário Empresas ' . $suffix,
        'email' => 'uep_' . $suffix . '@test.local',
        'senha_hash' => password_hash('123456', PASSWORD_DEFAULT),
        'tipo_acesso' => 'cliente',
        'id_cliente' => $matrizA,
    ]);

    $perms = new UsuarioEmpresaModel();
    $allowed = $perms->syncForUser($usuarioId, [$matrizA, $matrizB]);
    $expected = [$matrizA, $filialA1, $matrizB, $filialB2];
    sort($allowed);
    sort($expected);
    if ($allowed !== $expected) {
        failFast('Propagação em lote de múltiplas matrizes não corresponde ao esperado');
    }
    ok('Propagação em lote com múltiplas matrizes');

    $loginUser = $usuarios->find($usuarioId);
    Auth::login($loginUser ?: []);
    $scope = Auth::allowedClientIds();
    sort($scope);
    if ($scope !== $expected) {
        failFast('Escopo em sessão não refletiu permissões propagadas');
    }
    ok('Escopo em sessão com herança correta');

    $descA = TenantScopeResolver::descendantsInclusive($matrizA);
    if (in_array($filialA2, $descA, true)) {
        failFast('Filial restrita não deveria entrar na herança');
    }
    $descB = TenantScopeResolver::descendantsInclusive($matrizB);
    if (in_array($filialB1, $descB, true)) {
        failFast('Filial inativa não deveria entrar na herança');
    }
    ok('Tratamento de filiais inativas e restritas');

    echo "All user empresa permission tests passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if ($usuarioId > 0) {
        $pdo->prepare('DELETE FROM usuario_empresas WHERE usuario_id = :u')->execute(['u' => $usuarioId]);
        $pdo->prepare('DELETE FROM usuarios WHERE id = :u')->execute(['u' => $usuarioId]);
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
