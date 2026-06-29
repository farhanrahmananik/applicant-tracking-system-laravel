@extends('layouts.app')

@section('title', 'Edit Offer')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('offers.index') }}">Offers</a></li>
            <li class="breadcrumb-item"><a href="{{ route('offers.show', $offer) }}">{{ $offer->offer_title }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Offer Management</div>
            <h1 class="page-title">Edit draft offer</h1>
            <p class="page-subtitle">Update compensation and employment terms before the offer is sent.</p>
        </div>
    </header>

    <form class="resource-form" method="POST" action="{{ route('offers.update', $offer) }}">
        @csrf
        @method('PUT')
        @include('offers._form', ['offer' => $offer])
    </form>
@endsection
