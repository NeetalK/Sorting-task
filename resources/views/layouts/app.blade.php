<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Task Manager') · Task Manager</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    <main class="page-shell">
        <header class="site-header">
            <a class="brand" href="{{ route('tasks.index') }}">
                <span class="brand-mark">✓</span>
                <span>Task Manager</span>
            </a>
            <span class="header-note">A little more focus, every day.</span>
        </header>

        @if (session('status'))
            <div class="notice" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice notice-error" role="alert">
                <p>Please fix the following:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')

        <footer class="footer">Your tasks, in your order.</footer>
    </main>
</body>
</html>
