<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest as AttendanceCorrectionRequestModel;
use App\Models\AttendanceCorrectionRequestBreakTime;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionRequestController extends Controller
{
    public function store(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        $correctionRequest = AttendanceCorrectionRequestModel::create([
            'user_id' => Auth::id(),
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => $request->input('clock_in_at'),
            'requested_clock_out_at' => $request->input('clock_out_at'),
            'remark' => $request->input('remark'),
            'status' => 'pending',
        ]);

        foreach ($request->input('breaks', []) as $break) {
            $breakStartAt = $break['break_start_at'] ?? null;
            $breakEndAt = $break['break_end_at'] ?? null;

            if (!$breakStartAt && !$breakEndAt) {
                continue;
            }

            AttendanceCorrectionRequestBreakTime::create([
                'attendance_correction_request_id' => $correctionRequest->id,
                'break_start_at' => $breakStartAt,
                'break_end_at' => $breakEndAt,
            ]);
        }

        return back()->with('success', '修正申請を送信しました');
    }
}
