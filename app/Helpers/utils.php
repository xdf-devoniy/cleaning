<?php
namespace App\Helpers;

function array_pluck(array $items, string $key): array
{
    return array_map(fn($item) => $item[$key] ?? null, $items);
}
