@extends('layouts.app')

@section('title', 'Edit '.$department->name)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('departments.index') }}">Departments</a></li>
            <li class="breadcrumb-item"><a href="{{ route('departments.show', $department) }}">{{ $department->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Organization</div>
            <h1 class="page-title">Edit department</h1>
            <p class="page-subtitle">Update {{ $department->name }} and its company assignment.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('departments.update', $department) }}">
        @csrf
        @method('PUT')
        @include('departments._form', ['department' => $department])
    </form>
@endsection
