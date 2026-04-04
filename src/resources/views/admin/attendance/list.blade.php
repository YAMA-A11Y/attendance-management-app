@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list-page">
    <div class="attendance-list-page__content">
        <h1 class="attendance-list-page__title">
            {{ \Carbon\Carbon::parse($currentDate)->isoFormat('YYYY年M月D日の勤怠') }}
        </h1>

        <div class="attendance-list-page__month-nav">
            <a class="attendance-list-page__month-link" href="{{ route('admin.attendance.list', ['date' => $previousDate]) }}">← 前日</a>            

            <div class="attendance-list-page__month-current">
                <span class="attendance-list-page__month-icon">🗓</span>
                <span>{{ \Carbon\Carbon::parse($currentDate)->format('Y/m/d') }}</span>
            </div>

            <a class="attendance-list-page__month-link" href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}">翌日 →</a>
        </div>
            
        <div class="attendance-list-page_table-wrapper">
            <table class="attendance-list-table">
                <thead>
                    <tr>
                        <th>名前</th>
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
                            <td>{{ $dailyAttendance['user_name'] }}</td>
                            <td>{{ $dailyAttendance['clock_in_at'] ? mb_convert_kana($dailyAttendance['clock_in_at'], 'A') : '' }}</td>
                            <td>{{ $dailyAttendance['clock_out_at'] ? mb_convert_kana($dailyAttendance['clock_out_at'], 'A') : '' }}</td>
                            <td>{{ $dailyAttendance['break_duration'] ? mb_convert_kana($dailyAttendance['break_duration'], 'A') : '' }}</td>
                            <td>{{ $dailyAttendance['work_duration'] ? mb_convert_kana($dailyAttendance['work_duration'], 'A') : '' }}</td>
                            <td>
                                @if (!empty($dailyAttendance['attendance_id']))
                                    <a class="attendance-list-table__detail-link" href="{{ route('admin.attendance.show', ['id' => $dailyAttendance['attendance_id'], 'date' => $currentDate]) }}">詳細</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection