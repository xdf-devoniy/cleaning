<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Staff;
use App\Models\HR\TimeAttendance;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        return response()->json(
            Staff::with(['attendances' => fn ($q) => $q->latest()->limit(10)])->paginate(25)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'role' => 'required|string',
            'team' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric',
            'commission_rate' => 'nullable|numeric',
            'employment_type' => 'required|string',
            'hired_at' => 'nullable|date',
        ]);

        $staff = Staff::create($data);

        return response()->json($staff);
    }

    public function show(Staff $staff)
    {
        return response()->json($staff->load(['attendances', 'payrollItems', 'trainingRecords', 'performanceReviews']));
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'phone' => 'sometimes|string',
            'email' => 'nullable|email',
            'role' => 'sometimes|string',
            'team' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric',
            'commission_rate' => 'nullable|numeric',
            'employment_type' => 'sometimes|string',
            'hired_at' => 'nullable|date',
            'terminated_at' => 'nullable|date',
        ]);

        $staff->update($data);

        return response()->json($staff);
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function attendance(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'status' => 'required|string|in:present,absent,late,on_leave',
            'notes' => 'nullable|string',
        ]);

        $attendance = TimeAttendance::create([
            'staff_id' => $staff->id,
            'checked_in_at' => now(),
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json($attendance);
    }
}
