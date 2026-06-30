@extends('layouts.app')

@section('title', 'Hiring Pipeline')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hiring Pipeline</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Hiring Pipeline</h1>
            <p class="page-subtitle">Move active applications through a controlled, auditable workflow.</p>
        </div>
    </header>

    @error('to_stage')
        <div class="alert alert-danger app-alert" role="alert">{{ $message }}</div>
    @enderror

    <form class="pipeline-filter-toolbar" method="GET" action="{{ route('pipeline.index') }}">
        <div>
            <label class="visually-hidden" for="search">Search pipeline</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Candidate, email, or job title"
            >
        </div>
        <div>
            <label class="visually-hidden" for="job_posting_id">Job posting</label>
            <select class="form-select" id="job_posting_id" name="job_posting_id">
                <option value="">All job postings</option>
                @foreach ($jobPostings as $jobPosting)
                    <option value="{{ $jobPosting->id }}" @selected((string) request('job_posting_id') === (string) $jobPosting->id)>
                        {{ $jobPosting->title }} - {{ $jobPosting->company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Filter</button>
        @if (request()->filled('search') || request()->filled('job_posting_id'))
            <a class="btn btn-link" href="{{ route('pipeline.index') }}">Clear</a>
        @endif
    </form>

    <div class="pipeline-board" aria-label="Application pipeline board">
        @foreach ($pipelineColumns as $stage => $applications)
            <section class="pipeline-column pipeline-column-{{ $stage }}" aria-labelledby="pipeline-{{ $stage }}-title">
                <header class="pipeline-column-header">
                    <div>
                        <span class="pipeline-stage-mark" aria-hidden="true"></span>
                        <h2 id="pipeline-{{ $stage }}-title">{{ Illuminate\Support\Str::headline($stage) }}</h2>
                    </div>
                    <span>{{ $applications->count() }}</span>
                </header>

                <div class="pipeline-card-list">
                    @forelse ($applications as $application)
                        <article class="pipeline-card">
                            <div class="pipeline-card-topline">
                                <span>{{ $application->jobPosting->company->name }}</span>
                                <time datetime="{{ $application->applied_date->toDateString() }}">{{ $application->applied_date->format('M j') }}</time>
                            </div>
                            <h3>
                                <a href="{{ route('applications.show', $application) }}">{{ $application->candidate->full_name }}</a>
                            </h3>
                            <p>{{ $application->jobPosting->title }}</p>
                            <small>{{ $application->candidate->current_position ?? $application->candidate->email }}</small>

                            @can('pipeline.manage')
                                @if (($transitionMap[$stage] ?? []) !== [])
                                    <form class="pipeline-card-action" method="POST" action="{{ route('pipeline.transition', $application) }}">
                                        @csrf
                                        <label class="visually-hidden" for="to_stage_{{ $application->id }}">Move application</label>
                                        <select class="form-select form-select-sm" id="to_stage_{{ $application->id }}" name="to_stage" required>
                                            <option value="">Move to...</option>
                                            @foreach ($transitionMap[$stage] as $nextStage)
                                                <option value="{{ $nextStage }}">{{ Illuminate\Support\Str::headline($nextStage) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit">Move</button>
                                    </form>
                                @endif
                            @endcan
                        </article>
                    @empty
                        <div class="pipeline-empty-state">No applications</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
@endsection
