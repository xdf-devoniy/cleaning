<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Scheduling\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::with(['client', 'service', 'address'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('crew_id'), fn ($q) => $q->where('crew_id', $request->integer('crew_id')))
            ->orderBy('scheduled_at')
            ->paginate(25);

        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'address_id' => 'required|integer|exists:client_addresses,id',
            'service_id' => 'required|integer|exists:services,id',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'required|integer|min:15',
            'crew_id' => 'nullable|integer|exists:staff,id',
            'price' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'recurrence_rule' => 'nullable|string',
            'recurrence_ends_at' => 'nullable|date',
        ]);

        $job = Job::create($data + ['status' => JobStatus::Scheduled]);

        return response()->json($job->load(['client', 'service', 'address']));
    }

    public function show(Job $job)
    {
        return response()->json($job->load(['client', 'service', 'address', 'checklist.items', 'photos']));
    }

    public function update(Request $request, Job $job)
    {
        $data = $request->validate([
            'scheduled_at' => 'sometimes|date',
            'duration_minutes' => 'sometimes|integer|min:15',
            'crew_id' => 'nullable|integer|exists:staff,id',
            'price' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $job->update($data);

        return response()->json($job->fresh(['client', 'service', 'address']));
    }

    public function destroy(Job $job)
    {
        $job->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function reassign(Request $request, Job $job)
    {
        $data = $request->validate([
            'crew_id' => 'required|integer|exists:staff,id',
        ]);

        $job->update(['crew_id' => $data['crew_id']]);

        return response()->json($job->fresh('crew'));
    }

    public function updateStatus(Request $request, Job $job)
    {
        $data = $request->validate([
            'status' => 'required|string|in:scheduled,in_progress,completed,cancelled,no_show',
            'coordinates' => 'nullable|array',
            'coordinates.lat' => 'numeric',
            'coordinates.lng' => 'numeric',
        ]);

        $newStatus = JobStatus::from($data['status']);

        $job->update(['status' => $newStatus]);

        if ($newStatus === JobStatus::InProgress && $data['coordinates']) {
            $job->update(['start_coordinates' => $data['coordinates']]);
        }

        if (in_array($newStatus, [JobStatus::Completed, JobStatus::Cancelled, JobStatus::NoShow], true)) {
            $job->update(['end_coordinates' => $data['coordinates'] ?? null]);
        }

        return response()->json($job);
    }
}
