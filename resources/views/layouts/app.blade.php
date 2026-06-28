<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Dashboard') | {{ config('app.name') }}</title>
    <script src="{{ asset('js/ats-theme.js') }}"></script>
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
                <a
                    class="app-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}"
                    @if (request()->routeIs('dashboard')) aria-current="page" @endif
                >
                    Dashboard
                </a>

                @can('companies.view')
                    <div class="app-nav-label">Organization</div>
                    <a
                        class="app-nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}"
                        href="{{ route('companies.index') }}"
                        @if (request()->routeIs('companies.*')) aria-current="page" @endif
                    >
                        Companies
                    </a>
                @endcan
            </nav>
        </aside>

        <div class="app-frame">
            <header class="app-topbar">
                <div>
                    <div class="app-user-name">{{ auth()->user()->name }}</div>
                    <div class="app-user-email">{{ auth()->user()->email }}</div>
                </div>

                <div class="topbar-actions">
                    <label class="theme-control" for="theme-toggle">
                        <span>Dark mode</span>
                        <span class="form-check form-switch m-0">
                            <input
                                class="form-check-input"
                                id="theme-toggle"
                                type="checkbox"
                                role="switch"
                                data-theme-toggle
                            >
                        </span>
                    </label>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                    </form>
                </div>
            </header>

            <main class="app-main">
                @if (session('success'))
                    <div class="alert alert-success app-alert" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
