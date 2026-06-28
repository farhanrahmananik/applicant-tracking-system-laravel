<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Dashboard') | {{ config('app.name') }}</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link href="{{ asset('css/ats.css') }}" rel="stylesheet">
</head>
<body>
    <div class="app-shell">
        <aside class="app-sidebar">
            <a class="ats-brand" href="{{ route('dashboard') }}">
                <span class="ats-brand-mark">ATS</span>
                <span>Applicant Tracking</span>
            </a>

            <nav class="app-sidebar-nav" aria-label="Primary navigation">
                <div class="app-nav-label">Workspace</div>
                <a class="app-nav-link active" href="{{ route('dashboard') }}" aria-current="page">
                    Dashboard
                </a>
            </nav>
        </aside>

        <div class="app-frame">
            <header class="app-topbar">
                <div>
                    <div class="app-user-name">{{ auth()->user()->name }}</div>
                    <div class="app-user-email">{{ auth()->user()->email }}</div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                </form>
            </header>

            <main class="app-main">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
