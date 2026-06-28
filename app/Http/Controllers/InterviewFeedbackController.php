<?php

namespace App\Http\Controllers;

use App\Http\Requests\InterviewFeedback\StoreInterviewFeedbackRequest;
use App\Http\Requests\InterviewFeedback\UpdateInterviewFeedbackRequest;
use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use App\Services\InterviewFeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InterviewFeedbackController extends Controller
{
    public function __construct(
        private readonly InterviewFeedbackService $interviewFeedbackService,
    ) {}

    public function create(InterviewSchedule $interview): View
    {
        $interview->load(['application.candidate', 'application.jobPosting.company', 'interviewer']);

        return view('interview-feedback.create', compact('interview'));
    }

    public function store(
        StoreInterviewFeedbackRequest $request,
        InterviewSchedule $interview,
    ): RedirectResponse {
        $feedback = $this->interviewFeedbackService->create($interview, $request->validated());

        return redirect()
            ->route('interview-feedback.show', $feedback)
            ->with('success', 'Interview feedback submitted successfully.');
    }

    public function show(InterviewFeedback $feedback): View
    {
        $feedback->load([
            'interviewSchedule.application.candidate',
            'interviewSchedule.application.jobPosting.company',
            'interviewSchedule.interviewer',
            'submittedBy',
        ]);

        return view('interview-feedback.show', compact('feedback'));
    }

    public function edit(InterviewFeedback $feedback): View
    {
        $feedback->load([
            'interviewSchedule.application.candidate',
            'interviewSchedule.application.jobPosting.company',
            'submittedBy',
        ]);

        return view('interview-feedback.edit', compact('feedback'));
    }

    public function update(
        UpdateInterviewFeedbackRequest $request,
        InterviewFeedback $feedback,
    ): RedirectResponse {
        $feedback = $this->interviewFeedbackService->update($feedback, $request->validated());

        return redirect()
            ->route('interview-feedback.show', $feedback)
            ->with('success', 'Interview feedback updated successfully.');
    }
}
