<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'COACHTECH')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    @php
        $isAuthPage = request()->routeIs('login') || request()->routeIs('register') || request()->routeIs('verification.notice');
    @endphp

    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="{{ url('/') }}">
                <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH">
            </a>

            @if (!$isAuthPage)
                <nav class="header__nav" aria-label="グローバルナビゲーション">
                    <ul class="header__nav-list">
                        <li class="header__nav-item">
                            <a class="header__nav-link" href="{{ url('/attendance') }}">勤怠</a>
                        </li>
                        <li class="header__nav-item">
                            <a class="header__nav-link" href="{{ url('/attendance/list') }}">勤怠一覧</a>
                        </li>
                        <li class="header__nav-item">
                            <a class="header__nav-link" href="{{ url('/stamp_correction_request/list') }}">申請</a>
                        </li>
                        <li class="header__nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="header__nav-button" type="submit">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                </nav>
            @endif
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>
</body>

</html>