@extends('layouts.app')

@section('title', 'Interview Feedback')

@section('content')
    @php
        $interview = $feedback->interviewSchedule;
    @endphp

    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.index') }}">Interviews</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.show', $interview) }}">{{ $interview->application->candidate->full_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Feedback</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Interview feedback</div>
            <h1 class="page-title">{{ $interview->application->candidate->full_name }}</h1>
            <p class="page-subtitle">{{ $interview->application->jobPosting->title }} at {{ $interview->application->jobPosting->company->name }}</p>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('interviews.show', $interview) }}">Back</a>
            @can('interview-feedback.update')
                <a class="btn btn-primary" href="{{ route('interview-feedback.edit', $feedback) }}">Edit feedback</a>
            @endcan
        </div>
    </header>

    <section class="feedback-summary-card" aria-label="Feedback summary">
        <div>
            <span>Recommendation</span>
            <strong class="feedback-recommendation feedback-recommendation-{{ $feedback->recommendation }}">
                {{ Illuminate\Support\Str::headline($feedback->recommendation) }}
            </strong>
        </div>
        <div>
            <span>Rating</span>
            <div class="feedback-rating-display" aria-label="Rating {{ $feedback->rating }} out of 5">
                @for ($rating = 1; $rating <= 5; $rating++)
                    <i class="{{ $rating <= $feedback->rating ? 'active' : '' }}" aria-hidden="true"></i>
                @endfor
                <strong>{{ $feedback->rating }} / 5</strong>
            </div>
        </div>
        <div>
            <span>Submitted by</span>
            <strong>{{ $feedback->submittedBy->name }}</strong>
        </div>
        <div>
            <span>Submitted</span>
            <strong>{{ $feedback->submitted_at->format('M j, Y H:i') }}</strong>
        </div>
    </section>

    <section class="detail-section feedback-copy-section" aria-labelledby="feedback-summary-title">
        <div class="section-heading">
            <div>
                <h2 id="feedback-summary-title">Assessment summary</h2>
            </div>
        </div>
        <div class="feedback-long-copy">{{ $feedback->summary }}</div>
    </section>

    <div class="company-detail-grid feedback-evidence-grid">
        <section class="detail-section" aria-labelledby="feedback-strengths-title">
            <div class="section-heading">
                <div>
                    <h2 id="feedback-strengths-title">Strengths</h2>
                    <p>Role-relevant evidence supporting the assessment.</p>
                </div>
            </div>
            <div class="feedback-long-copy">{{ $feedback->strengths ?? 'No strengths were recorded.' }}</div>
        </section>

        <section class="detail-section" aria-labelledby="feedback-weaknesses-title">
            <div class="section-heading">
                <div>
                    <h2 id="feedback-weaknesses-title">Weaknesses</h2>
                    <p>Gaps or concerns identified during the interview.</p>
                </div>
            </div>
            <div class="feedback-long-copy">{{ $feedback->weaknesses ?? 'No weaknesses were recorded.' }}</div>
        </section>
    </div>
@endsection
