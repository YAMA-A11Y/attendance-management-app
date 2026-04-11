@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-attendance-requests.css') }}">
@endsection

@section('content')
<div class="admin-requests-page">
    <div class="admin-requests-page__inner">
        <h1 class="admin-requests-page__title">申請一覧</h1>

        <div class="admin-requests-tabs">
            <a class="admin-requests-tabs__item {{ $currentStatus === 'pending' ? 'is-active' : '' }}" href="{{ route('admin.requests.index', ['status' => 'pending']) }}">承認待ち</a>
            <a class="admin-requests-tabs__item {{ $currentStatus === 'approved' ? 'is-active' : '' }}" href="{{ route('admin.requests.index', ['status' => 'approved']) }}">承認済み</a>
        </div>

        <section class="admin-requests-card">
            <div class="admin-requests-table-wrap">
                <table class="admin-requests-table">
                    <thead class="admin-requests-table__head">
                        <tr>
                            <th>状態</th>
                            <th>名前</th>
                            <th>対象日時</th>
                            <th>申請理由</th>
                            <th>申請日時</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody class="admin-requests-table__body">
                        @forelse ($correctionRequests as $correctionRequest)
                            <tr>
                                <td>
                                    <span class="admin-requests-table__status">
                                        {{ $correctionRequest->status === 'approved' ? '承認済み' : '承認待ち' }}
                                    </span>
                                </td>
                                <td>{{ optional($correctionRequest->user)->name }}</td>
                                <td>{{ optional($correctionRequest->attendance)->work_date ? \Carbon\Carbon::parse($correctionRequest->attendance->work_date)->format('Y/m/d') : '' }}</td>
                                <td class="admin-requests-table__remark">{{ $correctionRequest->remark }}</td>
                                <td>{{ optional($correctionRequest->created_at)->format('Y/m/d') }}</td>
                                <td>
                                    <a class="admin-requests-table__detail-link" href="{{ route('admin.requests.show', ['attendanceCorrectionRequest' => $correctionRequest->id]) }}">詳細</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="admin-requests-table__empty" colspan="6">{{ $currentStatus === 'approved' ? '承認済みの申請はありません。' : '承認待ちの申請はありません。' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
