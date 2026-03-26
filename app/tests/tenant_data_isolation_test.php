<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\IndicadorModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'tdi_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$indicadorIds = [];

try {
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n, :c, :ct, :m, :mid)');
    $insCli->execute(['n' => 'Matriz ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-1', 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $matriz = (int)$pdo->lastInsertId();
    $clienteIds[] = $matriz;
    $insCli->execute(['n' => 'Filial ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-2', 'ct' => 'contato', 'm' => 0, 'mid' => $matriz]);
    $filial = (int)$pdo->lastInsertId();
    $clienteIds[] = $filial;
    $insCli->execute(['n' => 'Outro ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-3', 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $outro = (int)$pdo->lastInsertId();
    $clienteIds[] = $outro;

    Auth::login([
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@test.local',
        'tipo_acesso' => 'instituto',
        'id_cliente' => null,
    ]);

    $indicadores = new IndicadorModel();
    $idIndicadorMatriz = $indicadores->create([
        'cliente_id' => $matriz,
        'nome' => 'Indicador Matriz ' . $suffix,
        'unidade' => 'R$',
        'referencia' => date('Y-m-01'),
        'meta' => 10,
        'realizado' => 0,
    ]);
    $idIndicadorOutro = $indicadores->create([
        'cliente_id' => $outro,
        'nome' => 'Indicador Outro ' . $suffix,
        'unidade' => 'R$',
        'referencia' => date('Y-m-01'),
        'meta' => 20,
        'realizado' => 0,
    ]);
    $indicadorIds = array_filter([(int)$idIndicadorMatriz, (int)$idIndicadorOutro]);

    Auth::login([
        'id' => 2,
        'nome' => 'Cliente',
        'email' => 'cliente@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $matriz,
    ]);

    $scope = Auth::allowedClientIds();
    if (!in_array($matriz, $scope, true) || !in_array($filial, $scope, true) || in_array($outro, $scope, true)) {
        failFast('Escopo de autenticação inválido para usuário cliente');
    }
    ok('Escopo de autenticação com matriz e filial');

    $all = $indicadores->all();
    $names = array_map(fn(array $r): string => (string)$r['nome'], $all);
    if (!in_array('Indicador Matriz ' . $suffix, $names, true) || in_array('Indicador Outro ' . $suffix, $names, true)) {
        failFast('Filtro automático de leitura por cliente falhou');
    }
    ok('Filtro automático de leitura por cliente');

    $blockedDelete = $indicadores->delete((int)$idIndicadorOutro);
    if ($blockedDelete) {
        failFast('Delete fora do escopo deveria ser bloqueado');
    }
    ok('Bloqueio de operação fora do escopo');

    $allowedDelete = $indicadores->delete((int)$idIndicadorMatriz);
    if (!$allowedDelete) {
        failFast('Delete dentro do escopo deveria ser permitido');
    }
    ok('Permissão de operação dentro do escopo');

    echo "All tenant isolation tests passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($indicadorIds)) {
        $in = implode(',', array_map('intval', $indicadorIds));
        $pdo->exec("DELETE FROM indicadores WHERE id IN ($in)");
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
