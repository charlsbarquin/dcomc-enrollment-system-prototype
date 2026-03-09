@extends('auth.layouts.login')

@section('title', 'DCOMC Suite — Staff Portal')

@section('portal_name', 'Registrar, Staff, Dean & UNIFAST Portal Login')

@section('content')
    @if ($errors->any())
        <div class="alert" role="alert" aria-live="polite">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/login" class="js-auth-form" aria-label="Staff portal login" data-loading-text="Signing in…">
        @csrf
        <input type="hidden" name="portal_type" value="dcomc">

        <div class="mb-4">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email" autocomplete="username" aria-required="true">
        </div>
        <div class="mb-4">
            <label for="password">Password</label>
            <div class="login-password-wrap">
                <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter password" aria-required="true">
                <button type="button" class="login-password-toggle" aria-label="Show password" title="Show password">
                    <span class="login-password-icon-show" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></span>
                    <span class="login-password-icon-hide" aria-hidden="true" hidden><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg></span>
                </button>
            </div>
        </div>
        <div class="login-remember-forgot">
            <label class="inline-flex items-center">
                <input type="checkbox" name="remember" aria-label="Remember me">
                <span>Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}?portal=dcomc" aria-label="Forgot your password?">Forgot your password?</a>
            @endif
        </div>
        <button type="submit" class="btn-login js-submit-btn">Login</button>
    </form>
@endsection

@section('badge', 'Staff, Registrar, Dean & UNIFAST Access Only')

@section('scripts')
    <script>
        if (!/Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            var SWITCH_PASSWORD = 'dcomc-switch';
            var keysPressed = {};
            var popupOpen = false;
            function showSwitchPopup(targetUrl) {
                if (popupOpen) return;
                popupOpen = true;
                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,0.45);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:9999;';
                var panel = document.createElement('div');
                panel.style.cssText = 'background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.35);padding:16px;border-radius:12px;width:320px;box-shadow:0 20px 40px rgba(0,0,0,0.25);';
                var title = document.createElement('p');
                title.textContent = 'Enter password to switch portal';
                title.style.cssText = 'margin:0 0 10px 0;color:#ffffff;font-weight:600;font-size:14px;';
                var input = document.createElement('input');
                input.type = 'password';
                input.placeholder = 'Password';
                input.style.cssText = 'width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.45);background:rgba(255,255,255,0.2);color:#fff;outline:none;';
                var error = document.createElement('p');
                error.style.cssText = 'margin:8px 0 0 0;color:#fecaca;font-size:12px;display:none;';
                error.textContent = 'Incorrect password.';
                var controls = document.createElement('div');
                controls.style.cssText = 'display:flex;justify-content:flex-end;gap:8px;margin-top:12px;';
                var cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.textContent = 'Cancel';
                cancelBtn.style.cssText = 'padding:8px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.45);background:transparent;color:#fff;cursor:pointer;';
                var submitBtn = document.createElement('button');
                submitBtn.type = 'button';
                submitBtn.textContent = 'Continue';
                submitBtn.style.cssText = 'padding:8px 12px;border-radius:8px;border:none;background:#1E40AF;color:#fff;cursor:pointer;';
                function closePopup() { overlay.remove(); popupOpen = false; }
                function submitPassword() {
                    if (input.value === SWITCH_PASSWORD) { window.location.href = targetUrl; return; }
                    error.style.display = 'block';
                    input.focus();
                    input.select();
                }
                cancelBtn.addEventListener('click', closePopup);
                submitBtn.addEventListener('click', submitPassword);
                overlay.addEventListener('click', function (e) { if (e.target === overlay) closePopup(); });
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); submitPassword(); }
                    if (e.key === 'Escape') closePopup();
                });
                controls.appendChild(cancelBtn);
                controls.appendChild(submitBtn);
                panel.appendChild(title);
                panel.appendChild(input);
                panel.appendChild(error);
                panel.appendChild(controls);
                overlay.appendChild(panel);
                document.body.appendChild(overlay);
                input.focus();
            }
            document.addEventListener('keydown', function (e) {
                keysPressed[e.key.toLowerCase()] = true;
                if (!e.ctrlKey || !keysPressed['z']) return;
                if (e.key === 'ArrowRight') { e.preventDefault(); showSwitchPopup('/admin-login'); }
                else if (e.key === 'ArrowLeft') { e.preventDefault(); showSwitchPopup('/'); }
            });
            document.addEventListener('keyup', function (e) { keysPressed[e.key.toLowerCase()] = false; });
        }
    </script>
@endsection
