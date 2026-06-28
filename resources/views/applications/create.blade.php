@extends('layouts.app')

@section('title', 'Create Application')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Applications</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Create application</h1>
            <p class="page-subtitle">Link a candidate to a job posting and establish the initial status.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('applications.store') }}">
        @csrf
        @include('applications._form')
    </form>
@endsection
