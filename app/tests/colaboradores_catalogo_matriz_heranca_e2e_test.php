<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Services\ColaboradorImportService;
use App\Controllers\ColaboradoresController;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;
use App\Models\FuncaoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = null;
try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "SKIP: sem conexão com o banco para testes de catálogo matriz/filiais.\n";
    exit(0);
}

$suffix = 'cat_matriz_' . date('YmdHis') . '_' . random_int(100, 999);
$tag = substr($suffix, -6);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$funcaoIds = [];
$colabIds = [];
$cronogramaIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:t,1,NULL)')
        ->execute(['n' => 'Matriz ' . $tag, 'c' => '55.555.555/0001-' . random_int(10, 99), 't' => 'Contato']);
    $matrizId = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizId;

    $depModel = new DepartamentoModel();
    $setorModel = new SetorModel();
    $funcaoModel = new FuncaoModel();

    $depId = $depModel->create(['nome' => 'Dep Matriz ' . $tag, 'cliente_id' => $matrizId]);
    if ($depId <= 0) { failFast('Falha ao criar departamento na matriz'); }
    $depIds[] = $depId;

    $setorId = $setorModel->create(['nome' => 'Setor Matriz ' . $tag, 'departamento_id' => $depId]);
    if ($setorId <= 0) { failFast('Falha ao criar setor na matriz'); }
    $setorIds[] = $setorId;

    $funcaoId = $funcaoModel->create(['nome' => 'Funcao Matriz ' . $tag, 'setor_id' => $setorId]);
    if ($funcaoId <= 0) { failFast('Falha ao criar função na matriz'); }
    $funcaoIds[] = $funcaoId;
    ok('Criou catálogo (departamento/setor/função) na matriz');

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:t,0,:mid)')
        ->execute(['n' => 'Filial ' . $tag, 'c' => '66.666.666/0001-' . random_int(10, 99), 't' => 'Contato', 'mid' => $matrizId]);
    $filialId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialId;
    ok('Criou filial vinculada à matriz');

    Auth::login([
        'id' => 9901,
        'nome' => 'Teste E2E',
        'email' => 'e2e.' . $suffix . '@test.local',
        'tipo_acesso' => 'instituto',
        'id_cliente' => $matrizId,
    ]);

    $ctrl = new ColaboradoresController();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['route' => 'colaboradores/create', 'cliente' => $filialId];
    ob_start();
    $ctrl->create();
    $html = (string)ob_get_clean();
    if (!str_contains($html, 'Dep Matriz ' . $tag) || !str_contains($html, 'Funcao Matriz ' . $tag)) {
        failFast('Filial deveria herdar departamentos/funções da matriz ao abrir cadastro');
    }
    ok('Herança automática: filial carrega catálogo ativo da matriz');

    $depModel->update($depId, ['nome' => 'Dep Matriz Atualizado ' . $tag, 'cliente_id' => $matrizId]);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['route' => 'colaboradores/create', 'cliente' => $filialId];
    ob_start();
    $ctrl->create();
    $html2 = (string)ob_get_clean();
    if (!str_contains($html2, 'Dep Matriz Atualizado ' . $tag)) {
        failFast('Atualização de departamento na matriz deveria refletir imediatamente na filial');
    }
    ok('Sincronização em tempo real: update na matriz reflete na filial');

    $pdo->prepare('UPDATE funcoes SET ativo = 0 WHERE id = :id')->execute(['id' => $funcaoId]);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['route' => 'colaboradores/create', 'cliente' => $filialId];
    ob_start();
    $ctrl->create();
    $html3 = (string)ob_get_clean();
    if (str_contains($html3, 'Funcao Matriz ' . $tag)) {
        failFast('Função inativada na matriz não deveria aparecer como opção na filial');
    }
    ok('Inativação: função inativa na matriz é removida das opções na filial');

    $pdo->prepare('UPDATE funcoes SET ativo = 1 WHERE id = :id')->execute(['id' => $funcaoId]);

    $csvOk = implode("\n", [
        'Nome,Documento,DN,Celular,Email,Unidade,Função,Setor,Departamento',
        'Colab ' . $tag . ',11144477735,01/01/1990,11999999999,colab.' . $suffix . '@example.com,' . ('Filial ' . $tag) . ',' . ('Funcao Matriz ' . $tag) . ',' . ('Setor Matriz ' . $tag) . ',' . ('Dep Matriz Atualizado ' . $tag),
    ]) . "\n";
    $tmpOk = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'import_ok_' . $suffix . '.csv';
    file_put_contents($tmpOk, $csvOk);

    $service = new ColaboradorImportService($pdo);
    $resOk = $service->import($tmpOk, basename($tmpOk), 9901);
    if (empty($resOk['ok']) || (int)($resOk['inserted'] ?? 0) !== 1) {
        failFast('Importação para filial com catálogo da matriz deveria inserir 1 colaborador: ' . json_encode($resOk, JSON_UNESCAPED_UNICODE));
    }
    ok('Importação: filial usa catálogo da matriz e cadastra colaborador com sucesso');

    $stmt = $pdo->prepare('SELECT id, cliente_id, funcao_id FROM colaboradores WHERE email = :e LIMIT 1');
    $stmt->execute(['e' => 'colab.' . $suffix . '@example.com']);
    $row = $stmt->fetch();
    if (!$row) {
        failFast('Colaborador importado deveria existir');
    }
    $colabIds[] = (int)$row['id'];
    if ((int)$row['cliente_id'] !== $filialId) {
        failFast('Colaborador importado deveria pertencer à filial');
    }
    if ((int)$row['funcao_id'] !== $funcaoId) {
        failFast('Colaborador importado deveria referenciar função do catálogo da matriz');
    }
    ok('Importação: colaborador da filial referencia função da matriz');

    $countFilialDeps = (int)$pdo->query('SELECT COUNT(*) FROM departamentos WHERE cliente_id = ' . (int)$filialId)->fetchColumn();
    if ($countFilialDeps !== 0) {
        failFast('Importação não deveria criar departamentos na filial');
    }
    ok('Importação: bloqueia criação de departamentos na filial');

    $csvBad = implode("\n", [
        'Nome,Documento,DN,Celular,Email,Unidade,Função,Setor,Departamento',
        'Colab Bad ' . $tag . ',52998224725,01/01/1990,11999999998,bad.' . $suffix . '@example.com,' . ('Filial ' . $tag) . ',Funcao Inexistente,Setor Inexistente,Dep Inexistente',
    ]) . "\n";
    $tmpBad = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'import_bad_' . $suffix . '.csv';
    file_put_contents($tmpBad, $csvBad);
    $resBad = $service->import($tmpBad, basename($tmpBad), 9901);
    if (!empty($resBad['ok'])) {
        failFast('Importação para filial com catálogo inexistente deveria falhar');
    }
    $messages = array_map(static fn(array $e): string => (string)($e['message'] ?? ''), $resBad['errors'] ?? []);
    $joined = implode(' | ', $messages);
    if (strpos($joined, 'catálogo da matriz') === false) {
        failFast('Erros da importação deveriam indicar obrigatoriedade do catálogo da matriz');
    }
    ok('Importação: bloqueia criação de Setor/Função/Departamento inexistentes na matriz');

    echo "colaboradores_catalogo_matriz_heranca_e2e_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    try {
        foreach (['import_ok_' . $suffix . '.csv', 'import_bad_' . $suffix . '.csv'] as $name) {
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    } catch (Throwable $e) {
    }
    if (!empty($colabIds)) {
        $pdo->exec('DELETE FROM colaboradores WHERE id IN (' . implode(',', array_map('intval', $colabIds)) . ')');
    }
    if (!empty($funcaoIds)) {
        $pdo->exec('DELETE FROM funcoes WHERE id IN (' . implode(',', array_map('intval', $funcaoIds)) . ')');
    }
    if (!empty($setorIds)) {
        $pdo->exec('DELETE FROM setores WHERE id IN (' . implode(',', array_map('intval', $setorIds)) . ')');
    }
    if (!empty($depIds)) {
        $pdo->exec('DELETE FROM departamentos WHERE id IN (' . implode(',', array_map('intval', $depIds)) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
}
