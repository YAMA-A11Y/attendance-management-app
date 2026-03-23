<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceListController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $targetMonth = $request->input('month', now()->format('Y-m'));

        $currentMonth = Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendanceCollection = Attendance::with(['breakTimes', 'correctionRequests.breakTimes',])
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString()
            ])
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });

        $attendanceList = [];

        $currentDate = $startOfMonth->copy();

        while ($currentDate->lte($endOfMonth)) {

            $dateKey = $currentDate->format('Y-m-d');
            $attendance = $attendanceCollection->get($dateKey);

            $pendingCorrectionRequest = null;

            if ($attendance) {
                $pendingCorrectionRequest = $attendance->correctionRequests
                    ->where('status', 'pending')
                    ->sortByDesc('created_at')
                    ->first();
            }

            $displayClockInAt = $pendingCorrectionRequest && $pendingCorrectionRequest->requested_clock_in_at ? Carbon::parse($pendingCorrectionRequest->requested_clock_in_at)->format('H:i') : ($attendance && $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : null);

            $displayClockOutAt = $pendingCorrectionRequest && $pendingCorrectionRequest->requested_clock_out_at ? Carbon::parse($pendingCorrectionRequest->requested_clock_out_at)->format('H:i') : ($attendance && $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : null);

            $breakDurationMinutes = 0;
            $workDurationMinutes = 0;

            if ($attendance) {

                $displayBreakTimes = $pendingCorrectionRequest ? $pendingCorrectionRequest->breakTimes : $attendance->breakTimes;

                foreach ($displayBreakTimes as $break) {

                    if ($break->break_start_at && $break->break_end_at) {

                        $breakStart = Carbon::createFromFormat(
                            'H:i',
                            Carbon::parse($break->break_start_at)->format('H:i')
                        );

                        $breakEnd = Carbon::createFromFormat(
                            'H:i',
                            Carbon::parse($break->break_end_at)->format('H:i')
                        );

                        $breakDurationMinutes += $breakStart->diffInMinutes($breakEnd);
                    }
                }                

                if ($displayClockInAt && $displayClockOutAt) {

                    $clockIn = Carbon::createFromFormat(
                        'H:i', $displayClockInAt);

                    $clockOut = Carbon::createFromFormat(
                        'H:i',$displayClockOutAt);

                    $totalMinutes = $clockIn->diffInMinutes($clockOut);

                    $workDurationMinutes = $totalMinutes - $breakDurationMinutes;
                }
            }

            $attendanceList[] = [
                'date' => $currentDate->copy(),

                'attendance_id' => $attendance ? $attendance->id : null,

                'clock_in_at' => $displayClockInAt ?? '',

                'clock_out_at' => $displayClockOutAt ?? '',

                'break_duration' => $breakDurationMinutes > 0 ? sprintf('%02d:%02d', floor($breakDurationMinutes / 60), $breakDurationMinutes % 60) : '',

                'work_duration' => $workDurationMinutes > 0 ? sprintf('%02d:%02d', floor($workDurationMinutes / 60), $workDurationMinutes % 60) : '',
            ];

            $currentDate->addDay();
        }

        $displayMonth = $currentMonth->format('Y/m');

        $previousMonth = $currentMonth->copy()->subMonth()->format('Y-m');

        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('attendance.list', compact(
            'attendanceList',
            'displayMonth',
            'previousMonth',
            'nextMonth'
        ));
    }
}
