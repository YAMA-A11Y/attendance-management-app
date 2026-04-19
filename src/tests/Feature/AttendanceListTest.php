<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function validAttendanceData(array $overrides = [])
    {
        return array_merge([
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ], $overrides);
    }

    public function test_user_attendance_records_are_all_displayed_on_attendance_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $otherUser */
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-15',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $otherUser->id,
            'work_date' => '2026-04-20',
            'clock_in_at' => '08:00:00',
            'clock_out_at' => '17:00:00',
        ]));

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);

        $response->assertSee('０４/１０');
        $response->assertSee('０９：００');
        $response->assertSee('１８：００');

        $response->assertSee('０４/１５');
        $response->assertSee('１０：００');
        $response->assertSee('１９：００');

        $response->assertDontSee('０８：００');
        $response->assertDontSee('１７：００');
    }

    public function test_current_month_is_displayed_on_attendance_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $currentMonth = now()->format('Y/m');

        $response = $this->actingAs($user)->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee($currentMonth);
    }

    public function test_previous_month_attendance_is_displayed_when_previous_month_is_selected()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]));

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);

        $response->assertSee('2026/03');
        $response->assertSee('０３/１０');
        $response->assertSee('０９：００');
        $response->assertSee('１８：００');

        $response->assertDontSee('2026/04');
        $response->assertDontSee('０４/１０');
        $response->assertDontSee('１９：００');
    }

    public function test_next_month_attendance_is_displayed_when_next_month_is_selected()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-05-10',
            'clock_in_at' => '10:00:00',
            'clock_out_at' => '19:00:00',
        ]));

        $response = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-05',
        ]));

        $response->assertStatus(200);

        $response->assertSee('2026/05');
        $response->assertSee('０５/１０');
        $response->assertSee('１０：００');
        $response->assertSee('１９：００');

        $response->assertDontSee('2026/04');
        $response->assertDontSee('０４/１０');
        $response->assertDontSee('１８：００');
    }

    public function test_attendance_detail_page_is_displayed_when_detail_link_selected()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ]));

        $listResponse = $this->actingAs($user)->get(route('attendance.list', [
            'month' => '2026-04',
        ]));

        $detailResponse = $this->actingAs($user)->get(route('attendance.show', [
            'id' => $attendance->id,
        ]));

        $listResponse->assertStatus(200);
        $listResponse->assertSee(route('attendance.show', ['id' => $attendance->id]), false);
        $listResponse->assertSee('詳細');

        $detailResponse->assertStatus(200);
    }
}
