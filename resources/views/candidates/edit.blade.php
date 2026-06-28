@extends('layouts.app')

@section('title', 'Edit '.$candidate->full_name)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('candidates.index') }}">Candidates</a></li>
            <li class="breadcrumb-item"><a href="{{ route('candidates.show', $candidate) }}">{{ $candidate->full_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Edit candidate</h1>
            <p class="page-subtitle">Update {{ $candidate->full_name }} and professional details.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('candidates.update', $candidate) }}">
        @csrf
        @method('PUT')
        @include('candidates._form', ['candidate' => $candidate])
    </form>
@endsection
