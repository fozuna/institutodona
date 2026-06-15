<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ColaboradoresController;
use App\Core\Security;
use App\Database\Database;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$clienteId = 0;
$depId = 0;
$setorId = 0;
$funcaoId = 0;
$colabId = 0;

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute([
            'n' => 'Cliente Cadastro ' . $suffix,
            'c' => '77.777.777/0001-' . substr($suffix, 0, 2),
            't' => 'Contato',
        ]);
    $clienteId = (int)$pdo->lastInsertId();
    if ($clienteId <= 0) {
        failFast('Falha ao criar cliente');
    }

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:c)')
        ->execute(['n' => 'Departamento Cadastro ' . $suffix, 'c' => $clienteId]);
    $depId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor Cadastro ' . $suffix, 'd' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao Cadastro ' . $suffix, 's' => $setorId]);
    $funcaoId = (int)$pdo->lastInsertId();

    $_SESSION['user'] = [
        'id' => 901,
        'nome' => 'Instituto',
        'email' => 'instituto@test.local',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $csrf = Security::csrfToken();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = ['route' => 'colaboradores/store'];
    $_POST = [
        'csrf' => $csrf,
        'nome' => 'Colaborador Sem Email ' . $suffix,
        'email' => '',
        'funcao_id' => (string)$funcaoId,
        'lider' => 'não',
        'cliente' => (string)$clienteId,
    ];

    ob_start();
    (new ColaboradoresController())->store();
    ob_end_clean();

    $stmt = $pdo->prepare('SELECT id, nome, email FROM colaboradores WHERE nome = :n ORDER BY id DESC LIMIT 1');
    $stmt->execute(['n' => 'Colaborador Sem Email ' . $suffix]);
    $row = $stmt->fetch();
    if (!$row) {
        failFast('Cadastro sem email deveria criar o colaborador.');
    }
    $colabId = (int)$row['id'];
    if (($row['email'] ?? null) !== null && $row['email'] !== '') {
        failFast('Email vazio deveria ser aceito e persistido sem valor: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
    }
    ok('Cadastro manual aceita email vazio');

    echo "All colaboradores store optional email controller tests passed.\n";
} finally {
    try {
        if ($colabId > 0) {
            $pdo->prepare('DELETE FROM colaboradores WHERE id = :id')->execute(['id' => $colabId]);
        }
        if ($funcaoId > 0) {
            $pdo->prepare('DELETE FROM funcoes WHERE id = :id')->execute(['id' => $funcaoId]);
        }
        if ($setorId > 0) {
            $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $setorId]);
        }
        if ($depId > 0) {
            $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $depId]);
        }
        if ($clienteId > 0) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);
        }
    } catch (\Throwable $e) {}
}
