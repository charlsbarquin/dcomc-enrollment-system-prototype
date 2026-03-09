@extends('auth.layouts.login')

@section('title', 'Confirm Password')

@section('portal_name', 'Confirm Password')

@section('content')
    <p class="login-forgot-intro">
        This is a secure area. Please confirm your password before continuing.
    </p>

    @if ($errors->any())
        <div class="alert" role="alert" aria-live="polite">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" class="js-auth-form" aria-label="Confirm password form" data-loading-text="Confirming…">
        @csrf

        <div class="mb-4">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password"
                   placeholder="Enter your password" aria-required="true">
        </div>

        <button type="submit" class="btn-login js-submit-btn">Confirm</button>
    </form>
@endsection
