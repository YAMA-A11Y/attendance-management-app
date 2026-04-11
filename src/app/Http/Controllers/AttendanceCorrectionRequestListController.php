<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionRequestListController extends Controller
{
    public function index()
    {
        $currentStatus = request('status', 'pending');

        $correctionRequests = AttendanceCorrectionRequest::with(['attendance', 'user'])
            ->where('user_id', Auth::id())
            ->where('status', $currentStatus)
            ->latest()
            ->get();

        return view('attendance.requests', compact('correctionRequests', 'currentStatus'));
    }
}