@extends('layouts.app')

@section('content')
<div class="auth">
    <div class="auth__inner">
        <div class="auth__card">
            <h1 class="auth__title">管理者ログイン</h1>

            <form class="auth-form" method="POST" action="{{ route('admin.login.post') }}">
                @csrf

                <div class="auth-form__group">
                    <label class="auth-form__label" for="email">メールアドレス</label>
                    <input class="auth-form__input" id="email" type="email" name="email">
                    @error('email')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__group">
                    <label class="auth-form__label" for="password">パスワード</label>
                    <input class="auth-form__input" id="password" type="password" name="password">
                    @error('password')
                        <p class="auth-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-form__actions">
                    <button class="auth-form__submit" type="submit">管理者ログインする</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection