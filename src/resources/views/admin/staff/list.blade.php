@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="admin-staff-list-page">
    <div class="admin-staff-list-page-inner">
        <h1 class="admin-staff-list-page-title">スタッフ一覧</h1>

        <div class="admin-staff-list-table">
            <table class="admin-staff-list-table__table">
                <thead>
                    <tr>
                        <th class="admin-staff-list-table__heading">
                            <span class="admin-staff-list-table__heading-text admin-staff-list-table__heading-text--name">名前</span>
                        </th>
                        <th class="admin-staff-list-table__heading">
                            <span class="admin-staff-list-table__heading-text">メールアドレス</span>
                        </th>
                        <th class="admin-staff-list-table__heading">
                            <span class="admin-staff-list-table__heading-text admin-staff-list-table__heading-text--attendance">月次勤怠</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staffMembers as $staffMember)
                        <tr class="admin-staff-list-table__row">
                            <td class="admin-staff-list-table__data">{{ $staffMember->name}}</td>
                            <td class="admin-staff-list-table__data">{{ $staffMember->email}}</td>
                            <td class="admin-staff-list-table__data">
                                <a class="admin-staff-list-table__detail-link" href="{{ route('admin.attendance.staff' , $staffMember->id) }}">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection