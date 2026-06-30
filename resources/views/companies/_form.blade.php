@php
    $company = $company ?? null;
@endphp

<div class="form-section">
    <div class="form-section-heading">
        <h2>Company identity</h2>
        <p>Core organization details used throughout the ATS.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <label class="form-label" for="name">Company name</label>
            <input
                class="form-control @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $company?->name) }}"
                maxlength="255"
                required
                autofocus
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-lg-4">
            <label class="form-label" for="slug">Slug</label>
            <input
                class="form-control @error('slug') is-invalid @enderror"
                id="slug"
                name="slug"
                type="text"
                value="{{ old('slug', $company?->slug) }}"
                maxlength="255"
                placeholder="Generated from company name"
            >
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="description">Description</label>
            <textarea
                class="form-control @error('description') is-invalid @enderror"
                id="description"
                name="description"
                rows="4"
            >{{ old('description', $company?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Contact details</h2>
        <p>Public-facing communication and location information.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $company?->email) }}"
                maxlength="255"
                autocomplete="organization-email"
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="phone">Phone</label>
            <input
                class="form-control @error('phone') is-invalid @enderror"
                id="phone"
                name="phone"
                type="text"
                value="{{ old('phone', $company?->phone) }}"
                maxlength="50"
                autocomplete="organization-tel"
            >
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="website">Website</label>
            <input
                class="form-control @error('website') is-invalid @enderror"
                id="website"
                name="website"
                type="url"
                value="{{ old('website', $company?->website) }}"
                maxlength="255"
                placeholder="https://example.com"
                autocomplete="url"
            >
            @error('website')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="address">Address</label>
            <textarea
                class="form-control @error('address') is-invalid @enderror"
                id="address"
                name="address"
                rows="3"
                autocomplete="street-address"
            >{{ old('address', $company?->address) }}</textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="city">City</label>
            <input
                class="form-control @error('city') is-invalid @enderror"
                id="city"
                name="city"
                type="text"
                value="{{ old('city', $company?->city) }}"
                maxlength="120"
                autocomplete="address-level2"
            >
            @error('city')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="country">Country</label>
            <input
                class="form-control @error('country') is-invalid @enderror"
                id="country"
                name="country"
                type="text"
                value="{{ old('country', $company?->country) }}"
                maxlength="120"
                autocomplete="country-name"
            >
            @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section form-section-status">
    <div>
        <h2>Account status</h2>
        <p>Inactive companies remain available for historical records.</p>
    </div>
    <div class="form-check form-switch m-0">
        <input type="hidden" name="is_active" value="0">
        <input
            class="form-check-input"
            id="is_active"
            name="is_active"
            type="checkbox"
            role="switch"
            value="1"
            @checked((bool) old('is_active', $company?->is_active ?? true))
        >
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>

<div class="form-actions">
    <a class="btn btn-outline-secondary" href="{{ $company ? route('companies.show', $company) : route('companies.index') }}">
        <i class="bi bi-x-lg" aria-hidden="true"></i>Cancel
    </a>
    <button class="btn btn-primary" type="submit">
        <i class="bi bi-check2" aria-hidden="true"></i>
        {{ $company ? 'Save changes' : 'Create company' }}
    </button>
</div>
