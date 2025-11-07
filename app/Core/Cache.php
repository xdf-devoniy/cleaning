<?php
namespace App\Core;

class Cache
{
    private string $storePath;

    public function __construct(?string $storePath = null)
    {
        $this->storePath = $storePath ?? __DIR__ . '/../../storage/cache';
        if (!is_dir($this->storePath)) {
            mkdir($this->storePath, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);
        if (!file_exists($path)) {
            return $default;
        }
        $payload = file_get_contents($path);
        if (!$payload) {
            return $default;
        }
        $data = json_decode($payload, true);
        if (($data['expires_at'] ?? 0) && $data['expires_at'] < time()) {
            unlink($path);
            return $default;
        }
        return $data['value'] ?? $default;
    }

    public function put(string $key, mixed $value, int $ttlSeconds = 0): void
    {
        $payload = json_encode([
            'value' => $value,
            'expires_at' => $ttlSeconds ? time() + $ttlSeconds : null,
        ], JSON_THROW_ON_ERROR);
        file_put_contents($this->path($key), $payload);
    }

    private function path(string $key): string
    {
        return $this->storePath . '/' . md5($key) . '.cache';
    }
}
