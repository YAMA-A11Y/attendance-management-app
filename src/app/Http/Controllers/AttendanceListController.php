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

        $attendanceCollection = Attendance::with('breakTimes')
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

            $breakDurationSeconds = 0;
            $workDurationSeconds = 0;

            if ($attendance) {

                foreach ($attendance->breakTimes as $break) {

                    if ($break->break_start_at && $break->break_end_at) {

                        $breakDurationSeconds += Carbon::parse($break->break_start_at)
                            ->diffInSeconds(Carbon::parse($break->break_end_at));
                    }
                }

                if ($attendance->clock_in_at && $attendance->clock_out_at) {

                    $totalSeconds = Carbon::parse($attendance->clock_in_at)
                        ->diffInSeconds(Carbon::parse($attendance->clock_out_at));
                        
                    $workDurationSeconds = $totalSeconds - $breakDurationSeconds;
                }
            }

            $attendanceList[] = [

                'date' => $currentDate->copy(),

                'attendance_id' => $attendance ? $attendance->id : null,

                'clock_in_at' => $attendance && $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '',

                'clock_out_at' => $attendance && $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '',

                'break_duration' => $breakDurationSeconds > 0 ? gmdate('H:i', $breakDurationSeconds) : '',

                'work_duration' => $workDurationSeconds > 0 ? gmdate('H:i', $workDurationSeconds) : '',
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
