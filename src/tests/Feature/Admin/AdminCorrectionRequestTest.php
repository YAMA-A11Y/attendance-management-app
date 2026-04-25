<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequestBreakTime;
use App\Models\AttendanceCorrectionRequest as AttendanceCorrectionRequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser()
    {
        $admin = new Admin();
        $admin->id = 1;
        $admin->name = '管理者';
        $admin->email = 'admin@example.com';
        
        return $admin;
    }

    private function validAttendanceData(array $overrides = [])
    {
        return array_merge([
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
            'remark' => '元の備考',
        ], $overrides);
    }

    public function test_pending_correction_requests_are_displayed_on_admin_request_list()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $firstUser */
        $firstUser = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $secondUser */
        $secondUser = User::factory()->create([
            'name' => '佐藤 花子',
            'email_verified_at' => now(),
        ]);

        $firstAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $firstUser->id,
            'work_date' => '2026-04-10',
        ]));

        $secondAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $secondUser->id,
            'work_date' => '2026-04-11',
        ]));

        AttendanceCorrectionRequestModel::create([
            'user_id' => $firstUser->id,
            'attendance_id' => $firstAttendance->id,
            'work_date' => $firstAttendance->work_date,
            'requested_clock_in_at' => '08:30',
            'requested_clock_out_at' => '17:30',
            'remark' => '山田太郎の承認待ち申請',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $secondUser->id,
            'attendance_id' => $secondAttendance->id,
            'work_date' => $secondAttendance->work_date,
            'requested_clock_in_at' => '10:00',
            'requested_clock_out_at' => '19:00',
            'remark' => '佐藤花子の承認待ち申請',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $firstUser->id,
            'attendance_id' => $firstAttendance->id,
            'work_date' => $firstAttendance->work_date,
            'requested_clock_in_at' => '09:30',
            'requested_clock_out_at' => '18:30',
            'remark' => '山田太郎の承認済み申請',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.requests.index', [
            'status' => 'pending',
        ]));

        $response->assertStatus(200);
        $response->assertSee('申請一覧');
        $response->assertSee('承認待ち');

        $response->assertSee('山田 太郎');
        $response->assertSee('山田太郎の承認待ち申請');

        $response->assertSee('佐藤 花子');
        $response->assertSee('佐藤花子の承認待ち申請');

        $response->assertDontSee('山田太郎の承認済み申請');
    }

    public function test_approved_correction_requests_are_displayed_on_admin_request_list()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $firstUser */
        $firstUser = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $secondUser */
        $secondUser = User::factory()->create([
            'name' => '佐藤 花子',
            'email_verified_at' => now(),
        ]);

        $firstAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $firstUser->id,
            'work_date' => '2026-04-10',
        ]));

        $secondAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $secondUser->id,
            'work_date' => '2026-04-11',
        ]));

        AttendanceCorrectionRequestModel::create([
            'user_id' => $firstUser->id,
            'attendance_id' => $firstAttendance->id,
            'work_date' => $firstAttendance->work_date,
            'requested_clock_in_at' => '08:30',
            'requested_clock_out_at' => '17:30',
            'remark' => '山田太郎の承認済み申請',
            'status' => 'approved',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $secondUser->id,
            'attendance_id' => $secondAttendance->id,
            'work_date' => $secondAttendance->work_date,
            'requested_clock_in_at' => '10:00',
            'requested_clock_out_at' => '19:00',
            'remark' => '佐藤花子の承認済み申請',
            'status' => 'approved',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $firstUser->id,
            'attendance_id' => $firstAttendance->id,
            'work_date' => $firstAttendance->work_date,
            'requested_clock_in_at' => '09:30',
            'requested_clock_out_at' => '18:30',
            'remark' => '山田太郎の承認待ち申請',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.requests.index', [
            'status' => 'approved',
        ]));

        $response->assertStatus(200);
        $response->assertSee('申請一覧');
        $response->assertSee('承認済み');

        $response->assertSee('山田 太郎');
        $response->assertSee('山田太郎の承認済み申請');

        $response->assertSee('佐藤 花子');
        $response->assertSee('佐藤花子の承認済み申請');

        $response->assertDontSee('山田太郎の承認待ち申請');
    }

    public function test_correction_request_detail_is_displayed_correctly()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
            'remark' => '元の備考',
        ]));

        $correctionRequest = AttendanceCorrectionRequestModel::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => '08:30',
            'requested_clock_out_at' => '17:30',
            'remark' => '電車遅延のため修正申請',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequestBreakTime::create([
            'attendance_correction_request_id' => $correctionRequest->id,
            'break_start_at' => '12:15:00',
            'break_end_at' => '13:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.requests.show', [
            'attendanceCorrectionRequest' => $correctionRequest->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee('名前');
        $response->assertSee('山田 太郎');
        $response->assertSee('日付');
        $response->assertSee('２０２６年');
        $response->assertSee('４月１０日');

        $response->assertSee('出勤・退勤');
        $response->assertSee('value="08:30"', false);
        $response->assertSee('value="17:30"', false);

        $response->assertSee('休憩');
        $response->assertSee('value="12:15"', false);
        $response->assertSee('value="13:00"', false);

        $response->assertSee('備考');
        $response->assertSee('電車遅延のため修正申請');
        $response->assertSee('承認');
    }

    public function test_correction_request_is_approved_and_attendance_is_updated()
    {
        // Arrange
        $admin = $this->adminUser();

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
            'remark' => '元の備考',
        ]));

        $attendance->breakTimes()->create([
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        $correctionRequest = AttendanceCorrectionRequestModel::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => '08:30',
            'requested_clock_out_at' => '17:30',
            'remark' => '承認後の備考',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequestBreakTime::create([
            'attendance_correction_request_id' => $correctionRequest->id,
            'break_start_at' => '12:15:00',
            'break_end_at' => '13:00:00',
        ]);

        // Act
        $response = $this->actingAs($admin, 'admin')->post(route('admin.requests.approve', [
            'attendanceCorrectionRequest' => $correctionRequest->id,
        ]));

        // Assert
        $response->assertRedirect(route('admin.requests.show', [
            'attendanceCorrectionRequest' => $correctionRequest->id,
        ]));

        $correctionRequest->refresh();
        $attendance->refresh();
        $attendance->load('breakTimes');

        $this->assertSame('approved', $correctionRequest->status);
        $this->assertSame('08:30', $attendance->clock_in_at->format('H:i'));
        $this->assertSame('17:30', $attendance->clock_out_at->format('H:i'));
        $this->assertSame('承認後の備考', $attendance->remark);

        $this->assertCount(1, $attendance->breakTimes);
        $this->assertSame('12:15', \Carbon\Carbon::parse($attendance->breakTimes[0]->break_start_at)->format('H:i'));
        $this->assertSame('13:00', \Carbon\Carbon::parse($attendance->breakTimes[0]->break_end_at)->format('H:i'));
    }
}
