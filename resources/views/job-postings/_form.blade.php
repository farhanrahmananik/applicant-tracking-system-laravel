@php
    $jobPosting = $jobPosting ?? null;
@endphp

<div class="form-section">
    <div class="form-section-heading">
        <h2>Position and ownership</h2>
        <p>Define the vacancy and its place in the organization.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="company_id">Company</label>
            <select
                class="form-select @error('company_id') is-invalid @enderror"
                id="company_id"
                name="company_id"
                required
                data-company-select
                data-department-target="department_id"
            >
                <option value="">Select a company</option>
                @foreach ($companies as $companyOption)
                    <option
                        value="{{ $companyOption->id }}"
                        @selected((string) old('company_id', $jobPosting?->company_id) === (string) $companyOption->id)
                    >
                        {{ $companyOption->name }}{{ $companyOption->is_active ? '' : ' (Inactive)' }}
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="department_id">Department</label>
            <select
                class="form-select @error('department_id') is-invalid @enderror"
                id="department_id"
                name="department_id"
            >
                <option value="">No department</option>
                @foreach ($departments as $departmentOption)
                    <option
                        value="{{ $departmentOption->id }}"
                        data-company-id="{{ $departmentOption->company_id }}"
                        @selected((string) old('department_id', $jobPosting?->department_id) === (string) $departmentOption->id)
                    >
                        {{ $departmentOption->company->name }} - {{ $departmentOption->name }}{{ $departmentOption->is_active ? '' : ' (Inactive)' }}
                    </option>
                @endforeach
            </select>
            @error('department_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-lg-8">
            <label class="form-label" for="title">Job title</label>
            <input
                class="form-control @error('title') is-invalid @enderror"
                id="title"
                name="title"
                type="text"
                value="{{ old('title', $jobPosting?->title) }}"
                maxlength="255"
                required
                autofocus
            >
            @error('title')
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
                value="{{ old('slug', $jobPosting?->slug) }}"
                maxlength="255"
                placeholder="Generated from job title"
            >
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="employment_type">Employment type</label>
            <select
                class="form-select @error('employment_type') is-invalid @enderror"
                id="employment_type"
                name="employment_type"
                required
            >
                <option value="">Select employment type</option>
                @foreach (App\Models\JobPosting::EMPLOYMENT_TYPES as $employmentType)
                    <option value="{{ $employmentType }}" @selected(old('employment_type', $jobPosting?->employment_type) === $employmentType)>
                        {{ Illuminate\Support\Str::headline($employmentType) }}
                    </option>
                @endforeach
            </select>
            @error('employment_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="workplace_type">Workplace type</label>
            <select
                class="form-select @error('workplace_type') is-invalid @enderror"
                id="workplace_type"
                name="workplace_type"
                required
            >
                <option value="">Select workplace type</option>
                @foreach (App\Models\JobPosting::WORKPLACE_TYPES as $workplaceType)
                    <option value="{{ $workplaceType }}" @selected(old('workplace_type', $jobPosting?->workplace_type) === $workplaceType)>
                        {{ Illuminate\Support\Str::headline($workplaceType) }}
                    </option>
                @endforeach
            </select>
            @error('workplace_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="experience_level">Experience level</label>
            <input
                class="form-control @error('experience_level') is-invalid @enderror"
                id="experience_level"
                name="experience_level"
                type="text"
                value="{{ old('experience_level', $jobPosting?->experience_level) }}"
                maxlength="100"
                placeholder="Mid level"
            >
            @error('experience_level')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-8">
            <label class="form-label" for="location">Location</label>
            <input
                class="form-control @error('location') is-invalid @enderror"
                id="location"
                name="location"
                type="text"
                value="{{ old('location', $jobPosting?->location) }}"
                maxlength="255"
                placeholder="Berlin, Germany"
            >
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="openings">Openings</label>
            <input
                class="form-control @error('openings') is-invalid @enderror"
                id="openings"
                name="openings"
                type="number"
                value="{{ old('openings', $jobPosting?->openings ?? 1) }}"
                min="1"
                required
            >
            @error('openings')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Compensation</h2>
        <p>Optional salary range and currency.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="salary_min">Minimum salary</label>
            <input
                class="form-control @error('salary_min') is-invalid @enderror"
                id="salary_min"
                name="salary_min"
                type="number"
                value="{{ old('salary_min', $jobPosting?->salary_min) }}"
                min="0"
                step="0.01"
            >
            @error('salary_min')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="salary_max">Maximum salary</label>
            <input
                class="form-control @error('salary_max') is-invalid @enderror"
                id="salary_max"
                name="salary_max"
                type="number"
                value="{{ old('salary_max', $jobPosting?->salary_max) }}"
                min="0"
                step="0.01"
            >
            @error('salary_max')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="currency">Currency</label>
            <input
                class="form-control text-uppercase @error('currency') is-invalid @enderror"
                id="currency"
                name="currency"
                type="text"
                value="{{ old('currency', $jobPosting?->currency ?? 'EUR') }}"
                maxlength="3"
            >
            @error('currency')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Job content</h2>
        <p>Candidate-facing role information.</p>
    </div>

    <div class="row g-3">
        @foreach ([
            'description' => ['Description', true],
            'requirements' => ['Requirements', false],
            'responsibilities' => ['Responsibilities', false],
            'benefits' => ['Benefits', false],
        ] as $field => [$label, $required])
            <div class="col-12">
                <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                <textarea
                    class="form-control @error($field) is-invalid @enderror"
                    id="{{ $field }}"
                    name="{{ $field }}"
                    rows="{{ $field === 'description' ? 6 : 4 }}"
                    @if ($required) required @endif
                >{{ old($field, $jobPosting?->{$field}) }}</textarea>
                @error($field)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Publication</h2>
        <p>Control lifecycle and application window dates.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="status">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach (App\Models\JobPosting::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $jobPosting?->status ?? 'draft') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="published_at">Published at</label>
            <input
                class="form-control @error('published_at') is-invalid @enderror"
                id="published_at"
                name="published_at"
                type="datetime-local"
                value="{{ old('published_at', $jobPosting?->published_at?->format('Y-m-d\TH:i')) }}"
            >
            @error('published_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="closes_at">Closes at</label>
            <input
                class="form-control @error('closes_at') is-invalid @enderror"
                id="closes_at"
                name="closes_at"
                type="date"
                value="{{ old('closes_at', $jobPosting?->closes_at?->format('Y-m-d')) }}"
            >
            @error('closes_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-actions">
    <a
        class="btn btn-outline-secondary"
        href="{{ $jobPosting ? route('job-postings.show', $jobPosting) : route('job-postings.index') }}"
    >
        <i class="bi bi-x-lg" aria-hidden="true"></i>Cancel
    </a>
    <button class="btn btn-primary" type="submit">
        <i class="bi bi-check2" aria-hidden="true"></i>
        {{ $jobPosting ? 'Save changes' : 'Create job posting' }}
    </button>
</div>

@push('scripts')
    <script src="{{ asset('js/job-posting-form.js') }}"></script>
@endpush
