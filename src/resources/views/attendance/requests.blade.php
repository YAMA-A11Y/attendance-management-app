@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-requests.css') }}">
@endsection

@section('content')
<div class="attendance-requests-page">
    <div class="attendance-requests-page__inner">
        <h1 class="attendance-requests-page__title">申請一覧</h1>

        <div class="attendance-requests-tabs">
            <a href="#" class="attendance-requests-tabs__item is-active">承認待ち</a>
            <a href="#" class="attendance-requests-tabs__item">承認済み</a>
        </div>

        <section class="attendance-requests-card">

            <div class="attendance-requests-table-wrap">
                <table class="attendance-requests-table">
                    <thead class="attendance-requests-table__head">
                        <tr>
                            <th>状態</th>
                            <th>名前</th>
                            <th>対象日時</th>
                            <th>申請理由</th>
                            <th>申請日時</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody class="attendance-requests-table__body">
                        @forelse ($correctionRequests as $correctionRequest)
                            <tr>
                                <td>
                                    <span class="attendance-requests-table__status">{{ $correctionRequest->status === 'approved' ? '承認済み' : '承認待ち' }}</span>
                                </td>
                                <td>{{ optional($correctionRequest->user)->name }}</td>
                                <td>{{ optional($correctionRequest->attendance)->work_date ? \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('Y/m/d')
                                        : '' }}</td>
                                <td class="attendance-requests-table__remark">{{ $correctionRequest->remark }}</td>
                                <td>{{ optional($correctionRequest->created_at)->format('Y/m/d') }}</td>
                                <td>
                                    <a class="attendance-requests-table__detail-link" href="{{ route('attendance.show', ['id' => $correctionRequest->attendance_id]) }}">詳細</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="attendance-requests-table__empty">承認待ちの申請はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection