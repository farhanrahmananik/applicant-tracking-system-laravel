@extends('layouts.app')

@section('title', $jobPosting->title)

@section('content')
    @php
        $salary = null;

        if ($jobPosting->salary_min !== null || $jobPosting->salary_max !== null) {
            $minimum = $jobPosting->salary_min !== null
                ? number_format((float) $jobPosting->salary_min, 0)
                : null;
            $maximum = $jobPosting->salary_max !== null
                ? number_format((float) $jobPosting->salary_max, 0)
                : null;
            $range = $minimum && $maximum ? "{$minimum} - {$maximum}" : ($minimum ?? $maximum);
            $salary = trim(($jobPosting->currency ? $jobPosting->currency.' ' : '').$range);
        }
    @endphp

    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('job-postings.index') }}">Job Postings</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $jobPosting->title }}</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Job posting</div>
            <h1 class="page-title">{{ $jobPosting->title }}</h1>
            <p class="page-subtitle">
                {{ $jobPosting->company->name }}
                @if ($jobPosting->department)
                    - {{ $jobPosting->department->name }}
                @endif
            </p>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('job-postings.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back</a>
            @can('job-postings.update')
                <a class="btn btn-primary" href="{{ route('job-postings.edit', $jobPosting) }}"><i class="bi bi-pencil-square" aria-hidden="true"></i>Edit job posting</a>
            @endcan
            @can('job-postings.delete')
                <form
                    method="POST"
                    action="{{ route('job-postings.destroy', $jobPosting) }}"
                    onsubmit="return confirm('Delete this job posting? The record will be soft deleted.')"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endcan
        </div>
    </header>

    <section class="job-summary-card" aria-label="Job posting summary">
        <div class="job-summary-primary">
            <span class="job-badge job-status-{{ $jobPosting->status }}">
                {{ Illuminate\Support\Str::headline($jobPosting->status) }}
            </span>
            <div>
                <strong>{{ Illuminate\Support\Str::headline($jobPosting->employment_type) }}</strong>
                <span>{{ Illuminate\Support\Str::headline($jobPosting->workplace_type) }}</span>
            </div>
        </div>
        <div class="job-summary-metrics">
            <div>
                <span>Openings</span>
                <strong>{{ number_format($jobPosting->openings) }}</strong>
            </div>
            <div>
                <span>Salary</span>
                <strong>{{ $salary ?? 'Not disclosed' }}</strong>
            </div>
            <div>
                <span>Experience</span>
                <strong>{{ $jobPosting->experience_level ?? 'Not specified' }}</strong>
            </div>
            <div>
                <span>Location</span>
                <strong>{{ $jobPosting->location ?? 'Not specified' }}</strong>
            </div>
        </div>
    </section>

    <div class="company-detail-grid">
        <section class="detail-section" aria-labelledby="job-organization-title">
            <div class="section-heading">
                <div>
                    <h2 id="job-organization-title">Organization</h2>
                    <p>Company and department ownership.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Company</dt>
                    <dd>
                        @can('companies.view')
                            <a class="resource-inline-link" href="{{ route('companies.show', $jobPosting->company) }}">
                                {{ $jobPosting->company->name }}
                            </a>
                        @else
                            {{ $jobPosting->company->name }}
                        @endcan
                    </dd>
                </div>
                <div>
                    <dt>Department</dt>
                    <dd>
                        @if ($jobPosting->department)
                            @can('departments.view')
                                <a class="resource-inline-link" href="{{ route('departments.show', $jobPosting->department) }}">
                                    {{ $jobPosting->department->name }}
                                </a>
                            @else
                                {{ $jobPosting->department->name }}
                            @endcan
                        @else
                            Not assigned
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Reference</dt>
                    <dd>{{ $jobPosting->slug }}</dd>
                </div>
            </dl>
        </section>

        <section class="detail-section" aria-labelledby="job-timeline-title">
            <div class="section-heading">
                <div>
                    <h2 id="job-timeline-title">Publication timeline</h2>
                    <p>Publishing and closing dates.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Published</dt>
                    <dd>{{ $jobPosting->published_at?->format('M j, Y H:i') ?? 'Not published' }}</dd>
                </div>
                <div>
                    <dt>Closes</dt>
                    <dd>{{ $jobPosting->closes_at?->format('M j, Y') ?? 'Open-ended' }}</dd>
                </div>
                <div>
                    <dt>Updated</dt>
                    <dd>{{ $jobPosting->updated_at->format('M j, Y H:i') }}</dd>
                </div>
            </dl>
        </section>
    </div>

    @foreach ([
        'description' => 'Description',
        'requirements' => 'Requirements',
        'responsibilities' => 'Responsibilities',
        'benefits' => 'Benefits',
    ] as $field => $heading)
        @if ($jobPosting->{$field})
            <section class="detail-section job-content-section" aria-labelledby="job-{{ $field }}-title">
                <div class="section-heading">
                    <div>
                        <h2 id="job-{{ $field }}-title">{{ $heading }}</h2>
                    </div>
                </div>
                <div class="job-content-copy">{{ $jobPosting->{$field} }}</div>
            </section>
        @endif
    @endforeach

    @can('applications.view')
        <section class="detail-section related-applications-section" aria-labelledby="job-applications-title">
            <div class="section-heading related-applications-heading">
                <div>
                    <h2 id="job-applications-title">Applications</h2>
                    <p>Candidates connected to this job posting.</p>
                </div>
                <div class="related-applications-actions">
                    <span class="section-status">{{ $jobPosting->applications_count }} total</span>
                    @can('applications.create')
                        <a class="btn btn-sm btn-primary" href="{{ route('applications.create', ['job_posting_id' => $jobPosting->id]) }}">Create application</a>
                    @endcan
                </div>
            </div>
            @include('applications._related-table', [
                'applications' => $jobApplications,
                'context' => 'job',
            ])
        </section>
    @endcan

    <section class="detail-section job-audit-strip" aria-label="Record ownership">
        <div>
            <span>Created by</span>
            <strong>{{ $jobPosting->createdBy?->name ?? 'System' }}</strong>
        </div>
        <div>
            <span>Updated by</span>
            <strong>{{ $jobPosting->updatedBy?->name ?? 'Not updated' }}</strong>
        </div>
        <div>
            <span>Created</span>
            <strong>{{ $jobPosting->created_at->format('M j, Y H:i') }}</strong>
        </div>
    </section>
@endsection
