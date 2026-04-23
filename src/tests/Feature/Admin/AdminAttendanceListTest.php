<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
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

    public function test_all_users_attendance_for_the_day_is_displayed_on_admin_attendance_list()
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
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        $secondAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $secondUser->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]));

        BreakTime::create([
            'attendance_id' => $secondAttendance->id,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        Attendance::create($this->validAttendanceData([
            'user_id' => $firstUser->id,
            'work_date' => '2026-04-11',
            'clock_in_at' => '07:30:00',
            'clock_out_at' => '16:30:00',
        ]));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.list', [
            'date' => '2026-04-10',
        ]));

        $response->assertStatus(200);

        $response->assertSee('山田 太郎');
        $response->assertSee('佐藤 花子');

        $response->assertSee('０９：００');
        $response->assertSee('１８：００');
        $response->assertSee('１０：００');
        $response->assertSee('１９：００');

        $response->assertSee('０１：００');
        $response->assertSee('０８：００');

        $response->assertDontSee('０７：３０');
        $response->assertDontSee('１６：３０');
    }

    public function test_current_date_is_displayed_on_admin_attendance_list()
    {
        $admin = $this->adminUser();

        Carbon::setTestNow('2026-04-10 09:00:00');

        try {
            $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.list'));
            
            $response->assertStatus(200);
            $response->assertSee('2026年4月10日の勤怠');
            $response->assertSee('2026/04/10');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_previous_day_attendance_is_displayed_when_previous_day_is_selected()
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

        Attendance::create($this->validAttendanceData([
            'user_id' => $firstUser->id,
            'work_date' => '2026-04-09',
            'clock_in_at' => '08:30:00',
            'clock_out_at' => '17:30:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $secondUser->id,
            'work_date' => '2026-04-09',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $firstUser->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.list', [
            'date' => '2026-04-09',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026年4月9日の勤怠');
        $response->assertSee('2026/04/09');

        $response->assertSee('山田 太郎');
        $response->assertSee('佐藤 花子');
        $response->assertSee('０８：３０');
        $response->assertSee('１７：３０');
        $response->assertSee('１０：００');
        $response->assertSee('１９：００');

        $response->assertDontSee('2026年4月10日の勤怠');
        $response->assertDontSee('2026/04/10');
        $response->assertDontSee('１８：００');
    }

    public function test_next_day_attendance_is_displayed_when_next_day_is_selected()
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

        Attendance::create($this->validAttendanceData([
            'user_id' => $firstUser->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $firstUser->id,
            'work_date' => '2026-04-11',
            'clock_in_at' => '07:30:00',
            'clock_out_at' => '16:30:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $secondUser->id,
            'work_date' => '2026-04-11',
            'clock_in_at' => '11:00:00',
            'clock_out_at' => '20:00:00',
        ]));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.list', [
            'date' => '2026-04-11',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026年4月11日の勤怠');
        $response->assertSee('2026/04/11');

        $response->assertSee('山田 太郎');
        $response->assertSee('佐藤 花子');
        $response->assertSee('０７：３０');
        $response->assertSee('１６：３０');
        $response->assertSee('１１：００');
        $response->assertSee('２０：００');

        $response->assertDontSee('2026年4月10日の勤怠');
        $response->assertDontSee('2026/04/10');
        $response->assertDontSee('１８：００');
    }
}
