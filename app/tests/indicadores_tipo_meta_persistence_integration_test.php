<?php
require __DIR__ . '/../autoload.php';

use App\Core\Security;
use App\Database\Database;
use App\Controllers\IndicadoresController;
use App\Models\IndicadorModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

class TestIndicadoresControllerTipoMeta extends IndicadoresController
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
$cleanup = ['indicador_id' => 0, 'unidade_id' => 0, 'setor_id' => 0, 'departamento_id' => 0, 'cliente_id' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['indicador_id'])) { $pdo->prepare('DELETE FROM indicador_eventos WHERE indicador_id = :id')->execute(['id' => $cleanup['indicador_id']]); }
        if (!empty($cleanup['indicador_id'])) { $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $cleanup['indicador_id']]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        if (!empty($cleanup['setor_id'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_id']]); }
        if (!empty($cleanup['departamento_id'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]); }
        if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
    } catch (\Throwable $e) {}
});

if (!\App\Database\Database::columnExists('indicadores', 'tipo_meta')) {
    failFast('Coluna indicadores.tipo_meta não existe neste banco — migration 20260623220000_indicadores_tipo_meta_apply não foi aplicada');
}
ok('Coluna indicadores.tipo_meta existe no banco');

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute(['nome' => 'Cliente TipoMeta ' . $suffix, 'cnpj' => '66.666.666/0001-' . substr($suffix, 0, 2), 'contato' => 'Test']);
$clienteId = (int)$pdo->lastInsertId();
if ($clienteId <= 0) failFast('Falha ao criar cliente');
$cleanup['cliente_id'] = $clienteId;

$stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
$stmt->execute(['nome' => 'Dep ' . $suffix, 'cid' => $clienteId]);
$departamentoId = (int)$pdo->lastInsertId();
$cleanup['departamento_id'] = $departamentoId;

$stmt = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :did)');
$stmt->execute(['nome' => 'Setor ' . $suffix, 'did' => $departamentoId]);
$setorId = (int)$pdo->lastInsertId();
$cleanup['setor_id'] = $setorId;

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade TipoMeta ' . $suffix, 'simbolo' => '', 'tipo' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_id'] = $unidadeId;

// 1) Cadastro (store): seleciona "Teto máximo permitido" ("Indicar com teto").
$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['route' => 'indicadores/store'];
$_POST = [
    'csrf' => $csrf,
    'cliente_id' => (string)$clienteId,
    'cliente_nome' => 'Cliente TipoMeta ' . $suffix,
    'indicador' => 'Indicador TipoMeta ' . $suffix,
    'departamento_id' => (string)$departamentoId,
    'setor_id' => (string)$setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'tipo_meta' => 'maximo',
    'unidade_medida_id' => (string)$unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
unset($_SERVER['HTTP_X_REQUESTED_WITH']);

ob_start();
$ctrl = new TestIndicadoresControllerTipoMeta();
$ctrl->store();
ob_get_clean();
if (strpos($ctrl->redirectedTo, 'route=indicadores/index') === false) {
    failFast('Store não redirecionou como esperado: ' . $ctrl->redirectedTo);
}

$stmt = $pdo->prepare('SELECT id FROM indicadores WHERE indicador = :nome ORDER BY id DESC LIMIT 1');
$stmt->execute(['nome' => 'Indicador TipoMeta ' . $suffix]);
$indicadorId = (int)$stmt->fetchColumn();
if ($indicadorId <= 0) failFast('Indicador não foi criado via store');
$cleanup['indicador_id'] = $indicadorId;
ok('Cadastro criou o indicador');

$model = new IndicadorModel();
$fresh = $model->find($indicadorId);
if (($fresh['tipo_meta'] ?? '') !== 'maximo') {
    failFast('tipo_meta não persistiu como "maximo" após o cadastro. Atual: ' . json_encode($fresh['tipo_meta'] ?? null));
}
ok('Cadastro persiste "Indicar com teto" (tipo_meta=maximo)');

// 2) Edição: o formulário deve vir com a opção "maximo" pré-selecionada.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['route' => 'indicadores/edit', 'id' => (string)$indicadorId];
ob_start();
(new TestIndicadoresControllerTipoMeta())->edit();
$editHtml = (string)ob_get_clean();
if (!preg_match('/name="tipo_meta"[^>]*>.*?<option value="maximo"[^>]*selected[^>]*>/s', $editHtml)) {
    failFast('Tela de edição não pré-selecionou "Teto máximo permitido" para o indicador salvo com tipo_meta=maximo');
}
ok('Edição exibe "Indicar com teto" pré-selecionado corretamente');

// 3) Atualização: reenviar o formulário mantendo tipo_meta=maximo deve preservar a configuração.
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['route' => 'indicadores/update'];
$_POST = [
    'csrf' => Security::csrfToken(),
    'id' => (string)$indicadorId,
    'cliente_id' => (string)$clienteId,
    'cliente_nome' => 'Cliente TipoMeta ' . $suffix,
    'indicador' => 'Indicador TipoMeta ' . $suffix,
    'departamento_id' => (string)$departamentoId,
    'setor_id' => (string)$setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '15',
    'tipo_meta' => 'maximo',
    'unidade_medida_id' => (string)$unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
ob_start();
(new TestIndicadoresControllerTipoMeta())->update();
ob_get_clean();

$freshAfterUpdate = $model->find($indicadorId);
if (($freshAfterUpdate['tipo_meta'] ?? '') !== 'maximo') {
    failFast('tipo_meta não foi preservado após update(). Atual: ' . json_encode($freshAfterUpdate['tipo_meta'] ?? null));
}
ok('Edição/update preserva "Indicar com teto" (tipo_meta=maximo)');

echo "Indicadores tipo_meta persistence integration tests passed.\n";
ob_end_flush();
