@extends('auth.layouts.login')

@section('title', 'Reset Password')

@section('portal_name', 'Reset Password')

@section('content')
    <p class="login-forgot-intro">
        Enter your email and choose a new password below. Your password must meet the security requirements shown.
    </p>

    @if ($errors->any())
        <div class="alert" role="alert" aria-live="polite">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="js-auth-form" aria-label="Reset password form" data-loading-text="Resetting…">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-4">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $request->email) }}" required autofocus
                   autocomplete="username" placeholder="Enter your email" aria-required="true">
        </div>

        <div class="mb-4">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required autocomplete="new-password"
                   placeholder="Enter new password" aria-required="true">
        </div>

        <div class="mb-4">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                   autocomplete="new-password" placeholder="Confirm new password" aria-required="true">
        </div>

        <button type="submit" class="btn-login js-submit-btn">Reset Password</button>
    </form>
@endsection
