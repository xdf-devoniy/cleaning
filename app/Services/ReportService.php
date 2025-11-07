<?php
namespace App\Services;

class ReportService
{
    public function revenueSummary(string $from, string $to): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'total' => 0,
        ];
    }
}
