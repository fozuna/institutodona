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
$suffix = 'colab_chain_' . date('YmdHis') . '_' . random_int(100, 999);
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

    $makeScope = static function (int $clienteId) use ($depModel, $setorModel, $funcaoModel, $suffix, &$depIds, &$setorIds, &$funcaoIds): array {
        $depId = $depModel->create(['nome' => 'Dep ' . $suffix . ' ' . $clienteId, 'cliente_id' => $clienteId]);
        $depIds[] = $depId;
        $setorId = $setorModel->create(['nome' => 'Setor ' . $suffix . ' ' . $clienteId, 'departamento_id' => $depId]);
        $setorIds[] = $setorId;
        $funcaoId = $funcaoModel->create(['nome' => 'Funcao ' . $suffix . ' ' . $clienteId, 'setor_id' => $setorId]);
        $funcaoIds[] = $funcaoId;
        return ['dep' => $depId, 'setor' => $setorId, 'funcao' => $funcaoId];
    };

    $scopeMatriz = $makeScope($matrizId);
    $scopeA = $makeScope($filialAId);
    $scopeB = $makeScope($filialBId);

    $colabMatrizId = $colabModel->create([
        'nome' => 'Colab Matriz ' . $suffix,
        'email' => 'matriz.' . $suffix . '@example.com',
        'funcao_id' => $scopeMatriz['funcao'],
        'lider' => 'não',
        'cliente_id' => $matrizId,
        'ativo' => 1,
    ]);
    $colabIds[] = $colabMatrizId;

    $colabFilialAId = $colabModel->create([
        'nome' => 'Colab Filial A ' . $suffix,
        'email' => 'filial.a.' . $suffix . '@example.com',
        'funcao_id' => $scopeA['funcao'],
        'lider' => 'sim',
        'cliente_id' => $filialAId,
        'ativo' => 0,
    ]);
    $colabIds[] = $colabFilialAId;

    $colabFilialBId = $colabModel->create([
        'nome' => 'Colab Filial B ' . $suffix,
        'email' => 'filial.b.' . $suffix . '@example.com',
        'funcao_id' => $scopeB['funcao'],
        'lider' => 'não',
        'cliente_id' => $filialBId,
        'ativo' => 1,
    ]);
    $colabIds[] = $colabFilialBId;

    $controller = new ColaboradoresController();
    $_SERVER['REQUEST_METHOD'] = 'GET';

    $_GET = [
        'route' => 'colaboradores/filterAjax',
        'cliente' => $matrizId,
    ];
    ob_start();
    $controller->filterAjax();
    $json = (string)ob_get_clean();
    $payload = json_decode($json, true);
    if (!is_array($payload) || empty($payload['ok'])) {
        failFast('Resposta inválida do filterAjax (matriz): ' . $json);
    }
    if (str_contains((string)($payload['rows_html'] ?? ''), 'Colab Filial A ' . $suffix)) {
        failFast('Sem consolidar, não deveria trazer colaboradores de filiais');
    }
    ok('Filtro base respeita empresa selecionada sem consolidar');

    $_GET = [
        'route' => 'colaboradores/filterAjax',
        'cliente' => $matrizId,
        'all_funcionarios' => '1',
    ];
    ob_start();
    $controller->filterAjax();
    $payload = json_decode((string)ob_get_clean(), true);
    if (empty($payload['ok'])) {
        failFast('Resposta inválida do filterAjax (consolidar)');
    }
    $rows = (string)($payload['rows_html'] ?? '');
    if (!str_contains($rows, 'Colab Matriz ' . $suffix)
        || !str_contains($rows, 'Colab Filial A ' . $suffix)
        || !str_contains($rows, 'Colab Filial B ' . $suffix)) {
        failFast('Com consolidar, deveria trazer matriz e filiais');
    }
    ok('Consolidação respeita escopo empresarial (matriz + filiais)');

    $_GET = [
        'route' => 'colaboradores/filterAjax',
        'cliente' => $matrizId,
        'all_funcionarios' => '1',
        'unidade_id' => $filialAId,
    ];
    ob_start();
    $controller->filterAjax();
    $payload = json_decode((string)ob_get_clean(), true);
    $rows = (string)($payload['rows_html'] ?? '');
    if (!str_contains($rows, 'Colab Filial A ' . $suffix) || str_contains($rows, 'Colab Filial B ' . $suffix) || str_contains($rows, 'Colab Matriz ' . $suffix)) {
        failFast('Filtro por unidade deveria restringir aos colaboradores da unidade selecionada');
    }
    ok('Filtro por unidade restringe resultados corretamente');

    $_GET = [
        'route' => 'colaboradores/filterAjax',
        'cliente' => $matrizId,
        'all_funcionarios' => '1',
        'unidade_id' => $filialAId,
        'departamento' => $scopeB['dep'],
    ];
    ob_start();
    $controller->filterAjax();
    $payload = json_decode((string)ob_get_clean(), true);
    if ((int)($payload['filters']['departamento'] ?? 0) !== 0) {
        failFast('Departamento inválido para a unidade deveria ser normalizado para 0');
    }
    $rows = (string)($payload['rows_html'] ?? '');
    if (!str_contains($rows, 'Colab Filial A ' . $suffix)) {
        failFast('Após normalizar departamento inválido, deveria manter resultado correto');
    }
    ok('Normalização impede combinações inválidas entre filtros encadeados');

    $_GET = [
        'route' => 'colaboradores/filterAjax',
        'cliente' => $matrizId,
        'all_funcionarios' => '1',
        'status' => 'inativo',
    ];
    ob_start();
    $controller->filterAjax();
    $payload = json_decode((string)ob_get_clean(), true);
    $rows = (string)($payload['rows_html'] ?? '');
    if (!str_contains($rows, 'Colab Filial A ' . $suffix) || str_contains($rows, 'Colab Matriz ' . $suffix) || str_contains($rows, 'Colab Filial B ' . $suffix)) {
        failFast('Filtro por status deveria trazer apenas os colaboradores inativos');
    }
    ok('Filtro por status aplica corretamente');

    $_GET = [
        'route' => 'colaboradores/filterAjax',
        'cliente' => $matrizId,
        'all_funcionarios' => '1',
        'unidade_id' => $filialAId,
        'lider' => 'não',
    ];
    ob_start();
    $controller->filterAjax();
    $payload = json_decode((string)ob_get_clean(), true);
    $rows = (string)($payload['rows_html'] ?? '');
    if (!str_contains($rows, 'Nenhum colaborador encontrado')) {
        failFast('Combinação inválida deveria resultar em estado vazio');
    }
    ok('Estado vazio é retornado quando não há colaboradores para os filtros');

    echo "Colaboradores chained filters integration test passed.\n";
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
