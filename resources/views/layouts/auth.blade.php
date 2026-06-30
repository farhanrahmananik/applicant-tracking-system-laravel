<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Sign in') | {{ config('app.name') }}</title>
    <script src="{{ asset('js/ats-theme.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/ats.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-shell">
        <header class="auth-header">
            <a class="ats-brand" href="{{ route('login') }}">
                <span class="ats-brand-mark"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                <span class="ats-brand-copy">
                    <strong>Applicant Tracking</strong>
                    <small>HR Workspace</small>
                </span>
            </a>
            <button
                class="app-icon-button theme-control"
                id="theme-toggle"
                type="button"
                data-theme-toggle
                aria-label="Switch color theme"
                title="Switch color theme"
            >
                <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
            </button>
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
