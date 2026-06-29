<?php

namespace App\Services;

use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterviewFeedbackService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(InterviewSchedule $interview, array $data): InterviewFeedback
    {
        return DB::transaction(function () use ($interview, $data): InterviewFeedback {
            $interview = $this->lockInterview($interview);
            $this->ensureInterviewAcceptsFeedback($interview);

            $submitterId = Auth::id();

            if ($submitterId === null) {
                throw ValidationException::withMessages([
                    'summary' => 'An authenticated user is required to submit feedback.',
                ]);
            }

            $this->ensureFeedbackIsUnique($interview, $submitterId);

            $data['interview_schedule_id'] = $interview->id;
            $data['submitted_by_id'] = $submitterId;
            $data['submitted_at'] = now();

            $feedback = InterviewFeedback::query()->create($data)->load([
                'interviewSchedule.application.candidate',
                'interviewSchedule.application.jobPosting.company',
                'submittedBy',
            ]);
            $this->auditLogService->created(
                $feedback,
                "Interview feedback #{$feedback->getKey()} created.",
            );

            return $feedback;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InterviewFeedback $feedback, array $data): InterviewFeedback
    {
        return DB::transaction(function () use ($feedback, $data): InterviewFeedback {
            $feedback = InterviewFeedback::query()
                ->whereKey($feedback->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $before = $this->auditLogService->snapshot($feedback);
            $interview = $this->lockInterview($feedback->interviewSchedule);
            $this->ensureInterviewAcceptsFeedback($interview);

            $feedback->update($data);
            $this->auditLogService->updated(
                $feedback,
                $before,
                "Interview feedback #{$feedback->getKey()} updated.",
            );

            return $feedback->refresh()->load([
                'interviewSchedule.application.candidate',
                'interviewSchedule.application.jobPosting.company',
                'submittedBy',
            ]);
        }, 3);
    }

    private function lockInterview(InterviewSchedule $interview): InterviewSchedule
    {
        return InterviewSchedule::query()
            ->whereKey($interview->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @throws ValidationException
     */
    private function ensureInterviewAcceptsFeedback(InterviewSchedule $interview): void
    {
        if ($interview->status === 'cancelled') {
            throw ValidationException::withMessages([
                'summary' => 'Feedback cannot be submitted for a cancelled interview.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureFeedbackIsUnique(
        InterviewSchedule $interview,
        int $submitterId,
    ): void {
        $exists = InterviewFeedback::query()
            ->where('interview_schedule_id', $interview->id)
            ->where('submitted_by_id', $submitterId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'summary' => 'You have already submitted feedback for this interview. Edit the existing feedback instead.',
            ]);
        }
    }
}
