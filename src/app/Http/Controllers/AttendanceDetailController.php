<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceDetailController extends Controller
{
    public function show($id)
    {
        $attendance = Attendance::with(['breakTimes', 'user', 'correctionRequests'])->findOrFail($id);

        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        $pendingCorrectionRequest = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();

        return view('attendance.show', compact('attendance', 'pendingCorrectionRequest'));
    }
}