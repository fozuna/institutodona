<?php
namespace App\Core;

class AuditLogger
{
    private static function sanitize(array $data): array
    {
        $redact = [
            'senha', 'senha_hash', 'password', 'pass', 'csrf',
            'email', 'public_email', 'whatsapp', 'public_whatsapp',
            'nome', 'public_nome', 'empresa', 'public_empresa',
            'contato', 'telefone', 'celular',
        ];
        foreach ($data as $k => $v) {
            if (in_array($k, $redact, true)) {
                $data[$k] = '[redacted]';
            }
        }
        return $data;
    }

    public static function log(string $action, string $entity, ?int $recordId = null, array $payload = []): void
    {
        try {
            $user = $_SESSION['user'] ?? ['id' => null, 'email' => null, 'nome' => null, 'tipo_acesso' => null];
            $route = $_GET['route'] ?? null;
            $line = [
                'ts' => date('c'),
                'user_id' => isset($user['id']) ? (int)$user['id'] : null,
                'user_email' => $user['email'] ?? null,
                'user_nome' => $user['nome'] ?? null,
                'user_tipo' => $user['tipo_acesso'] ?? null,
                'user_role_label' => AccessControl::roleLabel((string)($user['tipo_acesso'] ?? '')),
                'user_cliente_id' => isset($user['id_cliente']) ? (int)$user['id_cliente'] : null,
                'allowed_client_ids' => array_values(array_map('intval', (array)($user['allowed_client_ids'] ?? []))),
                'route' => $route,
                'method' => strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'action' => $action,
                'entity' => $entity,
                'record_id' => $recordId,
                'result' => $payload['resultado'] ?? null,
                'post' => self::sanitize($_POST ?? []),
                'get' => self::sanitize($_GET ?? []),
                'payload' => self::sanitize($payload),
            ];
            $dir = __DIR__ . '/../../storage/logs';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $file = $dir . '/audit.log';
            @file_put_contents($file, json_encode($line, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
    }
}
