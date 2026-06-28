@extends('layouts.app')

@section('title', 'Submit Interview Feedback')

@section('content')
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
            <h1 class="page-title">Submit feedback</h1>
            <p class="page-subtitle">{{ $interview->application->candidate->full_name }} for {{ $interview->application->jobPosting->title }}.</p>
        </div>
    </header>

    <section class="feedback-context-strip" aria-label="Interview context">
        <div>
            <span>Interview</span>
            <strong>{{ Illuminate\Support\Str::headline($interview->type) }}</strong>
        </div>
        <div>
            <span>Scheduled</span>
            <strong>{{ $interview->scheduled_at->format('M j, Y H:i') }}</strong>
        </div>
        <div>
            <span>Interviewer</span>
            <strong>{{ $interview->interviewer->name }}</strong>
        </div>
    </section>

    <form class="resource-form" method="POST" action="{{ route('interviews.feedback.store', $interview) }}">
        @csrf
        @include('interview-feedback._form', ['interview' => $interview])
    </form>
@endsection
