<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Enums\JobStatus;
use App\Models\CRM\Client;
use App\Models\Finance\Invoice;
use App\Models\Scheduling\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientPortalController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
        ]);

        $client = Client::where('phone', $data['phone'])->firstOrFail();

        $token = Str::random(40);

        // store token or dispatch OTP (implementation placeholder)

        return response()->json([
            'token' => $token,
            'client_id' => $client->id,
        ]);
    }

    public function dashboard(Request $request)
    {
        $client = $request->user();
        if (! $client instanceof Client) {
            $client = Client::findOrFail($request->integer('client_id'));
        }

        return response()->json([
            'client' => $client,
            'jobs' => Job::where('client_id', $client->id)->latest('scheduled_at')->limit(10)->get(),
            'invoices' => Invoice::where('client_id', $client->id)->latest('issued_at')->limit(10)->get(),
            'loyalty' => optional($client->loyaltyAccount)->only(['points_balance', 'lifetime_points', 'tier']),
        ]);
    }

    public function reschedule(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer|exists:jobs,id',
            'scheduled_at' => 'required|date',
        ]);

        $clientId = $request->user() instanceof Client
            ? $request->user()->id
            : $request->integer('client_id');

        $job = Job::where('client_id', $clientId)->findOrFail($data['job_id']);
        $job->update([
            'scheduled_at' => $data['scheduled_at'],
            'status' => JobStatus::Scheduled,
        ]);

        return response()->json($job);
    }

    public function cancel(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|integer|exists:jobs,id',
        ]);

        $clientId = $request->user() instanceof Client
            ? $request->user()->id
            : $request->integer('client_id');

        $job = Job::where('client_id', $clientId)->findOrFail($data['job_id']);
        $job->update(['status' => JobStatus::Cancelled]);

        return response()->json($job);
    }
}
