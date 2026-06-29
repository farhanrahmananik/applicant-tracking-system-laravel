@extends('layouts.app')

@section('title', 'Create Offer')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('offers.index') }}">Offers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Offer Management</div>
            <h1 class="page-title">Create offer</h1>
            <p class="page-subtitle">Prepare a draft offer for a selected application.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('offers.store') }}">
        @csrf
        @include('offers._form')
    </form>
@endsection
