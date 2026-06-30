<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Dashboard') | {{ config('app.name') }}</title>
    <script src="{{ asset('js/ats-theme.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/ats.css') }}" rel="stylesheet">
</head>
<body class="app-body">
    <div class="app-shell">
        <div class="app-sidebar-backdrop" data-sidebar-backdrop></div>

        <aside class="app-sidebar" id="app-sidebar" data-app-sidebar>
            <div class="app-sidebar-header">
                <a class="ats-brand" href="{{ route('dashboard') }}">
                    <span class="ats-brand-mark"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                    <span class="ats-brand-copy">
                        <strong>Applicant Tracking</strong>
                        <small>HR Workspace</small>
                    </span>
                </a>
                <button
                    class="app-icon-button sidebar-close-button"
                    type="button"
                    data-sidebar-toggle
                    aria-label="Close navigation"
                    aria-expanded="false"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <nav class="app-sidebar-nav" aria-label="Primary navigation">
                <div class="app-nav-label">Workspace</div>
                <a
                    class="app-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}"
                    @if (request()->routeIs('dashboard')) aria-current="page" @endif
                >
                    <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>

                @canany(['companies.view', 'departments.view'])
                    <div class="app-nav-label">Organization</div>
                @endcanany

                @can('companies.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}"
                        href="{{ route('companies.index') }}"
                        @if (request()->routeIs('companies.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-buildings" aria-hidden="true"></i>
                        <span>Companies</span>
                    </a>
                @endcan

                @can('departments.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}"
                        href="{{ route('departments.index') }}"
                        @if (request()->routeIs('departments.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-diagram-3" aria-hidden="true"></i>
                        <span>Departments</span>
                    </a>
                @endcan

                @canany(['job-postings.view', 'candidates.view', 'applications.view', 'interviews.view', 'pipeline.view', 'offers.view'])
                    <div class="app-nav-label">Recruitment</div>
                @endcanany

                @can('job-postings.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('job-postings.*') ? 'active' : '' }}"
                        href="{{ route('job-postings.index') }}"
                        @if (request()->routeIs('job-postings.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-briefcase" aria-hidden="true"></i>
                        <span>Job Postings</span>
                    </a>
                @endcan

                @can('candidates.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('candidates.*') ? 'active' : '' }}"
                        href="{{ route('candidates.index') }}"
                        @if (request()->routeIs('candidates.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-person-vcard" aria-hidden="true"></i>
                        <span>Candidates</span>
                    </a>
                @endcan

                @can('applications.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('applications.*') ? 'active' : '' }}"
                        href="{{ route('applications.index') }}"
                        @if (request()->routeIs('applications.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        <span>Applications</span>
                    </a>
                @endcan

                @can('pipeline.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('pipeline.*') ? 'active' : '' }}"
                        href="{{ route('pipeline.index') }}"
                        @if (request()->routeIs('pipeline.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-kanban" aria-hidden="true"></i>
                        <span>Hiring Pipeline</span>
                    </a>
                @endcan

                @can('offers.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('offers.*') ? 'active' : '' }}"
                        href="{{ route('offers.index') }}"
                        @if (request()->routeIs('offers.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-envelope-paper" aria-hidden="true"></i>
                        <span>Offers</span>
                    </a>
                @endcan

                @can('interviews.view')
                    <a
                        class="app-nav-link {{ request()->routeIs('interviews.*') ? 'active' : '' }}"
                        href="{{ route('interviews.index') }}"
                        @if (request()->routeIs('interviews.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                        <span>Interviews</span>
                    </a>
                @endcan

                @can('reports.view')
                    <div class="app-nav-label">Insights</div>
                    <a
                        class="app-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                        href="{{ route('reports.index') }}"
                        @if (request()->routeIs('reports.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        <span>Reports</span>
                    </a>
                @endcan

                @can('view-audit-logs')
                    <div class="app-nav-label">Governance</div>
                    <a
                        class="app-nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}"
                        href="{{ route('audit-logs.index') }}"
                        @if (request()->routeIs('audit-logs.*')) aria-current="page" @endif
                    >
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <span>Audit Logs</span>
                    </a>
                @endcan
            </nav>

            <div class="app-sidebar-footer">
                <span class="system-status-dot" aria-hidden="true"></span>
                <span>
                    <strong>ATS Workspace</strong>
                    <small>All systems operational</small>
                </span>
            </div>
        </aside>

        <div class="app-frame">
            <header class="app-topbar">
                <div class="app-topbar-inner">
                    <div class="topbar-context">
                        <button
                            class="app-icon-button sidebar-menu-button"
                            type="button"
                            data-sidebar-toggle
                            aria-controls="app-sidebar"
                            aria-label="Open navigation"
                            aria-expanded="false"
                        >
                            <i class="bi bi-list" aria-hidden="true"></i>
                        </button>
                        <div>
                            <span>ATS Workspace</span>
                            <strong>@yield('title', 'Dashboard')</strong>
                        </div>
                    </div>

                    <div class="topbar-actions">
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

                        <div class="topbar-divider" aria-hidden="true"></div>

                        <div class="app-user-profile">
                            <span class="app-user-avatar" aria-hidden="true">
                                {{ strtoupper(Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="app-user-copy">
                                <strong class="app-user-name">{{ auth()->user()->name }}</strong>
                                <small class="app-user-email">{{ auth()->user()->email }}</small>
                            </span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="app-icon-button" type="submit" aria-label="Sign out" title="Sign out">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="app-main">
                @if (session('success'))
                    <div class="alert alert-success app-alert" role="status">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
