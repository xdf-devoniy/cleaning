<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\PayrollRun;
use App\Models\HR\Staff;
use App\Settings\PayrollSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function preview(Request $request, PayrollSettings $settings)
    {
        $periodStart = $request->date('start', now()->startOfWeek());
        $periodEnd = $request->date('end', now()->endOfWeek());

        $staff = Staff::with('attendances')->get()->map(function ($member) use ($settings) {
            $hours = $member->attendances->count() * ($settings->default_pay_cycle === 'weekly' ? 8 : 4);
            $base = $hours * ($member->hourly_rate ?? 0);

            return [
                'staff_id' => $member->id,
                'name' => $member->first_name.' '.$member->last_name,
                'hours' => $hours,
                'base_pay' => $base,
                'overtime_pay' => 0,
                'bonus' => 0,
                'deductions' => 0,
                'net_pay' => $base,
            ];
        });

        return response()->json([
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'items' => $staff,
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.staff_id' => 'required|integer|exists:staff,id',
            'items.*.base_pay' => 'required|numeric',
            'items.*.overtime_pay' => 'nullable|numeric',
            'items.*.bonus' => 'nullable|numeric',
            'items.*.deductions' => 'nullable|numeric',
        ]);

        $run = DB::transaction(function () use ($data) {
            $run = PayrollRun::create([
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'status' => 'processed',
                'processed_by' => auth()->id(),
                'gross_total' => 0,
                'net_total' => 0,
            ]);

            $gross = 0;
            $net = 0;

            foreach ($data['items'] as $item) {
                $netPay = ($item['base_pay'] + ($item['overtime_pay'] ?? 0) + ($item['bonus'] ?? 0)) - ($item['deductions'] ?? 0);
                $run->items()->create($item + [
                    'net_pay' => $netPay,
                ]);

                $gross += $item['base_pay'] + ($item['overtime_pay'] ?? 0);
                $net += $netPay;
            }

            $run->update([
                'gross_total' => $gross,
                'net_total' => $net,
            ]);

            return $run->load('items');
        });

        return response()->json($run);
    }
}
