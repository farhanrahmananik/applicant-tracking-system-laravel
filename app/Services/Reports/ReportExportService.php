<?php

namespace App\Services\Reports;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applications(array $filters): StreamedResponse
    {
        $rows = $this->reportService->applicationSummary($filters)['rows']->map(
            fn (array $row): array => [$row['label'], $row['count'], $row['percentage'].'%'],
        );

        return $this->download('application-summary', ['Status', 'Applications', 'Share'], $rows);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function jobPostings(array $filters): StreamedResponse
    {
        $rows = $this->reportService->jobPostingPerformance($filters)['rows']->map(
            fn (array $row): array => [
                $row['jobPosting']->title,
                $row['jobPosting']->company?->name,
                $row['jobPosting']->department?->name,
                $row['jobPosting']->status,
                $row['applications'],
                $row['candidates'],
                $row['interviews'],
                $row['offers'],
            ],
        );

        return $this->download('job-posting-performance', [
            'Job Posting',
            'Company',
            'Department',
            'Status',
            'Applications',
            'Unique Candidates',
            'Interviews',
            'Offers',
        ], $rows);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function interviews(array $filters): StreamedResponse
    {
        $report = $this->reportService->interviewSummary($filters);
        $rows = $report['statusRows']->map(
            fn (array $row): array => ['Schedule status', $row['label'], $row['count'], $row['percentage'].'%'],
        )->concat($report['outcomeRows']->map(
            fn (array $row): array => ['Feedback outcome', $row['label'], $row['count'], $row['percentage'].'%'],
        ));

        return $this->download(
            'interview-schedule-outcome',
            ['Category', 'Status or outcome', 'Count', 'Share'],
            $rows,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function pipeline(array $filters): StreamedResponse
    {
        $rows = $this->reportService->pipelineSummary($filters)['rows']->map(
            fn (array $row): array => [$row['label'], $row['count'], $row['percentage'].'%'],
        );

        return $this->download('hiring-pipeline-stage', ['Pipeline stage', 'Applications', 'Share'], $rows);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function offers(array $filters): StreamedResponse
    {
        $rows = $this->reportService->offerSummary($filters)['rows']->map(
            fn (array $row): array => [$row['label'], $row['count'], $row['percentage'].'%'],
        );

        return $this->download('offer-status', ['Offer status', 'Offers', 'Share'], $rows);
    }

    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private function download(string $name, array $headings, iterable $rows): StreamedResponse
    {
        $filename = $name.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($headings, $rows): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headings, ',', '"', '', "\r\n");

            foreach ($rows as $row) {
                fputcsv(
                    $stream,
                    array_map($this->sanitizeCell(...), $row),
                    ',',
                    '"',
                    '',
                    "\r\n",
                );
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function sanitizeCell(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
