<?php
namespace App\Helpers;

function format_tiyin(int $amount, string $currency = 'UZS'): string
{
    return number_format($amount / 100, 2) . ' ' . $currency;
}
