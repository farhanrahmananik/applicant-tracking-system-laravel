@extends('layouts.app')

@section('title', 'Edit Application')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Applications</a></li>
            <li class="breadcrumb-item"><a href="{{ route('applications.show', $application) }}">{{ $application->candidate->full_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Edit application</h1>
            <p class="page-subtitle">Update {{ $application->candidate->full_name }} for {{ $application->jobPosting->title }}.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('applications.update', $application) }}">
        @csrf
        @method('PUT')
        @include('applications._form', ['application' => $application])
    </form>
@endsection
