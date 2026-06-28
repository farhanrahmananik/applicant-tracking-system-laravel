@extends('layouts.app')

@section('title', 'Edit Interview Feedback')

@section('content')
    @php
        $interview = $feedback->interviewSchedule;
    @endphp

    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.index') }}">Interviews</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.show', $interview) }}">{{ $interview->application->candidate->full_name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interview-feedback.show', $feedback) }}">Feedback</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Interview feedback</div>
            <h1 class="page-title">Edit feedback</h1>
            <p class="page-subtitle">Update the assessment submitted by {{ $feedback->submittedBy->name }}.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('interview-feedback.update', $feedback) }}">
        @csrf
        @method('PUT')
        @include('interview-feedback._form', ['feedback' => $feedback, 'interview' => $interview])
    </form>
@endsection
