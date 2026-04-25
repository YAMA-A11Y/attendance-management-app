<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
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
            'remark' => '遅刻のため修正',
        ], $overrides);
    }

    private function validUpdateData(array $overrides = [])
    {
        return array_merge([
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
            'remark' => '管理者による修正',
            'breaks' => [],
        ], $overrides);
    }

    public function test_selected_attendance_data_displayed_on_admin_attendance_detail_page()
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
            'remark' => '電車遅延のため修正'
        ]));

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '12:00:00',
            'break_end_at' => '13:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee('名前');
        $response->assertSee('山田 太郎');
        $response->assertSee('日付');
        $response->assertSee('２０２６年');
        $response->assertSee('４月１０日');
        $response->assertSee('出勤・退勤');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSee('休憩');
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
        $response->assertSee('備考');
        $response->assertSee('電車遅延のため修正');
    }

    public function test_validation_error_is_displayed_when_clock_in_is_later_than_clock_out_on_admin_attendance_detail_page()
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
            'remark' => '元の備考'
        ]));

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', [
                'id' => $attendance->id,
            ]))
            ->post(route('admin.attendance.update', [
                'id' => $attendance->id,
            ]), $this->validUpdateData([
                'clock_in_at' => '18:00',
                'clock_out_at' => '09:00',
            ]));

        $response->assertRedirect(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_displayed_when_break_start_is_later_than_clock_out_on_admin_attendance_detail_page()
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

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', [
                'id' => $attendance->id,
            ]))
            ->post(route('admin.attendance.update', [
                'id' => $attendance->id,
            ]), $this->validUpdateData([
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'break_start_at' => '19:00',
                        'break_end_at' => '',
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'breaks' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_displayed_when_break_end_is_later_than_clock_out_on_admin_attendance_detail_page()
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

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', [
                'id' => $attendance->id,
            ]))
            ->post(route('admin.attendance.update', [
                'id' => $attendance->id,
            ]), $this->validUpdateData([
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'break_start_at' => '17:00',
                        'break_end_at' => '19:00',
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'breaks' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_displayed_when_remark_is_empty_on_admin_attendance_detail_page()
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

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance.show', [
                'id' => $attendance->id,
            ]))
            ->post(route('admin.attendance.update', [
                'id' => $attendance->id,
            ]), $this->validUpdateData([
                'remark' => '',
            ]));

        $response->assertRedirect(route('admin.attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'remark' => '備考を記入してください',
        ]);
    }
}
