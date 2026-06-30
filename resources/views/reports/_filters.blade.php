@if ($errors->any())
    <div class="alert alert-danger mt-4" role="alert">
        Review the report filters and try again.
    </div>
@endif

<form class="report-filter-panel" method="GET" action="{{ route($routeName) }}">
    @if (in_array('dates', $fields, true))
        <div>
            <label class="form-label" for="date_from">From date</label>
            <input
                class="form-control @error('date_from') is-invalid @enderror"
                id="date_from"
                name="date_from"
                type="date"
                value="{{ old('date_from', $filters['date_from'] ?? '') }}"
            >
        </div>
        <div>
            <label class="form-label" for="date_to">To date</label>
            <input
                class="form-control @error('date_to') is-invalid @enderror"
                id="date_to"
                name="date_to"
                type="date"
                value="{{ old('date_to', $filters['date_to'] ?? '') }}"
            >
        </div>
    @endif

    @if (in_array('company', $fields, true))
        <div>
            <label class="form-label" for="company_id">Company</label>
            <select class="form-select" id="company_id" name="company_id">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? null) == $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('department', $fields, true))
        <div>
            <label class="form-label" for="department_id">Department</label>
            <select class="form-select" id="department_id" name="department_id">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? null) == $department->id)>
                        {{ $department->name }}@if ($department->company) - {{ $department->company->name }}@endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('job', $fields, true))
        <div>
            <label class="form-label" for="job_posting_id">Job posting</label>
            <select class="form-select" id="job_posting_id" name="job_posting_id">
                <option value="">All job postings</option>
                @foreach ($jobPostings as $jobPosting)
                    <option value="{{ $jobPosting->id }}" @selected(($filters['job_posting_id'] ?? null) == $jobPosting->id)>
                        {{ $jobPosting->title }}@if ($jobPosting->company) - {{ $jobPosting->company->name }}@endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('application_status', $fields, true))
        <div>
            <label class="form-label" for="application_status">Application status</label>
            <select class="form-select" id="application_status" name="application_status">
                <option value="">All statuses</option>
                @foreach (\App\Models\Application::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['application_status'] ?? null) === $status)>
                        {{ \Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('pipeline_stage', $fields, true))
        <div>
            <label class="form-label" for="pipeline_stage">Pipeline stage</label>
            <select class="form-select" id="pipeline_stage" name="pipeline_stage">
                <option value="">All stages</option>
                @foreach (\App\Models\Application::PIPELINE_STAGES as $stage)
                    <option value="{{ $stage }}" @selected(($filters['pipeline_stage'] ?? null) === $stage)>
                        {{ \Illuminate\Support\Str::headline($stage) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('candidate_status', $fields, true))
        <div>
            <label class="form-label" for="candidate_status">Candidate status</label>
            <select class="form-select" id="candidate_status" name="candidate_status">
                <option value="">All statuses</option>
                @foreach (\App\Models\Candidate::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['candidate_status'] ?? null) === $status)>
                        {{ \Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('candidate_source', $fields, true))
        <div>
            <label class="form-label" for="candidate_source">Candidate source</label>
            <select class="form-select" id="candidate_source" name="candidate_source">
                <option value="">All sources</option>
                @foreach ($candidateSources as $source)
                    <option value="{{ $source }}" @selected(($filters['candidate_source'] ?? null) === $source)>
                        {{ $source }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('interviewer', $fields, true))
        <div>
            <label class="form-label" for="interviewer_id">Interviewer</label>
            <select class="form-select" id="interviewer_id" name="interviewer_id">
                <option value="">All interviewers</option>
                @foreach ($interviewers as $interviewer)
                    <option value="{{ $interviewer->id }}" @selected(($filters['interviewer_id'] ?? null) == $interviewer->id)>
                        {{ $interviewer->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('interview_status', $fields, true))
        <div>
            <label class="form-label" for="interview_status">Schedule status</label>
            <select class="form-select" id="interview_status" name="interview_status">
                <option value="">All statuses</option>
                @foreach (\App\Models\InterviewSchedule::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['interview_status'] ?? null) === $status)>
                        {{ \Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (in_array('offer_status', $fields, true))
        <div>
            <label class="form-label" for="offer_status">Offer status</label>
            <select class="form-select" id="offer_status" name="offer_status">
                <option value="">All statuses</option>
                @foreach (\App\Models\Offer::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['offer_status'] ?? null) === $status)>
                        {{ \Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="report-filter-actions">
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Apply filters</button>
        <a class="btn btn-outline-secondary" href="{{ route($routeName) }}">Reset</a>
    </div>
</form>
