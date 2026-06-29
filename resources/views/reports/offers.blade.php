@extends('layouts.app')

@section('title', 'Offer Status Report')

@section('content')
    @include('reports._header', [
        'title' => 'Offer Status',
        'subtitle' => 'Offer volume, unresolved decisions, and final candidate responses.',
        'exportRoute' => 'reports.offers.export',
    ])

    @include('reports._filters', [
        'routeName' => 'reports.offers',
        'fields' => ['dates', 'company', 'department', 'job', 'offer_status'],
    ])

    @include('reports._metrics', ['items' => [
        ['label' => 'Total offers', 'value' => $metrics['total']],
        ['label' => 'Pending', 'value' => $metrics['pending']],
        ['label' => 'Accepted', 'value' => $metrics['accepted']],
        ['label' => 'Declined', 'value' => $metrics['declined']],
        ['label' => 'Expired', 'value' => $metrics['expired']],
    ]])

    @include('reports._distribution', [
        'title' => 'Offer status distribution',
        'description' => 'Offers grouped by their current workflow status.',
        'groupLabel' => 'Offer status',
    ])
@endsection
