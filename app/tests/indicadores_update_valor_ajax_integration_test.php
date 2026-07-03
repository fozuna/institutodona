<?php
require __DIR__ . '/../autoload.php';

use App\Core\Security;
use App\Database\Database;
use App\Controllers\IndicadoresController;
use App\Models\IndicadorModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

class TestIndicadoresController extends IndicadoresController
{
    public string $redirectedTo = '';

    protected function redirect(string $url): void
    {
        $this->redirectedTo = $url;
    }
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = [
    'indicador_ids' => [],
    'unidade_id' => 0,
    'setor_id' => 0,
    'departamento_id' => 0,
    'cliente_id' => 0,
];

register_shutdown_function(function() use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['indicador_ids']) && is_array($cleanup['indicador_ids'])) {
            $stmt = $pdo->prepare('DELETE FROM indicadores WHERE id = :id');
            foreach (array_values(array_unique(array_map('intval', $cleanup['indicador_ids']))) as $id) {
                if ($id > 0) {
                    $stmt->execute(['id' => $id]);
                }
            }
        }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        if (!empty($cleanup['setor_id'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_id']]); }
        if (!empty($cleanup['departamento_id'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]); }
        if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
    } catch (\Throwable $e) {}
});

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute([
    'nome' => 'Cliente Indicadores ' . $suffix,
    'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
    'contato' => 'Test',
]);
$clienteId = (int)$pdo->lastInsertId();
if ($clienteId <= 0) failFast('Falha ao criar cliente');
$cleanup['cliente_id'] = $clienteId;
ok('Cliente criado');

$stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
$stmt->execute(['nome' => 'Dep ' . $suffix, 'cid' => $clienteId]);
$departamentoId = (int)$pdo->lastInsertId();
if ($departamentoId <= 0) failFast('Falha ao criar departamento');
$cleanup['departamento_id'] = $departamentoId;

$stmt = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :did)');
$stmt->execute(['nome' => 'Setor ' . $suffix, 'did' => $departamentoId]);
$setorId = (int)$pdo->lastInsertId();
if ($setorId <= 0) failFast('Falha ao criar setor');
$cleanup['setor_id'] = $setorId;

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade Teste ' . $suffix, 'simbolo' => '', 'tipo' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
if ($unidadeId <= 0) failFast('Falha ao criar unidade');
$cleanup['unidade_id'] = $unidadeId;

