<?php

namespace App\Http\Controllers\Scheduling;

use App\Http\Controllers\Controller;
use App\Models\Scheduling\Job;
use App\Models\Scheduling\JobChecklist;
use Illuminate\Http\Request;

class JobChecklistController extends Controller
{
    public function store(Request $request, Job $job)
    {
        $data = $request->validate([
            'score' => 'nullable|numeric',
            'completed_at' => 'nullable|date',
            'feedback' => 'nullable|string',
            'items' => 'array',
            'items.*.label' => 'required|string',
            'items.*.is_completed' => 'boolean',
            'items.*.score' => 'nullable|numeric',
            'items.*.notes' => 'nullable|string',
        ]);

        $checklist = $job->checklist()->create(collect($data)->except('items')->toArray());

        foreach ($data['items'] ?? [] as $item) {
            $checklist->items()->create($item);
        }

        return response()->json($checklist->load('items'));
    }

    public function update(Request $request, JobChecklist $checklist)
    {
        $data = $request->validate([
            'score' => 'nullable|numeric',
            'completed_at' => 'nullable|date',
            'feedback' => 'nullable|string',
        ]);

        $checklist->update($data);

        return response()->json($checklist->fresh('items'));
    }
}
