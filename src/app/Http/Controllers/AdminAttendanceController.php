<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function show($id)
    {
        $attendance = Attendance::with([
            'user',
            'breakTimes',
            'correctionRequests.breakTimes',
        ])->findOrFail($id);

        $pendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();

        return view('admin.attendance.show', compact('attendance', 'pendingCorrectionRequest'));
    }

    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::with('correctionRequests')->findOrFail($id);

        $pendingCorrectionRequest = $attendance->correctionRequests
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();

        if ($pendingCorrectionRequest) {
            return redirect()->route('admin.attendance.show', ['id' => $attendance->id]);
        }

        DB::transaction(function () use ($request, $attendance) {
            $attendance->clock_in_at = $request->input('clock_in_at');
            $attendance->clock_out_at = $request->input('clock_out_at');
            $attendance->remark = $request->input('remark');
            $attendance->save();

            BreakTime::where('attendance_id', $attendance->id)->delete();

            $breakInputs = $request->input('breaks', []);

            foreach ($breakInputs as $breakInput) {
                $breakStartAt = $breakInput['break_start_at'] ?? null;
                $breakEndAt = $breakInput['break_end_at'] ?? null;

                if (!$breakStartAt && !$breakEndAt) {
                    continue;
                }

                BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start_at' => $breakStartAt,
                'break_end_at' => $breakEndAt,
                ]);
            }
        });

        return redirect()->route('admin.attendance.list', [
            'date' => \Carbon\Carbon::parse($attendance->work_date)->toDateString(),
        ]);
    }
}
