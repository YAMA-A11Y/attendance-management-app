<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_datetime_is_displayed_in_attendance_page()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00'));
        
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('2026年4月18日(土)');
        $response->assertSee('09:05');

        Carbon::setTestNow();
    }

    public function test_status_is_before_work()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    public function test_status_is_working()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_status_is_on_break()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9,0),
            'status' => Attendance::STATUS_ON_BREAK,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now(),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    public function test_status_is_finished()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9,0),
            'clock_out_at' => Carbon::now(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }
}
