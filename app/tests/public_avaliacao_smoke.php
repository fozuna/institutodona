<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\AvaliacaoPublicaModel;

$pdo = Database::getConnection();
$avaliacaoId = (int)$pdo->query('SELECT id FROM avaliacoes ORDER BY id DESC LIMIT 1')->fetchColumn();

if ($avaliacaoId <= 0) {
    $clienteId = (int)$pdo->query('SELECT id FROM clientes ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($clienteId <= 0) {
        echo "NO_CLIENTE";
        exit(0);
    }
    $columns = $pdo->query('SHOW COLUMNS FROM avaliacoes')->fetchAll(PDO::FETCH_ASSOC);
    $data = [];
    foreach ($columns as $column) {
        $field = (string)($column['Field'] ?? '');
        if ($field === 'id') {
            continue;
        }
        if ($field === 'cliente_id') {
            $data[$field] = $clienteId;
            continue;
        }
        if ($field === 'empresa_nome') {
            $data[$field] = 'Smoke Empresa';
            continue;
        }
        if ($field === 'contato' || $field === 'nome') {
            $data[$field] = 'Smoke Cliente';
            continue;
        }
        if ($field === 'email') {
            $data[$field] = 'smoke@example.com';
            continue;
        }
        if ($field === 'whatsapp') {
            $data[$field] = '11999999999';
            continue;
        }
        if ($field === 'numero_funcionarios') {
            $data[$field] = 10;
            continue;
        }
        if ($field === 'numero_lideres') {
            $data[$field] = 2;
            continue;
        }
        if ($field === 'faturamento_medio_anual') {
            $data[$field] = 100000;
            continue;
        }
        if ($field === 'tomador_decisao') {
            $data[$field] = 1;
            continue;
        }
        if ($field === 'respostas_json') {
            $data[$field] = json_encode(['financeiro' => [], 'mercado' => [], 'pessoas' => [], 'processo' => []]);
            continue;
        }
        if (in_array($field, ['nota_financeiro', 'nota_mercado', 'nota_pessoas', 'nota_processo', 'financeiro_nota', 'mercado_nota', 'pessoas_nota', 'processo_nota'], true)) {
            $data[$field] = 0;
            continue;
        }
        if (in_array($field, ['realidade_financeiro', 'realidade_mercado', 'realidade_pessoas', 'realidade_processo', 'financeiro_realidade', 'mercado_realidade', 'pessoas_realidade', 'processo_realidade'], true)) {
            $data[$field] = 0;
            continue;
        }
        if ($field === 'created_at') {
            $data[$field] = date('Y-m-d H:i:s');
            continue;
        }
        if (($column['Null'] ?? '') === 'YES') {
            $data[$field] = null;
            continue;
        }
        $type = strtolower((string)($column['Type'] ?? ''));
        if (preg_match('/int|decimal|float|double/', $type)) {
            $data[$field] = 0;
            continue;
        }
        if (preg_match('/date|time|year/', $type)) {
            $data[$field] = date('Y-m-d H:i:s');
            continue;
        }
        $data[$field] = '';
    }
    $insertColumns = array_keys($data);
    $placeholders = array_map(static fn($field) => ':' . $field, $insertColumns);
    $sql = 'INSERT INTO avaliacoes (' . implode(',', $insertColumns) . ') VALUES (' . implode(',', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    $avaliacaoId = (int)$pdo->lastInsertId();
}

$publicaModel = new AvaliacaoPublicaModel();
$publica = $publicaModel->createOrRefreshForAvaliacao($avaliacaoId, 'Smoke Empresa');
if (empty($publica['token'])) {
    echo "NO_TOKEN";
    exit(0);
}

$publicaModel->startByToken($publica['token'], [
    'nome' => 'Cliente Público',
    'empresa' => 'Empresa Pública',
    'whatsapp' => '11999999999',
    'email' => 'cliente.publico@example.com',
    'numero_funcionarios' => 20,
    'numero_lideres' => 3,
    'faturamento_anual' => 200000,
    'tomador_decisao' => 1,
]);

$aposInicio = $publicaModel->findByToken($publica['token']);
$publicaModel->concludeByToken($publica['token'], [
    'respostas_json' => json_encode([
        'financeiro' => [1, 2],
        'mercado' => [1],
        'pessoas' => [],
        'processo' => [1, 2, 3],
    ]),
    'nota_financeiro' => 2,
    'nota_mercado' => 1,
    'nota_pessoas' => 0,
    'nota_processo' => 3,
    'realidade_financeiro' => 29,
    'realidade_mercado' => 14,
    'realidade_pessoas' => 0,
    'realidade_processo' => 43,
]);

$aposConclusao = $publicaModel->findByToken($publica['token']);
$novoLink = $publicaModel->createOrRefreshForAvaliacao($avaliacaoId, '', true);
$formularioLimpo = ($novoLink['nome'] ?? null) === null
    && ($novoLink['empresa'] ?? null) === null
    && ($novoLink['email'] ?? null) === null
    && ($novoLink['whatsapp'] ?? null) === null
    && (string)($novoLink['status'] ?? '') === 'pendente';
$expirada = $novoLink;
$pdo->prepare('UPDATE avaliacoes_publicas SET expiracao = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id = :id')
    ->execute(['id' => (int)$expirada['id']]);

echo json_encode([
    'token' => $publica['token'],
    'novo_token' => $novoLink['token'] ?? null,
    'token_expirado' => $expirada['token'] ?? null,
    'status_inicio' => $aposInicio['status'] ?? null,
    'status_final' => $aposConclusao['status'] ?? null,
    'tem_conclusao' => !empty($aposConclusao['data_conclusao']),
    'formulario_limpo' => $formularioLimpo,
], JSON_UNESCAPED_UNICODE);
