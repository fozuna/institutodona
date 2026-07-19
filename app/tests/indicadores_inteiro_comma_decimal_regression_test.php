<?php
require __DIR__ . '/../autoload.php';

use App\Core\Security;
use App\Database\Database;
use App\Controllers\IndicadoresController;
use App\Models\IndicadorModel;
use App\Models\IndicadorEventoModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

class TestIndicadoresControllerComma extends IndicadoresController
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

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute(['nome' => 'Cliente Inteiro ' . $suffix, 'cnpj' => '55.555.555/0001-' . substr($suffix, 0, 2), 'contato' => 'Test']);
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

// Unidade de medida do tipo "inteiro" — o cenário relatado pelo cliente.
$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade Inteiro ' . $suffix, 'simbolo' => 'un', 'tipo' => 'inteiro']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_id'] = $unidadeId;

$model = new IndicadorModel();

// 1) Cadastro: meta com vírgula ("5,00") deve ser aceita para indicador tipo inteiro.
$payload = [
    'cliente_id' => $clienteId,
    'indicador' => 'Indicador Inteiro ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '5,00',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = $model->validate($payload);
if ($errors) failFast('Cadastro rejeitou meta com vírgula para indicador inteiro: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
$indicadorId = $model->create($payload, 1);
if ($indicadorId <= 0) failFast('Falha ao criar indicador');
$cleanup['indicador_id'] = $indicadorId;
ok('Cadastro aceita vírgula decimal na meta de indicador tipo inteiro');

// 2) Edição inline na listagem (updateValorAjax): vírgula deve ser aceita; fracionário deve ser rejeitado.
$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['route' => 'indicadores/updateValorAjax'];
$_POST = ['csrf' => $csrf, 'id' => (string)$indicadorId, 'valor' => '8,00'];
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
ob_start();
(new IndicadoresController())->updateValorAjax();
$out = (string)ob_get_clean();
$payloadOut = json_decode($out, true);
if (!is_array($payloadOut) || empty($payloadOut['ok'])) {
    failFast('updateValorAjax rejeitou "8,00" para indicador tipo inteiro: ' . $out);
}
$fresh = $model->find($indicadorId);
if (abs((float)$fresh['valor'] - 8.0) > 0.0001) {
    failFast('Valor "8,00" não persistiu como 8 para indicador tipo inteiro. Atual: ' . json_encode($fresh['valor']));
}
ok('Edição inline (cards) aceita vírgula decimal para indicador tipo inteiro e persiste corretamente');

$_POST = ['csrf' => Security::csrfToken(), 'id' => (string)$indicadorId, 'valor' => '8,5'];
ob_start();
(new IndicadoresController())->updateValorAjax();
$outFrac = (string)ob_get_clean();
$payloadFrac = json_decode($outFrac, true);
if (!is_array($payloadFrac) || !empty($payloadFrac['ok'])) {
    failFast('updateValorAjax deveria rejeitar "8,5" (fracionário) para indicador tipo inteiro: ' . $outFrac);
}
ok('Edição inline rejeita corretamente valor fracionário com vírgula para indicador tipo inteiro');

// 3) Tela "Lançar Valor" (updateRealizado -> IndicadorEventoModel::updateAchievedValue).
$eventos = new IndicadorEventoModel();
$stmt = $pdo->prepare('SELECT id, periodo_inicio, periodo_fim FROM indicador_eventos WHERE indicador_id = :id ORDER BY id LIMIT 1');
$stmt->execute(['id' => $indicadorId]);
$evento = $stmt->fetch();
if (!$evento) failFast('Nenhum evento foi gerado automaticamente para o indicador de teste');
$eventoId = (int)$evento['id'];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['route' => 'indicadores/updateRealizado'];
unset($_SERVER['HTTP_X_REQUESTED_WITH']);
$_POST = [
    'csrf' => Security::csrfToken(),
    'evento_id' => (string)$eventoId,
    'cliente' => (string)$clienteId,
    'indicador_id' => (string)$indicadorId,
    'periodo_inicio' => (string)$evento['periodo_inicio'],
    'periodo_fim' => (string)$evento['periodo_fim'],
    'valor' => '9,00',
];
$ctrl = new TestIndicadoresControllerComma();
ob_start();
$ctrl->updateRealizado();
ob_get_clean();
if (strpos((string)($_SESSION['flash_error'] ?? ''), '') !== false && !empty($_SESSION['flash_error'])) {
    failFast('updateRealizado retornou erro inesperado para "9,00" em indicador tipo inteiro: ' . $_SESSION['flash_error']);
}
$freshEvento = $eventos->find($eventoId);
if (abs((float)$freshEvento['valor_atingido'] - 9.0) > 0.0001) {
    failFast('Valor "9,00" não persistiu como 9 no lançamento (Lançar Valor) para indicador tipo inteiro. Atual: ' . json_encode($freshEvento['valor_atingido']));
}
ok('Tela "Lançar Valor" aceita vírgula decimal para indicador tipo inteiro e persiste corretamente');

unset($_SESSION['flash_error']);
$_POST['valor'] = '9,7';
$ctrl2 = new TestIndicadoresControllerComma();
ob_start();
$ctrl2->updateRealizado();
ob_get_clean();
if (empty($_SESSION['flash_error'])) {
    failFast('"Lançar Valor" deveria rejeitar "9,7" (fracionário) para indicador tipo inteiro, mas não sinalizou erro');
}
$freshEvento2 = $eventos->find($eventoId);
if (abs((float)$freshEvento2['valor_atingido'] - 9.0) > 0.0001) {
    failFast('Valor fracionário "9,7" foi persistido indevidamente para indicador tipo inteiro. Atual: ' . json_encode($freshEvento2['valor_atingido']));
}
ok('Tela "Lançar Valor" rejeita corretamente valor fracionário com vírgula para indicador tipo inteiro, sem alterar o valor já salvo');

echo "Indicadores inteiro comma decimal regression tests passed.\n";
ob_end_flush();
