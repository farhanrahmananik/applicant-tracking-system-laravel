<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Sign in') | {{ config('app.name') }}</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link href="{{ asset('css/ats.css') }}" rel="stylesheet">
</head>
<body>
    <div class="auth-shell">
        <header class="auth-header">
            <a class="ats-brand" href="{{ route('login') }}">
                <span class="ats-brand-mark">ATS</span>
                <span>{{ config('app.name') }}</span>
            </a>
        </header>

        <main class="auth-main">
            @yield('content')
        </main>

        <footer class="auth-footer">
            &copy; {{ now()->year }} {{ config('app.name') }}
        </footer>
    </div>
</body>
</html>
