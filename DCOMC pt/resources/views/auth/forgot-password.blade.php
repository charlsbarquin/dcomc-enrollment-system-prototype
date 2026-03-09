@php
    $isStudent = $isStudentPortal ?? false;
    $backUrl = $isStudent ? url('/') : url('/dcomc-login');
    $backText = $isStudent ? 'Back to Student Login' : 'Back to Portal Login';
@endphp
@extends('auth.layouts.login')

@section('title', 'Forgot Password')

@section('portal_name', 'Forgot Password')

@section('content')
    <p class="login-forgot-intro">
        @if($isStudent)
            Enter your Student ID / School ID below. We will send a password reset link to the email address on file for your account.
        @else
            Enter your email address and we will email you a password reset link so you can choose a new password.
        @endif
    </p>

    @if (session('status'))
        <div class="login-success" role="status" aria-live="polite">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert" role="alert" aria-live="polite">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="js-auth-form" aria-label="Forgot password form" data-loading-text="Sending…">
        @csrf
        @if($isStudent)
            <input type="hidden" name="portal_type" value="student">
        @endif

        <div class="mb-4">
            <label for="email">{{ $isStudent ? 'Student ID / School ID' : 'Email' }}</label>
            <input type="{{ $isStudent ? 'text' : 'email' }}" name="email" id="email" value="{{ old('email') }}" required autofocus
                   placeholder="{{ $isStudent ? 'Enter your Student ID or School ID' : 'Enter your email' }}"
                   autocomplete="username" aria-required="true">
        </div>

        <button type="submit" class="btn-login js-submit-btn">
            {{ $isStudent ? 'Send Password Reset Link' : 'Email Password Reset Link' }}
        </button>
    </form>

    <p style="margin-top: 1.25rem; text-align: center;">
        <a href="{{ $backUrl }}" class="login-back-link" aria-label="{{ $backText }}">{{ $backText }}</a>
    </p>
@endsection
