<?php
namespace App\Services;

use App\Models\Invoice;

class InvoiceService
{
    public function generateNumber(): string
    {
        $prefix = 'CLN';
        $sequence = str_pad((string)(count(Invoice::all()) + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . date('Ym') . $sequence;
    }
}
