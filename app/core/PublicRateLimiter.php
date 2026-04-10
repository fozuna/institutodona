<?php
namespace App\Core;

class PublicRateLimiter
{
    public function allow(string $key, int $limit, int $windowSeconds): bool
    {
        $dir = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'institutodona-rate-limit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . sha1($key) . '.json';
        $now = time();
        $payload = [
            'start' => $now,
            'count' => 0,
        ];
        if (is_file($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if (is_array($decoded)) {
                $payload['start'] = isset($decoded['start']) ? (int)$decoded['start'] : $now;
                $payload['count'] = isset($decoded['count']) ? (int)$decoded['count'] : 0;
            }
        }
        if (($now - (int)$payload['start']) >= $windowSeconds) {
            $payload['start'] = $now;
            $payload['count'] = 0;
        }
        $payload['count']++;
        @file_put_contents($file, json_encode($payload));
        return $payload['count'] <= $limit;
    }
}
