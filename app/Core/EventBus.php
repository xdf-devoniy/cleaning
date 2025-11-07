<?php
namespace App\Core;

class EventBus
{
    private static ?self $instance = null;
    private array $listeners = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }
}
