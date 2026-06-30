@extends('layouts.app')

@section('title', 'Interviews')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Interviews</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Interview Scheduling</h1>
            <p class="page-subtitle">Coordinate upcoming conversations for active job applications.</p>
        </div>

        @can('interviews.create')
            <a class="btn btn-primary" href="{{ route('interviews.create') }}"><i class="bi bi-calendar-plus" aria-hidden="true"></i>Schedule interview</a>
        @endcan
    </header>

    <form class="interview-filter-toolbar" method="GET" action="{{ route('interviews.index') }}">
        <div class="interview-filter-search">
            <label class="visually-hidden" for="search">Search interviews</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Candidate, job title, or interviewer"
            >
        </div>
        <div>
            <label class="visually-hidden" for="type">Type</label>
            <select class="form-select" id="type" name="type">
                <option value="">All types</option>
                @foreach (App\Models\InterviewSchedule::TYPES as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>
                        {{ Illuminate\Support\Str::headline($type) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach (App\Models\InterviewSchedule::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="date_from">From date</label>
            <input class="form-control" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}" title="From date">
        </div>
        <div>
            <label class="visually-hidden" for="date_to">To date</label>
            <input class="form-control" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}" title="To date">
        </div>
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Filter</button>
        @if (collect(['search', 'type', 'status', 'date_from', 'date_to'])->contains(fn ($key) => request()->filled($key)))
            <a class="btn btn-link" href="{{ route('interviews.index') }}">Clear</a>
        @endif
    </form>

    <section class="data-panel" aria-label="Interview schedules">
        <div class="table-responsive">
            <table class="table resource-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Schedule</th>
                        <th scope="col">Candidate</th>
                        <th scope="col">Job posting</th>
                        <th scope="col">Interviewer</th>
                        <th scope="col">Type</th>
                        <th scope="col">Status</th>
                        <th class="text-end" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($interviews as $interview)
                        <tr>
                            <td>
                                <div class="interview-date-cell">
                                    <strong>{{ $interview->scheduled_at->format('M j, Y') }}</strong>
                                    <small>{{ $interview->scheduled_at->format('H:i') }} - {{ $interview->duration_minutes }} min</small>
                                </div>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $interview->application->candidate->full_name }}</span>
                                <small>{{ $interview->application->candidate->email }}</small>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $interview->application->jobPosting->title }}</span>
                                <small>{{ $interview->application->jobPosting->company->name }}</small>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $interview->interviewer->name }}</span>
                                <small>{{ $interview->interviewer->email }}</small>
                            </td>
                            <td>
                                <span class="interview-badge interview-type-{{ $interview->type }}">
                                    {{ Illuminate\Support\Str::headline($interview->type) }}
                                </span>
                            </td>
                            <td>
                                <span class="interview-badge interview-status-{{ $interview->status }}">
                                    {{ Illuminate\Support\Str::headline($interview->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary table-icon-action" href="{{ route('interviews.show', $interview) }}" aria-label="View interview #{{ $interview->id }}" title="View"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                    @can('interviews.update')
                                        <a class="btn btn-sm btn-outline-secondary table-icon-action" href="{{ route('interviews.edit', $interview) }}" aria-label="Edit interview #{{ $interview->id }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    @endcan
                                    @can('interviews.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('interviews.destroy', $interview) }}"
                                            onsubmit="return confirm('Delete this interview schedule? The record will be soft deleted.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger table-icon-action" type="submit" aria-label="Delete interview #{{ $interview->id }}" title="Delete"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-table-state" colspan="7">No interviews match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($interviews->hasPages())
        <div class="resource-pagination">
            {{ $interviews->links() }}
        </div>
    @endif
@endsection
