<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\AuditLog;
class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::latest()->paginate(50);

        return response()->json($logs);
    }
}
