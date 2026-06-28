@extends('layouts.app')

@section('title', 'Departments')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Departments</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Organization</div>
            <h1 class="page-title">Departments</h1>
            <p class="page-subtitle">Manage company teams and operational units.</p>
        </div>

        @can('departments.create')
            <a class="btn btn-primary" href="{{ route('departments.create') }}">Create department</a>
        @endcan
    </header>

    <form class="company-toolbar" method="GET" action="{{ route('departments.index') }}">
        <div class="company-search-field">
            <label class="visually-hidden" for="search">Search departments</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Search department, company, email, or location"
            >
        </div>
        <div>
            <label class="visually-hidden" for="company_id">Company</label>
            <select class="form-select" id="company_id" name="company_id">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
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
        @if (request()->filled('search') || request()->filled('status') || request()->filled('company_id'))
            <a class="btn btn-link" href="{{ route('departments.index') }}">Clear</a>
        @endif
    </form>

    <section class="data-panel" aria-label="Department records">
        <div class="table-responsive">
            <table class="table resource-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Department</th>
                        <th scope="col">Company</th>
                        <th scope="col">Contact</th>
                        <th scope="col">Location</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created</th>
                        <th class="text-end" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td>
                                <div class="company-name-cell">
                                    <span class="company-mark department-mark" aria-hidden="true">
                                        {{ strtoupper(Illuminate\Support\Str::substr($department->name, 0, 1)) }}
                                    </span>
                                    <span>
                                        <a href="{{ route('departments.show', $department) }}">{{ $department->name }}</a>
                                        <small>{{ $department->slug }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                @can('companies.view')
                                    <a class="resource-inline-link" href="{{ route('companies.show', $department->company) }}">
                                        {{ $department->company->name }}
                                    </a>
                                @else
                                    {{ $department->company->name }}
                                @endcan
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $department->email ?? 'No email' }}</span>
                                <small>{{ $department->phone ?? 'No phone' }}</small>
                            </td>
                            <td>{{ $department->location ?? 'Not provided' }}</td>
                            <td>
                                <span class="status-pill {{ $department->is_active ? 'status-pill-active' : 'status-pill-inactive' }}">
                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $department->created_at->format('M j, Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('departments.show', $department) }}">View</a>
                                    @can('departments.update')
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('departments.edit', $department) }}">Edit</a>
                                    @endcan
                                    @can('departments.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('departments.destroy', $department) }}"
                                            onsubmit="return confirm('Delete this department? The record will be soft deleted.')"
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
                            <td class="empty-table-state" colspan="7">
                                No departments match the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($departments->hasPages())
        <div class="resource-pagination">
            {{ $departments->links() }}
        </div>
    @endif
@endsection
