@extends('layouts.app')

@section('title', 'Application Summary Report')

@section('content')
    @include('reports._header', [
        'title' => 'Application Summary',
        'subtitle' => 'Application volume and status distribution across the recruitment funnel.',
        'exportRoute' => 'reports.applications.export',
    ])

    @include('reports._filters', [
        'routeName' => 'reports.applications',
        'fields' => ['dates', 'company', 'department', 'job', 'application_status'],
    ])

    @include('reports._metrics', ['items' => [
        ['label' => 'Total applications', 'value' => $metrics['total']],
        ['label' => 'Active pipeline', 'value' => $metrics['active']],
        ['label' => 'Selected', 'value' => $metrics['selected']],
        ['label' => 'Closed', 'value' => $metrics['terminal']],
    ]])

    @include('reports._distribution', [
        'title' => 'Application status distribution',
        'description' => 'Current stage of applications matching the selected filters.',
        'groupLabel' => 'Application status',
    ])
@endsection
