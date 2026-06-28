@php
    $feedback = $feedback ?? null;
@endphp

<div class="form-section">
    <div class="form-section-heading">
        <h2>Assessment</h2>
        <p>Record a concise evaluation and hiring recommendation.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <label class="form-label" for="summary">Summary</label>
            <textarea
                class="form-control @error('summary') is-invalid @enderror"
                id="summary"
                name="summary"
                rows="6"
                maxlength="2000"
                required
                autofocus
                placeholder="Summarize the interview evidence and overall assessment"
            >{{ old('summary', $feedback?->summary) }}</textarea>
            @error('summary')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-lg-4">
            <div class="feedback-decision-panel">
                <div>
                    <label class="form-label" for="recommendation">Recommendation</label>
                    <select class="form-select @error('recommendation') is-invalid @enderror" id="recommendation" name="recommendation" required>
                        <option value="">Select recommendation</option>
                        @foreach (App\Models\InterviewFeedback::RECOMMENDATIONS as $recommendation)
                            <option value="{{ $recommendation }}" @selected(old('recommendation', $feedback?->recommendation) === $recommendation)>
                                {{ Illuminate\Support\Str::headline($recommendation) }}
                            </option>
                        @endforeach
                    </select>
                    @error('recommendation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <fieldset>
                    <legend class="form-label">Rating</legend>
                    <div class="feedback-rating-control">
                        @for ($rating = 1; $rating <= 5; $rating++)
                            <input
                                class="btn-check"
                                id="rating_{{ $rating }}"
                                name="rating"
                                type="radio"
                                value="{{ $rating }}"
                                @checked((int) old('rating', $feedback?->rating) === $rating)
                                required
                            >
                            <label class="feedback-rating-option" for="rating_{{ $rating }}">{{ $rating }}</label>
                        @endfor
                    </div>
                    @error('rating')
                        <div class="feedback-field-error">{{ $message }}</div>
                    @enderror
                </fieldset>
            </div>
        </div>
    </div>
</div>

<div class="form-section">
    <div class="form-section-heading">
        <h2>Evidence</h2>
        <p>Capture role-relevant strengths and areas of concern.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <label class="form-label" for="strengths">Strengths</label>
            <textarea
                class="form-control @error('strengths') is-invalid @enderror"
                id="strengths"
                name="strengths"
                rows="7"
                maxlength="5000"
                placeholder="Skills, experience, and evidence that support the recommendation"
            >{{ old('strengths', $feedback?->strengths) }}</textarea>
            @error('strengths')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-lg-6">
            <label class="form-label" for="weaknesses">Weaknesses</label>
            <textarea
                class="form-control @error('weaknesses') is-invalid @enderror"
                id="weaknesses"
                name="weaknesses"
                rows="7"
                maxlength="5000"
                placeholder="Gaps, concerns, or evidence that should be considered"
            >{{ old('weaknesses', $feedback?->weaknesses) }}</textarea>
            @error('weaknesses')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-actions">
    <a
        class="btn btn-outline-secondary"
        href="{{ $feedback ? route('interview-feedback.show', $feedback) : route('interviews.show', $interview) }}"
    >
        Cancel
    </a>
    <button class="btn btn-primary" type="submit">
        {{ $feedback ? 'Save feedback' : 'Submit feedback' }}
    </button>
</div>
