@props(['title' => 'Dashboard'])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Salamaid Admin</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="admin-body">
    <div class="admin-app">
        <x-admin.sidebar />

        <main class="admin-main">
            <x-admin.topbar :title="$title" />

            <div class="admin-content">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('modals')
</body>
</html>
