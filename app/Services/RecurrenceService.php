<?php
namespace App\Services;

class RecurrenceService
{
    public function parse(string $rrule): array
    {
        return ['rrule' => $rrule];
    }
}
