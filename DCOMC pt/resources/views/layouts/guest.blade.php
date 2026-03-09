<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/css/bootstrap-override.css', 'resources/js/app.js'])
    </head>
    <body class="bg-light min-vh-100 d-flex flex-column justify-content-center align-items-center pt-4 pb-4">
        <div>
            <a href="/">
                <x-application-logo class="d-block" style="width:5rem;height:5rem;fill:currentColor;" />
            </a>
        </div>
        <div class="container mt-4" style="max-width: 28rem;">
            <div class="card shadow-sm overflow-hidden">
                <div class="card-body p-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
