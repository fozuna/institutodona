<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\IndicadorModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'tdi_' . date('YmdHis') . '_' . random_int(100, 999);
$cnpjSeed = preg_replace('/\D+/', '', $suffix) ?: (string)time();
$cnpjBase = str_pad(substr($cnpjSeed, -11), 11, '0', STR_PAD_LEFT);
$cnpjMatriz = $cnpjBase . '000';
$cnpjFilial = $cnpjBase . '001';
$cnpjOutro = $cnpjBase . '002';
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$indicadorIds = [];
$unidadeId = 0;

try {
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n, :c, :ct, :m, :mid)');
    $insCli->execute(['n' => 'Matriz ' . $suffix, 'c' => $cnpjMatriz, 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $matriz = (int)$pdo->lastInsertId();
    $clienteIds[] = $matriz;
    $insCli->execute(['n' => 'Filial ' . $suffix, 'c' => $cnpjFilial, 'ct' => 'contato', 'm' => 0, 'mid' => $matriz]);
    $filial = (int)$pdo->lastInsertId();
    $clienteIds[] = $filial;
    $insCli->execute(['n' => 'Outro ' . $suffix, 'c' => $cnpjOutro, 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $outro = (int)$pdo->lastInsertId();
    $clienteIds[] = $outro;

    $insDep = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cliente_id)');
    $insDep->execute(['nome' => 'Departamento Matriz ' . $suffix, 'cliente_id' => $matriz]);
    $departamentoMatriz = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoMatriz;
    $insDep->execute(['nome' => 'Departamento Outro ' . $suffix, 'cliente_id' => $outro]);
    $departamentoOutro = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoOutro;

    $insSetor = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :departamento_id)');
    $insSetor->execute(['nome' => 'Setor Matriz ' . $suffix, 'departamento_id' => $departamentoMatriz]);
    $setorMatriz = (int)$pdo->lastInsertId();
    $setorIds[] = $setorMatriz;
    $insSetor->execute(['nome' => 'Setor Outro ' . $suffix, 'departamento_id' => $departamentoOutro]);
    $setorOutro = (int)$pdo->lastInsertId();
    $setorIds[] = $setorOutro;

    $stmtUnidade = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
    $stmtUnidade->execute([
        'nome' => 'Unidade TDI ' . $suffix,
        'simbolo' => 'un',
        'tipo' => 'inteiro',
    ]);
    $unidadeId = (int)$pdo->lastInsertId();

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
        'indicador' => 'Indicador Matriz ' . $suffix,
        'departamento_id' => $departamentoMatriz,
        'setor_id' => $setorMatriz,
        'responsavel_ids' => [],
        'periodicidade_tipo' => 'mensal',
        'data_inicial' => date('Y-m-01'),
        'data_final' => date('Y-m-t'),
        'valor' => '10',
        'unidade_medida_id' => $unidadeId,
        'valor_minimo' => '0',
        'valor_maximo' => '100',
    ], 1);
    $idIndicadorOutro = $indicadores->create([
        'cliente_id' => $outro,
        'indicador' => 'Indicador Outro ' . $suffix,
        'departamento_id' => $departamentoOutro,
        'setor_id' => $setorOutro,
        'responsavel_ids' => [],
        'periodicidade_tipo' => 'mensal',
        'data_inicial' => date('Y-m-01'),
        'data_final' => date('Y-m-t'),
        'valor' => '20',
        'unidade_medida_id' => $unidadeId,
        'valor_minimo' => '0',
        'valor_maximo' => '100',
    ], 1);
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

    $blockedDelete = $indicadores->softDelete((int)$idIndicadorOutro, 2);
    if ($blockedDelete) {
        failFast('Delete fora do escopo deveria ser bloqueado');
    }
    ok('Bloqueio de operação fora do escopo');

    $allowedDelete = $indicadores->softDelete((int)$idIndicadorMatriz, 2);
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
        $pdo->exec("DELETE FROM indicador_eventos WHERE indicador_id IN ($in)");
        $pdo->exec("DELETE FROM indicadores WHERE id IN ($in)");
    }
    if (!empty($setorIds)) {
        $in = implode(',', array_map('intval', $setorIds));
        $pdo->exec("DELETE FROM setores WHERE id IN ($in)");
    }
    if (!empty($departamentoIds)) {
        $in = implode(',', array_map('intval', $departamentoIds));
        $pdo->exec("DELETE FROM departamentos WHERE id IN ($in)");
    }
    if ($unidadeId > 0) {
        $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $unidadeId]);
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
