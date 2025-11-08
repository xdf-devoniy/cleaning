<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CRM\Client;
use App\Models\Finance\Invoice;
use App\Models\HR\Staff;
use App\Models\Inventory\InventoryItem;
use App\Models\Scheduling\Job;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function show(string $type, Request $request)
    {
        return response()->json($this->buildReport($type, $request));
    }

    public function export(string $type, Request $request)
    {
        $data = $this->buildReport($type, $request);

        $path = 'exports/'.$type.'-'.now()->timestamp.'.json';
        Storage::disk('local')->put($path, json_encode($data));

        return response()->json(['path' => $path]);
    }

    private function buildReport(string $type, Request $request): array
    {
        $start = $request->date('start', now()->subMonth());
        $end = $request->date('end', now());

        return match ($type) {
            'revenue' => $this->revenueReport($start, $end),
            'utilization' => $this->utilizationReport($start, $end),
            'loyalty' => $this->loyaltyReport($start, $end),
            'inventory' => $this->inventoryReport($start, $end),
            default => ['error' => 'Unknown report'],
        };
    }

    private function revenueReport($start, $end): array
    {
        $period = CarbonPeriod::create($start, '1 week', $end);
        $series = [];

        foreach ($period as $date) {
            $next = $date->copy()->addWeek();
            $series[] = [
                'period' => $date->format('Y-m-d'),
                'total' => Invoice::whereBetween('issued_at', [$date, $next])->sum('total'),
                'paid' => Invoice::whereBetween('issued_at', [$date, $next])->sum('total') - Invoice::whereBetween('issued_at', [$date, $next])->sum('balance_due'),
            ];
        }

        return [
            'series' => $series,
            'total' => Invoice::whereBetween('issued_at', [$start, $end])->sum('total'),
            'outstanding' => Invoice::whereBetween('issued_at', [$start, $end])->sum('balance_due'),
        ];
    }

    private function utilizationReport($start, $end): array
    {
        $jobs = Job::whereBetween('scheduled_at', [$start, $end])->get();
        $perCleaner = $jobs->groupBy('crew_id')->map(fn ($group) => [
            'jobs' => $group->count(),
            'hours' => $group->sum('duration_minutes') / 60,
        ]);

        return [
            'total_jobs' => $jobs->count(),
            'total_hours' => $jobs->sum('duration_minutes') / 60,
            'per_cleaner' => $perCleaner,
        ];
    }

    private function loyaltyReport($start, $end): array
    {
        $clients = Client::with('loyaltyAccount')->get();
        $tiers = $clients->groupBy(fn ($client) => optional($client->loyaltyAccount)->tier ?? 'base')
            ->map->count();

        return [
            'tier_distribution' => $tiers->toArray(),
            'ltv_average' => round((float) $clients->avg(fn ($client) => $client->lifetime_value), 2),
        ];
    }

    private function inventoryReport($start, $end): array
    {
        $items = InventoryItem::with('usages')->get();

        return [
            'low_stock' => $items
                ->filter(fn ($item) => $item->on_hand <= $item->min_stock_level)
                ->map(fn ($item) => $item->only(['id', 'name', 'sku', 'on_hand', 'min_stock_level']))
                ->values(),
            'usage' => $items->map(fn ($item) => [
                'item' => $item->name,
                'quantity_used' => $item->usages->whereBetween('used_at', [$start, $end])->sum('quantity'),
            ])->values(),
        ];
    }
}
