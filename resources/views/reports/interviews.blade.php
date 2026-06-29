@extends('layouts.app')

@section('title', 'Interview Schedule and Outcome Report')

@section('content')
    @include('reports._header', [
        'title' => 'Interview Schedule & Outcome',
        'subtitle' => 'Interview delivery status and submitted panel recommendations.',
        'exportRoute' => 'reports.interviews.export',
    ])

    @include('reports._filters', [
        'routeName' => 'reports.interviews',
        'fields' => ['dates', 'job', 'interviewer', 'interview_status'],
    ])

    @include('reports._metrics', ['items' => [
        ['label' => 'Total interviews', 'value' => $metrics['total']],
        ['label' => 'Upcoming', 'value' => $metrics['upcoming']],
        ['label' => 'Completed', 'value' => $metrics['completed']],
        ['label' => 'Cancelled', 'value' => $metrics['cancelled']],
    ]])

    <div class="report-split-grid">
        @include('reports._distribution', [
            'title' => 'Schedule status',
            'description' => 'Interview events grouped by their delivery status.',
            'groupLabel' => 'Schedule status',
            'rows' => $statusRows,
        ])

        @include('reports._distribution', [
            'title' => 'Feedback outcome',
            'description' => 'Submitted feedback grouped by hiring recommendation.',
            'groupLabel' => 'Recommendation',
            'rows' => $outcomeRows,
        ])
    </div>
@endsection
