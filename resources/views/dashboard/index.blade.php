@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $totalUsers = $metrics['total_users']['value'];
        $upcomingModules = [
            ['name' => 'Company', 'summary' => 'Organization profiles and account settings.'],
            ['name' => 'Department', 'summary' => 'Teams, ownership, and reporting structure.'],
            ['name' => 'Job Posting', 'summary' => 'Vacancies, publishing, and hiring ownership.'],
            ['name' => 'Candidate', 'summary' => 'Candidate records, profiles, and applications.'],
            ['name' => 'Interview', 'summary' => 'Scheduling, panels, and structured feedback.'],
            ['name' => 'Reports', 'summary' => 'Recruitment activity and pipeline insights.'],
        ];
    @endphp

    <header class="dashboard-page-header">
        <div>
            <div class="page-kicker">Workspace overview</div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Authentication and access activity across the ATS.</p>
        </div>
        <time class="dashboard-date" datetime="{{ now()->toDateString() }}">
            {{ now()->format('D, M j, Y') }}
        </time>
    </header>

    <section class="welcome-card" aria-labelledby="welcome-title">
        <div>
            <div class="welcome-eyebrow">Signed in as {{ auth()->user()->email }}</div>
            <h2 id="welcome-title">Welcome back, {{ auth()->user()->name }}</h2>
            <p>Your workspace reflects the current authentication and authorization foundation.</p>
        </div>
        <div class="welcome-access">
            <span class="welcome-access-label">Access</span>
            <div>
                @forelse (auth()->user()->roles as $role)
                    <span class="role-badge">{{ $role->name }}</span>
                @empty
                    <span class="text-secondary">No role assigned</span>
                @endforelse
            </div>
        </div>
    </section>

    <section class="dashboard-section" aria-labelledby="metrics-title">
        <div class="section-heading">
            <div>
                <h2 id="metrics-title">Account metrics</h2>
                <p>Current totals from the authentication and RBAC tables.</p>
            </div>
        </div>

        <div class="metric-grid">
            @foreach ($metrics as $key => $metric)
                <article class="metric-card metric-card-{{ $key }}">
                    <div class="metric-label">{{ $metric['label'] }}</div>
                    <div class="metric-value">{{ number_format($metric['value']) }}</div>
                    <div class="metric-context">{{ $metric['context'] }}</div>
                </article>
            @endforeach
        </div>
    </section>

    <div class="dashboard-detail-grid">
        <section class="dashboard-section" aria-labelledby="recent-users-title">
            <div class="section-heading">
                <div>
                    <h2 id="recent-users-title">Recent users</h2>
                    <p>Latest registered accounts and their access state.</p>
                </div>
            </div>

            <div class="table-responsive dashboard-table-wrap">
                <table class="table dashboard-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentUsers as $user)
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <span class="user-avatar" aria-hidden="true">
                                            {{ strtoupper(Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                                        </span>
                                        <span>
                                            <span class="user-cell-name">{{ $user->name }}</span>
                                            <span class="user-cell-email">{{ $user->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @forelse ($user->roles as $role)
                                        <span class="table-role">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-secondary">Unassigned</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="status-pill {{ $user->is_active ? 'status-pill-active' : 'status-pill-inactive' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    {{ $user->created_at?->timezone(config('app.timezone'))->format('M j, Y') ?? 'Unknown' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="empty-table-state" colspan="4">No users available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-section" aria-labelledby="role-distribution-title">
            <div class="section-heading">
                <div>
                    <h2 id="role-distribution-title">Role distribution</h2>
                    <p>User assignments may include more than one role.</p>
                </div>
            </div>

            <div class="role-distribution-list">
                @forelse ($roleDistribution as $role)
                    @php
                        $percentage = $totalUsers > 0
                            ? min(100, (int) round(($role->users_count / $totalUsers) * 100))
                            : 0;
                    @endphp
                    <div class="role-distribution-item">
                        <div class="role-distribution-meta">
                            <span>{{ $role->name }}</span>
                            <span>{{ number_format($role->users_count) }}</span>
                        </div>
                        <div
                            class="role-progress"
                            role="progressbar"
                            aria-label="{{ $role->name }} users"
                            aria-valuenow="{{ $percentage }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <span style="width: {{ $percentage }}%"></span>
                        </div>
                    </div>
                @empty
                    <div class="empty-section-state">No roles have been configured.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="dashboard-section upcoming-section" aria-labelledby="upcoming-modules-title">
        <div class="section-heading">
            <div>
                <h2 id="upcoming-modules-title">Upcoming ATS Modules</h2>
                <p>Planned recruitment capabilities.</p>
            </div>
            <span class="section-status">Roadmap</span>
        </div>

        <div class="module-grid">
            @foreach ($upcomingModules as $module)
                <article class="module-card">
                    <div class="module-card-header">
                        <h3>{{ $module['name'] }}</h3>
                        <span>Planned</span>
                    </div>
                    <p>{{ $module['summary'] }}</p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
