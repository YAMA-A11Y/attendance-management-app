@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list-page">
    <div class="attendance-list-page__content">
        <h1 class="attendance-list-page__title">勤怠一覧</h1>

        <div class="attendance-list-page__month-nav">
            <a class="attendance-list-page__month-link" href="{{ route('attendance.list', ['month' => $previousMonth]) }}">← 前月</a>

            <div class="attendance-list-page__month-current">
                <span class="attendance-list-page__month-icon">🗓</span>
                <span>{{ $displayMonth }}</span>
            </div>
            
            <a class="attendance-list-page__month-link" href="{{ route('attendance.list', ['month' => $nextMonth]) }}">翌月 →</a>
        </div>

        <div class="attendance-list-page__table-wrapper">
            <table class="attendance-list-table">
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
                                    <a class="attendance-list-table__detail-link" href="{{ route('attendance.show', ['id' => $dailyAttendance['attendance_id']]) }}">詳細</a>
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