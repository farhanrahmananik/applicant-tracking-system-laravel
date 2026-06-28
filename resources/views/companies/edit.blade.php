@extends('layouts.app')

@section('title', 'Edit '.$company->name)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Companies</a></li>
            <li class="breadcrumb-item"><a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Organization</div>
            <h1 class="page-title">Edit company</h1>
            <p class="page-subtitle">Update {{ $company->name }} and its contact information.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('companies.update', $company) }}">
        @csrf
        @method('PUT')
        @include('companies._form', ['company' => $company])
    </form>
@endsection
