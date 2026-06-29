<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\ReportFilterRequest;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportExportService $reportExportService,
    ) {}

    public function index(): View
    {
        return view('reports.index', [
            'metrics' => $this->reportService->overview(),
        ]);
    }

    public function applications(ReportFilterRequest $request): View
    {
        return $this->reportView('reports.applications', $request, 'applicationSummary');
    }

    public function candidates(ReportFilterRequest $request): View
    {
        return $this->reportView('reports.candidates', $request, 'candidateSummary');
    }

    public function jobPostings(ReportFilterRequest $request): View
    {
        return $this->reportView('reports.job-postings', $request, 'jobPostingPerformance');
    }

    public function interviews(ReportFilterRequest $request): View
    {
        return $this->reportView('reports.interviews', $request, 'interviewSummary');
    }

    public function pipeline(ReportFilterRequest $request): View
    {
        return $this->reportView('reports.pipeline', $request, 'pipelineSummary');
    }

    public function offers(ReportFilterRequest $request): View
    {
        return $this->reportView('reports.offers', $request, 'offerSummary');
    }

    public function exportApplications(ReportFilterRequest $request): StreamedResponse
    {
        return $this->reportExportService->applications($request->validated());
    }

    public function exportJobPostings(ReportFilterRequest $request): StreamedResponse
    {
        return $this->reportExportService->jobPostings($request->validated());
    }

    public function exportInterviews(ReportFilterRequest $request): StreamedResponse
    {
        return $this->reportExportService->interviews($request->validated());
    }

    public function exportPipeline(ReportFilterRequest $request): StreamedResponse
    {
        return $this->reportExportService->pipeline($request->validated());
    }

    public function exportOffers(ReportFilterRequest $request): StreamedResponse
    {
        return $this->reportExportService->offers($request->validated());
    }

    private function reportView(
        string $view,
        ReportFilterRequest $request,
        string $reportMethod,
    ): View {
        $filters = $request->validated();

        return view($view, [
            ...$this->reportService->{$reportMethod}($filters),
            ...$this->reportService->filterOptions(),
            'filters' => $filters,
        ]);
    }
}
