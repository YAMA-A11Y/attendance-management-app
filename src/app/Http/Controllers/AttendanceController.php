<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $weekdays = ['日','月','火','水','木','金','土',];

        $currentDate = $now->format('Y年n月j日') . '(' . $weekdays[$now->dayOfWeek] . ')';

        $currentTime = $now->format('H:i');

        $status = 'before_work';
        
        $statusLabels = [
            'before_work' => '勤務外',
            'working' => '出勤中',
            'on_break' => '休憩中',
            'finished' => '退勤済',
        ];

        $statusLabel = $statusLabels[$status];

        return view('attendance.index', compact(
            'currentDate',
            'currentTime',
            'status',
            'statusLabel'
        ));
    }
}
