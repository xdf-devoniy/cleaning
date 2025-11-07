<?php
namespace App\Services;

class PricingService
{
    public function price(array $orderDraft): array
    {
        $subtotal = 0;
        foreach ($orderDraft['items'] ?? [] as $item) {
            $subtotal += ($item['qty'] ?? 1) * ($item['unit_price'] ?? 0);
        }
        return [
            'subtotal' => $subtotal,
            'discount' => 0,
            'surcharge' => 0,
            'tax' => 0,
            'total' => $subtotal,
        ];
    }
}
