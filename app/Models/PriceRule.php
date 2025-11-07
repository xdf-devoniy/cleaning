<?php
namespace App\Models;

use App\Core\DB;
use PDO;

class PriceRule extends Model
{
    protected static string $table = 'price_rules';

    public static function matching(int $serviceId, int $qty, string $scheduledAt): array
    {
        $day = strtoupper(date('D', strtotime($scheduledAt)));
        $time = date('H:i', strtotime($scheduledAt));
        $stmt = DB::conn()->prepare('SELECT * FROM price_rules WHERE (service_id IS NULL OR service_id = :service) AND (min_qty IS NULL OR min_qty <= :qty)');
        $stmt->execute([':service' => $serviceId, ':qty' => $qty]);
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $matched = [];
        foreach ($rules as $rule) {
            if (!empty($rule['day_of_week'])) {
                $days = array_map('trim', explode(',', strtoupper($rule['day_of_week'])));
                if (!in_array($day, $days, true)) {
                    continue;
                }
            }
            if (!empty($rule['time_range'])) {
                $parts = array_map('trim', explode('-', $rule['time_range']));
                if (count($parts) === 2) {
                    [$start, $end] = $parts;
                    if ($time < $start || $time > $end) {
                        continue;
                    }
                }
            }
            $matched[] = $rule;
        }
        return $matched;
    }
}
