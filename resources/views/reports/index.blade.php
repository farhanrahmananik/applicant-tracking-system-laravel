@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="report-page-header">
        <div>
            <div class="page-kicker">Recruitment intelligence</div>
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">Operational ATS insights from the current recruitment workflow.</p>
        </div>
        <div class="report-snapshot-date">
            <span>Snapshot</span>
            <strong>{{ now()->format('M j, Y') }}</strong>
        </div>
    </div>

    @include('reports._metrics', ['items' => [
        ['label' => 'Applications', 'value' => $metrics['applications'], 'context' => 'Across all pipeline stages'],
        ['label' => 'Candidates', 'value' => $metrics['candidates'], 'context' => 'Active candidate records'],
        ['label' => 'Openings', 'value' => $metrics['job_postings'], 'context' => 'Job posting records'],
        ['label' => 'Interviews', 'value' => $metrics['interviews'], 'context' => 'Scheduled workflow events'],
        ['label' => 'Offers', 'value' => $metrics['offers'], 'context' => 'Offer lifecycle records'],
        ['label' => 'Selected', 'value' => $metrics['selected'], 'context' => 'Applications at final stage'],
    ]])

    <section class="report-catalog-section">
        <div class="report-section-heading">
            <div>
                <h2>Report catalog</h2>
                <p>Choose an operational view, refine its scope, and export the result where available.</p>
            </div>
            <span>6 reports</span>
        </div>

        <div class="report-catalog-grid">
            @foreach ([
                ['route' => 'reports.applications', 'mark' => 'AP', 'title' => 'Application Summary', 'copy' => 'Application volume and distribution across statuses and pipeline stages.', 'export' => true],
                ['route' => 'reports.candidates', 'mark' => 'CS', 'title' => 'Candidate Source & Status', 'copy' => 'Candidate acquisition sources and current profile status mix.', 'export' => false],
                ['route' => 'reports.job-postings', 'mark' => 'JP', 'title' => 'Job Posting Performance', 'copy' => 'Compare applications, unique candidates, interviews, and offers by role.', 'export' => true],
                ['route' => 'reports.interviews', 'mark' => 'IN', 'title' => 'Interview Schedule & Outcome', 'copy' => 'Schedule health and interviewer feedback recommendations.', 'export' => true],
                ['route' => 'reports.pipeline', 'mark' => 'HP', 'title' => 'Hiring Pipeline Stages', 'copy' => 'Current application concentration across every hiring stage.', 'export' => true],
                ['route' => 'reports.offers', 'mark' => 'OF', 'title' => 'Offer Status', 'copy' => 'Offer volume, pending decisions, and terminal outcomes.', 'export' => true],
            ] as $report)
                <a class="report-catalog-card" href="{{ route($report['route']) }}">
                    <div class="report-catalog-mark">{{ $report['mark'] }}</div>
                    <div>
                        <div class="report-catalog-title">
                            <h2>{{ $report['title'] }}</h2>
                            @if ($report['export'])
                                <span>CSV</span>
                            @endif
                        </div>
                        <p>{{ $report['copy'] }}</p>
                        <strong>Open report <span aria-hidden="true">&rarr;</span></strong>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endsection
