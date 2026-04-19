<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function validAttendanceData(array $overrides = [])
    {
        return array_merge([
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ], $overrides);
    }

    public function test_logged_in_user_name_is_displayed_on_attendance_detail_page()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田　太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $response = $this->actingAs($user)->get(route('attendance.show', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('名前');
        $response->assertSee('山田　太郎');
    }

    public function test_selected_date_is_displayed_on_attendance_detail_page()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田　太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $response = $this->actingAs($user)->get(route('attendance.show', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('日付');
        $response->assertSee('２０２６年');
        $response->assertSee('４月１０日');
    }

    public function test_clock_in_and_clock_out_times_are_displayed_on_attendance_detail_page()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田　太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        $response = $this->actingAs($user)->get(route('attendance.show', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('出勤・退勤');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    public function test_break_times_are_displayed_on_attendance_detail_page()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田　太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('休憩');
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }
}
