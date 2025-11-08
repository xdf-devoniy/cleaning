<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use App\Models\Automation\Workflow;
use App\Settings\AutomationSettings;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function index(AutomationSettings $settings)
    {
        return response()->json([
            'workflows' => Workflow::all(),
            'settings' => $settings->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'trigger' => 'required|array',
            'actions' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $workflow = Workflow::create($data);

        return response()->json($workflow);
    }

    public function show(Workflow $automation)
    {
        return response()->json($automation);
    }

    public function update(Request $request, Workflow $automation)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'trigger' => 'nullable|array',
            'actions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $automation->update($data);

        return response()->json($automation);
    }

    public function destroy(Workflow $automation)
    {
        $automation->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function run(Workflow $automation)
    {
        // dispatch automation job
        return response()->json(['status' => 'queued']);
    }

    public function paymentWebhook(Request $request)
    {
        // process payment callback
        return response()->json(['status' => 'ok']);
    }
}
