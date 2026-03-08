@extends('layouts.app')

@section('title', 'ログイン | COACHTECH')

@section('content')
<div class="auth">
    <div class="auth__inner">
        <section class="auth__card" aria-labelledby="login-heading">
            <h1 class="auth__title" id="login-heading">ログイン</h1>

            @if (session('status'))
                <div class="auth-form__status">
                    {{ session('status') }}
                </div>
            @endif

            <form class="auth-form" method="POST" action="{{ route('login') }}">
                @csrf

                <div class="auth-form__group">
                    <label class="auth-form__label" for="email">メールアドレス</label>
                    <input class="auth-form__input" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus>
                    @error('email')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__group">
                    <label class="auth-form__label" for="password">パスワード</label>
                    <input class="auth-form__input" id="password" type="password" name="password" autocomplete="current-password">
                    @error('password')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__actions">
                    <button class="auth-form__submit" type="submit">ログインする</button>
                </div>
            </form>

            <div class="auth__link">
                <a href="{{ route('register') }}">会員登録はこちらから</a>
            </div>
        </section>
    </div>
</div>
@endsection