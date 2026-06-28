@php
    $interview = $interview ?? null;
    $selectedApplicationId = old('application_id', $interview?->application_id ?? request('application_id'));
    $selectedInterviewerId = old('interviewer_id', $interview?->interviewer_id);
@endphp

<div class="form-section">
    <div class="form-section-heading">
        <h2>Application and interviewer</h2>
        <p>Connect this schedule to an active application and an internal ATS user.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <label class="form-label" for="application_id">Application</label>
            <select class="form-select @error('application_id') is-invalid @enderror" id="application_id" name="application_id" required autofocus>
                <option value="">Select an application</option>
                @foreach ($applications as $applicationOption)
                    <option value="{{ $applicationOption->id }}" @selected((string) $selectedApplicationId === (string) $applicationOption->id)>
                        {{ $applicationOption->candidate->full_name }} - {{ $applicationOption->jobPosting->title }} at {{ $applicationOption->jobPosting->company->name }} ({{ Illuminate\Support\Str::headline($applicationOption->current_status) }})
                    </option>
                @endforeach
            </select>
            @error('application_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-lg-5">
            <label class="form-label" for="interviewer_id">Interviewer</label>
            <select class="form-select @error('interviewer_id') is-invalid @enderror" id="interviewer_id" name="interviewer_id" required>
                <option value="">Select an interviewer</option>
                @foreach ($interviewers as $interviewerOption)
                    <option value="{{ $interviewerOption->id }}" @selected((string) $selectedInterviewerId === (string) $interviewerOption->id)>
                        {{ $interviewerOption->name }} - {{ $interviewerOption->email }}{{ $interviewerOption->is_active ? '' : ' (Inactive)' }}
                    </option>
                @endforeach
            </select>
            @error('interviewer_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Schedule</h2>
        <p>Set the interview format, time, and duration.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="type">Type</label>
            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                <option value="">Select type</option>
                @foreach (App\Models\InterviewSchedule::TYPES as $type)
                    <option value="{{ $type }}" @selected(old('type', $interview?->type) === $type)>
                        {{ Illuminate\Support\Str::headline($type) }}
                    </option>
                @endforeach
            </select>
            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach (App\Models\InterviewSchedule::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $interview?->status ?? 'scheduled') === $status)>
                        {{ Illuminate\Support\Str::headline($status) }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label" for="scheduled_at">Scheduled at</label>
            <input
                class="form-control @error('scheduled_at') is-invalid @enderror"
                id="scheduled_at"
                name="scheduled_at"
                type="datetime-local"
                value="{{ old('scheduled_at', $interview?->scheduled_at?->format('Y-m-d\TH:i') ?? now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i')) }}"
                required
            >
            @error('scheduled_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-2">
            <label class="form-label" for="duration_minutes">Duration</label>
            <input
                class="form-control @error('duration_minutes') is-invalid @enderror"
                id="duration_minutes"
                name="duration_minutes"
                type="number"
                value="{{ old('duration_minutes', $interview?->duration_minutes ?? 60) }}"
                min="15"
                max="480"
                step="15"
                required
            >
            @error('duration_minutes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Location and context</h2>
        <p>Add joining details and internal scheduling notes.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="location">Location</label>
            <input
                class="form-control @error('location') is-invalid @enderror"
                id="location"
                name="location"
                type="text"
                value="{{ old('location', $interview?->location) }}"
                maxlength="255"
                placeholder="Office, room, or phone contact"
            >
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label" for="meeting_link">Meeting link</label>
            <input
                class="form-control @error('meeting_link') is-invalid @enderror"
                id="meeting_link"
                name="meeting_link"
                type="url"
                value="{{ old('meeting_link', $interview?->meeting_link) }}"
                maxlength="2048"
                placeholder="https://meet.example.com/session"
            >
            @error('meeting_link')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="notes">Notes</label>
            <textarea
                class="form-control @error('notes') is-invalid @enderror"
                id="notes"
                name="notes"
                rows="5"
                maxlength="5000"
                placeholder="Add preparation or scheduling context"
            >{{ old('notes', $interview?->notes) }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-actions">
    <a class="btn btn-outline-secondary" href="{{ $interview ? route('interviews.show', $interview) : route('interviews.index') }}">Cancel</a>
    <button class="btn btn-primary" type="submit">
        {{ $interview ? 'Save changes' : 'Schedule interview' }}
    </button>
</div>
