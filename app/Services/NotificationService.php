<?php
namespace App\Services;

use App\Core\Queue;

class NotificationService
{
    public function queue(string $template, array $payload, string $channel = 'telegram'): void
    {
        $queue = new Queue();
        $queue->push('notifications', [
            'template' => $template,
            'payload' => $payload,
            'channel' => $channel,
        ]);
    }
}
