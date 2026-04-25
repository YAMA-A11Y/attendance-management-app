<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffAttendanceTest extends TestCase
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
        ], $overrides);
    }

    public function test_selected_user_attendance_is_displayed_on_admin_staff_attendance_page()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $staffMember */
        $staffMember = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $otherUser */
        $otherUser = User::factory()->create([
            'name' => '佐藤 花子',
            'email_verified_at' => now(),
        ]);

        $firstAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $staffMember->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        BreakTime::create([
            'attendance_id' => $firstAttendance->id,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        Attendance::create($this->validAttendanceData([
            'user_id' => $staffMember->id,
            'work_date' => '2026-04-15',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $otherUser->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '07:30:00',
            'clock_out_at' => '16:30:00',
        ]));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.staff', [
            'id' => $staffMember->id,
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);
        $response->assertSee('山田 太郎さんの勤怠');
        $response->assertSee('2026/04');

        $response->assertSee('０４/１０');
        $response->assertSee('０９：００');
        $response->assertSee('１８：００');
        $response->assertSee('０１：００');
        $response->assertSee('０８：００');

        $response->assertSee('０４/１５');
        $response->assertSee('１０：００');
        $response->assertSee('１９：００');

        $response->assertDontSee('佐藤 花子');
        $response->assertDontSee('０７：３０');
        $response->assertDontSee('１６：３０');
    }

    public function test_previous_month_attendance_is_displayed_on_admin_staff_attendance_page()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $staffMember */
        $staffMember = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        Attendance::create($this->validAttendanceData([
            'user_id' => $staffMember->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '08:30:00',
            'clock_out_at' => '17:30:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $staffMember->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.staff', [
            'id' => $staffMember->id,
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);
        $response->assertSee('山田 太郎さんの勤怠');
        $response->assertSee('2026/03');

        $response->assertSee('０３/１０');
        $response->assertSee('０８：３０');
        $response->assertSee('１７：３０');

        $response->assertDontSee('2026/04');
        $response->assertDontSee('０４/１０');
        $response->assertDontSee('１８：００');
    }

    public function test_next_month_attendance_is_displayed_on_admin_staff_attendance_page()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $staffMember */
        $staffMember = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        Attendance::create($this->validAttendanceData([
            'user_id' => $staffMember->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $staffMember->id,
            'work_date' => '2026-05-10',
            'clock_in_at' => '07:30:00',
            'clock_out_at' => '16:30:00',
        ]));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.staff', [
            'id' => $staffMember->id,
            'month' => '2026-05',
        ]));

        $response->assertStatus(200);
        $response->assertSee('山田 太郎さんの勤怠');
        $response->assertSee('2026/05');

        $response->assertSee('０５/１０');
        $response->assertSee('０７：３０');
        $response->assertSee('１６：３０');

        $response->assertDontSee('2026/04');
        $response->assertDontSee('０４/１０');
        $response->assertDontSee('１８：００');
    }

        public function test_attendance_detail_page_is_displayed_when_detail_link_on_admin_staff_attendance_page_is_selected()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $staffMember */
        $staffMember = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $staffMember->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
            'remark' => '詳細確認用の備考',
        ]));

        $listResponse = $this->actingAs($admin, 'admin')->get(route('admin.attendance.staff', [
            'id' => $staffMember->id,
            'month' => '2026-04',
        ]));

        $detailResponse = $this->actingAs($admin, 'admin')->get(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));

        $listResponse->assertStatus(200);
        $listResponse->assertSee(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]), false);
        $listResponse->assertSee('詳細');

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee('山田 太郎');
        $detailResponse->assertSee('詳細確認用の備考');
    }
}