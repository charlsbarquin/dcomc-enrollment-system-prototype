<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
    @stack('styles')
</head>
<body class="dashboard-wrap bg-[#F1F5F9] min-h-screen overflow-x-hidden text-gray-800 font-data">
    @include('dashboards.partials.admin-sidebar')

    {{-- Page-level loading indicator: hidden when DOM is ready --}}
    <div id="admin-page-loading" class="admin-loading-bar" aria-hidden="true"></div>
    <script>
        (function(){ var el = document.getElementById('admin-page-loading'); if(el) document.addEventListener('DOMContentLoaded', function(){ el.classList.add('admin-loading-done'); }); })();
    </script>

    <main class="dashboard-main flex flex-col overflow-hidden">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
