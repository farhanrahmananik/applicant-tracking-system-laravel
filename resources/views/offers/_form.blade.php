@php
    $offer = $offer ?? null;
    $selectedApplicationId = old('application_id', request('application_id'));
@endphp

@if ($offer)
    <section class="offer-form-context" aria-label="Offer application">
        <div>
            <span>Candidate</span>
            <strong>{{ $offer->application->candidate->full_name }}</strong>
        </div>
        <div>
            <span>Position</span>
            <strong>{{ $offer->application->jobPosting->title }}</strong>
        </div>
        <div>
            <span>Company</span>
            <strong>{{ $offer->application->jobPosting->company->name }}</strong>
        </div>
    </section>
@endif

<div class="form-section">
    <div class="form-section-heading">
        <h2>Offer details</h2>
        <p>Define the selected application and proposed role.</p>
    </div>

    <div class="row g-3">
        @unless ($offer)
            <div class="col-12">
                <label class="form-label" for="application_id">Selected application</label>
                <select class="form-select @error('application_id') is-invalid @enderror" id="application_id" name="application_id" required autofocus>
                    <option value="">Select an application</option>
                    @foreach ($applications as $applicationOption)
                        <option value="{{ $applicationOption->id }}" @selected((string) $selectedApplicationId === (string) $applicationOption->id)>
                            {{ $applicationOption->candidate->full_name }} - {{ $applicationOption->jobPosting->title }} at {{ $applicationOption->jobPosting->company->name }}
                        </option>
                    @endforeach
                </select>
                @error('application_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endunless

        <div class="col-md-8">
            <label class="form-label" for="offer_title">Offer title</label>
            <input
                class="form-control @error('offer_title') is-invalid @enderror"
                id="offer_title"
                name="offer_title"
                type="text"
                value="{{ old('offer_title', $offer?->offer_title) }}"
                maxlength="255"
                required
                @if ($offer) autofocus @endif
            >
            @error('offer_title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="employment_type">Employment type</label>
            <select class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type" required>
                <option value="">Select employment type</option>
                @foreach (App\Models\JobPosting::EMPLOYMENT_TYPES as $employmentType)
                    <option value="{{ $employmentType }}" @selected(old('employment_type', $offer?->employment_type) === $employmentType)>
                        {{ Illuminate\Support\Str::headline($employmentType) }}
                    </option>
                @endforeach
            </select>
            @error('employment_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Compensation and dates</h2>
        <p>Record the proposed salary and response timeline.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label" for="salary_amount">Salary amount</label>
            <input
                class="form-control @error('salary_amount') is-invalid @enderror"
                id="salary_amount"
                name="salary_amount"
                type="number"
                value="{{ old('salary_amount', $offer?->salary_amount) }}"
                min="0.01"
                step="0.01"
                required
            >
            @error('salary_amount')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="currency">Currency</label>
            <input
                class="form-control text-uppercase @error('currency') is-invalid @enderror"
                id="currency"
                name="currency"
                type="text"
                value="{{ old('currency', $offer?->currency ?? 'EUR') }}"
                minlength="3"
                maxlength="3"
                required
            >
            @error('currency')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-5">
            <label class="form-label" for="expiry_date">Offer expiry date</label>
            <input
                class="form-control @error('expiry_date') is-invalid @enderror"
                id="expiry_date"
                name="expiry_date"
                type="date"
                value="{{ old('expiry_date', $offer?->expiry_date?->format('Y-m-d') ?? now()->addWeeks(2)->toDateString()) }}"
                required
            >
            @error('expiry_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-5">
            <label class="form-label" for="expected_joining_date">Expected joining date</label>
            <input
                class="form-control @error('expected_joining_date') is-invalid @enderror"
                id="expected_joining_date"
                name="expected_joining_date"
                type="date"
                value="{{ old('expected_joining_date', $offer?->expected_joining_date?->format('Y-m-d')) }}"
            >
            @error('expected_joining_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Internal notes</h2>
        <p>Optional context for the HR and recruitment team.</p>
    </div>

    <textarea
        class="form-control @error('notes') is-invalid @enderror"
        id="notes"
        name="notes"
        rows="6"
        maxlength="5000"
        placeholder="Add offer-specific context"
    >{{ old('notes', $offer?->notes) }}</textarea>
    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-actions">
    <a class="btn btn-outline-secondary" href="{{ $offer ? route('offers.show', $offer) : route('offers.index') }}"><i class="bi bi-x-lg" aria-hidden="true"></i>Cancel</a>
    <button class="btn btn-primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>{{ $offer ? 'Save changes' : 'Create offer' }}</button>
</div>
