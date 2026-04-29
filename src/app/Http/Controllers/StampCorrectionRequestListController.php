<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestListController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            return $this->adminIndex($request);
        }

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return $this->userIndex($request);
        }

        return redirect()->route('login');
    }

    private function userIndex(Request $request)
    {
        $currentStatus = $request->query('status', 'pending');

        if (! in_array($currentStatus, ['pending', 'approved'], true)) {
            $currentStatus = 'pending';
        }

        $correctionRequests = AttendanceCorrectionRequest::with(['attendance', 'user'])
            ->where('user_id', Auth::guard('web')->id())
            ->where('status', $currentStatus)
            ->latest()
            ->get();

        return view('attendance.requests', compact('correctionRequests', 'currentStatus'));
    }

    private function adminIndex(Request $request)
    {
        $currentStatus = $request->query('status', 'pending');

        if (! in_array($currentStatus, ['pending', 'approved'], true)) {
            $currentStatus = 'pending';
        }

        $correctionRequests = AttendanceCorrectionRequest::query()
            ->with(['user', 'attendance'])
            ->where('status', $currentStatus)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.attendance.requests', compact('correctionRequests', 'currentStatus'));
    }
}