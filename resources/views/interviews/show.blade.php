@extends('layouts.app')

@section('title', $interview->application->candidate->full_name.' Interview')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.index') }}">Interviews</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $interview->application->candidate->full_name }}</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Interview schedule</div>
            <h1 class="page-title">{{ $interview->application->candidate->full_name }}</h1>
            <p class="page-subtitle">{{ $interview->application->jobPosting->title }} at {{ $interview->application->jobPosting->company->name }}</p>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('interviews.index') }}">Back</a>
            @can('interviews.update')
                <a class="btn btn-primary" href="{{ route('interviews.edit', $interview) }}">Edit interview</a>
            @endcan
            @can('interviews.delete')
                <form
                    method="POST"
                    action="{{ route('interviews.destroy', $interview) }}"
                    onsubmit="return confirm('Delete this interview schedule? The record will be soft deleted.')"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endcan
        </div>
    </header>

    <section class="interview-summary-card" aria-label="Interview summary">
        <div>
            <span>Scheduled</span>
            <strong>{{ $interview->scheduled_at->format('M j, Y H:i') }}</strong>
        </div>
        <div>
            <span>Duration</span>
            <strong>{{ $interview->duration_minutes }} minutes</strong>
        </div>
        <div>
            <span>Type</span>
            <strong class="interview-badge interview-type-{{ $interview->type }}">
                {{ Illuminate\Support\Str::headline($interview->type) }}
            </strong>
        </div>
        <div>
            <span>Status</span>
            <strong class="interview-badge interview-status-{{ $interview->status }}">
                {{ Illuminate\Support\Str::headline($interview->status) }}
            </strong>
        </div>
    </section>

    <div class="company-detail-grid">
        <section class="detail-section" aria-labelledby="interview-application-title">
            <div class="section-heading">
                <div>
                    <h2 id="interview-application-title">Application</h2>
                    <p>Candidate and position connected to this schedule.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Candidate</dt>
                    <dd>{{ $interview->application->candidate->full_name }}</dd>
                </div>
                <div>
                    <dt>Job posting</dt>
                    <dd>{{ $interview->application->jobPosting->title }}</dd>
                </div>
                <div>
                    <dt>Application status</dt>
                    <dd>
                        <span class="application-badge application-status-{{ $interview->application->current_status }}">
                            {{ Illuminate\Support\Str::headline($interview->application->current_status) }}
                        </span>
                    </dd>
                </div>
                @can('applications.view')
                    <div>
                        <dt>Application record</dt>
                        <dd><a class="resource-inline-link" href="{{ route('applications.show', $interview->application) }}">View application</a></dd>
                    </div>
                @endcan
            </dl>
        </section>

        <section class="detail-section" aria-labelledby="interview-owner-title">
            <div class="section-heading">
                <div>
                    <h2 id="interview-owner-title">Interviewer</h2>
                    <p>Assigned ATS team member and joining details.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Name</dt>
                    <dd>{{ $interview->interviewer->name }}</dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>{{ $interview->interviewer->email }}</dd>
                </div>
                <div>
                    <dt>Location</dt>
                    <dd>{{ $interview->location ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Meeting link</dt>
                    <dd>
                        @if ($interview->meeting_link)
                            <a class="resource-inline-link" href="{{ $interview->meeting_link }}" target="_blank" rel="noopener noreferrer">Open meeting</a>
                        @else
                            Not provided
                        @endif
                    </dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="detail-section interview-notes-section" aria-labelledby="interview-notes-title">
        <div class="section-heading">
            <div>
                <h2 id="interview-notes-title">Scheduling notes</h2>
                <p>Internal context for this interview appointment.</p>
            </div>
        </div>
        <div class="interview-notes-copy">{{ $interview->notes ?? 'No scheduling notes have been added.' }}</div>
    </section>

    <section class="detail-section job-audit-strip" aria-label="Record ownership">
        <div>
            <span>Created by</span>
            <strong>{{ $interview->createdBy?->name ?? 'System' }}</strong>
        </div>
        <div>
            <span>Updated by</span>
            <strong>{{ $interview->updatedBy?->name ?? 'Not updated' }}</strong>
        </div>
        <div>
            <span>Created</span>
            <strong>{{ $interview->created_at->format('M j, Y H:i') }}</strong>
        </div>
    </section>
@endsection
