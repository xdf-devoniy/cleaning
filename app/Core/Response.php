<?php
namespace App\Core;

class Response
{
    public function __construct(private string $body, private int $status = 200, private array $headers = [])
    {
    }

    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers);
        return new self($html, $status, $headers);
    }

    public static function json(array $payload, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        return new self(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $status, $headers);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }
        echo $this->body;
    }
}
