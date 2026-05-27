<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ColaboradoresController;
use App\Core\Security;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;
use App\Models\FuncaoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'colab_matriz_catalog_' . date('YmdHis') . '_' . random_int(100, 999);

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

    $insCli->execute(['n' => 'Filial ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 0, 'mid' => $matrizId]);
    $filialId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialId;

    $insCli->execute(['n' => 'Independente ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $indepId = (int)$pdo->lastInsertId();
    $clienteIds[] = $indepId;

    $depMatriz = $depModel->create(['nome' => 'Dep Matriz ' . $suffix, 'cliente_id' => $matrizId]);
    if ($depMatriz <= 0) { failFast('Falha ao criar departamento da matriz'); }
    $depIds[] = $depMatriz;
    $setorMatriz = $setorModel->create(['nome' => 'Setor Matriz ' . $suffix, 'departamento_id' => $depMatriz]);
    if ($setorMatriz <= 0) { failFast('Falha ao criar setor da matriz'); }
    $setorIds[] = $setorMatriz;
    $funcaoMatriz = $funcaoModel->create(['nome' => 'Funcao Matriz ' . $suffix, 'setor_id' => $setorMatriz]);
    if ($funcaoMatriz <= 0) { failFast('Falha ao criar função da matriz'); }
    $funcaoIds[] = $funcaoMatriz;

    $depIndep = $depModel->create(['nome' => 'Dep Indep ' . $suffix, 'cliente_id' => $indepId]);
    if ($depIndep <= 0) { failFast('Falha ao criar departamento independente'); }
    $depIds[] = $depIndep;
    $setorIndep = $setorModel->create(['nome' => 'Setor Indep ' . $suffix, 'departamento_id' => $depIndep]);
    if ($setorIndep <= 0) { failFast('Falha ao criar setor independente'); }
    $setorIds[] = $setorIndep;
    $funcaoIndep = $funcaoModel->create(['nome' => 'Funcao Indep ' . $suffix, 'setor_id' => $setorIndep]);
    if ($funcaoIndep <= 0) { failFast('Falha ao criar função independente'); }
    $funcaoIds[] = $funcaoIndep;

    $ctrl = new ColaboradoresController();
    $_SERVER['REQUEST_METHOD'] = 'GET';

    $_GET = ['route' => 'colaboradores/create', 'cliente' => $matrizId];
    ob_start();
    $ctrl->create();
    $htmlMatriz = (string)ob_get_clean();
    if (!str_contains($htmlMatriz, 'Dep Matriz ' . $suffix) || str_contains($htmlMatriz, 'Dep Indep ' . $suffix)) {
        failFast('Matriz deveria ver apenas seus próprios cadastros globais');
    }
    ok('Matriz acessa seus próprios cadastros');

    $_GET = ['route' => 'colaboradores/create', 'cliente' => $filialId];
    ob_start();
    $ctrl->create();
    $htmlFilial = (string)ob_get_clean();
    if (!str_contains($htmlFilial, 'Dep Matriz ' . $suffix) || str_contains($htmlFilial, 'Dep Indep ' . $suffix)) {
        failFast('Filial deveria carregar cadastros globais da sua matriz');
    }
    ok('Filial acessa cadastros da matriz vinculada');

    $_GET = ['route' => 'colaboradores/create', 'cliente' => $indepId];
    ob_start();
    $ctrl->create();
    $htmlIndep = (string)ob_get_clean();
    if (!str_contains($htmlIndep, 'Dep Indep ' . $suffix) || str_contains($htmlIndep, 'Dep Matriz ' . $suffix)) {
        failFast('Empresa independente deveria ver apenas seus próprios cadastros');
    }
    ok('Empresa independente mantém isolamento de cadastros');

    $before = (int)$pdo->query("SELECT COUNT(*) FROM colaboradores WHERE nome LIKE 'Colab % $suffix'")->fetchColumn();
    $csrf = Security::csrfToken();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = ['route' => 'colaboradores/store'];
    $_POST = [
        'csrf' => $csrf,
        'nome' => 'Colab Filial OK ' . $suffix,
        'email' => 'ok.' . $suffix . '@example.com',
        'funcao_id' => (string)$funcaoMatriz,
        'lider' => 'não',
        'cliente' => (string)$filialId,
    ];
    ob_start();
    $ctrl->store();
    ob_end_clean();
    $afterOk = (int)$pdo->query("SELECT COUNT(*) FROM colaboradores WHERE nome LIKE 'Colab % $suffix'")->fetchColumn();
    if ($afterOk !== $before + 1) {
        failFast('Cadastro de colaborador da filial com função da matriz deveria criar registro');
    }
    $stmt = $pdo->prepare('SELECT id, cliente_id, funcao_id FROM colaboradores WHERE nome = :n ORDER BY id DESC LIMIT 1');
    $stmt->execute(['n' => 'Colab Filial OK ' . $suffix]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['cliente_id'] !== $filialId || (int)$row['funcao_id'] !== $funcaoMatriz) {
        failFast('Registro criado não preservou cliente/funcao corretamente: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
    }
    $colabIds[] = (int)$row['id'];
    ok('Filial cria colaborador usando função global da matriz');

    $beforeBad = (int)$pdo->query("SELECT COUNT(*) FROM colaboradores WHERE nome LIKE 'Colab % $suffix'")->fetchColumn();
    $csrf = Security::csrfToken();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = ['route' => 'colaboradores/store'];
    $_POST = [
        'csrf' => $csrf,
        'nome' => 'Colab Filial BAD ' . $suffix,
        'email' => 'bad.' . $suffix . '@example.com',
        'funcao_id' => (string)$funcaoIndep,
        'lider' => 'não',
        'cliente' => (string)$filialId,
    ];
    ob_start();
    $ctrl->store();
    ob_end_clean();
    $afterBad = (int)$pdo->query("SELECT COUNT(*) FROM colaboradores WHERE nome LIKE 'Colab % $suffix'")->fetchColumn();
    if ($afterBad !== $beforeBad) {
        failFast('Não deveria permitir função de empresa não relacionada ao cadastrar colaborador na filial');
    }
    ok('Validação bloqueia vazamento de dados entre empresas não relacionadas');

    echo "Colaboradores matriz catalog scope integration test passed.\n";
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

