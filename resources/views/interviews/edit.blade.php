@extends('layouts.app')

@section('title', 'Edit Interview')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.index') }}">Interviews</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.show', $interview) }}">{{ $interview->application->candidate->full_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Edit interview</h1>
            <p class="page-subtitle">Update the schedule for {{ $interview->application->candidate->full_name }}.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('interviews.update', $interview) }}">
        @csrf
        @method('PUT')
        @include('interviews._form', ['interview' => $interview])
    </form>
@endsection
