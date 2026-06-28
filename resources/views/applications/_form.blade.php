@php
    $application = $application ?? null;
    $selectedCandidateId = old('candidate_id', $application?->candidate_id ?? request('candidate_id'));
    $selectedJobPostingId = old('job_posting_id', $application?->job_posting_id ?? request('job_posting_id'));
@endphp

<div class="form-section">
    <div class="form-section-heading">
        <h2>Candidate and position</h2>
        <p>Connect an existing candidate to an existing job posting.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <label class="form-label" for="candidate_id">Candidate</label>
            <select class="form-select @error('candidate_id') is-invalid @enderror" id="candidate_id" name="candidate_id" required autofocus>
                <option value="">Select a candidate</option>
                @foreach ($candidates as $candidateOption)
                    <option value="{{ $candidateOption->id }}" @selected((string) $selectedCandidateId === (string) $candidateOption->id)>
                        {{ $candidateOption->full_name }} - {{ $candidateOption->email }}
                    </option>
                @endforeach
            </select>
            @error('candidate_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-lg-6">
            <label class="form-label" for="job_posting_id">Job posting</label>
            <select class="form-select @error('job_posting_id') is-invalid @enderror" id="job_posting_id" name="job_posting_id" required>
                <option value="">Select a job posting</option>
                @foreach ($jobPostings as $jobPostingOption)
                    <option value="{{ $jobPostingOption->id }}" @selected((string) $selectedJobPostingId === (string) $jobPostingOption->id)>
                        {{ $jobPostingOption->title }} - {{ $jobPostingOption->company->name }} ({{ Illuminate\Support\Str::headline($jobPostingOption->status) }})
                    </option>
                @endforeach
            </select>
            @error('job_posting_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Application details</h2>
        <p>Record how and when the application entered the ATS.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="source">Source</label>
            <input
                class="form-control @error('source') is-invalid @enderror"
                id="source"
                name="source"
                type="text"
                value="{{ old('source', $application?->source) }}"
                maxlength="100"
                placeholder="Referral, career site, job board"
            >
            @error('source')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="applied_date">Applied date</label>
            <input
                class="form-control @error('applied_date') is-invalid @enderror"
                id="applied_date"
                name="applied_date"
                type="date"
                value="{{ old('applied_date', $application?->applied_date?->format('Y-m-d') ?? now()->toDateString()) }}"
                max="{{ now()->toDateString() }}"
                required
            >
            @error('applied_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="current_status">Status</label>
            <select class="form-select @error('current_status') is-invalid @enderror" id="current_status" name="current_status" required>
                @foreach (App\Models\Application::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('current_status', $application?->current_status ?? 'applied') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
            @error('current_status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="notes">Notes</label>
            <textarea
                class="form-control @error('notes') is-invalid @enderror"
                id="notes"
                name="notes"
                rows="6"
                maxlength="5000"
                placeholder="Add relevant context for the recruiting team"
            >{{ old('notes', $application?->notes) }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-actions">
    <a class="btn btn-outline-secondary" href="{{ $application ? route('applications.show', $application) : route('applications.index') }}">Cancel</a>
    <button class="btn btn-primary" type="submit">
        {{ $application ? 'Save changes' : 'Create application' }}
    </button>
</div>
