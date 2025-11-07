<?php
namespace App\Services;

class SchedulerService
{
    public function availability(int $userId, string $start, string $end): bool
    {
        return true;
    }

    public function detectConflicts(array $order): array
    {
        return [];
    }

    public function suggestSlots(array $address, int $duration): array
    {
        return [];
    }
}
