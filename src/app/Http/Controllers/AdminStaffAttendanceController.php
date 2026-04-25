<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffAttendanceController extends Controller
{
    public function index(Request $request, $id)
    {
        $staffMember = User::findOrFail($id);

        $currentMonth = $this->resolveCurrentMonth($request);
        $attendanceList = $this->buildAttendanceList($staffMember, $currentMonth);

        $displayMonth = $currentMonth->format('Y/m');
        $currentMonthParam = $currentMonth->format('Y-m');
        $previousMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('admin.attendance.staff', compact(
            'staffMember',
            'attendanceList',
            'displayMonth',
            'currentMonthParam',
            'previousMonth',
            'nextMonth'
        ));
    }

    public function exportCsv(Request $request, $id)
    {
        $staffMember = User::findOrFail($id);

        $currentMonth = $this->resolveCurrentMonth($request);
        $attendanceList = $this->buildAttendanceList($staffMember, $currentMonth);

        $fileName = 'staff_' . $staffMember->id . '_' . $currentMonth->format('Y-m') . '.csv';

        $response = new StreamedResponse(function () use ($attendanceList) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                mb_convert_encoding('日付', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('出勤', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('退勤', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('休憩', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('合計', 'SJIS-win', 'UTF-8'),
            ]);

            foreach ($attendanceList as $dailyAttendance) {
                fputcsv($output, [
                    mb_convert_encoding($dailyAttendance['date']->format('Y/m/d') . '(' . $dailyAttendance['date']->isoFormat('dd') . ')', 'SJIS-win', 'UTF-8'),
                    mb_convert_encoding($dailyAttendance['clock_in_at'], 'SJIS-win', 'UTF-8'),
                    mb_convert_encoding($dailyAttendance['clock_out_at'], 'SJIS-win', 'UTF-8'),
                    mb_convert_encoding($dailyAttendance['break_duration'], 'SJIS-win', 'UTF-8'),
                    mb_convert_encoding($dailyAttendance['work_duration'], 'SJIS-win', 'UTF-8'),
                ]);
            }
            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=Shift_JIS');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    private function resolveCurrentMonth(Request $request) {
        $targetMonth = $request->input('month');

        return $targetMonth ? Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth() : Carbon::today()->startOfMonth();
    }

    private function buildAttendanceList(User $staffMember, Carbon $currentMonth) {

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

                $totalBreakMinutes = 0;

                foreach ($attendance->breakTimes as $breakTime) {
                    if (!$breakTime->break_start_at || !$breakTime->break_end_at) {
                        continue;
                    }

                    $breakStart = Carbon::createFromFormat(
                        'H:i',
                        Carbon::parse($breakTime->break_start_at)->format('H:i')
                    );

                    $breakEnd = Carbon::createFromFormat(
                        'H:i',
                        Carbon::parse($breakTime->break_end_at)->format('H:i')
                    );

                    $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                }

                if ($totalBreakMinutes > 0) {
                    $breakHours = floor($totalBreakMinutes / 60);
                    $breakMinutes = $totalBreakMinutes % 60;
                    $breakDuration = sprintf('%02d:%02d', $breakHours, $breakMinutes);
                }

                if ($clockInAt && $clockOutAt) {
                    $clockIn = Carbon::createFromFormat('H:i', $clockInAt);
                    $clockOut = Carbon::createFromFormat('H:i', $clockOutAt);

                    $workMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;

                    if ($workMinutes > 0) {
                        $workHours = floor($workMinutes / 60);
                        $remainingMinutes = $workMinutes % 60;
                        $workDuration = sprintf('%02d:%02d', $workHours, $remainingMinutes);
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
        
        return $attendanceList;
    }
}
