<?php
/**
 * Probe em subprocesso para as acoes de encerramento de treinamento: as
 * actions do controller sempre terminam em redirect() (header + exit).
 *
 * Uso: php treinamentos_encerramento_probe.php <acao> <tipo_acesso> <treinamento_id> [cliente_id]
 *   acao: encerrar | reabrir | store_agenda
 */
require_once __DIR__ . '/../../autoload.php';

use App\Controllers\TreinamentosController;
use App\Core\Security;

session_start();

$acao = (string)($argv[1] ?? '');
$tipoAcesso = (string)($argv[2] ?? 'instituto');
$treinamentoId = (int)($argv[3] ?? 0);
$clienteId = (int)($argv[4] ?? 0);

// Id inexistente em usuarios: Auth::refreshScope() mantem o perfil da sessao.
$_SESSION['user'] = [
    'id' => 987654321,
    'nome' => 'Probe ' . $tipoAcesso,
    'email' => 'probe@example.com',
    'tipo_acesso' => $tipoAcesso,
    'id_cliente' => $clienteId > 0 ? $clienteId : null,
    'allowed_client_ids' => $clienteId > 0 ? [$clienteId] : [],
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['route'] = 'treinamentos/' . $acao;
$_POST = ['csrf' => Security::csrfToken()];

$controller = new TreinamentosController();
switch ($acao) {
    case 'encerrar':
        $_POST += ['id' => (string)$treinamentoId, 'justificativa' => 'Probe'];
        $controller->encerrar();
        break;
    case 'reabrir':
        $_POST += ['id' => (string)$treinamentoId];
        $controller->reabrir();
        break;
    case 'store_agenda':
        $_POST += [
            'treinamento_id' => (string)$treinamentoId,
            'data' => date('Y-m-d\TH:i', strtotime('+20 days')),
            'data_fim' => date('Y-m-d\TH:i', strtotime('+20 days +2 hours')),
            'unidade_id' => (string)$clienteId,
        ];
        $controller->storeAgenda();
        break;
    default:
        fwrite(STDERR, "acao invalida\n");
        exit(2);
}
