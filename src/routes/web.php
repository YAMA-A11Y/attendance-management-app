<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use App\Http\Controllers\AttendanceCorrectionRequestListController;
use App\Http\Controllers\StampCorrectionRequestListController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminCorrectionRequestController;
use App\Http\Controllers\AdminStaffAttendanceController;
use App\Http\Controllers\AdminStaffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/login', [LoginController::class, 'store']);
Route::post('/register', [RegisterController::class, 'store']);

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/break-start', [AttendanceController::class, 'startBreak'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'endBreak'])->name('attendance.break-end');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/detail/{id}/request', [AttendanceCorrectionRequestController::class, 'store'])->name('attendance.correction_request.store');

    Route::get('/attendance/requests', [AttendanceCorrectionRequestListController::class, 'index'])->name('attendance.requests');
});

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::get('/stamp_correction_request/list', [StampCorrectionRequestListController::class, 'index'])
    ->name('admin.requests.index');

Route::middleware('admin')->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.list');
    Route::get('/admin/attendance/staff/{id}', [AdminStaffAttendanceController::class, 'index'])->name('admin.attendance.staff');
    Route::get('/admin/attendance/staff/{id}/csv', [AdminStaffAttendanceController::class, 'exportCsv'])->name('admin.attendance.staff.csv');
    Route::get('/stamp_correction_request/approve/{attendanceCorrectionRequest}', [AdminCorrectionRequestController::class, 'show'])->name('admin.requests.show');
    Route::post('/stamp_correction_request/approve/{attendanceCorrectionRequest}', [AdminCorrectionRequestController::class, 'approve'])->name('admin.requests.approve');
});