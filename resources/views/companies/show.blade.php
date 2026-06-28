@extends('layouts.app')

@section('title', $company->name)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Companies</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $company->name }}</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div class="company-heading">
            <span class="company-mark company-mark-large" aria-hidden="true">
                {{ strtoupper(Illuminate\Support\Str::substr($company->name, 0, 1)) }}
            </span>
            <div>
                <div class="page-kicker">Company profile</div>
                <h1 class="page-title">{{ $company->name }}</h1>
                <p class="page-subtitle">{{ $company->slug }}</p>
            </div>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('companies.index') }}">Back</a>
            @can('companies.update')
                <a class="btn btn-primary" href="{{ route('companies.edit', $company) }}">Edit company</a>
            @endcan
        </div>
    </header>

    <section class="company-summary" aria-label="Company summary">
        <div>
            <span>Status</span>
            <strong class="status-pill {{ $company->is_active ? 'status-pill-active' : 'status-pill-inactive' }}">
                {{ $company->is_active ? 'Active' : 'Inactive' }}
            </strong>
        </div>
        <div>
            <span>Created</span>
            <strong>{{ $company->created_at->format('M j, Y') }}</strong>
        </div>
        <div>
            <span>Last updated</span>
            <strong>{{ $company->updated_at->format('M j, Y') }}</strong>
        </div>
    </section>

    <div class="company-detail-grid">
        <section class="detail-section" aria-labelledby="contact-title">
            <div class="section-heading">
                <div>
                    <h2 id="contact-title">Contact information</h2>
                    <p>Primary organization contact details.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Email</dt>
                    <dd>{{ $company->email ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Phone</dt>
                    <dd>{{ $company->phone ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Website</dt>
                    <dd>
                        @if ($company->website)
                            <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">{{ $company->website }}</a>
                        @else
                            Not provided
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="detail-section" aria-labelledby="location-title">
            <div class="section-heading">
                <div>
                    <h2 id="location-title">Location</h2>
                    <p>Registered company address.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Address</dt>
                    <dd>{{ $company->address ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>City</dt>
                    <dd>{{ $company->city ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Country</dt>
                    <dd>{{ $company->country ?? 'Not provided' }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="detail-section company-description" aria-labelledby="description-title">
        <div class="section-heading">
            <div>
                <h2 id="description-title">Description</h2>
                <p>Organization profile notes.</p>
            </div>
        </div>
        <p>{{ $company->description ?? 'No description has been added.' }}</p>
    </section>
@endsection
