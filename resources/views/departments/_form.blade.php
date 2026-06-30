@php
    $department = $department ?? null;
@endphp

<div class="form-section">
    <div class="form-section-heading">
        <h2>Department identity</h2>
        <p>Define where this department belongs and how it appears in the ATS.</p>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="company_id">Company</label>
            <select
                class="form-select @error('company_id') is-invalid @enderror"
                id="company_id"
                name="company_id"
                required
            >
                <option value="">Select a company</option>
                @foreach ($companies as $companyOption)
                    <option
                        value="{{ $companyOption->id }}"
                        @selected((string) old('company_id', $department?->company_id) === (string) $companyOption->id)
                    >
                        {{ $companyOption->name }}{{ $companyOption->is_active ? '' : ' (Inactive)' }}
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-lg-8">
            <label class="form-label" for="name">Department name</label>
            <input
                class="form-control @error('name') is-invalid @enderror"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $department?->name) }}"
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
                value="{{ old('slug', $department?->slug) }}"
                maxlength="255"
                placeholder="Generated from department name"
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
            >{{ old('description', $department?->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Contact and location</h2>
        <p>Department-specific communication and workplace details.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $department?->email) }}"
                maxlength="255"
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
                value="{{ old('phone', $department?->phone) }}"
                maxlength="50"
            >
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="location">Location</label>
            <input
                class="form-control @error('location') is-invalid @enderror"
                id="location"
                name="location"
                type="text"
                value="{{ old('location', $department?->location) }}"
                maxlength="255"
                placeholder="Office, building, city, or remote"
            >
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section form-section-status">
    <div>
        <h2>Department status</h2>
        <p>Inactive departments remain available for historical records.</p>
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
            @checked((bool) old('is_active', $department?->is_active ?? true))
        >
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>

<div class="form-actions">
    <a
        class="btn btn-outline-secondary"
        href="{{ $department ? route('departments.show', $department) : route('departments.index') }}"
    >
        <i class="bi bi-x-lg" aria-hidden="true"></i>Cancel
    </a>
    <button class="btn btn-primary" type="submit">
        <i class="bi bi-check2" aria-hidden="true"></i>
        {{ $department ? 'Save changes' : 'Create department' }}
    </button>
</div>
