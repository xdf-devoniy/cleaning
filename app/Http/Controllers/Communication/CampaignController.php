<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Communication\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        return response()->json(Campaign::latest()->paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'segment_query' => 'nullable|string',
            'channels' => 'required|array|min:1',
            'scheduled_at' => 'nullable|date',
            'payload' => 'required|array',
        ]);

        $campaign = Campaign::create($data + ['status' => 'draft']);

        return response()->json($campaign);
    }

    public function show(Campaign $campaign)
    {
        return response()->json($campaign->load('messages'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'segment_query' => 'nullable|string',
            'channels' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
            'payload' => 'nullable|array',
        ]);

        $campaign->update($data);

        return response()->json($campaign);
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function dispatchCampaign(Campaign $campaign)
    {
        $campaign->update(['status' => 'queued']);

        // job dispatch placeholder

        return response()->json($campaign);
    }

    public function telegramWebhook(Request $request)
    {
        // handle Telegram bot updates
        return response()->json(['status' => 'received']);
    }
}
