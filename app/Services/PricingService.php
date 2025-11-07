<?php
namespace App\Services;

use App\Models\{PriceRule, Service};

class PricingService
{
    public function price(array $orderDraft): array
    {
        $subtotal = 0;
        $discount = 0;
        $surcharge = 0;
        $items = [];
        $scheduledAt = $orderDraft['scheduled_at'] ?? date('c');

        foreach ($orderDraft['items'] ?? [] as $item) {
            $service = Service::findActive((int)$item['service_id']);
            if (!$service) {
                continue;
            }
            $qty = max(1, (int)($item['qty'] ?? 1));
            $unitPrice = (int)($item['unit_price'] ?? $service['base_price']);
            $lineTotal = $qty * $unitPrice;
            $subtotal += $lineTotal;

            $rules = PriceRule::matching((int)$service['id'], $qty, $scheduledAt);
            foreach ($rules as $rule) {
                if (!empty($rule['discount_pct'])) {
                    $discount += (int)round($lineTotal * ((int)$rule['discount_pct']) / 100);
                }
                if (!empty($rule['surcharge_pct'])) {
                    $surcharge += (int)round($lineTotal * ((int)$rule['surcharge_pct']) / 100);
                }
            }

            $items[] = [
                'service_id' => (int)$service['id'],
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        $tax = 0;
        $total = $subtotal - $discount + $surcharge + $tax;

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'surcharge' => $surcharge,
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
