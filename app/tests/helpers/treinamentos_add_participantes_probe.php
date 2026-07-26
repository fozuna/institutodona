<?php
require_once __DIR__ . '/../../autoload.php';

use App\Controllers\TreinamentosController;
use App\Core\Security;

session_start();

$agendaId = (int)($argv[1] ?? 0);
$colaboradorIds = array_slice($argv, 2);

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$csrf = Security::csrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['route'] = 'treinamentos/add_participantes';
$_POST = [
    'csrf' => $csrf,
    'agenda_id' => (string)$agendaId,
    'colaborador_ids' => $colaboradorIds,
];

(new TreinamentosController())->addParticipantes();
