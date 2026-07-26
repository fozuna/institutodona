<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\BaseController;
use App\Models\ClienteModel;
use App\Services\AgendaEventService;

class AgendaController extends BaseController
{
    private AgendaEventService $service;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->service = new AgendaEventService();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $type = AgendaEventService::normalizeTypeFilter($_GET['type'] ?? 'all');
        $clienteId = $this->resolveAgendaClienteFilter();
        $calendar = AgendaEventService::buildMonthContext($year, $month);
        $events = $this->service->eventsForRange($calendar['start'], $calendar['end'], $type, $clienteId);

        $this->render('agenda/index', [
            'calendar' => $calendar,
            'events' => $events,
            'eventType' => $type,
            'clientes' => $this->clientes->all(),
            'clienteId' => $clienteId,
        ]);
    }

    public function apiEvents(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $start = (string)($_GET['start'] ?? '');
        $end = (string)($_GET['end'] ?? '');
        $type = AgendaEventService::normalizeTypeFilter($_GET['type'] ?? 'all');
        $clienteId = $this->resolveAgendaClienteFilter();
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            http_response_code(400);
            echo json_encode(['error' => 'Intervalo inválido.']);
            return;
        }
        $events = $this->service->eventsForRange($start, $end, $type, $clienteId);
        echo json_encode([
            'items' => $events,
            'grouped' => AgendaEventService::groupByDate($events),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * "Todos" (nenhum cliente selecionado) deve continuar significando "todos os
     * clientes permitidos ao usuário", não um único cliente - por isso não reaproveita
     * resolveScopedClienteId() aqui, que colapsa para um cliente só quando nada é
     * informado. Um cliente_id inválido ou fora do escopo do usuário é ignorado
     * silenciosamente (cai em "Todos"), nunca vaza dados de outro tenant.
     */
    private function resolveAgendaClienteFilter(): ?int
    {
        $requested = (int)($_GET['cliente'] ?? 0);
        if ($requested <= 0) {
            return null;
        }
        if (Auth::isInstituto()) {
            return $requested;
        }
        return in_array($requested, Auth::allowedClientIds(), true) ? $requested : null;
    }
}
