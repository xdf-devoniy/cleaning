<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Resources\Loyalty\ReferralResource;
use App\Models\CRM\Client;
use App\Models\Loyalty\LoyaltyAccount;
use App\Models\Loyalty\Referral;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function generate(Client $client)
    {
        $account = LoyaltyAccount::firstOrCreate(['client_id' => $client->id], [
            'points_balance' => 0,
            'lifetime_points' => 0,
            'tier' => 'base',
        ]);

        $referral = $account->referrals()->create([
            'code' => Str::upper(Str::random(8)),
            'bonus_points' => 0,
        ]);

        return ReferralResource::make($referral);
    }
}
