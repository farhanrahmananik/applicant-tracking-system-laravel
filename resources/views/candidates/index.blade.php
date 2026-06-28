@extends('layouts.app')

@section('title', 'Candidates')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Candidates</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Candidates</h1>
            <p class="page-subtitle">Manage candidate profiles and professional information.</p>
        </div>

        @can('candidates.create')
            <a class="btn btn-primary" href="{{ route('candidates.create') }}">Add candidate</a>
        @endcan
    </header>

    <form class="company-toolbar candidate-filter-toolbar" method="GET" action="{{ route('candidates.index') }}">
        <div class="candidate-filter-search">
            <label class="visually-hidden" for="search">Search candidates</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Search name, email, skills, or position"
            >
        </div>
        <div>
            <label class="visually-hidden" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach (App\Models\Candidate::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
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
        <div>
            <label class="visually-hidden" for="availability">Availability</label>
            <select class="form-select" id="availability" name="availability">
                <option value="">All availability</option>
                @foreach ($availabilities as $availability)
                    <option value="{{ $availability }}" @selected(request('availability') === $availability)>
                        {{ Illuminate\Support\Str::headline($availability) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="experience_min">Minimum experience</label>
            <input
                class="form-control"
                id="experience_min"
                name="experience_min"
                type="number"
                value="{{ request('experience_min') }}"
                min="0"
                step="0.1"
                placeholder="Min years"
            >
        </div>
        <div>
            <label class="visually-hidden" for="experience_max">Maximum experience</label>
            <input
                class="form-control"
                id="experience_max"
                name="experience_max"
                type="number"
                value="{{ request('experience_max') }}"
                min="0"
                step="0.1"
                placeholder="Max years"
            >
        </div>
        <button class="btn btn-outline-secondary" type="submit">Filter</button>
        @if (collect(['search', 'status', 'source', 'availability', 'experience_min', 'experience_max'])->contains(fn ($key) => request()->filled($key)))
            <a class="btn btn-link" href="{{ route('candidates.index') }}">Clear</a>
        @endif
    </form>

    <section class="data-panel" aria-label="Candidate records">
        <div class="table-responsive">
            <table class="table resource-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Candidate</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Current position</th>
                        <th scope="col">Experience</th>
                        <th scope="col">Source</th>
                        <th scope="col">Availability</th>
                        <th scope="col">Status</th>
                        <th class="text-end" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($candidates as $candidate)
                        <tr>
                            <td>
                                <div class="candidate-name-cell">
                                    <span class="candidate-avatar" aria-hidden="true">
                                        {{ strtoupper(Illuminate\Support\Str::substr($candidate->first_name, 0, 1).Illuminate\Support\Str::substr($candidate->last_name ?? '', 0, 1)) }}
                                    </span>
                                    <span>
                                        <a href="{{ route('candidates.show', $candidate) }}">{{ $candidate->full_name }}</a>
                                        <small>{{ $candidate->location ?? 'Location not set' }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $candidate->email }}</span>
                                <small>{{ $candidate->phone ?? 'No phone' }}</small>
                            </td>
                            <td>{{ $candidate->current_position ?? 'Not provided' }}</td>
                            <td>{{ $candidate->experience_years !== null ? $candidate->experience_years.' years' : 'Not provided' }}</td>
                            <td>
                                @if ($candidate->source)
                                    <span class="candidate-badge candidate-badge-source">
                                        {{ Illuminate\Support\Str::headline($candidate->source) }}
                                    </span>
                                @else
                                    <span class="text-secondary">Not provided</span>
                                @endif
                            </td>
                            <td>
                                @if ($candidate->availability)
                                    <span class="candidate-badge candidate-badge-availability">
                                        {{ Illuminate\Support\Str::headline($candidate->availability) }}
                                    </span>
                                @else
                                    <span class="text-secondary">Not provided</span>
                                @endif
                            </td>
                            <td>
                                <span class="candidate-badge candidate-status-{{ $candidate->status }}">
                                    {{ Illuminate\Support\Str::headline($candidate->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('candidates.show', $candidate) }}">View</a>
                                    @can('candidates.edit')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('candidates.edit', $candidate) }}">Edit</a>
                                    @endcan
                                    @can('candidates.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('candidates.destroy', $candidate) }}"
                                            onsubmit="return confirm('Delete this candidate? The record will be soft deleted.')"
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
                            <td class="empty-table-state" colspan="8">No candidates match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($candidates->hasPages())
        <div class="resource-pagination">
            {{ $candidates->links() }}
        </div>
    @endif
@endsection
