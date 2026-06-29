@extends('layouts.app')

@section('title', 'Hiring Pipeline Stage Report')

@section('content')
    @include('reports._header', [
        'title' => 'Hiring Pipeline Stages',
        'subtitle' => 'A point-in-time view of application concentration across the hiring workflow.',
        'exportRoute' => 'reports.pipeline.export',
    ])

    @include('reports._filters', [
        'routeName' => 'reports.pipeline',
        'fields' => ['dates', 'company', 'department', 'job', 'pipeline_stage'],
    ])

    @include('reports._metrics', ['items' => [
        ['label' => 'Pipeline total', 'value' => $metrics['total']],
        ['label' => 'Screening', 'value' => $metrics['screening']],
        ['label' => 'Interview', 'value' => $metrics['interview']],
        ['label' => 'Selected', 'value' => $metrics['selected']],
    ]])

    @include('reports._distribution', [
        'title' => 'Pipeline distribution',
        'description' => 'Applications grouped by their current hiring pipeline stage.',
        'groupLabel' => 'Pipeline stage',
    ])
@endsection
