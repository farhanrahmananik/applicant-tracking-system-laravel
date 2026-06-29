@extends('layouts.app')

@section('title', 'Audit Event')

@section('content')
    <div class="audit-page-header">
        <div>
            <div class="page-kicker">Governance / Event #{{ $auditLog->id }}</div>
            <h1 class="page-title">Audit Event Detail</h1>
            <p class="page-subtitle">Recorded {{ $auditLog->created_at?->format('M j, Y \a\t H:i:s') }}.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('audit-logs.index') }}">Back to audit logs</a>
    </div>

    <div class="audit-event-strip">
        <div>
            <span>Action</span>
            <strong>
                <span class="audit-action-badge audit-action-{{ $auditLog->action }}">
                    {{ \Illuminate\Support\Str::headline($auditLog->action) }}
                </span>
            </strong>
        </div>
        <div>
            <span>Module</span>
            <strong>{{ \Illuminate\Support\Str::headline($auditLog->entity_type) }}</strong>
        </div>
        <div>
            <span>Entity ID</span>
            <strong>#{{ $auditLog->auditable_id ?? 'n/a' }}</strong>
        </div>
        <div>
            <span>Actor</span>
            <strong>{{ $auditLog->actor?->name ?? 'System' }}</strong>
            <small>{{ $auditLog->actor?->email }}</small>
        </div>
    </div>

    <section class="audit-detail-section">
        <div class="audit-detail-heading">
            <h2>Event summary</h2>
        </div>
        <p class="audit-summary-copy">{{ $auditLog->summary }}</p>
    </section>

    <div class="audit-diff-grid">
        <section class="audit-detail-section">
            <div class="audit-detail-heading">
                <h2>Previous values</h2>
                <span>{{ count($auditLog->old_values ?? []) }} fields</span>
            </div>
            @if ($auditLog->old_values)
                <pre class="audit-json-block">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <div class="audit-detail-empty">No previous values were recorded for this event.</div>
            @endif
        </section>

        <section class="audit-detail-section">
            <div class="audit-detail-heading">
                <h2>New values</h2>
                <span>{{ count($auditLog->new_values ?? []) }} fields</span>
            </div>
            @if ($auditLog->new_values)
                <pre class="audit-json-block">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <div class="audit-detail-empty">No new values were recorded for this event.</div>
            @endif
        </section>
    </div>

    <section class="audit-detail-section">
        <div class="audit-detail-heading">
            <h2>Request context</h2>
        </div>
        <dl class="audit-context-list">
            <div>
                <dt>IP address</dt>
                <dd>{{ $auditLog->ip_address ?? 'Not available' }}</dd>
            </div>
            <div>
                <dt>User agent</dt>
                <dd>{{ $auditLog->user_agent ?? 'Not available' }}</dd>
            </div>
            <div>
                <dt>Recorded at</dt>
                <dd>{{ $auditLog->created_at?->toIso8601String() }}</dd>
            </div>
        </dl>
    </section>
@endsection
