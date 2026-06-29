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
            <span>Pipeline stage</span>
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

    @can('pipeline.view')
        <div class="pipeline-detail-grid">
            <section class="detail-section pipeline-move-section" aria-labelledby="pipeline-move-title">
                <div class="section-heading">
                    <div>
                        <h2 id="pipeline-move-title">Move application</h2>
                        <p>Advance this application through an allowed workflow transition.</p>
                    </div>
                </div>

                @can('pipeline.manage')
                    @if ($pipelineTransitions !== [])
                        <form class="pipeline-transition-form" method="POST" action="{{ route('pipeline.transition', $application) }}">
                            @csrf
                            <div>
                                <label class="form-label" for="to_stage">Next stage</label>
                                <select class="form-select @error('to_stage') is-invalid @enderror" id="to_stage" name="to_stage" required>
                                    <option value="">Select stage</option>
                                    @foreach ($pipelineTransitions as $nextStage)
                                        <option value="{{ $nextStage }}" @selected(old('to_stage') === $nextStage)>
                                            {{ Illuminate\Support\Str::headline($nextStage) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('to_stage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" for="note">Reason or note</label>
                                <textarea
                                    class="form-control @error('note') is-invalid @enderror"
                                    id="note"
                                    name="note"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Optional context for this stage change"
                                >{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button class="btn btn-primary" type="submit">Update stage</button>
                        </form>
                    @else
                        <div class="pipeline-terminal-state">This application is in a terminal pipeline stage.</div>
                    @endif
                @endcan
            </section>

            <section class="detail-section pipeline-history-section" aria-labelledby="pipeline-history-title">
                <div class="section-heading">
                    <div>
                        <h2 id="pipeline-history-title">Stage history</h2>
                        <p>Recorded transitions for this application.</p>
                    </div>
                </div>
                <div class="pipeline-history-list">
                    @forelse ($stageHistories as $history)
                        <article class="pipeline-history-item">
                            <span class="pipeline-history-dot" aria-hidden="true"></span>
                            <div>
                                <div class="pipeline-history-stages">
                                    <span>{{ Illuminate\Support\Str::headline($history->from_stage) }}</span>
                                    <strong>{{ Illuminate\Support\Str::headline($history->to_stage) }}</strong>
                                </div>
                                <p>{{ $history->note ?? 'No transition note provided.' }}</p>
                                <small>{{ $history->changedBy?->name ?? 'System' }} - {{ $history->changed_at->format('M j, Y H:i') }}</small>
                            </div>
                        </article>
                    @empty
                        <div class="pipeline-history-empty">No stage changes have been recorded.</div>
                    @endforelse
                </div>
            </section>
        </div>
    @endcan

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
                            @can('interview-feedback.view')
                                <th scope="col">Feedback</th>
                            @endcan
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
                                @can('interview-feedback.view')
                                    <td>
                                        @if ($relatedInterview->feedback->isNotEmpty())
                                            <span class="table-primary-value">
                                                {{ number_format((float) $relatedInterview->feedback->avg('rating'), 1) }} / 5
                                            </span>
                                            <small>{{ $relatedInterview->feedback->count() }} submitted</small>
                                        @else
                                            <span class="table-muted-value">Not submitted</span>
                                        @endif
                                    </td>
                                @endcan
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('interviews.show', $relatedInterview) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="empty-table-state" colspan="{{ auth()->user()->can('interview-feedback.view') ? 6 : 5 }}">No interviews have been scheduled.</td>
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
