@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
    <div class="verify">
        <div class="verify__inner">
            <p class="verify__text">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <div class="verify__actions">
                <a class="verify__btn" href="http://localhost:8025" target="_blank" rel="noopener">
                    認証はこちらから
                </a>

                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button class="verify__link" type="submit">認証メールを再送する</button>
                </form>
            </div>
        </div>
    </div>
@endsection