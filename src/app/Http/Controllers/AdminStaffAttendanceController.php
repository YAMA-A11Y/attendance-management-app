<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminStaffAttendanceController extends Controller
{
    public function index(Request $request, $id)
    {
        $staffMember = User::findOrFail($id);

        $targetMonth = $request->input('month');

        $currentMonth = $targetMonth ? Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth() : Carbon::today()->startOfMonth();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::query()
            ->with('breakTimes')
            ->where('user_id', $staffMember->id)
            ->whereBetween('work_date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString(),
            ])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->toDateString();
            });

        $attendanceList = [];
        $dateCursor = $startOfMonth->copy();

        while ($dateCursor->lte($endOfMonth)) {
            $workDate = $dateCursor->toDateString();
            $attendance = $attendances->get($workDate);

            $clockInAt = '';
            $clockOutAt = '';
            $breakDuration = '';
            $workDuration = '';
            $attendanceId = null;

            if ($attendance) {
                $attendanceId = $attendance->id;
                $clockInAt = $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '';
                $clockOutAt = $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '';

                $totalBreakMinutes = $attendance->breakTimes->sum(function ($breakTime) {
                    if (!$breakTime->break_started_at || !$breakTime->break_ended_at) {
                        return 0;
                    }

                    return Carbon::parse($breakTime->break_started_at)->diffInMinutes(Carbon::parse($breakTime->break_ended_at));
                });

                if ($totalBreakMinutes > 0) {
                    $breakHours = floor($totalBreakMinutes / 60);
                    $breakMinutes = $totalBreakMinutes % 60;
                    $breakDuration = sprintf('%d:%02d', $breakHours, $breakMinutes);
                }

                if ($attendance->clpck_in_at && $attendance->clock_out_at) {
                    $workMinutes = Carbon::parse($attendance->clock_in_at)->diffInMinutes(Carbon::parse($attendance->clock_out_at)) - $totalBreakMinutes;

                    if ($workMinutes > 0) {
                        $workHours = floor($workMinutes / 60);
                        $remainingMinutes = $workMinutes % 60;
                        $workDuration = sprintf('%d:%02d', $workHours, $remainingMinutes);
                    }
                }
            }

            $attendanceList[] = [
                'date' => $dateCursor->copy(),
                'clock_in_at' => $clockInAt,
                'clock_out_at' => $clockOutAt,
                'break_duration' => $breakDuration,
                'work_duration' => $workDuration,
                'attendance_id' => $attendanceId,
            ];

            $dateCursor->addDay();
        }

        $displayMonth = $currentMonth->format('Y/m');
        $previousMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('admin.attendance.staff', compact(
            'staffMember',
            'attendanceList',
            'displayMonth',
            'previousMonth',
            'nextMonth'
        ));
    }
}
