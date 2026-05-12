<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ColaboradoresController;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;
use App\Models\FuncaoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'colab_scope_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$funcaoIds = [];
$colabIds = [];

$makeCnpj = static function (): string {
    $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
};

try {
    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $depModel = new DepartamentoModel();
    $setorModel = new SetorModel();
    $funcaoModel = new FuncaoModel();
    $colabModel = new ColaboradorModel();

    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $insCli->execute(['n' => 'Matriz ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $matrizId = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizId;

    $insCli->execute(['n' => 'Filial A ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 0, 'mid' => $matrizId]);
    $filialAId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialAId;

    $insCli->execute(['n' => 'Filial B ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 0, 'mid' => $matrizId]);
    $filialBId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialBId;

    $makeScope = static function (int $clienteId) use ($depModel, $setorModel, $funcaoModel, $suffix, &$depIds, &$setorIds, &$funcaoIds): int {
        $depId = $depModel->create(['nome' => 'Dep ' . $suffix . ' ' . $clienteId, 'cliente_id' => $clienteId]);
        $depIds[] = $depId;
        $setorId = $setorModel->create(['nome' => 'Setor ' . $suffix . ' ' . $clienteId, 'departamento_id' => $depId]);
        $setorIds[] = $setorId;
        $funcaoId = $funcaoModel->create(['nome' => 'Funcao ' . $suffix . ' ' . $clienteId, 'setor_id' => $setorId]);
        $funcaoIds[] = $funcaoId;
        return $funcaoId;
    };

    $funcMatriz = $makeScope($matrizId);
    $funcFilialA = $makeScope($filialAId);
    $funcFilialB = $makeScope($filialBId);

    $colabMatrizId = $colabModel->create([
        'nome' => 'Colab Matriz ' . $suffix,
        'email' => 'matriz.' . $suffix . '@example.com',
        'funcao_id' => $funcMatriz,
        'lider' => 'não',
        'cliente_id' => $matrizId,
    ]);
    $colabIds[] = $colabMatrizId;

    $colabFilialAId = $colabModel->create([
        'nome' => 'Colab Filial A ' . $suffix,
        'email' => 'filial.a.' . $suffix . '@example.com',
        'funcao_id' => $funcFilialA,
        'lider' => 'sim',
        'cliente_id' => $filialAId,
    ]);
    $colabIds[] = $colabFilialAId;

    $colabFilialBId = $colabModel->create([
        'nome' => 'Colab Filial B ' . $suffix,
        'email' => 'filial.b.' . $suffix . '@example.com',
        'funcao_id' => $funcFilialB,
        'lider' => 'não',
        'cliente_id' => $filialBId,
    ]);
    $colabIds[] = $colabFilialBId;

    $controller = new ColaboradoresController();
    $_SERVER['REQUEST_METHOD'] = 'GET';

    $_GET = [
        'route' => 'colaboradores/index',
        'cliente' => $matrizId,
    ];
    ob_start();
    $controller->index();
    $htmlMatriz = (string)ob_get_clean();

    if (!str_contains($htmlMatriz, 'Colab Matriz ' . $suffix)) {
        failFast('Filtro por matriz (sem consolidar) deveria exibir colaborador da matriz');
    }
    if (str_contains($htmlMatriz, 'Colab Filial A ' . $suffix) || str_contains($htmlMatriz, 'Colab Filial B ' . $suffix)) {
        failFast('Filtro por matriz (sem consolidar) não deveria exibir colaboradores de filiais');
    }
    ok('Matriz sem "Selecionar todos" exibe apenas colaboradores da matriz');

    $_GET = [
        'route' => 'colaboradores/index',
        'cliente' => $matrizId,
        'all_funcionarios' => '1',
    ];
    ob_start();
    $controller->index();
    $htmlMatrizAll = (string)ob_get_clean();

    if (!str_contains($htmlMatrizAll, 'Colab Matriz ' . $suffix)
        || !str_contains($htmlMatrizAll, 'Colab Filial A ' . $suffix)
        || !str_contains($htmlMatrizAll, 'Colab Filial B ' . $suffix)) {
        failFast('Matriz com "Selecionar todos" deveria exibir matriz e filiais');
    }
    if (substr_count($htmlMatrizAll, 'Colab Matriz ' . $suffix) !== 1
        || substr_count($htmlMatrizAll, 'Colab Filial A ' . $suffix) !== 1
        || substr_count($htmlMatrizAll, 'Colab Filial B ' . $suffix) !== 1) {
        failFast('Consolidação não deveria duplicar colaboradores');
    }
    ok('Matriz com "Selecionar todos" agrega matriz e filiais sem duplicação');

    $_GET = [
        'route' => 'colaboradores/index',
        'cliente' => $filialAId,
    ];
    ob_start();
    $controller->index();
    $htmlFilial = (string)ob_get_clean();

    if (!str_contains($htmlFilial, 'Colab Filial A ' . $suffix)) {
        failFast('Filtro por filial (sem consolidar) deveria exibir colaborador da filial');
    }
    if (str_contains($htmlFilial, 'Colab Matriz ' . $suffix) || str_contains($htmlFilial, 'Colab Filial B ' . $suffix)) {
        failFast('Filtro por filial (sem consolidar) não deveria exibir colaboradores de outras unidades');
    }
    ok('Filial sem "Selecionar todos" exibe apenas colaboradores da filial');

    $_GET = [
        'route' => 'colaboradores/index',
        'cliente' => $filialAId,
        'all_funcionarios' => '1',
    ];
    ob_start();
    $controller->index();
    $htmlFilialAll = (string)ob_get_clean();

    if (!str_contains($htmlFilialAll, 'Colab Matriz ' . $suffix)
        || !str_contains($htmlFilialAll, 'Colab Filial A ' . $suffix)
        || !str_contains($htmlFilialAll, 'Colab Filial B ' . $suffix)) {
        $counts = [
            'matriz' => substr_count($htmlFilialAll, 'Colab Matriz ' . $suffix),
            'filial_a' => substr_count($htmlFilialAll, 'Colab Filial A ' . $suffix),
            'filial_b' => substr_count($htmlFilialAll, 'Colab Filial B ' . $suffix),
        ];
        failFast('Filial com "Selecionar todos" deveria exibir matriz e todas as filiais. Contagens: ' . json_encode($counts));
    }
    ok('Filial com "Selecionar todos" agrega grupo empresarial completo');

    echo "Colaboradores filtro matriz/filiais smoke passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($colabIds)) {
        $pdo->exec('DELETE FROM colaboradores WHERE id IN (' . implode(',', array_map('intval', array_filter($colabIds))) . ')');
    }
    if (!empty($funcaoIds)) {
        $pdo->exec('DELETE FROM funcoes WHERE id IN (' . implode(',', array_map('intval', array_filter($funcaoIds))) . ')');
    }
    if (!empty($setorIds)) {
        $pdo->exec('DELETE FROM setores WHERE id IN (' . implode(',', array_map('intval', array_filter($setorIds))) . ')');
    }
    if (!empty($depIds)) {
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', array_filter($depIds))) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', array_filter($clienteIds))) . ')');
    }
    unset($_SESSION['user']);
}
