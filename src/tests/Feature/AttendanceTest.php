<?php

namespace Tests\Feature;

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
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('2026年4月18日(土)');
        $response->assertSee('09:05');

        Carbon::setTestNow();
    }
}
