@extends('layouts.app')

@section('title', 'Create Job Posting')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('job-postings.index') }}">Job Postings</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Create job posting</h1>
            <p class="page-subtitle">Prepare a vacancy for the hiring workflow.</p>
        </div>
    </header>

    <form class="resource-form job-posting-form" method="POST" action="{{ route('job-postings.store') }}">
        @csrf
        @include('job-postings._form')
    </form>
@endsection
