@extends('layouts.app')

@section('title', 'Companies')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Companies</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Organization</div>
            <h1 class="page-title">Companies</h1>
            <p class="page-subtitle">Manage organizations using the ATS workspace.</p>
        </div>

        @can('companies.create')
            <a class="btn btn-primary" href="{{ route('companies.create') }}">Create company</a>
        @endcan
    </header>

    <form class="company-toolbar" method="GET" action="{{ route('companies.index') }}">
        <div class="company-search-field">
            <label class="visually-hidden" for="search">Search companies</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Search name, email, city, or country"
            >
        </div>
        <div>
            <label class="visually-hidden" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <button class="btn btn-outline-secondary" type="submit">Filter</button>
        @if (request()->filled('search') || request()->filled('status'))
            <a class="btn btn-link" href="{{ route('companies.index') }}">Clear</a>
        @endif
    </form>

    <section class="data-panel" aria-label="Company records">
        <div class="table-responsive">
            <table class="table resource-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Company</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Location</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created</th>
                        <th class="text-end" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            <td>
                                <div class="company-name-cell">
                                    <span class="company-mark" aria-hidden="true">
                                        {{ strtoupper(Illuminate\Support\Str::substr($company->name, 0, 1)) }}
                                    </span>
                                    <span>
                                        <a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a>
                                        <small>{{ $company->slug }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $company->email ?? 'No email' }}</span>
                                <small>{{ $company->phone ?? 'No phone' }}</small>
                            </td>
                            <td>
                                {{ collect([$company->city, $company->country])->filter()->join(', ') ?: 'Not provided' }}
                            </td>
                            <td>
                                <span class="status-pill {{ $company->is_active ? 'status-pill-active' : 'status-pill-inactive' }}">
                                    {{ $company->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $company->created_at->format('M j, Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('companies.show', $company) }}">View</a>
                                    @can('companies.update')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('companies.edit', $company) }}">Edit</a>
                                    @endcan
                                    @can('companies.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('companies.destroy', $company) }}"
                                            onsubmit="return confirm('Delete this company? The record will be soft deleted.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-table-state" colspan="6">
                                No companies match the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($companies->hasPages())
        <div class="resource-pagination">
            {{ $companies->links() }}
        </div>
    @endif
@endsection
