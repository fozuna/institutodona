<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\PdfSupport;

class LogsController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $q = trim((string)($_GET['q'] ?? ''));
        $limit = max(50, min(5000, (int)($_GET['limit'] ?? 500)));
        $file = __DIR__ . '/../../storage/logs/audit.log';
        $lines = [];
        if (is_file($file)) {
            $raw = $this->tailLines($file, $limit);
            $lines = array_reverse($raw);
            if (!Auth::isInstituto()) {
                $allowed = Auth::allowedClientIds();
                $lines = array_values(array_filter($lines, function (string $line) use ($allowed) {
                    $decoded = json_decode($line, true);
                    if (!is_array($decoded)) {
                        return false;
                    }
                    $payload = is_array($decoded['payload'] ?? null) ? $decoded['payload'] : [];
                    $get = is_array($decoded['get'] ?? null) ? $decoded['get'] : [];
                    $post = is_array($decoded['post'] ?? null) ? $decoded['post'] : [];
                    $clienteId = 0;
                    foreach ([
                        $payload['cliente_id'] ?? null,
                        $payload['empresa_id'] ?? null,
                        $get['cliente_id'] ?? null,
                        $get['cliente'] ?? null,
                        $post['cliente_id'] ?? null,
                        $post['empresa_id'] ?? null,
                    ] as $candidate) {
                        $cid = (int)($candidate ?? 0);
                        if ($cid > 0) { $clienteId = $cid; break; }
                    }
                    if ($clienteId <= 0) {
                        return false;
                    }
                    return in_array($clienteId, $allowed, true);
                }));
            }
            if ($q !== '') {
                $needle = mb_strtolower($q);
                $lines = array_values(array_filter($lines, static function (string $line) use ($needle): bool {
                    return mb_strpos(mb_strtolower($line), $needle) !== false;
                }));
            }
        }
        $this->render('logs/index', ['lines' => $lines, 'q' => $q, 'limit' => $limit]);
    }

    private function tailLines(string $file, int $maxLines): array
    {
        $fp = @fopen($file, 'rb');
        if (!$fp) {
            return [];
        }
        $pos = -1;
        $lines = [];
        $buffer = '';
        $chunkSize = 8192;
        fseek($fp, 0, SEEK_END);
        $size = ftell($fp);
        while ($size > 0 && count($lines) <= ($maxLines + 5)) {
            $read = min($chunkSize, $size);
            $size -= $read;
            fseek($fp, $size);
            $chunk = fread($fp, $read);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $buffer = $chunk . $buffer;
            while (($p = strrpos($buffer, "\n")) !== false) {
                $line = substr($buffer, $p + 1);
                $buffer = substr($buffer, 0, $p);
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = $line;
                    if (count($lines) >= $maxLines) {
                        fclose($fp);
                        return array_reverse($lines);
                    }
                }
            }
        }
        $tail = trim($buffer);
        if ($tail !== '') {
            $lines[] = $tail;
        }
        fclose($fp);
        $lines = array_slice($lines, 0, $maxLines);
        return array_reverse($lines);
    }

    public function iconHealth(): void
    {
        $this->requireLogin();
        $type = trim((string)($_GET['type'] ?? ''));
        $msg = trim((string)($_GET['msg'] ?? ''));
        if ($type === '') {
            http_response_code(204);
            return;
        }
        AuditLogger::log('ui_icon_health', 'ui', null, [
            'type' => substr($type, 0, 80),
            'msg' => substr($msg, 0, 240),
            'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180),
        ]);
        http_response_code(204);
    }

    public function pdfHealth(): void
    {
        $this->requireRole('instituto');
        header('Content-Type: application/json; charset=utf-8');
        $diag = PdfSupport::dompdfDiagnostics();
        AuditLogger::log('pdf_health_check', 'pdf', null, ['diagnostics' => $diag]);
        echo json_encode([
            'ok' => true,
            'diagnostics' => $diag,
        ], JSON_UNESCAPED_UNICODE);
    }
}
