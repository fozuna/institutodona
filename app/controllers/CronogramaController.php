<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\CronogramaTrafficLight;
use App\Core\Security;
use App\Core\AuditLogger;
use App\Models\CronogramaModel;
use App\Models\CronogramaEventoModel;
use App\Models\ClienteModel;
use DateTimeImmutable;

class CronogramaController extends BaseController
{
    private const PERIODICIDADES = [
        'unico' => 'Unico (sem repeticao)',
        'mensal' => 'Mensal',
        'bimestral' => 'Bimestral',
        'trimestral' => 'Trimestral',
        'semestral' => 'Semestral',
        'anual' => 'Anual',
    ];

    private CronogramaModel $cronogramas;
    private CronogramaEventoModel $eventos;

    public function __construct()
    {
        $this->cronogramas = new CronogramaModel();
        $this->eventos = new CronogramaEventoModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $cid = (int)($_GET['id_cliente'] ?? 0);
        $items = $cid ? $this->cronogramas->byCliente($cid) : $this->cronogramas->all();
        $this->render('cronograma/index', ['items' => $items]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $pref = (int)($_GET['id_cliente'] ?? 0);
        $this->render('cronograma/create', ['clientes' => $clientes, 'pref' => $pref]);
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'ano' => (int)($_POST['ano'] ?? date('Y')),
        ];
        if ($data['id_cliente'] && $data['ano']) {
            $id = $this->cronogramas->create($data);
            header('Location: index.php?route=cronograma/show&id=' . $id);
            return;
        }
        header('Location: index.php?route=cronograma/index');
    }

    public function selectCliente(): void
    {
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $this->render('cronograma/select_cliente', ['clientes' => $clientes]);
    }

    public function show(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_GET['status_filter'] ?? 'todos');
        $allEvents = $this->eventos->byCronograma($id);
        $events = $this->annotateEvents($allEvents);
        $grid = $this->buildGrid($events);
        $events = $this->filterEventsByTraffic($events, $statusFilter);
        $grid = $this->filterGridByTraffic($grid, $statusFilter);

        AuditLogger::log('cronograma_show', 'cronograma', $id, [
            'cronograma_found' => (bool)$crono,
            'events_count' => count($allEvents),
            'status_filter' => $statusFilter,
        ]);

