@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
    <div class="audit-page-header">
        <div>
            <div class="page-kicker">Governance</div>
            <h1 class="page-title">Audit Logs</h1>
            <p class="page-subtitle">Administrative activity across ATS recruitment workflows.</p>
        </div>

        @can('export-audit-logs')
            <a class="btn btn-primary" href="{{ route('audit-logs.export', $filters) }}">
                Export CSV
            </a>
        @endcan
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mt-4" role="alert">
            Review the audit filters and try again.
        </div>
    @endif

    <form class="audit-filter-panel" method="GET" action="{{ route('audit-logs.index') }}">
        <div class="audit-filter-search">
            <label class="form-label" for="search">Keyword or entity ID</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ old('search', $filters['search'] ?? '') }}"
                placeholder="Search summary, actor, action..."
            >
        </div>

        <div>
            <label class="form-label" for="date_from">From date</label>
            <input
                class="form-control @error('date_from') is-invalid @enderror"
                id="date_from"
                name="date_from"
                type="date"
                value="{{ old('date_from', $filters['date_from'] ?? '') }}"
            >
        </div>

        <div>
            <label class="form-label" for="date_to">To date</label>
            <input
                class="form-control @error('date_to') is-invalid @enderror"
                id="date_to"
                name="date_to"
                type="date"
                value="{{ old('date_to', $filters['date_to'] ?? '') }}"
            >
        </div>

        <div>
            <label class="form-label" for="actor_id">Actor</label>
            <select class="form-select" id="actor_id" name="actor_id">
                <option value="">All actors</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}" @selected(($filters['actor_id'] ?? null) == $actor->id)>
                        {{ $actor->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label" for="action">Action</label>
            <select class="form-select" id="action" name="action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? null) === $action)>
                        {{ \Illuminate\Support\Str::headline($action) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label" for="auditable_type">Module</label>
            <select class="form-select" id="auditable_type" name="auditable_type">
                <option value="">All modules</option>
                @foreach ($auditableTypes as $type)
                    <option value="{{ $type }}" @selected(($filters['auditable_type'] ?? null) === $type)>
                        {{ \Illuminate\Support\Str::headline(class_basename($type)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="audit-filter-actions">
            <button class="btn btn-primary" type="submit">Apply filters</button>
            <a class="btn btn-outline-secondary" href="{{ route('audit-logs.index') }}">Reset</a>
        </div>
    </form>

    <section class="audit-ledger">
        <div class="audit-ledger-heading">
            <div>
                <h2>Activity ledger</h2>
                <p>Sanitized, append-only records for administrative actions.</p>
            </div>
            <span>{{ number_format($auditLogs->total()) }} events</span>
        </div>

        <div class="table-responsive">
            <table class="table audit-table mb-0">
                <thead>
                    <tr>
                        <th scope="col">Event</th>
                        <th scope="col">Actor</th>
                        <th scope="col">Entity</th>
                        <th scope="col">Summary</th>
                        <th scope="col">Timestamp</th>
                        <th class="text-end" scope="col"><span class="visually-hidden">View</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $auditLog)
                        <tr>
                            <td>
                                <span class="audit-action-badge audit-action-{{ $auditLog->action }}">
                                    {{ \Illuminate\Support\Str::headline($auditLog->action) }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $auditLog->actor?->name ?? 'System' }}</strong>
                                <small>{{ $auditLog->actor?->email ?? 'No authenticated actor' }}</small>
                            </td>
                            <td>
                                <span class="audit-entity-badge">
                                    {{ \Illuminate\Support\Str::headline($auditLog->entity_type) }}
                                </span>
                                <small>#{{ $auditLog->auditable_id ?? 'n/a' }}</small>
                            </td>
                            <td class="audit-summary-cell">{{ $auditLog->summary }}</td>
                            <td>
                                <time datetime="{{ $auditLog->created_at?->toIso8601String() }}">
                                    {{ $auditLog->created_at?->format('M j, Y') }}
                                    <small>{{ $auditLog->created_at?->format('H:i:s') }}</small>
                                </time>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('audit-logs.show', $auditLog) }}">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="audit-empty-state" colspan="6">
                                No audit activity matches the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($auditLogs->hasPages())
            <div class="audit-pagination">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </section>
@endsection
