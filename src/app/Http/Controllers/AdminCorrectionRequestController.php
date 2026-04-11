<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $attendanceCorrectionRequest->load([
            'user',
            'attendance.user',
            'attendance.breakTimes',
            'breakTimes',
            ]);

        $attendance = $attendanceCorrectionRequest->attendance;

        return view('admin.attendance.approve', compact('attendanceCorrectionRequest', 'attendance'));
    }

    public function approve(AttendanceCorrectionRequest $attendanceCorrectionRequest)
    {
        DB::transaction(function () use ($attendanceCorrectionRequest) {
            $correctionRequest = AttendanceCorrectionRequest::with([
                'attendance.breakTimes',
                'breakTimes',
            ])->lockForUpdate()->findOrFail($attendanceCorrectionRequest->id);

            if ($correctionRequest->status === 'approved') {
                return;
            }

            $attendance = $correctionRequest->attendance;

            $attendance->update([
                'clock_in_at' => $correctionRequest->requested_clock_in_at,
                'clock_out_at' => $correctionRequest->requested_clock_out_at,
                'remark' => $correctionRequest->remark,
            ]);

            $attendance->breakTimes()->delete();

            foreach ($correctionRequest->breakTimes as $breakTime) {
                $attendance->breakTimes()->create([
                    'break_start_at' => $breakTime->break_start_at,
                    'break_end_at' => $breakTime->break_end_at,
                ]);
            }

            $correctionRequest->update([
                'status' => 'approved',
            ]);
        });

        return redirect()->route('admin.requests.show', [
            'attendanceCorrectionRequest' => $attendanceCorrectionRequest->id,
        ]);
    }
}
