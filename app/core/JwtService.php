<?php
namespace App\Core;

class JwtService
{
    public static function issue(array $user, int $ttlSeconds = 28800): string
    {
        $now = time();
        $payload = [
            'sub' => (int)($user['id'] ?? 0),
            'nome' => (string)($user['nome'] ?? ''),
            'email' => (string)($user['email'] ?? ''),
            'tipo_acesso' => (string)($user['tipo_acesso'] ?? ''),
            'id_cliente' => isset($user['id_cliente']) ? (int)$user['id_cliente'] : null,
            'iat' => $now,
            'exp' => $now + max(300, $ttlSeconds),
        ];
        return self::encode($payload);
    }

    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$h64, $p64, $s64] = $parts;
        $header = json_decode((string)self::base64UrlDecode($h64), true);
        $payload = json_decode((string)self::base64UrlDecode($p64), true);
        if (!is_array($header) || !is_array($payload)) {
            return null;
        }
        if (($header['alg'] ?? '') !== 'HS256') {
            return null;
        }
        $signed = $h64 . '.' . $p64;
        $expected = self::base64UrlEncode(hash_hmac('sha256', $signed, self::secret(), true));
        if (!hash_equals($expected, $s64)) {
            return null;
        }
        $exp = (int)($payload['exp'] ?? 0);
        if ($exp <= 0 || $exp < time()) {
            return null;
        }
        return $payload;
    }

    private static function encode(array $payload): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $h64 = self::base64UrlEncode((string)json_encode($header, JSON_UNESCAPED_UNICODE));
        $p64 = self::base64UrlEncode((string)json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig = self::base64UrlEncode(hash_hmac('sha256', $h64 . '.' . $p64, self::secret(), true));
        return $h64 . '.' . $p64 . '.' . $sig;
    }

    private static function secret(): string
    {
        $envSecret = trim((string)(getenv('JWT_SECRET') ?: ''));
        if ($envSecret !== '') {
            return $envSecret;
        }
        $cfgPath = __DIR__ . '/../../config/config.php';
        $cfg = file_exists($cfgPath) ? (require $cfgPath) : [];
        $configSecret = trim((string)($cfg['app']['jwt_secret'] ?? ''));
        if ($configSecret !== '') {
            return $configSecret;
        }
        $seed = (string)($cfg['db']['host'] ?? 'localhost') . '|' . (string)($cfg['db']['dbname'] ?? 'app');
        return hash('sha256', $seed . '|institutodona-jwt');
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|false
    {
        $pad = strlen($value) % 4;
        if ($pad) {
            $value .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($value, '-_', '+/'));
    }
}
