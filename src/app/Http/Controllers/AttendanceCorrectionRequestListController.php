<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionRequestListController extends Controller
{
    public function index()
    {
        $requests = AttendanceCorrectionRequest::with(['attendance', 'user'])
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('attendance.requests', ['correctionRequests' => $requests,]);
    }
}
