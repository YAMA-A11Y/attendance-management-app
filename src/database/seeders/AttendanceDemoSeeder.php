<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceDemoSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['name' => '山田 太郎', 'email' => 'user1@example.com'],
            ['name' => '佐藤 花子', 'email' => 'user2@example.com'],
            ['name' => '鈴木 一郎', 'email' => 'user3@example.com'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            for ($dayOffset = 1; $dayOffset <= 5; $dayOffset++) {
                $workDate = Carbon::today()->subDays($dayOffset);

                $attendance = Attendance::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'work_date' => $workDate->toDateString(),
                    ],
                    [
                        'clock_in_at' => $workDate->copy()->setTime(9, 0),
                        'clock_out_at' => $workDate->copy()->setTime(18, 0),
                        'status' => Attendance::STATUS_FINISHED,
                    ]
                );

                $attendance->breakTimes()->delete();

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $workDate->copy()->setTime(12, 0),
                    'break_end_at' => $workDate->copy()->setTime(13, 0),
                ]);
            }
        }
    }
}