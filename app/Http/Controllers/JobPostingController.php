<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobPosting\StoreJobPostingRequest;
use App\Http\Requests\JobPosting\UpdateJobPostingRequest;
use App\Models\JobPosting;
use App\Services\JobPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function __construct(
        private readonly JobPostingService $jobPostingService,
    ) {}

    public function index(Request $request): View
    {
        return view('job-postings.index', [
            'jobPostings' => $this->jobPostingService->paginate($request->only([
                'search',
                'company_id',
                'department_id',
                'status',
                'employment_type',
                'workplace_type',
            ])),
            'companies' => $this->jobPostingService->companyOptions(),
            'departments' => $this->jobPostingService->departmentOptions(),
        ]);
    }

    public function create(): View
    {
        return view('job-postings.create', [
            'companies' => $this->jobPostingService->companyOptions(),
            'departments' => $this->jobPostingService->departmentOptions(),
        ]);
    }

    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $jobPosting = $this->jobPostingService->create($request->validated());

        return redirect()
            ->route('job-postings.show', $jobPosting)
            ->with('success', 'Job posting created successfully.');
    }

    public function show(JobPosting $jobPosting): View
    {
        $jobPosting->load(['company', 'department', 'createdBy', 'updatedBy']);

        return view('job-postings.show', compact('jobPosting'));
    }

    public function edit(JobPosting $jobPosting): View
    {
        return view('job-postings.edit', [
            'jobPosting' => $jobPosting->load(['company', 'department']),
            'companies' => $this->jobPostingService->companyOptions($jobPosting->company_id),
            'departments' => $this->jobPostingService->departmentOptions($jobPosting->department_id),
        ]);
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        $jobPosting = $this->jobPostingService->update($jobPosting, $request->validated());

        return redirect()
            ->route('job-postings.show', $jobPosting)
            ->with('success', 'Job posting updated successfully.');
    }

    public function destroy(JobPosting $jobPosting): RedirectResponse
    {
        $this->jobPostingService->delete($jobPosting);

        return redirect()
            ->route('job-postings.index')
            ->with('success', 'Job posting deleted successfully.');
    }
}
