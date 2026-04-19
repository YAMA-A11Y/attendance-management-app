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
        Carbon::setTestNow(Carbon::parse('2026-04-18 12:30:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
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
        Carbon::setTestNow(Carbon::parse('2026-04-18 18:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'clock_out_at' => Carbon::now(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

    public function test_user_can_clock_in()
    {               
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $beforeResponse = $this->get(route('attendance.index'));
        $clockInResponse = $this->post(route('attendance.clock-in'));
        $afterResponse = $this->get(route('attendance.index'));

        $beforeResponse->assertStatus(200);
        $beforeResponse->assertSee('出勤');
        
        $clockInResponse->assertRedirect(route('attendance.index'));

        $afterResponse->assertStatus(200);
        $afterResponse->assertSee('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        Carbon::setTestNow();
    }

    public function test_user_cannot_clock_in_twice_in_one_day()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 18:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'clock_out_at' => Carbon::now(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertDontSee('出勤');
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

    public function test_clock_in_time_is_displayed_in_attendance_list()
    {               
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);
        $this->post(route('attendance.clock-in'));

        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('出勤');
        
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        Carbon::setTestNow();
    }

    public function test_user_can_start_break()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 12:00:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $this->actingAs($user);

        $beforeResponse = $this->get(route('attendance.index'));
        $breakStartResponse = $this->post(route('attendance.break-start'));
        $afterResponse = $this->get(route('attendance.index'));

        $beforeResponse->assertStatus(200);
        $beforeResponse->assertSee('休憩入');
        
        $breakStartResponse->assertRedirect(route('attendance.index'));

        $afterResponse->assertStatus(200);
        $afterResponse->assertSee('休憩中');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => Attendance::first()->id,
            'break_start_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'break_end_at' => null,
        ]);

        Carbon::setTestNow();
    }

    public function test_user_can_start_break_multiple_times_in_one_day()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 12:00:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'status' => Attendance::STATUS_WORKING,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->copy()->setTime(10, 0),
            'break_end_at' => Carbon::now()->copy()->setTime(10, 15),
        ]);

        $this->actingAs($user);

        $response = $this->post(route('attendance.break-start'));
        $afterResponse = $this->get(route('attendance.index'));

        $response->assertRedirect(route('attendance.index'));
        $afterResponse->assertStatus(200);
        $afterResponse->assertSee('休憩中'); 

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'break_end_at' => null,
        ]);

        $this->assertSame(2, BreakTime::where('attendance_id', $attendance->id)->count());

        Carbon::setTestNow();
    }

    public function test_user_can_end_break()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 12:30:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'status' => Attendance::STATUS_ON_BREAK,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->copy()->setTime(12, 0),
            'break_end_at' => null,
        ]);

        $this->actingAs($user);

        $beforeResponse = $this->get(route('attendance.index'));
        $breakEndResponse = $this->post(route('attendance.break-end'));
        $afterResponse = $this->get(route('attendance.index'));

        $beforeResponse->assertStatus(200);
        $beforeResponse->assertSee('休憩戻');
        
        $breakEndResponse->assertRedirect(route('attendance.index'));

        $afterResponse->assertStatus(200);
        $afterResponse->assertSee('出勤中');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->copy()->setTime(12, 0)->format('Y-m-d H:i:s'),
            'break_end_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        Carbon::setTestNow();
    }

    public function test_user_can_end_break_multiple_times_in_one_day()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 15:00:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'status' => Attendance::STATUS_ON_BREAK,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->copy()->setTime(10, 0),
            'break_end_at' => Carbon::now()->copy()->setTime(10, 15),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->copy()->setTime(14, 30),
            'break_end_at' => null,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('attendance.break-end'));
        $afterResponse = $this->get(route('attendance.index'));

        $response->assertRedirect(route('attendance.index'));
        $afterResponse->assertStatus(200);
        $afterResponse->assertSee('出勤中'); 

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->copy()->setTime(14, 30)->format('Y-m-d H:i:s'),
            'break_end_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame(2, BreakTime::where('attendance_id', $attendance->id)->count());

        Carbon::setTestNow();
    }

    public function test_break_time_is_displayed_in_attendance_list()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 12:30:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'status' => Attendance::STATUS_WORKING,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::now()->copy()->setTime(12, 0),
            'break_end_at' => Carbon::now()->copy()->setTime(12, 30),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('００：３０');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-04-18 12:00:00',
            'break_end_at' => '2026-04-18 12:30:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_user_can_clock_out()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 18:00:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_in_at' => Carbon::now()->copy()->setTime(9, 0),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $this->actingAs($user);

        $beforeResponse = $this->get(route('attendance.index'));
        $clockOutResponse = $this->post(route('attendance.clock-out'));
        $afterResponse = $this->get(route('attendance.index'));

        $beforeResponse->assertStatus(200);
        $beforeResponse->assertSee('退勤');

        $clockOutResponse->assertRedirect(route('attendance.index'));
        
        $afterResponse->assertStatus(200);
        $afterResponse->assertSee('退勤済'); 

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_out_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        Carbon::setTestNow();
    }

    public function test_clock_out_time_is_displayed_in_attendance_list()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 18:00:00'));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.clock-in'));
        $this->post(route('attendance.clock-out'));

        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('１８：００');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::now()->toDateString(),
            'clock_out_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        Carbon::setTestNow();
    }
}