        $this->render('cronograma/show', [
            'crono' => $crono,
            'events' => $events,
            'grid' => $grid,
            'periodicidades' => self::PERIODICIDADES,
            'statusFilter' => $statusFilter,
            'flashSuccess' => $this->takeFlash('flash_success'),
            'flashError' => $this->takeFlash('flash_error'),
        ]);
    }

    public function addEvento(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $data = [
            'data' => $_POST['data'] ?? null,
            'topico' => trim($_POST['topico'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'atividade' => trim($_POST['atividade'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'modelo' => $_POST['modelo'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
            'periodicidade' => $_POST['periodicidade'] ?? 'unico',
        ];
        $isValid = $idCronograma && $data['data'] && $data['topico'] && $data['atividade'];
        AuditLogger::log('cronograma_add_evento_attempt', 'cronograma_evento', null, [
            'id_cronograma' => $idCronograma,
            'is_valid' => (bool)$isValid,
        ]);
        if ($isValid) {
            try {
                $newId = $this->eventos->create($idCronograma, $data);
                $_SESSION['flash_success'] = 'Evento salvo com recorrencia processada com sucesso.';
                AuditLogger::log('cronograma_add_evento_success', 'cronograma_evento', $newId, [
                    'id_cronograma' => $idCronograma,
                    'periodicidade' => $data['periodicidade'],
                ]);
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                AuditLogger::log('cronograma_add_evento_error', 'cronograma_evento', null, [
                    'id_cronograma' => $idCronograma,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $_SESSION['flash_error'] = 'Preencha os campos obrigatorios do evento.';
            AuditLogger::log('cronograma_add_evento_invalid', 'cronograma_evento', null, [
                'id_cronograma' => $idCronograma,
            ]);
        }
        header('Location: index.php?route=cronograma/show&id=' . $idCronograma . '&status_filter=' . urlencode($statusFilter));
    }

    public function toggleStatus(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $id = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $realizado = (int)($_POST['realizado'] ?? 0) === 1;
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $targetStatus = $realizado ? 'Realizado' : 'Planejado';

        $ok = $id > 0 ? $this->eventos->setStatus($id, $targetStatus) : false;
        if (!$ok) {
            http_response_code(400);
            $this->respondToggleStatus(['ok' => false, 'message' => 'Nao foi possivel atualizar o status do evento.'], $idCronograma, $statusFilter);
            return;
        }

        $event = $this->annotateEvent($this->eventos->find($id));
        if (!$event) {
            http_response_code(404);
            $this->respondToggleStatus(['ok' => false, 'message' => 'Evento nao encontrado apos a atualizacao.'], $idCronograma, $statusFilter);
            return;
        }
        $series = $this->annotateEvents($this->eventos->seriesMembers((int)$event['serie_id']));
        $grid = $this->buildGrid($series);
        $row = $grid[(int)$event['serie_id']] ?? null;

        $payload = [
            'ok' => true,
            'occurrence' => [
                'id' => (int)$event['id'],
                'status' => (string)$event['status'],
                'traffic' => $event['traffic'],
            ],
            'series' => [
                'serie_id' => (int)$event['serie_id'],
                'traffic' => $row['traffic'] ?? CronogramaTrafficLight::series([]),
                'months' => $row['meses'] ?? [],
            ],
        ];
        $this->respondToggleStatus($payload, $idCronograma, $statusFilter);
    }

    public function addEventoForm(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $this->render('cronograma/add_evento', [
            'crono' => $crono,
            'periodicidades' => self::PERIODICIDADES,
        ]);
    }

    public function updateEvento(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $data = [
            'data' => $_POST['data'] ?? null,
            'topico' => trim($_POST['topico'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'atividade' => trim($_POST['atividade'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'modelo' => $_POST['modelo'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
            'periodicidade' => $_POST['periodicidade'] ?? 'unico',
        ];
        $scope = ($_POST['escopo'] ?? 'evento') === 'serie' ? 'serie' : 'evento';
        if ($id) {
            try {
                $this->eventos->update($id, $data, $scope);
                $_SESSION['flash_success'] = $scope === 'serie'
                    ? 'Serie atualizada com sucesso.'
                    : 'Ocorrencia atualizada com sucesso.';
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        header('Location: index.php?route=cronograma/show&id=' . $idCronograma . '&status_filter=' . urlencode($statusFilter));
    }

    public function deleteEvento(): void
    {
        $this->requireRole('instituto');
        $src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $src['csrf'] ?? null;
            if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        }
        $id = (int)($src['id'] ?? 0);
        $idCronograma = (int)($src['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($src['status_filter'] ?? 'todos');
        $scope = (($src['escopo'] ?? 'evento') === 'serie') ? 'serie' : 'evento';
        if ($id) {
            try {
                $this->eventos->delete($id, $scope);
                $_SESSION['flash_success'] = $scope === 'serie'
                    ? 'Serie excluida com sucesso.'
                    : 'Ocorrencia excluida com sucesso.';
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        header('Location: index.php?route=cronograma/show&id=' . $idCronograma . '&status_filter=' . urlencode($statusFilter));
    }

    private function buildGrid(array $events): array
    {
        $grid = [];
        foreach ($events as $ev) {
            $serieId = (int)($ev['serie_id'] ?? $ev['id']);
            if (!isset($grid[$serieId])) {
                $grid[$serieId] = [
                    'serie_id' => $serieId,
                    'topico' => $ev['topico'],
                    'unidade' => $ev['unidade'] ?? '',
                    'atividade' => $ev['atividade'],
                    'responsavel' => $ev['responsavel'] ?? '',
                    'periodicidade' => $ev['periodicidade'] ?? 'unico',
                    'meses' => array_fill(1, 12, ['marked' => false, 'count' => 0, 'events' => []]),
                ];
            }
            $month = (int)date('n', strtotime((string)$ev['data']));
            $grid[$serieId]['meses'][$month]['marked'] = true;
            $grid[$serieId]['meses'][$month]['count']++;
            $grid[$serieId]['meses'][$month]['events'][] = $ev;
        }
        foreach ($grid as &$row) {
            $row['traffic'] = CronogramaTrafficLight::series($row['meses'], new DateTimeImmutable('today'));
            $row['status'] = $row['traffic']['label'];
            for ($month = 1; $month <= 12; $month++) {
                $row['meses'][$month]['traffic'] = CronogramaTrafficLight::monthCell($row['meses'][$month]['events'] ?? [], new DateTimeImmutable('today'));
            }
        }
        unset($row);
        return $grid;
    }

    private function annotateEvents(array $events): array
    {
        $annotated = [];
        foreach ($events as $event) {
            $annotatedEvent = $this->annotateEvent($event);
            if ($annotatedEvent !== null) {
                $annotated[] = $annotatedEvent;
            }
        }
        return $annotated;
    }

    private function annotateEvent(?array $event): ?array
    {
        if (!$event) {
            return null;
        }
        $event['traffic'] = CronogramaTrafficLight::occurrence($event, new DateTimeImmutable('today'));
        return $event;
    }

    private function filterEventsByTraffic(array $events, string $statusFilter): array
    {
        if ($statusFilter === 'todos') {
            return $events;
        }
        return array_values(array_filter($events, static function (array $event) use ($statusFilter): bool {
            return (string)($event['traffic']['filter_key'] ?? 'pendente') === $statusFilter;
        }));
    }

    private function filterGridByTraffic(array $grid, string $statusFilter): array
    {
        if ($statusFilter === 'todos') {
            return $grid;
        }
        return array_values(array_filter($grid, static function (array $row) use ($statusFilter): bool {
            return (string)($row['traffic']['filter_key'] ?? 'pendente') === $statusFilter;
        }));
    }

    private function respondToggleStatus(array $payload, int $idCronograma, string $statusFilter): void
    {
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            return;
        }
        $_SESSION[$payload['ok'] ? 'flash_success' : 'flash_error'] = $payload['message'] ?? ($payload['ok'] ? 'Status atualizado.' : 'Falha ao atualizar status.');
        header('Location: index.php?route=cronograma/show&id=' . $idCronograma . '&status_filter=' . urlencode($statusFilter));
    }

    private function takeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return is_string($value) && $value !== '' ? $value : null;
    }
}
