<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCandidateRequest;
use App\Http\Requests\UpdateCandidateRequest;
use App\Models\Candidate;
use App\Services\CandidateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function __construct(
        private readonly CandidateService $candidateService,
    ) {}

    public function index(Request $request): View
    {
        return view('candidates.index', [
            'candidates' => $this->candidateService->paginate($request->only([
                'search',
                'status',
                'source',
                'availability',
                'experience_min',
                'experience_max',
            ])),
            'sources' => $this->candidateService->sourceOptions(),
            'availabilities' => $this->candidateService->availabilityOptions(),
        ]);
    }

    public function create(): View
    {
        return view('candidates.create');
    }

    public function store(StoreCandidateRequest $request): RedirectResponse
    {
        $candidate = $this->candidateService->create($request->validated());

        return redirect()
            ->route('candidates.show', $candidate)
            ->with('success', 'Candidate created successfully.');
    }

    public function show(Candidate $candidate): View
    {
        $candidate->load('resumes.uploadedBy:id,name');

        $candidateApplications = collect();

        if (Gate::allows('applications.view')) {
            $candidate->loadCount('applications');
            $candidateApplications = $candidate->applications()
                ->with(['jobPosting:id,company_id,title,status,deleted_at', 'jobPosting.company:id,name,deleted_at'])
                ->limit(8)
                ->get();
        }

        return view('candidates.show', compact('candidate', 'candidateApplications'));
    }

    public function edit(Candidate $candidate): View
    {
        return view('candidates.edit', compact('candidate'));
    }

    public function update(UpdateCandidateRequest $request, Candidate $candidate): RedirectResponse
    {
        $candidate = $this->candidateService->update($candidate, $request->validated());

        return redirect()
            ->route('candidates.show', $candidate)
            ->with('success', 'Candidate updated successfully.');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->candidateService->delete($candidate);

        return redirect()
            ->route('candidates.index')
            ->with('success', 'Candidate deleted successfully.');
    }
}
