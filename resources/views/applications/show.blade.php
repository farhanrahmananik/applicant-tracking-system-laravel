@extends('layouts.app')

@section('title', $application->candidate->full_name.' Application')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Applications</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $application->candidate->full_name }}</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Application</div>
            <h1 class="page-title">{{ $application->candidate->full_name }}</h1>
            <p class="page-subtitle">{{ $application->jobPosting->title }} at {{ $application->jobPosting->company->name }}</p>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('applications.index') }}">Back</a>
            @can('applications.update')
                <a class="btn btn-primary" href="{{ route('applications.edit', $application) }}">Edit application</a>
            @endcan
            @can('applications.delete')
                <form
                    method="POST"
                    action="{{ route('applications.destroy', $application) }}"
                    onsubmit="return confirm('Delete this application? The record will be soft deleted.')"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endcan
        </div>
    </header>

    <section class="application-summary-card" aria-label="Application summary">
        <div>
            <span>Status</span>
            <strong class="application-badge application-status-{{ $application->current_status }}">
                {{ Illuminate\Support\Str::headline($application->current_status) }}
            </strong>
        </div>
        <div>
            <span>Applied</span>
            <strong>{{ $application->applied_date->format('M j, Y') }}</strong>
        </div>
        <div>
            <span>Source</span>
            <strong>{{ $application->source ? Illuminate\Support\Str::headline($application->source) : 'Not provided' }}</strong>
        </div>
        <div>
            <span>Last updated</span>
            <strong>{{ $application->updated_at->format('M j, Y H:i') }}</strong>
        </div>
    </section>

    <div class="company-detail-grid">
        <section class="detail-section" aria-labelledby="application-candidate-title">
            <div class="section-heading">
                <div>
                    <h2 id="application-candidate-title">Candidate</h2>
                    <p>Candidate identity and contact details.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Name</dt>
                    <dd>
                        @can('candidates.view')
                            <a class="resource-inline-link" href="{{ route('candidates.show', $application->candidate) }}">
                                {{ $application->candidate->full_name }}
                            </a>
                        @else
                            {{ $application->candidate->full_name }}
                        @endcan
                    </dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>{{ $application->candidate->email }}</dd>
                </div>
                <div>
                    <dt>Current position</dt>
                    <dd>{{ $application->candidate->current_position ?? 'Not provided' }}</dd>
                </div>
            </dl>
        </section>

        <section class="detail-section" aria-labelledby="application-job-title">
            <div class="section-heading">
                <div>
                    <h2 id="application-job-title">Job posting</h2>
                    <p>Position and organizational context.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Position</dt>
                    <dd>
                        @can('job-postings.view')
                            <a class="resource-inline-link" href="{{ route('job-postings.show', $application->jobPosting) }}">
                                {{ $application->jobPosting->title }}
                            </a>
                        @else
                            {{ $application->jobPosting->title }}
                        @endcan
                    </dd>
                </div>
                <div>
                    <dt>Company</dt>
                    <dd>{{ $application->jobPosting->company->name }}</dd>
                </div>
                <div>
                    <dt>Department</dt>
                    <dd>{{ $application->jobPosting->department?->name ?? 'Not assigned' }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="detail-section application-notes-section" aria-labelledby="application-notes-title">
        <div class="section-heading">
            <div>
                <h2 id="application-notes-title">Notes</h2>
                <p>Internal recruiting context for this application.</p>
            </div>
        </div>
        <div class="application-notes-copy">{{ $application->notes ?? 'No notes have been added.' }}</div>
    </section>

    <section class="detail-section job-audit-strip" aria-label="Record ownership">
        <div>
            <span>Created by</span>
            <strong>{{ $application->createdBy?->name ?? 'System' }}</strong>
        </div>
        <div>
            <span>Updated by</span>
            <strong>{{ $application->updatedBy?->name ?? 'Not updated' }}</strong>
        </div>
        <div>
            <span>Created</span>
            <strong>{{ $application->created_at->format('M j, Y H:i') }}</strong>
        </div>
    </section>
@endsection
