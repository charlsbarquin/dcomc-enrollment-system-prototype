<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DCOMC') }} — Enrollment System</title>
    @vite(['resources/css/app.css', 'resources/css/bootstrap-override.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background: linear-gradient(180deg, #EFF6FF 0%, #FFFFFF 100%); font-family: 'Roboto', sans-serif;">
    <div class="w-full max-w-md">
        {{-- Login Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-8">
                {{-- School Logo (top center) --}}
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('images/logo.png') }}" alt="Daraga Community College" class="h-20 w-auto object-contain" onerror="this.style.display='none'">
                </div>

                <h1 class="text-xl font-semibold text-gray-900 text-center mb-1" style="font-family: 'Figtree', sans-serif;">Sign In</h1>
                <p class="text-sm text-gray-500 text-center mb-6">Daraga Community College Enrollment System</p>

                {{-- Multi-role indicator --}}
                <div class="flex items-center justify-center gap-2 mb-6 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-[#1E40AF]" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                        Students
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-[#1E40AF]" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                        Faculty
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-[#1E40AF]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm14 1a1 1 0 11-2 1 1 0 012 0v2a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 012 0v2h8V6z" clip-rule="evenodd"/></svg>
                        Admin
                    </span>
                </div>

                {{-- Portal type (required by backend) — subtle selector --}}
                <div class="mb-4" x-data="{ portal: 'student' }" x-id="portal-selector">
                    <p class="text-xs text-gray-500 mb-2">I am signing in as</p>
                    <div class="flex gap-2">
                        <button type="button" @click="portal = 'student'" :class="portal === 'student' ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'bg-white text-gray-600 border-gray-300'"
                                class="flex-1 py-2 px-3 rounded border text-sm font-medium">Student</button>
                        <button type="button" @click="portal = 'dcomc'" :class="portal === 'dcomc' ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'bg-white text-gray-600 border-gray-300'"
                                class="flex-1 py-2 px-3 rounded border text-sm font-medium">Faculty / Staff</button>
                        <button type="button" @click="portal = 'admin'" :class="portal === 'admin' ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'bg-white text-gray-600 border-gray-300'"
                                class="flex-1 py-2 px-3 rounded border text-sm font-medium">Admin</button>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded border border-[#F97316]/30 bg-[#FFF7ED] text-sm text-gray-800">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ url('login') }}">
                    @csrf
                    <input type="hidden" name="portal_type" value="student" id="portal-type-input">

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1" style="font-family: 'Roboto', sans-serif;">School Email or Student ID</label>
                        <input type="text"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               required
                               autofocus
                               autocomplete="username"
                               placeholder="Enter School Email or Student ID"
                               class="w-full rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-[#1E40AF] focus:ring-1 focus:ring-[#1E40AF]"
                               style="font-family: 'Roboto', sans-serif;">
                    </div>

                    <div class="mb-4" x-data="{ show: false }">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1" style="font-family: 'Roboto', sans-serif;">Password</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'"
                                   name="password"
                                   id="password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="Enter password"
                                   class="w-full rounded border border-gray-300 px-3 py-2 pr-10 text-gray-900 placeholder-gray-400 focus:border-[#1E40AF] focus:ring-1 focus:ring-[#1E40AF]"
                                   style="font-family: 'Roboto', sans-serif;">
                            <button type="button"
                                    @click="show = !show"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-500 hover:text-gray-700"
                                    :aria-label="show ? 'Hide password' : 'Show password'">
                                <template x-if="show">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </template>
                                <template x-if="!show">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </template>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#1E40AF] focus:ring-[#1E40AF]">
                            <span class="text-sm text-gray-600" style="font-family: 'Roboto', sans-serif;">Remember me</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-[#1E40AF] hover:underline" style="font-family: 'Roboto', sans-serif;">Forgot Password?</a>
                        @endif
                    </div>

                    <button type="submit"
                            class="w-full py-3 rounded font-medium text-white bg-[#1E40AF] hover:bg-[#1E3A8A] focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2"
                            style="font-family: 'Roboto', sans-serif;">
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Sync portal selector with hidden form input --}}
    <script>
        document.addEventListener('alpine:initialized', function () {
            const form = document.getElementById('login-form');
            const portalInput = form && form.querySelector('#portal-type-input');
            const container = document.querySelector('[x-data*="portal"]');
            if (!form || !portalInput || !container) return;
            form.addEventListener('submit', function () {
                var root = container.__x;
                if (root && root.$data && root.$data.portal) {
                    portalInput.value = root.$data.portal;
                }
            });
        });
    </script>
</body>
</html>
