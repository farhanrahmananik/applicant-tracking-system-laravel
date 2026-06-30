@extends('layouts.app')

@section('title', 'Job Postings')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Job Postings</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Job Postings</h1>
            <p class="page-subtitle">Manage vacancies across companies and departments.</p>
        </div>

        @can('job-postings.create')
            <a class="btn btn-primary" href="{{ route('job-postings.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>Create job posting</a>
        @endcan
    </header>

    <form class="company-toolbar job-filter-toolbar" method="GET" action="{{ route('job-postings.index') }}">
        <div class="job-filter-search">
            <label class="visually-hidden" for="search">Search job postings</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Search by job title"
            >
        </div>
        <div>
            <label class="visually-hidden" for="company_id">Company</label>
            <select
                class="form-select"
                id="company_id"
                name="company_id"
                data-company-select
                data-department-target="department_id"
            >
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="department_id">Department</label>
            <select class="form-select" id="department_id" name="department_id">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option
                        value="{{ $department->id }}"
                        data-company-id="{{ $department->company_id }}"
                        @selected((string) request('department_id') === (string) $department->id)
                    >
                        {{ $department->company->name }} - {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach (App\Models\JobPosting::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="employment_type">Employment type</label>
            <select class="form-select" id="employment_type" name="employment_type">
                <option value="">All employment types</option>
                @foreach (App\Models\JobPosting::EMPLOYMENT_TYPES as $employmentType)
                    <option value="{{ $employmentType }}" @selected(request('employment_type') === $employmentType)>
                        {{ Illuminate\Support\Str::headline($employmentType) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="visually-hidden" for="workplace_type">Workplace type</label>
            <select class="form-select" id="workplace_type" name="workplace_type">
                <option value="">All workplace types</option>
                @foreach (App\Models\JobPosting::WORKPLACE_TYPES as $workplaceType)
                    <option value="{{ $workplaceType }}" @selected(request('workplace_type') === $workplaceType)>
                        {{ Illuminate\Support\Str::headline($workplaceType) }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Filter</button>
        @if (collect(['search', 'company_id', 'department_id', 'status', 'employment_type', 'workplace_type'])->contains(fn ($key) => request()->filled($key)))
            <a class="btn btn-link" href="{{ route('job-postings.index') }}">Clear</a>
        @endif
    </form>

    <section class="data-panel" aria-label="Job posting records">
        <div class="table-responsive">
            <table class="table resource-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Position</th>
                        <th scope="col">Organization</th>
                        <th scope="col">Status</th>
                        <th scope="col">Arrangement</th>
                        <th scope="col">Openings</th>
                        <th scope="col">Closes</th>
                        <th class="text-end" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobPostings as $jobPosting)
                        <tr>
                            <td>
                                <div class="job-title-cell">
                                    <a href="{{ route('job-postings.show', $jobPosting) }}">{{ $jobPosting->title }}</a>
                                    <small>{{ $jobPosting->location ?? 'Location not set' }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $jobPosting->company->name }}</span>
                                <small>{{ $jobPosting->department?->name ?? 'No department' }}</small>
                            </td>
                            <td>
                                <span class="job-badge job-status-{{ $jobPosting->status }}">
                                    {{ Illuminate\Support\Str::headline($jobPosting->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="job-badge job-badge-neutral">
                                    {{ Illuminate\Support\Str::headline($jobPosting->employment_type) }}
                                </span>
                                <small>{{ Illuminate\Support\Str::headline($jobPosting->workplace_type) }}</small>
                            </td>
                            <td>{{ number_format($jobPosting->openings) }}</td>
                            <td class="text-nowrap">{{ $jobPosting->closes_at?->format('M j, Y') ?? 'Open-ended' }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary table-icon-action" href="{{ route('job-postings.show', $jobPosting) }}" aria-label="View {{ $jobPosting->title }}" title="View"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                    @can('job-postings.update')
                                        <a class="btn btn-sm btn-outline-secondary table-icon-action" href="{{ route('job-postings.edit', $jobPosting) }}" aria-label="Edit {{ $jobPosting->title }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    @endcan
                                    @can('job-postings.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('job-postings.destroy', $jobPosting) }}"
                                            onsubmit="return confirm('Delete this job posting? The record will be soft deleted.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger table-icon-action" type="submit" aria-label="Delete {{ $jobPosting->title }}" title="Delete"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-table-state" colspan="7">No job postings match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($jobPostings->hasPages())
        <div class="resource-pagination">
            {{ $jobPostings->links() }}
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/job-posting-form.js') }}"></script>
@endpush
