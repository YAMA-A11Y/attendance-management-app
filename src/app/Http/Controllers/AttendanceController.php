<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $weekdays = ['日','月','火','水','木','金','土',];

        $currentDate = $now->format('Y年n月j日') . '(' . $weekdays[$now->dayOfWeek] . ')';
        $currentTime = $now->format('H:i');

        $todayAttendance = Attendance::with('activeBreakTime')
            ->where('user_id', Auth::id())
            ->whereDate('work_date', $now->toDateString())
            ->first();

            if (!$todayAttendance) {
                $status = Attendance::STATUS_BEFORE_WORK;
            } elseif ($todayAttendance->clock_out_at) {
                $status = Attendance::STATUS_FINISHED;
            } elseif ($todayAttendance->activeBreakTime) {
                $status = Attendance::STATUS_ON_BREAK;
            } else {
                $status = Attendance::STATUS_WORKING;
            }
        
        $statusLabels = [
            Attendance::STATUS_BEFORE_WORK => '勤務外',
            Attendance::STATUS_WORKING => '出勤中',
            Attendance::STATUS_ON_BREAK => '休憩中',
            Attendance::STATUS_FINISHED => '退勤済',
        ];

        $statusLabel = $statusLabels[$status];

        return view('attendance.index', compact(
            'currentDate',
            'currentTime',
            'status',
            'statusLabel'
        ));
    }

    public function clockIn()
    {
        $now = Carbon::now();

        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $now->toDateString())
            ->first();

        if ($todayAttendance) {
            return redirect()->route('attendance.index');
        }

        Attendance::create([
            'user_id' => Auth::id(),
            'work_date' => $now->toDateString(),
            'clock_in_at' => $now,
            'status' => Attendance::STATUS_WORKING,
        ]);

        return redirect()->route('attendance.index');
    }

    public function startBreak()
    {
        $now = Carbon::now();

        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $now->toDateString())
            ->first();

        if (!$todayAttendance) {
            return redirect()->route('attendance.index');
        }

        if ($todayAttendance->clock_out_at) {
            return redirect()->route('attendance.index');
        }

        $activeBreakTime = BreakTime::where('attendance_id', $todayAttendance->id)
            ->whereNull('break_end_at')
            ->first();

        if ($activeBreakTime) {
            return redirect()->route('attendance.index');
        }

        BreakTime::create([
            'attendance_id' => $todayAttendance->id,
            'break_start_at' => $now,
        ]);

        $todayAttendance->update([
            'status' => Attendance::STATUS_ON_BREAK,
        ]);

        return redirect()->route('attendance.index');
    }

    public function endBreak()
    {
        $now = Carbon::now();

        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $now->toDateString())
            ->first();

        if (!$todayAttendance) {
            return redirect()->route('attendance.index');
        }

        if ($todayAttendance->clock_out_at) {
            return redirect()->route('attendance.index');
        }

        $activeBreakTime = BreakTime::where('attendance_id', $todayAttendance->id)
            ->whereNull('break_end_at')
            ->first();

        if (!$activeBreakTime) {
            return redirect()->route('attendance.index');
        }

        $activeBreakTime->update([
            'break_end_at' => $now,
        ]);

        $todayAttendance->update([
            'status' => Attendance::STATUS_WORKING,
        ]);

        return redirect()->route('attendance.index');
    }

    public function clockOut()
    {
        $now = Carbon::now();

        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $now->toDateString())
            ->first();

        if (!$todayAttendance) {
            return redirect()->route('attendance.index');
        }

        if ($todayAttendance->clock_out_at) {
            return redirect()->route('attendance.index');
        }

        $activeBreakTime = BreakTime::where('attendance_id', $todayAttendance->id)
            ->whereNull('break_end_at')
            ->first();

        if ($activeBreakTime) {
            return redirect()->route('attendance.index');
        }

        $todayAttendance->update([
            'clock_out_at' => $now,
            'status' => Attendance::STATUS_FINISHED,
        ]);

        return redirect()->route('attendance.index');
    }
}
