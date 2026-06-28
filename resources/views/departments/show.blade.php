@extends('layouts.app')

@section('title', $department->name)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('departments.index') }}">Departments</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $department->name }}</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div class="company-heading">
            <span class="company-mark company-mark-large department-mark" aria-hidden="true">
                {{ strtoupper(Illuminate\Support\Str::substr($department->name, 0, 1)) }}
            </span>
            <div>
                <div class="page-kicker">Department profile</div>
                <h1 class="page-title">{{ $department->name }}</h1>
                <p class="page-subtitle">{{ $department->slug }}</p>
            </div>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('departments.index') }}">Back</a>
            @can('departments.update')
                <a class="btn btn-primary" href="{{ route('departments.edit', $department) }}">Edit department</a>
            @endcan
        </div>
    </header>

    <section class="company-summary" aria-label="Department summary">
        <div>
            <span>Status</span>
            <strong class="status-pill {{ $department->is_active ? 'status-pill-active' : 'status-pill-inactive' }}">
                {{ $department->is_active ? 'Active' : 'Inactive' }}
            </strong>
        </div>
        <div>
            <span>Company</span>
            <strong>
                @can('companies.view')
                    <a class="resource-inline-link" href="{{ route('companies.show', $department->company) }}">
                        {{ $department->company->name }}
                    </a>
                @else
                    {{ $department->company->name }}
                @endcan
            </strong>
        </div>
        <div>
            <span>Created</span>
            <strong>{{ $department->created_at->format('M j, Y') }}</strong>
        </div>
    </section>

    <div class="company-detail-grid">
        <section class="detail-section" aria-labelledby="department-contact-title">
            <div class="section-heading">
                <div>
                    <h2 id="department-contact-title">Contact information</h2>
                    <p>Department-specific communication details.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Email</dt>
                    <dd>{{ $department->email ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Phone</dt>
                    <dd>{{ $department->phone ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Location</dt>
                    <dd>{{ $department->location ?? 'Not provided' }}</dd>
                </div>
            </dl>
        </section>

        <section class="detail-section" aria-labelledby="department-company-title">
            <div class="section-heading">
                <div>
                    <h2 id="department-company-title">Company</h2>
                    <p>Organization ownership and status.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Name</dt>
                    <dd>{{ $department->company->name }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{{ $department->company->is_active ? 'Active' : 'Inactive' }}</dd>
                </div>
                <div>
                    <dt>Location</dt>
                    <dd>{{ collect([$department->company->city, $department->company->country])->filter()->join(', ') ?: 'Not provided' }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="detail-section company-description" aria-labelledby="department-description-title">
        <div class="section-heading">
            <div>
                <h2 id="department-description-title">Description</h2>
                <p>Department profile notes.</p>
            </div>
        </div>
        <p>{{ $department->description ?? 'No description has been added.' }}</p>
    </section>
@endsection
