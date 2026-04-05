<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;

class AdminCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $currentStatus = $request->query('status', 'pending');

        if (!in_array($currentStatus, ['pending', 'approved'], true)) {
            $currentStatus = 'pending';
        }

        $correctionRequests = AttendanceCorrectionRequest::query()
            ->with(['user', 'attendance'])
            ->where('status', $currentStatus)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.attendance.requests', compact('correctionRequests', 'currentStatus'));
    }

    public function show(AttendanceCorrectionRequest $attendanceCorrectionRequest)
    {
        $attendanceCorrectionRequest->load(['user', 'attendance', 'breakTimes']);

        return view('admin.attendance.request', compact('attendanceCorrectionRequest'));
    }
}
