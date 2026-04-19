<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Services\AgendaEventService;

class AgendaController extends BaseController
{
    private AgendaEventService $service;

    public function __construct()
    {
        $this->service = new AgendaEventService();
    }

    public function index(): void
    {
        $this->requireLogin();
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $type = AgendaEventService::normalizeTypeFilter($_GET['type'] ?? 'all');
        $calendar = AgendaEventService::buildMonthContext($year, $month);
        $events = $this->service->eventsForRange($calendar['start'], $calendar['end'], $type);

        $this->render('agenda/index', [
            'calendar' => $calendar,
            'events' => $events,
            'eventType' => $type,
        ]);
    }

    public function apiEvents(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $start = (string)($_GET['start'] ?? '');
        $end = (string)($_GET['end'] ?? '');
        $type = AgendaEventService::normalizeTypeFilter($_GET['type'] ?? 'all');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            http_response_code(400);
            echo json_encode(['error' => 'Intervalo inválido.']);
            return;
        }
        $events = $this->service->eventsForRange($start, $end, $type);
        echo json_encode([
            'items' => $events,
            'grouped' => AgendaEventService::groupByDate($events),
        ], JSON_UNESCAPED_UNICODE);
    }
}
