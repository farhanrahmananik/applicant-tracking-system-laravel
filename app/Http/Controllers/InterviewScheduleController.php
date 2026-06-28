<?php

namespace App\Http\Controllers;

use App\Http\Requests\Interview\StoreInterviewScheduleRequest;
use App\Http\Requests\Interview\UpdateInterviewScheduleRequest;
use App\Models\InterviewSchedule;
use App\Services\InterviewScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InterviewScheduleController extends Controller
{
    public function __construct(
        private readonly InterviewScheduleService $interviewScheduleService,
    ) {}

    public function index(Request $request): View
    {
        return view('interviews.index', [
            'interviews' => $this->interviewScheduleService->paginate($request->only([
                'search',
                'type',
                'status',
                'date_from',
                'date_to',
            ])),
        ]);
    }

    public function create(): View
    {
        return view('interviews.create', [
            'applications' => $this->interviewScheduleService->applicationOptions(),
            'interviewers' => $this->interviewScheduleService->interviewerOptions(),
        ]);
    }

    public function store(StoreInterviewScheduleRequest $request): RedirectResponse
    {
        $interview = $this->interviewScheduleService->create($request->validated());

        return redirect()
            ->route('interviews.show', $interview)
            ->with('success', 'Interview scheduled successfully.');
    }

    public function show(InterviewSchedule $interview): View
    {
        $interview->load([
            'application.candidate',
            'application.jobPosting.company',
            'application.jobPosting.department',
            'interviewer',
            'createdBy',
            'updatedBy',
        ]);

        $interviewFeedback = collect();

        if (Gate::any(['interview-feedback.view', 'interview-feedback.create'])) {
            $interviewFeedback = $interview->feedback()
                ->with('submittedBy:id,name,email')
                ->get();
        }

        return view('interviews.show', compact('interview', 'interviewFeedback'));
    }

    public function edit(InterviewSchedule $interview): View
    {
        return view('interviews.edit', [
            'interview' => $interview->load(['application.candidate', 'application.jobPosting', 'interviewer']),
            'applications' => $this->interviewScheduleService->applicationOptions($interview->application_id),
            'interviewers' => $this->interviewScheduleService->interviewerOptions($interview->interviewer_id),
        ]);
    }

    public function update(
        UpdateInterviewScheduleRequest $request,
        InterviewSchedule $interview,
    ): RedirectResponse {
        $interview = $this->interviewScheduleService->update($interview, $request->validated());

        return redirect()
            ->route('interviews.show', $interview)
            ->with('success', 'Interview updated successfully.');
    }

    public function destroy(InterviewSchedule $interview): RedirectResponse
    {
        $this->interviewScheduleService->delete($interview);

        return redirect()
            ->route('interviews.index')
            ->with('success', 'Interview deleted successfully.');
    }
}
