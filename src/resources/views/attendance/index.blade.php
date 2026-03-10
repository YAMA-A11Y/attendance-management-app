@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-page">
    <div class="attendance-page__inner">
        <p class="attendance-page__status">{{ $statusLabel }}</p>

        <h1 class="attendance-page__date">{{ $currentDate }}</h1>

        <p class="attendance-page__time">{{ $currentTime }}</p>

        <div class="attendance-page__actions">
            @if ($status === 'before_work')
                <form action="{{ route('attendance.clock-in') }}" method="POST">
                    @csrf
                    <button class="attendance-page__button" type="submit">出勤</button>
                </form>
            @elseif ($status === 'working')              
                <form action="{{ route('attendance.clock-out') }}" method="POST">
                    @csrf
                    <button class="attendance-page__button" type="submit">退勤</button>
                </form>

                <form action="{{ route('attendance.break-start') }}" method="POST">
                    @csrf
                    <button class="attendance-page__button attendance-page__button--secondary" type="submit">休憩入</button>
                </form>

            @elseif ($status === 'on_break')
                <form action="{{ route('attendance.break-end') }}" method="POST">
                    @csrf
                    <button class="attendance-page__button attendance-page__button--secondary" type="submit">休憩戻</button>
                </form>
            @elseif ($status === 'finished')
                <p class="attendance-page__message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>
@endsection