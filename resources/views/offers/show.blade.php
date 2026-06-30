@extends('layouts.app')

@section('title', $offer->offer_title)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('offers.index') }}">Offers</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $offer->offer_title }}</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Offer Management</div>
            <h1 class="page-title">{{ $offer->offer_title }}</h1>
            <p class="page-subtitle">{{ $offer->application->candidate->full_name }} for {{ $offer->application->jobPosting->title }}.</p>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('offers.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back</a>
            @can('offers.update')
                @if ($offer->isDraft())
                    <a class="btn btn-primary" href="{{ route('offers.edit', $offer) }}"><i class="bi bi-pencil-square" aria-hidden="true"></i>Edit offer</a>
                @endif
            @endcan
        </div>
    </header>

    <section class="offer-summary-card" aria-label="Offer summary">
        <div>
            <span>Status</span>
            <strong class="offer-badge offer-status-{{ $offer->status }}">
                {{ Illuminate\Support\Str::headline($offer->status) }}
            </strong>
        </div>
        <div>
            <span>Compensation</span>
            <strong>{{ $offer->currency }} {{ number_format((float) $offer->salary_amount, 2) }}</strong>
        </div>
        <div>
            <span>Expires</span>
            <strong>{{ $offer->expiry_date->format('M j, Y') }}</strong>
        </div>
        <div>
            <span>Expected joining</span>
            <strong>{{ $offer->expected_joining_date?->format('M j, Y') ?? 'Not specified' }}</strong>
        </div>
    </section>

    <div class="company-detail-grid">
        <section class="detail-section" aria-labelledby="offer-candidate-title">
            <div class="section-heading">
                <div>
                    <h2 id="offer-candidate-title">Candidate</h2>
                    <p>Recipient and application context.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Name</dt>
                    <dd>{{ $offer->application->candidate->full_name }}</dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>{{ $offer->application->candidate->email }}</dd>
                </div>
                <div>
                    <dt>Application</dt>
                    <dd>
                        @can('applications.view')
                            <a class="resource-inline-link" href="{{ route('applications.show', $offer->application) }}">View application</a>
                        @else
                            #{{ $offer->application_id }}
                        @endcan
                    </dd>
                </div>
            </dl>
        </section>

        <section class="detail-section" aria-labelledby="offer-position-title">
            <div class="section-heading">
                <div>
                    <h2 id="offer-position-title">Position</h2>
                    <p>Role and organizational details.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Job posting</dt>
                    <dd>{{ $offer->application->jobPosting->title }}</dd>
                </div>
                <div>
                    <dt>Company</dt>
                    <dd>{{ $offer->application->jobPosting->company->name }}</dd>
                </div>
                <div>
                    <dt>Department</dt>
                    <dd>{{ $offer->application->jobPosting->department?->name ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt>Employment type</dt>
                    <dd>{{ Illuminate\Support\Str::headline($offer->employment_type) }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="detail-section offer-notes-section" aria-labelledby="offer-notes-title">
        <div class="section-heading">
            <div>
                <h2 id="offer-notes-title">Internal notes</h2>
                <p>Offer-specific context for the recruitment team.</p>
            </div>
        </div>
        <div class="offer-notes-copy">{{ $offer->notes ?? 'No notes have been added.' }}</div>
    </section>

    <div class="offer-workflow-grid">
        <section class="detail-section" aria-labelledby="offer-status-action-title">
            <div class="section-heading">
                <div>
                    <h2 id="offer-status-action-title">Update offer status</h2>
                    <p>Move the offer through a valid workflow transition.</p>
                </div>
            </div>

            @can('offers.update')
                @if ($statusTransitions !== [])
                    <form class="offer-transition-form" method="POST" action="{{ route('offers.transition', $offer) }}">
                        @csrf
                        <div>
                            <label class="form-label" for="to_status">Next status</label>
                            <select class="form-select @error('to_status') is-invalid @enderror" id="to_status" name="to_status" required>
                                <option value="">Select status</option>
                                @foreach ($statusTransitions as $nextStatus)
                                    <option value="{{ $nextStatus }}" @selected(old('to_status') === $nextStatus)>
                                        {{ Illuminate\Support\Str::headline($nextStatus) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('to_status')
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
                                placeholder="Optional context for this status change"
                            >{{ old('note') }}</textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button class="btn btn-primary" type="submit">Update status</button>
                    </form>
                @else
                    <div class="offer-terminal-state">This offer is in a terminal status.</div>
                @endif
            @endcan
        </section>

        <section class="detail-section" aria-labelledby="offer-history-title">
            <div class="section-heading">
                <div>
                    <h2 id="offer-history-title">Status history</h2>
                    <p>Recorded workflow changes for this offer.</p>
                </div>
            </div>
            <div class="offer-history-list">
                @forelse ($offer->statusHistories as $history)
                    <article class="offer-history-item">
                        <span class="offer-history-dot" aria-hidden="true"></span>
                        <div>
                            <div class="offer-history-statuses">
                                <span>{{ Illuminate\Support\Str::headline($history->from_status) }}</span>
                                <strong>{{ Illuminate\Support\Str::headline($history->to_status) }}</strong>
                            </div>
                            <p>{{ $history->note ?? 'No status note provided.' }}</p>
                            <small>{{ $history->changedBy?->name ?? 'System' }} - {{ $history->changed_at->format('M j, Y H:i') }}</small>
                        </div>
                    </article>
                @empty
                    <div class="offer-history-empty">No status changes have been recorded.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="detail-section job-audit-strip" aria-label="Record ownership">
        <div>
            <span>Created by</span>
            <strong>{{ $offer->createdBy?->name ?? 'System' }}</strong>
        </div>
        <div>
            <span>Updated by</span>
            <strong>{{ $offer->updatedBy?->name ?? 'Not updated' }}</strong>
        </div>
        <div>
            <span>Created</span>
            <strong>{{ $offer->created_at->format('M j, Y H:i') }}</strong>
        </div>
    </section>
@endsection
