@extends('layouts.app')

@section('title', 'Edit '.$jobPosting->title)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('job-postings.index') }}">Job Postings</a></li>
            <li class="breadcrumb-item"><a href="{{ route('job-postings.show', $jobPosting) }}">{{ $jobPosting->title }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Edit job posting</h1>
            <p class="page-subtitle">Update {{ $jobPosting->title }} and its publication state.</p>
        </div>
    </header>

    <form class="resource-form job-posting-form" method="POST" action="{{ route('job-postings.update', $jobPosting) }}">
        @csrf
        @method('PUT')
        @include('job-postings._form', ['jobPosting' => $jobPosting])
    </form>
@endsection
