@extends('layouts.app')

@section('title', '会員登録 | COACHTECH')

@section('content')
<div class="auth">
    <div class="auth__inner">
        <section class="auth__card" aria-labelledby="register-heading">
            <h1 class="auth__title" id="register-heading">会員登録</h1>
            <form class="auth-form" method="POST" action="{{ route('register') }}">
                @csrf

                <div class="auth-form__group">
                    <label class="auth-form__label" for="name">名前</label>
                    <input class="auth-form__input" id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" autofocus>
                    @error('name')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__group">
                    <label class="auth-form__label" for="email">メールアドレス</label>
                    <input class="auth-form__input" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email">
                    @error('email')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__group">
                    <label class="auth-form__label" for="password">パスワード</label>
                    <input class="auth-form__input" id="password" type="password" name="password" autocomplete="new-password">
                    @error('password')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__group">
                    <label class="auth-form__label" for="password_confirmation">確認用パスワード</label>
                    <input class="auth-form__input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                    @error('password_confirmation')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__actions">
                    <button class="auth-form__submit" type="submit">登録する</button>
                </div>
            </form>

            
            <div class="auth__link">
                <a href="{{ route('login') }}">ログインはこちらから</a>
            </div>
        </section>
    </div>
</div>
@endsection