<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $currentDate = $request->input('date', now()->toDateString());
        $currentDateCarbon = Carbon::parse($currentDate);

        $attendances = Attendance::with([
            'user',
            'breakTimes',
            'correctionRequests.breakTimes',
        ])
        ->whereDate('work_date', $currentDate)
        ->get();

        $attendanceList = $attendances->map(function ($attendance) {

            $pendingCorrectionRequest = $attendance->correctionRequests
                ->where('status', 'pending')
                ->sortByDesc('created_at')
                ->first();

            $displayClockInAt = $pendingCorrectionRequest && $pendingCorrectionRequest->requested_clock_in_at ? Carbon::parse($pendingCorrectionRequest->requested_clock_in_at)->format('H:i') : ($attendance && $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : null);

            $displayClockOutAt = $pendingCorrectionRequest && $pendingCorrectionRequest->requested_clock_out_at ? Carbon::parse($pendingCorrectionRequest->requested_clock_out_at)->format('H:i') : ($attendance && $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : null);

            $breakDurationMinutes = 0;
            $workDurationMinutes = 0;

            $displayBreakTimes = $pendingCorrectionRequest ? $pendingCorrectionRequest->breakTimes : $attendance->breakTimes;

            foreach ($displayBreakTimes as $break) {
                if ($break->break_start_at && $break->break_end_at) {

                    $breakStart = Carbon::parse($break->break_start_at);
                    $breakEnd = Carbon::parse($break->break_end_at);

                    $breakDurationMinutes += $breakStart->diffInMinutes($breakEnd);
                }
            }

            if ($displayClockInAt && $displayClockOutAt) {
                $clockIn = Carbon::createFromFormat('H:i', $displayClockInAt);
                $clockOut = Carbon::createFromFormat('H:i', $displayClockOutAt);

                $totalMinutes = $clockIn->diffInMinutes($clockOut);
                $workDurationMinutes = $totalMinutes - $breakDurationMinutes;
            }

            return [
                'attendance_id' => $attendance->id,
                'user_name' => optional($attendance->user)->name,
                'clock_in_at' => $displayClockInAt ?? '',
                'clock_out_at' => $displayClockOutAt ?? '',
                'break_duration' => $breakDurationMinutes > 0 ? sprintf('%02d:%02d', floor($breakDurationMinutes / 60), $breakDurationMinutes % 60) : '',
                'work_duration' => $workDurationMinutes > 0 ? sprintf('%02d:%02d', floor($workDurationMinutes / 60), $workDurationMinutes % 60) : '',
            ];
        });

        return view('admin.attendance.list', [
            'currentDate' => $currentDate,
            'previousDate' => $currentDateCarbon->copy()->subDay()->toDateString(),
            'nextDate' => $currentDateCarbon->copy()->addDay()->toDateString(),
            'attendanceList' => $attendanceList,
        ]);
    }
}
