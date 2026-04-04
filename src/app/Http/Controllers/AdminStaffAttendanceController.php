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
            ->get();

        return view('admin.attendance.staff', compact(
            'staffMember',
            'attendances'            ,
            'currentMonth'
        ));
    }
}
