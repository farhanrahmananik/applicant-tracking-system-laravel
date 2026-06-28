@extends('layouts.app')

@section('title', 'Create Candidate')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('candidates.index') }}">Candidates</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Create candidate</h1>
            <p class="page-subtitle">Add a candidate profile to the ATS directory.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('candidates.store') }}">
        @csrf
        @include('candidates._form')
    </form>
@endsection
