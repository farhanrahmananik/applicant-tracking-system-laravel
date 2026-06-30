@extends('layouts.app')

@section('title', 'Offers')

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Offers</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div>
            <div class="page-kicker">Recruitment</div>
            <h1 class="page-title">Offer Management</h1>
            <p class="page-subtitle">Prepare and track employment offers for selected candidates.</p>
        </div>

        @can('offers.create')
            <a class="btn btn-primary" href="{{ route('offers.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>Create offer</a>
        @endcan
    </header>

    <form class="offer-filter-toolbar" method="GET" action="{{ route('offers.index') }}">
        <div>
            <label class="visually-hidden" for="search">Search offers</label>
            <input
                class="form-control"
                id="search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                placeholder="Candidate, job title, or offer title"
            >
        </div>
        <div>
            <label class="visually-hidden" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach (App\Models\Offer::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Filter</button>
        @if (request()->filled('search') || request()->filled('status'))
            <a class="btn btn-link" href="{{ route('offers.index') }}">Clear</a>
        @endif
    </form>

    <section class="data-panel" aria-label="Offer records">
        <div class="table-responsive">
            <table class="table resource-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Candidate</th>
                        <th scope="col">Offer</th>
                        <th scope="col">Compensation</th>
                        <th scope="col">Status</th>
                        <th scope="col">Expiry</th>
                        <th class="text-end" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offers as $offer)
                        <tr>
                            <td>
                                <span class="table-primary-value">{{ $offer->application->candidate->full_name }}</span>
                                <small>{{ $offer->application->candidate->email }}</small>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $offer->offer_title }}</span>
                                <small>{{ $offer->application->jobPosting->title }} - {{ $offer->application->jobPosting->company->name }}</small>
                            </td>
                            <td>
                                <span class="table-primary-value">{{ $offer->currency }} {{ number_format((float) $offer->salary_amount, 2) }}</span>
                                <small>{{ Illuminate\Support\Str::headline($offer->employment_type) }}</small>
                            </td>
                            <td>
                                <span class="offer-badge offer-status-{{ $offer->status }}">
                                    {{ Illuminate\Support\Str::headline($offer->status) }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $offer->expiry_date->format('M j, Y') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary table-icon-action" href="{{ route('offers.show', $offer) }}" aria-label="View offer #{{ $offer->id }}" title="View"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                    @can('offers.update')
                                        @if ($offer->isDraft())
                                            <a class="btn btn-sm btn-outline-secondary table-icon-action" href="{{ route('offers.edit', $offer) }}" aria-label="Edit offer #{{ $offer->id }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-table-state" colspan="6">No offers match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($offers->hasPages())
        <div class="resource-pagination">
            {{ $offers->links() }}
        </div>
    @endif
@endsection
