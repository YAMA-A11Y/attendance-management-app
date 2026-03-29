@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail-page">
    <div class="attendance-detail-container">
        <h1 class="attendance-detail-page__title">勤怠詳細</h1>

        <form action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" method="POST" class="attendance-detail-form">
            @csrf

            @if ($errors->any())
                <div class="attendance-detail-error-box">
                    <ul class="attendance-detail-error-list">
                        @foreach ($errors->all() as $error)
                            <li class="attendance-detail-error-list__item">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $displayClockInAt = old(
                    'clock_in_at',
                    $pendingCorrectionRequest && $pendingCorrectionRequest->requested_clock_in_at
                        ? \Carbon\Carbon::parse($pendingCorrectionRequest->requested_clock_in_at)->format('H:i')
                        : ($attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '')
                );

                $displayClockOutAt = old(
                    'clock_out_at',
                    $pendingCorrectionRequest && $pendingCorrectionRequest->requested_clock_out_at
                        ? \Carbon\Carbon::parse($pendingCorrectionRequest->requested_clock_out_at)->format('H:i')
                        : ($attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '')
                );

                $displayRemark = old(
                    'remark',
                    $pendingCorrectionRequest->remark ?? ($attendance->remark ?? '')
                );

                $breakTimes = $pendingCorrectionRequest
                    ? $pendingCorrectionRequest->breakTimes
                    : ($attendance->breakTimes ?? collect());

                $breakInputCount = $breakTimes->count() + 1;
            @endphp

            <div class="attendance-detail-card">
                <div class="attendance-detail-row">
                    <div class="attendance-detail-row__label">名前</div>
                    <div class="attendance-detail-row__value attendance-detail-row__value--name">
                        <span>{{ $attendance->user->name ?? '名前未設定' }}</span>
                    </div>
                </div>

                @php
                    $workDate = \Carbon\Carbon::parse($attendance->work_date);
                @endphp

                <div class="attendance-detail-row">
                    <div class="attendance-detail-row__label">日付</div>
                    <div class="attendance-detail-row__value attendance-detail-row__value--date">
                        <span class="attendance-detail-date-text attendance-detail-date-text--year">{{ mb_convert_kana($workDate->format('Y'), 'N') }}年</span>
                        <span class="attendance-detail-date-spacer" aria-hidden="true"></span>
                        <span class="attendance-detail-date-text attendance-detail-date-text--md">{{ mb_convert_kana($workDate->format('n'), 'N') }}月{{ mb_convert_kana($workDate->format('j'), 'N') }}日</span>
                    </div>
                </div>

                <div class="attendance-detail-row">
                    <div class="attendance-detail-row__label">出勤・退勤</div>
                    <div class="attendance-detail-row__value attendance-detail-row__value--time-range">
                        <input class="attendance-detail-time-input" type="time" name="clock_in_at" class="attendance-detail-time-input" value="{{ $displayClockInAt }}">
                        <span class="attendance-detail-time-separator">〜</span>
                        <input class="attendance-detail-time-input" type="time" name="clock_out_at" value="{{ $displayClockOutAt }}">
                    </div>
                </div>

                @for ($i = 0; $i < $breakInputCount; $i++)
                    @php
                        $break = $breakTimes[$i] ?? null;
                    @endphp

                    <div class="attendance-detail-row">
                        <div class="attendance-detail-row__label">
                            {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
                        </div>
                        <div class="attendance-detail-row__value attendance-detail-row__value--time-range">
                            <input class="attendance-detail-time-input" type="time" name="breaks[{{ $i }}][break_start_at]" value="{{ old('breaks.' . $i . '.break_start_at', $break && $break->break_start_at ? Carbon\Carbon::parse($break->break_start_at)->format('H:i') : '') }}">
                            <span class="attendance-detail-time-separator">〜</span>
                            <input class="attendance-detail-time-input" 
                                type="time" name="breaks[{{ $i }}][break_end_at]" value="{{ old('breaks.' . $i . '.break_end_at', $break && $break->break_end_at ? \Carbon\Carbon::parse($break->break_end_at)->format('H:i') : '') }}">
                        </div>
                    </div>
                @endfor

                <div class="attendance-detail-row attendance-detail-row--textarea">
                    <div class="attendance-detail-row__label">備考</div>
                    <div class="attendance-detail-row__value">
                        <textarea class="attendance-detail-remark-textarea"    name="remark">{{ $displayRemark }}</textarea>
                    </div>
                </div>
            </div>

            <div class="attendance-detail-button-area">
                <button class="attendance-detail-button" type="submit" >修正</button>
            </div>
        </form>
    </div>
</div>
@endsection