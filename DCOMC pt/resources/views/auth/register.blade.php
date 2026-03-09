<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - DCOMC</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <aside class="w-64 bg-blue-800 text-white flex flex-col">
        <div class="p-6 text-2xl font-bold border-b border-blue-700">
            Registrar Panel
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="/registrar/dashboard" class="block py-2.5 px-4 rounded bg-blue-700 transition">🏠 Dashboard</a>
            <a href="/registrar/registration" class="block py-2.5 px-4 rounded hover:bg-blue-700 transition">📝 Registration</a>
            <a href="/registrar/students" class="block py-2.5 px-4 rounded hover:bg-blue-700 transition">🎓 Students</a>
        </nav>
        <div class="p-4 border-t border-blue-700">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 py-2 rounded text-sm font-semibold transition">Log Out</button>
            </form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto">
        <header class="bg-white shadow p-4 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Dashboard Overview</h2>
            <div class="text-sm text-gray-500">Logged in as: <span class="font-bold text-blue-800">{{ Auth::user()->name }}</span></div>
        </header>

        <div class="p-8">
            <div class="bg-white p-6 rounded shadow-lg border-t-4 border-blue-800">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome, {{ Auth::user()->name }}!</h1>
                <p class="text-gray-600">You are securely logged into the Registrar Dashboard.</p>
                <p class="text-sm text-gray-400 mt-4">Use the sidebar to manage enrollments and view student records.</p>
            </div>
        </div>
    </main>

</body>
</html>