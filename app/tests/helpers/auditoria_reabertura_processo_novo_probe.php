<?php
// Executa reabrirAuditoria() como a PRIMEIRA interacao com SetorMetricaModel
// num processo PHP genuinamente novo - reproduz exatamente o que acontece
// numa requisicao HTTP real de producao (cada request e um processo novo,
// entao a memoizacao estatica de SetorMetricaModel::$ensured sempre comeca
// em false). Rodado via subprocesso pelo teste que o invoca, pois um
// processo "ja aquecido" (que ja chamou SetorMetricaModel::ensure() antes)
// mascara o bug de commit implicito descoberto durante a implementacao do
// item 10 (Fluxo B).
//
// Uso: php auditoria_reabertura_processo_novo_probe.php <auditoriaId> <setorId>
// Saida: JSON com o resultado.

require_once __DIR__ . '/../../autoload.php';

use App\Core\Auth;
use App\Models\AuditoriaModel;

$auditoriaId = (int)($argv[1] ?? 0);
$setorId = (int)($argv[2] ?? 0);

Auth::login([
    'id' => 3002,
    'nome' => 'Probe Processo Novo',
    'email' => 'probe.fresh@test.local',
    'tipo_acesso' => 'instituto',
    'id_cliente' => null,
]);

$model = new AuditoriaModel();
$ok = $model->reabrirAuditoria($auditoriaId, 3002, 'Teste de processo novo (probe isolado)');
$check = $model->find($auditoriaId);

echo json_encode([
    'ok' => $ok,
    'lastError' => $model->getLastError(),
    'status_apos' => $check['status'] ?? null,
    'realizada_at_apos' => $check['realizada_at'] ?? null,
], JSON_UNESCAPED_UNICODE);
