@extends('layouts.app')

@section('title', 'Create Department')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('departments.index') }}">Departments</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Organization</div>
            <h1 class="page-title">Create department</h1>
            <p class="page-subtitle">Add a department within an existing company.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('departments.store') }}">
        @csrf
        @include('departments._form')
    </form>
@endsection
