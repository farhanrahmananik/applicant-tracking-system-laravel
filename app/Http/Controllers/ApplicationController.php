<?php

namespace App\Http\Controllers;

use App\Http\Requests\Application\StoreApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationRequest;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\HiringPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly HiringPipelineService $hiringPipelineService,
    ) {}

    public function index(Request $request): View
    {
        return view('applications.index', [
            'applications' => $this->applicationService->paginate($request->only([
                'search',
                'current_status',
                'job_posting_id',
                'source',
            ])),
            'jobPostings' => $this->applicationService->jobPostingOptions(),
            'sources' => $this->applicationService->sourceOptions(),
        ]);
    }

    public function create(): View
    {
        return view('applications.create', [
            'candidates' => $this->applicationService->candidateOptions(),
            'jobPostings' => $this->applicationService->jobPostingOptions(),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $application = $this->applicationService->create($request->validated());

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application created successfully.');
    }

    public function show(Application $application): View
    {
        $application->load(['candidate', 'jobPosting.company', 'jobPosting.department', 'createdBy', 'updatedBy']);

        $applicationInterviews = collect();
        $stageHistories = collect();
        $pipelineTransitions = [];

        if (Gate::allows('pipeline.view')) {
            $stageHistories = $application->stageHistories()
                ->with('changedBy:id,name')
                ->limit(20)
                ->get();
            $pipelineTransitions = $this->hiringPipelineService->allowedTransitions(
                $application->current_status,
            );
        }

        if (Gate::allows('interviews.view')) {
            $application->loadCount('interviewSchedules');
            $relations = ['interviewer:id,name,email'];

            if (Gate::allows('interview-feedback.view')) {
                $relations[] = 'feedback:id,interview_schedule_id,rating,recommendation,submitted_at';
            }

            $applicationInterviews = $application->interviewSchedules()
                ->with($relations)
                ->limit(8)
                ->get();
        }

        return view('applications.show', compact(
            'application',
            'applicationInterviews',
            'stageHistories',
            'pipelineTransitions',
        ));
    }

    public function edit(Application $application): View
    {
        return view('applications.edit', [
            'application' => $application->load(['candidate', 'jobPosting']),
            'candidates' => $this->applicationService->candidateOptions(),
            'jobPostings' => $this->applicationService->jobPostingOptions(),
            'pipelineTransitions' => $this->hiringPipelineService->allowedTransitions(
                $application->current_status,
            ),
        ]);
    }

    public function update(
        UpdateApplicationRequest $request,
        Application $application,
    ): RedirectResponse {
        $application = $this->applicationService->update($application, $request->validated());

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application updated successfully.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $this->applicationService->delete($application);

        return redirect()
            ->route('applications.index')
            ->with('success', 'Application deleted successfully.');
    }
}
