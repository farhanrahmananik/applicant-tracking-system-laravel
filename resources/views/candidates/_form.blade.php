@php
    $candidate = $candidate ?? null;
@endphp

<div class="form-section">
    <div class="form-section-heading">
        <h2>Candidate identity</h2>
        <p>Core contact and profile information.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="first_name">First name</label>
            <input
                class="form-control @error('first_name') is-invalid @enderror"
                id="first_name"
                name="first_name"
                type="text"
                value="{{ old('first_name', $candidate?->first_name) }}"
                maxlength="100"
                required
                autofocus
                autocomplete="given-name"
            >
            @error('first_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="last_name">Last name</label>
            <input
                class="form-control @error('last_name') is-invalid @enderror"
                id="last_name"
                name="last_name"
                type="text"
                value="{{ old('last_name', $candidate?->last_name) }}"
                maxlength="100"
                autocomplete="family-name"
            >
            @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-control @error('email') is-invalid @enderror"
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $candidate?->email) }}"
                maxlength="255"
                required
                autocomplete="email"
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
                value="{{ old('phone', $candidate?->phone) }}"
                maxlength="50"
                autocomplete="tel"
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
                value="{{ old('location', $candidate?->location) }}"
                maxlength="255"
                placeholder="Berlin, Germany"
            >
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Professional profile</h2>
        <p>Current role, experience, and candidate capabilities.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="current_position">Current position</label>
            <input
                class="form-control @error('current_position') is-invalid @enderror"
                id="current_position"
                name="current_position"
                type="text"
                value="{{ old('current_position', $candidate?->current_position) }}"
                maxlength="180"
            >
            @error('current_position')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="experience_years">Experience years</label>
            <input
                class="form-control @error('experience_years') is-invalid @enderror"
                id="experience_years"
                name="experience_years"
                type="number"
                value="{{ old('experience_years', $candidate?->experience_years) }}"
                min="0"
                max="80"
                step="0.1"
            >
            @error('experience_years')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="skills">Skills</label>
            <textarea
                class="form-control @error('skills') is-invalid @enderror"
                id="skills"
                name="skills"
                rows="4"
                placeholder="PHP, Laravel, MySQL, stakeholder communication"
            >{{ old('skills', $candidate?->skills) }}</textarea>
            @error('skills')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="expected_salary">Expected salary</label>
            <input
                class="form-control @error('expected_salary') is-invalid @enderror"
                id="expected_salary"
                name="expected_salary"
                type="number"
                value="{{ old('expected_salary', $candidate?->expected_salary) }}"
                min="0"
                step="0.01"
            >
            @error('expected_salary')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="availability">Availability</label>
            <input
                class="form-control @error('availability') is-invalid @enderror"
                id="availability"
                name="availability"
                type="text"
                value="{{ old('availability', $candidate?->availability) }}"
                maxlength="100"
                placeholder="Immediate or one month"
            >
            @error('availability')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Candidate record</h2>
        <p>Profile source and administrative status.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="source">Source</label>
            <input
                class="form-control @error('source') is-invalid @enderror"
                id="source"
                name="source"
                type="text"
                value="{{ old('source', $candidate?->source) }}"
                maxlength="100"
                placeholder="Referral, LinkedIn, or career site"
            >
            @error('source')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="status">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach (App\Models\Candidate::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $candidate?->status ?? 'new') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-actions">
    <a
        class="btn btn-outline-secondary"
        href="{{ $candidate ? route('candidates.show', $candidate) : route('candidates.index') }}"
    >
        Cancel
    </a>
    <button class="btn btn-primary" type="submit">
        {{ $candidate ? 'Save changes' : 'Create candidate' }}
    </button>
</div>
