<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCandidateResumeRequest;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Services\CandidateResumeService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateResumeController extends Controller
{
    public function __construct(
        private readonly CandidateResumeService $candidateResumeService,
    ) {}

    public function store(StoreCandidateResumeRequest $request, Candidate $candidate): RedirectResponse
    {
        $this->candidateResumeService->upload(
            $candidate,
            $request->file('resume'),
            $request->boolean('is_primary'),
        );

        return redirect()
            ->route('candidates.show', $candidate)
            ->with('success', 'Resume uploaded successfully.');
    }

    public function download(Candidate $candidate, CandidateResume $resume): StreamedResponse
    {
        return $this->candidateResumeService->download($resume);
    }

    public function destroy(Candidate $candidate, CandidateResume $resume): RedirectResponse
    {
        $this->candidateResumeService->delete($resume);

        return redirect()
            ->route('candidates.show', $candidate)
            ->with('success', 'Resume deleted successfully.');
    }
}
