<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ClientesController;
use App\Database\Database;
use App\Models\PlanoAcaoTaskModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$tasks = new PlanoAcaoTaskModel();
$suffix = 'perfil_filial_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$taskIds = [];

try {
    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Instituto',
        'email' => 'instituto@example.com',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ];

    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $makeCnpj = static function () : string {
        $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
        return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
    };
    $insCli->execute(['n' => 'Matriz ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => null]);
    $matrizId = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizId;

    $insCli->execute(['n' => 'Filial A ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => $matrizId]);
    $filialAId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialAId;

    $insCli->execute(['n' => 'Filial B ' . $suffix, 'c' => $makeCnpj(), 'ct' => 'Contato', 'm' => 1, 'mid' => $matrizId]);
    $filialBId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialBId;

    $taskIds[] = $tasks->create([
        'id_cliente' => $matrizId,
        'titulo' => 'Plano Matriz ' . $suffix,
        'status' => 'Planejado',
        'progresso' => 0,
    ]);
    $taskIds[] = $tasks->create([
        'id_cliente' => $filialAId,
        'titulo' => 'Plano Filial A ' . $suffix,
        'status' => 'Em Andamento',
        'progresso' => 50,
    ]);
    $taskIds[] = $tasks->create([
        'id_cliente' => $filialBId,
        'titulo' => 'Plano Filial B ' . $suffix,
        'status' => 'Pendente',
        'progresso' => 0,
    ]);

    $controller = new ClientesController();

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [
        'route' => 'clientes/show',
        'id' => $matrizId,
    ];
    ob_start();
    $controller->show();
    $htmlAgregado = (string)ob_get_clean();

    if (!str_contains($htmlAgregado, 'name="filial_id"')) {
        failFast('Perfil da matriz deveria exibir dropdown de filial');
    }
    if (!str_contains($htmlAgregado, 'Todas as filiais')) {
        failFast('Dropdown deveria oferecer opção agregada para toda a empresa');
    }
    if (!str_contains($htmlAgregado, 'Plano Matriz ' . $suffix)
        || !str_contains($htmlAgregado, 'Plano Filial A ' . $suffix)
        || !str_contains($htmlAgregado, 'Plano Filial B ' . $suffix)) {
        failFast('Sem filial selecionada a tela deveria agregar matriz e filiais');
    }
    ok('Perfil agrega dados de toda a empresa quando nenhuma filial é selecionada');

    $_GET = [
        'route' => 'clientes/show',
        'id' => $matrizId,
        'filial_id' => $filialAId,
    ];
    ob_start();
    $controller->show();
    $htmlFilial = (string)ob_get_clean();

    if (!str_contains($htmlFilial, 'Plano Filial A ' . $suffix)) {
        failFast('Perfil filtrado deveria exibir os dados da filial selecionada');
    }
    if (str_contains($htmlFilial, 'Plano Matriz ' . $suffix) || str_contains($htmlFilial, 'Plano Filial B ' . $suffix)) {
        failFast('Perfil filtrado não deveria exibir dados de outras unidades');
    }
    ok('Perfil restringe a listagem para a filial selecionada');

    echo "Cliente perfil filial filter smoke passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if (!empty($taskIds)) {
        $pdo->exec('DELETE FROM pdca_tasks WHERE id IN (' . implode(',', array_map('intval', array_filter($taskIds))) . ')');
    }
    if (!empty($clienteIds)) {
        $pdo->exec('DELETE FROM clientes WHERE id IN (' . implode(',', array_map('intval', $clienteIds)) . ')');
    }
}
