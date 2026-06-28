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

    @can('interviews.view')
        <section class="detail-section application-interviews-section" aria-labelledby="application-interviews-title">
            <div class="section-heading application-interviews-heading">
                <div>
                    <h2 id="application-interviews-title">Interviews</h2>
                    <p>Scheduled conversations connected to this application.</p>
                </div>
                <div class="application-interviews-actions">
                    <span class="section-status">{{ $application->interview_schedules_count }} total</span>
                    @can('interviews.create')
                        @unless ($application->isTerminal())
                            <a class="btn btn-sm btn-primary" href="{{ route('interviews.create', ['application_id' => $application->id]) }}">Schedule interview</a>
                        @endunless
                    @endcan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table related-interview-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Schedule</th>
                            <th scope="col">Interviewer</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                            <th class="text-end" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applicationInterviews as $relatedInterview)
                            <tr>
                                <td>
                                    <span class="table-primary-value">{{ $relatedInterview->scheduled_at->format('M j, Y') }}</span>
                                    <small>{{ $relatedInterview->scheduled_at->format('H:i') }} - {{ $relatedInterview->duration_minutes }} min</small>
                                </td>
                                <td>
                                    <span class="table-primary-value">{{ $relatedInterview->interviewer->name }}</span>
                                    <small>{{ $relatedInterview->interviewer->email }}</small>
                                </td>
                                <td>
                                    <span class="interview-badge interview-type-{{ $relatedInterview->type }}">
                                        {{ Illuminate\Support\Str::headline($relatedInterview->type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="interview-badge interview-status-{{ $relatedInterview->status }}">
                                        {{ Illuminate\Support\Str::headline($relatedInterview->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('interviews.show', $relatedInterview) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="empty-table-state" colspan="5">No interviews have been scheduled.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endcan

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
