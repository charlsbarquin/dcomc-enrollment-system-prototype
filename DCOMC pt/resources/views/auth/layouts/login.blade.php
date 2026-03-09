<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in') — Daraga Community College</title>
    @include('layouts.partials.offline-assets')
    <style>
        .login-font-heading { font-family: 'Figtree', sans-serif; }
        .login-font-data { font-family: 'Roboto', sans-serif; }
        .login-page-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
            position: relative;
        }
        .login-bg {
            position: fixed;
            inset: 0;
            background-color: #1E40AF;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: 0;
        }
        .login-bg-img { background-image: url("{{ asset('images/campus-image.jpg') }}"); }
        .login-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(160deg, rgba(30, 64, 175, 0.85) 0%, rgba(29, 58, 138, 0.85) 50%, rgba(30, 64, 175, 0.84) 100%);
            z-index: 1;
        }
        .login-card-wrap { position: relative; z-index: 2; width: 100%; max-width: 28rem; box-sizing: border-box; }
        .login-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
            border-top: 10px solid #1E40AF;
            overflow: hidden;
        }
        @media (max-width: 640px) {
            .login-card-wrap { max-width: 100%; }
            .login-card .login-body { padding-left: 1.5rem; padding-right: 1.5rem; padding-bottom: 2rem; }
        }
        .login-card .login-logo-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 2rem;
            margin-bottom: 0.75rem;
        }
        .login-card .login-logo-wrap img {
            height: 5.5rem;
            width: auto;
            max-width: 220px;
            object-fit: contain;
        }
        .login-card .login-college {
            font-family: 'Figtree', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            text-align: center;
            color: #1E40AF;
            margin: 0 0 0.25rem;
        }
        .login-card .login-portal-name {
            font-family: 'Figtree', sans-serif;
            font-weight: 500;
            font-size: 0.9375rem;
            text-align: center;
            color: #6b7280;
            margin: 0 0 1.75rem;
        }
        .login-card .login-body { padding: 0 2.25rem 2.25rem; }
        .login-card .login-body > .alert { margin-bottom: 1.25rem; }
        .login-card .login-body form > div label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #374151;
            font-family: 'Figtree', sans-serif;
        }
        .login-card input[type="text"],
        .login-card input[type="email"],
        .login-card input[type="password"] {
            display: block;
            width: 100%;
            padding: 0.75rem 0.875rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #111827;
            background: #eff6ff;
            border: 2px solid #bfdbfe;
            border-radius: 0.5rem;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }
        .login-card input::placeholder { color: #94a3b8; }
        .login-card input:focus {
            border-color: #1E40AF;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.2);
            background: #fff;
        }
        .login-card .btn-login {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            color: #fff;
            background: #1E40AF;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Roboto', sans-serif;
            box-sizing: border-box;
            transition: background-color 0.2s ease;
        }
        .login-card .btn-login:hover { background: #1e3a8a; }
        .login-card .btn-login:disabled {
            opacity: 0.8;
            cursor: not-allowed;
        }
        .login-card .btn-login:disabled:hover { background: #1E40AF; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-0 { margin: 0; }
        .login-card .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-family: 'Roboto', sans-serif;
        }
        .login-card .login-success {
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            font-family: 'Roboto', sans-serif;
        }
        .login-forgot-intro {
            font-family: 'Roboto', sans-serif;
            font-size: 0.875rem;
            color: #4b5563;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }
        .login-card .login-back-link {
            color: #1E40AF;
            text-decoration: none;
            font-size: 0.875rem;
            font-family: 'Roboto', sans-serif;
        }
        .login-card .login-back-link:hover { text-decoration: underline; }
        .login-card .login-remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .login-card .login-remember-forgot a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
            font-family: 'Roboto', sans-serif;
        }
        .login-card .login-remember-forgot a:hover { text-decoration: underline; color: #1E40AF; }
        .login-card .login-remember-forgot label { margin: 0; font-size: 0.875rem; font-weight: 500; cursor: pointer; color: #3b82f6; font-family: 'Roboto', sans-serif; display: inline-flex; align-items: center; }
        .login-card .login-remember-forgot input[type="checkbox"] {
            width: 1.125rem;
            height: 1.125rem;
            margin: 0 0.5rem 0 0;
            border: 2px solid #94a3b8;
            border-radius: 0.25rem;
            accent-color: #1E40AF;
            cursor: pointer;
            flex-shrink: 0;
            appearance: none;
            -webkit-appearance: none;
            background: #fff;
            position: relative;
            vertical-align: middle;
        }
        .login-card .login-remember-forgot input[type="checkbox"]:checked {
            background: #1E40AF;
            border-color: #1E40AF;
        }
        .login-card .login-remember-forgot input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            left: 0.35rem;
            top: 0.15rem;
            width: 0.35rem;
            height: 0.6rem;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .login-card .login-remember-forgot input[type="checkbox"]:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.3);
        }
        .login-card .login-badge {
            text-align: center;
            font-size: 12px;
            line-height: 1.4;
            color: #9ca3af;
            margin: 0;
            padding: 1.25rem 2.25rem;
            border-top: 1px solid #e5e7eb;
            font-family: 'Roboto', sans-serif;
        }
        /* Toasts */
        .login-toast-container {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            max-width: calc(100vw - 2rem);
            width: 100%;
            max-width: 24rem;
            pointer-events: none;
        }
        .login-toast {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: 'Roboto', sans-serif;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            pointer-events: auto;
            animation: login-toast-in 0.25s ease;
        }
        .login-toast--success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .login-toast--error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        @keyframes login-toast-in { from { opacity: 0; transform: translateY(-0.5rem); } to { opacity: 1; transform: translateY(0); } }
        .login-skip-link {
            position: fixed;
            top: -3rem;
            left: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #1E40AF;
            color: #fff;
            font-size: 0.875rem;
            font-family: 'Roboto', sans-serif;
            text-decoration: none;
            border-radius: 0.375rem;
            z-index: 10000;
            transition: top 0.2s ease;
        }
        .login-skip-link:focus { top: 0.75rem; outline: 2px solid #fff; outline-offset: 2px; }
        .login-password-wrap { position: relative; }
        .login-password-wrap input { padding-right: 2.75rem; }
        .login-password-toggle {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
        }
        .login-password-toggle:hover { color: #374151; }
        .login-password-toggle:focus { outline: none; box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.3); }
        .login-password-toggle svg { width: 1.25rem; height: 1.25rem; }
        .login-password-icon-hide[hidden] { display: none !important; }
        .login-password-icon-show[hidden] { display: none !important; }
        @media (max-width: 640px) {
            .login-toast-container { top: 0.75rem; max-width: calc(100vw - 1.5rem); }
            .login-card .btn-login { min-height: 48px; }
        }
    </style>
</head>
<body>
    <a href="#main-content" class="login-skip-link">Skip to form</a>
    <div id="login-toast-container" class="login-toast-container" role="region" aria-label="Notifications"></div>
    <div class="login-page-wrap">
        <div class="login-bg login-bg-img" role="img" aria-hidden="true"></div>
        <div class="login-overlay" aria-hidden="true"></div>
        <main id="main-content" class="login-card-wrap" tabindex="-1">
            <div class="login-card">
                <div class="login-logo-wrap">
                    <img src="{{ asset('images/logo.png') }}" alt="Daraga Community College" onerror="this.style.display='none'">
                </div>
                <h1 class="login-college">Daraga Community College</h1>
                <p class="login-portal-name">@yield('portal_name')</p>
                <div class="login-body">
                    @yield('content')
                </div>
                @hasSection('badge')
                <p class="login-badge">@yield('badge')</p>
                @endif
            </div>
        </main>
    </div>
    <script>
        (function() {
            var container = document.getElementById('login-toast-container');
            if (!container) return;
            function toast(message, type) {
                type = type || 'success';
                var el = document.createElement('div');
                el.className = 'login-toast login-toast--' + type;
                el.setAttribute('role', 'alert');
                el.setAttribute('aria-live', 'polite');
                el.textContent = message;
                container.appendChild(el);
                setTimeout(function() { el.remove(); }, 5000);
            }
            @if (session('status'))
                toast({{ Js::from(session('status')) }}, 'success');
            @endif
            @if (session('error'))
                toast({{ Js::from(session('error')) }}, 'error');
            @endif
            @if ($errors->any())
                @foreach ($errors->all() as $err)
                    toast({{ Js::from($err) }}, 'error');
                @endforeach
            @endif
            document.querySelectorAll('.js-auth-form').forEach(function(form) {
                form.addEventListener('submit', function() {
                    var btn = form.querySelector('.js-submit-btn');
                    var text = form.getAttribute('data-loading-text');
                    if (btn && text) {
                        btn.disabled = true;
                        btn.dataset.originalText = btn.textContent;
                        btn.textContent = text;
                    }
                });
            });
            document.querySelectorAll('.login-password-toggle').forEach(function(btn) {
                var wrap = btn.closest('.login-password-wrap');
                if (!wrap) return;
                var input = wrap.querySelector('input');
                var iconShow = wrap.querySelector('.login-password-icon-show');
                var iconHide = wrap.querySelector('.login-password-icon-hide');
                if (!input) return;
                btn.addEventListener('click', function() {
                    var isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                    btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
                    if (iconShow) iconShow.hidden = isPassword;
                    if (iconHide) iconHide.hidden = !isPassword;
                });
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>
