<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/css/bootstrap-override.css', 'resources/js/app.js'])
    </head>
    <body class="bg-light min-vh-100">
        @include('layouts.navigation')
        @isset($header)
            <header class="bg-white border-bottom shadow-sm">
                <div class="container-fluid py-3 px-4">
                    {{ $header }}
                </div>
            </header>
        @endisset
        <main class="py-4">
            <div class="container-fluid px-4">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