$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['route' => 'indicadores/store'];
$_POST = [
    'csrf' => $csrf,
    'cliente_id' => (string)$clienteId,
    'cliente_nome' => 'Cliente Indicadores ' . $suffix,
    'indicador' => 'Indicador Store ' . $suffix,
    'departamento_id' => (string)$departamentoId,
    'setor_id' => (string)$setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => (string)$unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

ob_start();
$ctrl = new TestIndicadoresController();
$ctrl->store();
$html = ob_get_clean();
if ($html !== '') {
    failFast('Store não deveria renderizar HTML em caso de sucesso');
}
if ($ctrl->redirectedTo === '' || strpos($ctrl->redirectedTo, 'route=indicadores/index') === false) {
    failFast('Store não redirecionou corretamente: ' . $ctrl->redirectedTo);
}
if (strpos($ctrl->redirectedTo, '&cliente=' . $clienteId) === false) {
    failFast('Store não preservou cliente no redirect: ' . $ctrl->redirectedTo);
}
$stmt = $pdo->prepare('SELECT id, cliente_id FROM indicadores WHERE indicador = :nome ORDER BY id DESC LIMIT 1');
$stmt->execute(['nome' => 'Indicador Store ' . $suffix]);
$createdRow = $stmt->fetch();
if (!$createdRow) {
    failFast('Indicador não foi criado via controller store');
}
ok('Store criou indicador');
if ((int)$createdRow['cliente_id'] !== $clienteId) {
    failFast('cliente_id não persistiu via store. Atual=' . json_encode($createdRow['cliente_id']));
}
ok('Store persistiu cliente_id corretamente');
$freshStore = (new IndicadorModel())->find((int)$createdRow['id']);
if (($freshStore['tipo_meta'] ?? '') !== 'minimo') {
    failFast('tipo_meta não persistiu com default minimo via store');
}
ok('Store persistiu tipo_meta corretamente');
$cleanup['indicador_ids'][] = (int)$createdRow['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM indicadores WHERE indicador = :nome');
$stmt->execute(['nome' => 'Indicador Missing Cliente ' . $suffix]);
$beforeMissing = (int)$stmt->fetchColumn();
$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['route' => 'indicadores/store'];
$_POST = [
    'csrf' => $csrf,
    'cliente_id' => '',
    'cliente_nome' => 'Cliente Indicadores ' . $suffix,
    'indicador' => 'Indicador Missing Cliente ' . $suffix,
    'departamento_id' => (string)$departamentoId,
    'setor_id' => (string)$setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => (string)$unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
ob_start();
(new TestIndicadoresController())->store();
$outMissing = ob_get_clean();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM indicadores WHERE indicador = :nome');
$stmt->execute(['nome' => 'Indicador Missing Cliente ' . $suffix]);
$afterMissing = (int)$stmt->fetchColumn();
if ($afterMissing !== $beforeMissing) {
    failFast('Store deveria bloquear criação sem cliente_id');
}
if (strpos($outMissing, 'Selecione um cliente ativo e válido.') === false) {
    failFast('Store deveria exibir mensagem de erro para cliente inválido');
}
ok('Store bloqueia criação sem cliente selecionado');

$model = new IndicadorModel();
$payload = [
    'cliente_id' => $clienteId,
    'indicador' => 'Indicador ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = $model->validate($payload);
if ($errors) failFast('Payload inválido: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
$indicadorId = $model->create($payload, 1);
if ($indicadorId <= 0) failFast('Falha ao criar indicador');
$cleanup['indicador_ids'][] = $indicadorId;
ok('Indicador criado');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['csrf' => $csrf, 'id' => $indicadorId, 'valor' => '25,50'];
$_GET = ['route' => 'indicadores/updateValorAjax'];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

ob_start();
(new IndicadoresController())->updateValorAjax();
$out = ob_get_clean();
$payload = json_decode((string)$out, true);
if (!is_array($payload) || empty($payload['ok'])) {
    $lastJson = '';
    if (preg_match('/(\{.*\})\s*$/s', (string)$out, $m)) {
        $lastJson = $m[1];
    }
    $payload = $lastJson ? json_decode($lastJson, true) : null;
    if (!is_array($payload) || empty($payload['ok'])) {
        failFast('Resposta inválida: ' . $out);
    }
}
ok('Resposta ok');

$fresh = $model->find($indicadorId);
if (!$fresh) failFast('Indicador não encontrado após update');
if (abs((float)$fresh['valor'] - 25.50) > 0.0001) {
    failFast('Valor não persistiu. Atual: ' . json_encode($fresh['valor']));
}
ok('Persistência ok');

$_POST = ['csrf' => $csrf, 'id' => $indicadorId, 'valor' => '-12,75'];
$_GET = ['route' => 'indicadores/updateValorAjax'];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

ob_start();
(new IndicadoresController())->updateValorAjax();
$outNegative = ob_get_clean();
$payloadNegative = json_decode((string)$outNegative, true);
if (!is_array($payloadNegative) || !empty($payloadNegative['ok'])) {
    failFast('Update com meta negativa deveria falhar: ' . $outNegative);
}
ok('Update AJAX bloqueia meta negativa');

$freshNegative = $model->find($indicadorId);
if (!$freshNegative) failFast('Indicador não encontrado após update negativo');
if (abs((float)$freshNegative['valor'] - 25.50) > 0.0001) {
    failFast('Valor deveria permanecer inalterado após rejeitar meta negativa. Atual: ' . json_encode($freshNegative['valor']));
}
ok('Update inválido não altera a meta persistida');

echo "All indicadores updateValorAjax integration tests passed.\n";
ob_end_flush();
