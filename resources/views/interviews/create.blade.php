@extends('layouts.app')

@section('title', 'Schedule Interview')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('interviews.index') }}">Interviews</a></li>
            <li class="breadcrumb-item active" aria-current="page">Schedule</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Schedule interview</h1>
            <p class="page-subtitle">Set up an interview for an active job application.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('interviews.store') }}">
        @csrf
        @include('interviews._form')
    </form>
@endsection
