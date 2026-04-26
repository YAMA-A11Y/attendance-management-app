<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest as AttendanceCorrectionRequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validAttendanceData(array $overrides = [])
    {
        return array_merge([
            'clock_in_at' => '09:00:00',
            'clock_out_at' => '18:00:00',
        ], $overrides);
    }

    private function validRequestData(array $overrides = [])
    {
        return array_merge([
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'remark' => '電車遅延のため修正申請',
            'breaks' => [],
        ], $overrides);
    }

    public function test_validation_error_is_displayed_when_clock_in_is_later_than_clock_out()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $invalidRequestData = $this->validRequestData([
            'clock_in_at' => '18:00',
            'clock_out_at' => '09:00',
        ]);

        $response = $this->actingAs($user)->from(route('attendance.show', [
            'id' => $attendance->id,
        ]))->post(route('attendance.correction_request.store', [
            'id' => $attendance->id
        ]), $invalidRequestData);

        $response->assertRedirect(route('attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_displayed_when_break_start_is_later_than_clock_out()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $invalidRequestData = $this->validRequestData([
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'break_start_at' => '19:00',
                    'break_end_at' => '',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->from(route('attendance.show', [
            'id' => $attendance->id,
        ]))->post(route('attendance.correction_request.store', [
            'id' => $attendance->id
        ]), $invalidRequestData);

        $response->assertRedirect(route('attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'breaks.0.break_start_at' => '休憩時間が不適切な値です',
        ]);
    }
    
    public function test_validation_error_is_displayed_when_break_end_is_later_than_clock_out()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $invalidRequestData = $this->validRequestData([
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'break_start_at' => '17:00',
                    'break_end_at' => '19:00',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->from(route('attendance.show', [
            'id' => $attendance->id,
        ]))->post(route('attendance.correction_request.store', [
            'id' => $attendance->id,
        ]), $invalidRequestData);

        $response->assertRedirect(route('attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'breaks.0.break_end_at' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_validation_error_is_displayed_when_remark_is_empty()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $invalidRequestData = $this->validRequestData([
            'remark' => '',
        ]);

        $response = $this->actingAs($user)->from(route('attendance.show', [
            'id' => $attendance->id,
        ]))->post(route('attendance.correction_request.store', [
            'id' => $attendance->id,
        ]), $invalidRequestData);

        $response->assertRedirect(route('attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasErrors([
            'remark' => '備考を記入してください',
        ]);
    }

    public function test_correction_request_is_created()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $requestData = $this->validRequestData([
            'clock_in_at' => '08:30',
            'clock_out_at' => '17:30',
            'remark' => '電車遅延のため修正申請',
            'breaks' => [
                [
                    'break_start_at' => '12:15',
                    'break_end_at' => '13:00',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->from(route('attendance.show', [
            'id' => $attendance->id,
        ]))->post(route('attendance.correction_request.store', [
            'id' => $attendance->id,
        ]), $requestData);

        $response->assertRedirect(route('attendance.show', [
            'id' => $attendance->id,
        ]));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', '修正申請を送信しました');

        $correctionRequest = AttendanceCorrectionRequestModel::with('breakTimes')
            ->latest('id')
            ->first();

        $this->assertNotNull($correctionRequest);
        $this->assertSame($user->id, $correctionRequest->user_id);
        $this->assertSame($attendance->id, $correctionRequest->attendance_id);
        $this->assertSame('pending', $correctionRequest->status);
        $this->assertSame('電車遅延のため修正申請', $correctionRequest->remark);
        $this->assertSame('08:30', substr($correctionRequest->requested_clock_in_at, 0, 5));
        $this->assertSame('17:30', substr($correctionRequest->requested_clock_out_at, 0, 5));

        $this->assertCount(1, $correctionRequest->breakTimes);
        $this->assertSame('12:15', substr($correctionRequest->breakTimes[0]->break_start_at, 0, 5));
        $this->assertSame('13:00', substr($correctionRequest->breakTimes[0]->break_end_at, 0, 5));
    }

    public function test_pending_correction_requests_are_displayed_on_request_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $otherUser */
        $otherUser = User::factory()->create([
            'name' => '佐藤 花子',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $otherAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $otherUser->id,
            'work_date' => '2026-04-11',
        ]));

        AttendanceCorrectionRequestModel::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => '08:30',
            'requested_clock_out_at' => '17:30',
            'remark' => '自分の承認待ち申請',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => '09:30',
            'requested_clock_out_at' => '18:30',
            'remark' => '自分の承認済み申請',
            'status' => 'approved',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $otherUser->id,
            'attendance_id' => $otherAttendance->id,
            'work_date' => $otherAttendance->work_date,
            'requested_clock_in_at' => '10:00',
            'requested_clock_out_at' => '19:00',
            'remark' => '他人の承認待ち申請',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.requests', [
            'status' => 'pending',
        ]));

        $response->assertStatus(200);
        $response->assertSee('申請一覧');
        $response->assertSee('承認待ち');
        $response->assertSee('山田 太郎');
        $response->assertSee('自分の承認待ち申請');

        $response->assertDontSee('自分の承認済み申請');
        $response->assertDontSee('他人の承認待ち申請');
        $response->assertDontSee('佐藤 花子');
    }

    public function test_approved_correction_requests_are_displayed_on_request_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $otherUser */
        $otherUser = User::factory()->create([
            'name' => '佐藤 花子',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        $otherAttendance = Attendance::create($this->validAttendanceData([
            'user_id' => $otherUser->id,
            'work_date' => '2026-04-11',
        ]));

        AttendanceCorrectionRequestModel::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => '08:30',
            'requested_clock_out_at' => '17:30',
            'remark' => '自分の承認済み申請',
            'status' => 'approved',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => '09:30',
            'requested_clock_out_at' => '18:30',
            'remark' => '自分の承認待ち申請',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequestModel::create([
            'user_id' => $otherUser->id,
            'attendance_id' => $otherAttendance->id,
            'work_date' => $otherAttendance->work_date,
            'requested_clock_in_at' => '10:00',
            'requested_clock_out_at' => '19:00',
            'remark' => '他人の承認済み申請',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.requests', [
            'status' => 'approved',
        ]));

        $response->assertStatus(200);
        $response->assertSee('申請一覧');
        $response->assertSee('承認済み');
        $response->assertSee('山田 太郎');
        $response->assertSee('自分の承認済み申請');

        $response->assertDontSee('自分の承認待ち申請');
        $response->assertDontSee('他人の承認済み申請');
        $response->assertDontSee('佐藤 花子');
    }

    public function test_attendance_detail_page_is_displayed_when_detail_link_on_request_list_is_selected()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create($this->validAttendanceData([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]));

        AttendanceCorrectionRequestModel::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'requested_clock_in_at' => '08:30',
            'requested_clock_out_at' => '17:30',
            'remark' => '詳細画面遷移確認用の申請',
            'status' => 'pending',
        ]);

        $listResponse = $this->actingAs($user)->get(route('attendance.requests', [
            'status' => 'pending',
        ]));

        $detailResponse = $this->actingAs($user)->get(route('attendance.show', [
            'id' => $attendance->id,
        ]));

        $listResponse->assertStatus(200);
        $listResponse->assertSee(route('attendance.show', [
            'id' => $attendance->id,
        ]), false);
        $listResponse->assertSee('詳細');

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee('山田 太郎');
    }
}
