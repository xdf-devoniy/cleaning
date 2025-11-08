<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Resources\Loyalty\LoyaltyAccountResource;
use App\Models\Loyalty\LoyaltyAccount;
use App\Models\Loyalty\LoyaltyTransaction;
use App\Settings\LoyaltySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PointController extends Controller
{
    public function index(Request $request)
    {
        $accounts = LoyaltyAccount::with(['client', 'transactions' => fn ($q) => $q->latest()->limit(20)])
            ->paginate(25);

        return LoyaltyAccountResource::collection($accounts);
    }

    public function store(Request $request, LoyaltySettings $settings)
    {
        $data = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'points' => 'required|numeric',
            'type' => 'required|string|in:earn,redeem,adjust',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'expires_at' => 'nullable|date',
        ]);

        $account = LoyaltyAccount::firstOrCreate(['client_id' => $data['client_id']], [
            'points_balance' => 0,
            'lifetime_points' => 0,
            'tier' => 'base',
        ]);

        DB::transaction(function () use (&$account, $data, $settings) {
            $points = (float) $data['points'];

            if ($data['type'] === 'redeem') {
                $points = -abs($points);
            }

            $account->points_balance += $points;
            if ($points > 0) {
                $account->lifetime_points += $points;
            }
            $account->tier = $this->resolveTier($account->lifetime_points, $settings);
            $account->save();

            LoyaltyTransaction::create([
                'account_id' => $account->id,
                'type' => $data['type'],
                'points' => $points,
                'reference_type' => $data['reference_type'],
                'reference_id' => $data['reference_id'],
                'expires_at' => $data['expires_at'],
                'metadata' => [
                    'adjusted_by' => auth()->id(),
                ],
            ]);
        });

        return LoyaltyAccountResource::make($account->fresh(['transactions', 'referrals']));
    }

    public function redeem(Request $request, LoyaltyAccount $account)
    {
        $data = $request->validate([
            'points' => 'required|numeric|min:1',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);

        abort_if($account->points_balance < $data['points'], 422, 'Insufficient points');

        return $this->store(new Request($data + [
            'client_id' => $account->client_id,
            'type' => 'redeem',
        ]), app(LoyaltySettings::class));
    }

    public function adjust(Request $request, LoyaltyAccount $account)
    {
        $data = $request->validate([
            'points' => 'required|numeric',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);

        return $this->store(new Request($data + [
            'client_id' => $account->client_id,
            'type' => 'adjust',
        ]), app(LoyaltySettings::class));
    }

    private function resolveTier(float $points, LoyaltySettings $settings): string
    {
        if ($points >= $settings->tier_upgrade_threshold_platinum) {
            return 'platinum';
        }

        if ($points >= $settings->tier_upgrade_threshold_gold) {
            return 'gold';
        }

        if ($points >= $settings->tier_upgrade_threshold_silver) {
            return 'silver';
        }

        return 'base';
    }
}
