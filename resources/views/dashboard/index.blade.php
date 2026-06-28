@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-kicker">Dashboard</div>
    <h1 class="page-title">Welcome, {{ auth()->user()->name }}</h1>
    <p class="page-subtitle">Your ATS workspace is ready.</p>

    <section class="status-panel" aria-label="Account status">
        <div class="status-row">
            <div class="status-label">Session</div>
            <div class="status-value">
                <span class="status-indicator"></span>Active
            </div>
        </div>
        <div class="status-row">
            <div class="status-label">Roles</div>
            <div class="status-value">
                @forelse (auth()->user()->roles as $role)
                    <span class="role-badge">{{ $role->name }}</span>
                @empty
                    <span class="text-secondary">No role assigned</span>
                @endforelse
            </div>
        </div>
        <div class="status-row">
            <div class="status-label">Last sign-in</div>
            <div class="status-value">
                {{ auth()->user()->last_login_at?->timezone(config('app.timezone'))->format('M j, Y H:i') ?? 'First session' }}
            </div>
        </div>
    </section>
@endsection
