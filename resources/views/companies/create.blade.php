@extends('layouts.app')

@section('title', 'Create Company')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Companies</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Organization</div>
            <h1 class="page-title">Create company</h1>
            <p class="page-subtitle">Add an organization to the ATS workspace.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('companies.store') }}">
        @csrf
        @include('companies._form')
    </form>
@endsection
