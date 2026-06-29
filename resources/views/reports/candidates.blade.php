@extends('layouts.app')

@section('title', 'Candidate Source and Status Report')

@section('content')
    @include('reports._header', [
        'title' => 'Candidate Source & Status',
        'subtitle' => 'Candidate acquisition mix and current profile status across the talent pool.',
    ])

    @include('reports._filters', [
        'routeName' => 'reports.candidates',
        'fields' => ['dates', 'candidate_status', 'candidate_source'],
    ])

    @include('reports._metrics', ['items' => [
        ['label' => 'Total candidates', 'value' => $metrics['total']],
        ['label' => 'Active', 'value' => $metrics['active']],
        ['label' => 'New', 'value' => $metrics['new']],
        ['label' => 'Source channels', 'value' => $metrics['sources']],
    ]])

    <div class="report-split-grid">
        @include('reports._distribution', [
            'title' => 'Candidate status',
            'description' => 'Current profile readiness and lifecycle state.',
            'groupLabel' => 'Status',
            'rows' => $statusRows,
        ])

        @include('reports._distribution', [
            'title' => 'Candidate source',
            'description' => 'Channels contributing candidates in this period.',
            'groupLabel' => 'Source',
            'rows' => $sourceRows,
        ])
    </div>
@endsection
