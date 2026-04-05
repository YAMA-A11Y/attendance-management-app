@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-attendance.css') }}">
@endsection

@section('content')
<div class="admin-staff-attendance-page">
    <div class="admin-staff-attendance-page__content">
        <h1 class="admin-staff-attendance-page__title">{{ $staffMember->name }}さんの勤怠</h1>

        <div class="admin-staff-attendance-page__month-nav">
            <a
                class="admin-staff-attendance-page__month-link"
                href="{{ route('admin.attendance.staff', ['id' => $staffMember->id, 'month' => $previousMonth]) }}"
            >
                ← 前月
            </a>

            <div class="admin-staff-attendance-page__month-current">
                <span class="admin-staff-attendance-page__month-icon">🗓</span>
                <span>{{ $displayMonth }}</span>
            </div>

            <a
                class="admin-staff-attendance-page__month-link"
                href="{{ route('admin.attendance.staff', ['id' => $staffMember->id, 'month' => $nextMonth]) }}"
            >
                翌月 →
            </a>
        </div>

        <div class="admin-staff-attendance-page__table-wrapper">
            <table class="admin-staff-attendance-table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceList as $dailyAttendance)
                        <tr>
                            <td>
                                {{ mb_convert_kana($dailyAttendance['date']->format('m/d'), 'N') }}
                                ({{ $dailyAttendance['date']->isoFormat('dd') }})
                            </td>
                            <td>{{ $dailyAttendance['clock_in_at'] ? mb_convert_kana($dailyAttendance['clock_in_at'], 'A') : '' }}</td>
                            <td>{{ $dailyAttendance['clock_out_at'] ? mb_convert_kana($dailyAttendance['clock_out_at'], 'A') : '' }}</td>
                            <td>{{ $dailyAttendance['break_duration'] ? mb_convert_kana($dailyAttendance['break_duration'], 'A') : '' }}</td>
                            <td>{{ $dailyAttendance['work_duration'] ? mb_convert_kana($dailyAttendance['work_duration'], 'A') : '' }}</td>
                            <td>
                                @if (!empty($dailyAttendance['attendance_id']))
                                    <a
                                        class="admin-staff-attendance-table__detail-link"
                                        href="{{ route('admin.attendance.show', ['id' => $dailyAttendance['attendance_id']]) }}"
                                    >
                                        詳細
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="admin-staff-attendance-page__csv-button-wrapper">
            <a class="admin-staff-attendance-page__csv-button" href="{{ route('admin.attendance.staff.csv', ['id' => $staffMember->id, 'month' => $currentMonthParam]) }}" >ＣＳＶ出力</a>
        </div>
    </div>
</div>
@endsection