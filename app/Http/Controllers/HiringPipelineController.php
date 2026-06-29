<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pipeline\TransitionApplicationStageRequest;
use App\Models\Application;
use App\Services\HiringPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HiringPipelineController extends Controller
{
    public function __construct(
        private readonly HiringPipelineService $hiringPipelineService,
    ) {}

    public function index(Request $request): View
    {
        return view('pipeline.index', [
            'pipelineColumns' => $this->hiringPipelineService->board($request->only([
                'search',
                'job_posting_id',
            ])),
            'jobPostings' => $this->hiringPipelineService->jobPostingOptions(),
            'transitionMap' => $this->hiringPipelineService->transitionMap(),
        ]);
    }

    public function transition(
        TransitionApplicationStageRequest $request,
        Application $application,
    ): RedirectResponse {
        $data = $request->validated();
        $this->hiringPipelineService->transition(
            $application,
            $data['to_stage'],
            $data['note'] ?? null,
        );

        return back()->with('success', 'Application pipeline stage updated successfully.');
    }
}
