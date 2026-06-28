@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Applications</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Applications</h1>
            <p class="page-subtitle">Track candidates connected to current and past job postings.</p>
        </div>

        @can('applications.create')
            <a class="btn btn-primary" href="{{ route('applications.create') }}">Create application</a>
        @endcan
    </header>

    <form class="application-filter-toolbar" method="GET" action="{{ route('applications.index') }}">
        <div class="application-filter-search">
            <label class="visually-hidden" for="search">Search applications</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Candidate, email, or job title"
            >
        </div>
        <div>
            <label class="visually-hidden" for="current_status">Status</label>
            <select class="form-select" id="current_status" name="current_status">
                <option value="">All statuses</option>
                @foreach (App\Models\Application::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('current_status') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="job_posting_id">Job posting</label>
            <select class="form-select" id="job_posting_id" name="job_posting_id">
                <option value="">All job postings</option>
                @foreach ($jobPostings as $jobPosting)
                    <option value="{{ $jobPosting->id }}" @selected((string) request('job_posting_id') === (string) $jobPosting->id)>
                        {{ $jobPosting->title }} - {{ $jobPosting->company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="source">Source</label>
            <select class="form-select" id="source" name="source">
                <option value="">All sources</option>
                @foreach ($sources as $source)
                    <option value="{{ $source }}" @selected(request('source') === $source)>
                        {{ Illuminate\Support\Str::headline($source) }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-outline-secondary" type="submit">Filter</button>
        @if (collect(['search', 'current_status', 'job_posting_id', 'source'])->contains(fn ($key) => request()->filled($key)))
            <a class="btn btn-link" href="{{ route('applications.index') }}">Clear</a>
        @endif
    </form>

    <section class="data-panel" aria-label="Application records">
        <div class="table-responsive">
            <table class="table resource-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Candidate</th>
                        <th scope="col">Job posting</th>
                        <th scope="col">Status</th>
                        <th scope="col">Source</th>
                        <th scope="col">Applied</th>
                        <th class="text-end" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>
                                <div class="application-person-cell">
                                    <a href="{{ route('applications.show', $application) }}">{{ $application->candidate->full_name }}</a>
                                    <small>{{ $application->candidate->email }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $application->jobPosting->title }}</span>
                                <small>{{ $application->jobPosting->company->name }}</small>
                            </td>
                            <td>
                                <span class="application-badge application-status-{{ $application->current_status }}">
                                    {{ Illuminate\Support\Str::headline($application->current_status) }}
                                </span>
                            </td>
                            <td>{{ $application->source ? Illuminate\Support\Str::headline($application->source) : 'Not provided' }}</td>
                            <td class="text-nowrap">{{ $application->applied_date->format('M j, Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('applications.show', $application) }}">View</a>
                                    @can('applications.update')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('applications.edit', $application) }}">Edit</a>
                                    @endcan
                                    @can('applications.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('applications.destroy', $application) }}"
                                            onsubmit="return confirm('Delete this application? The record will be soft deleted.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-table-state" colspan="6">No applications match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($applications->hasPages())
        <div class="resource-pagination">
            {{ $applications->links() }}
        </div>
    @endif
@endsection
