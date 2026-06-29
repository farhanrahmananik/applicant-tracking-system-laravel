<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogExportService
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogQueryService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function download(array $filters): StreamedResponse
    {
        $filename = 'audit-logs-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the audit CSV output stream.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Timestamp',
                'Actor',
                'Actor Email',
                'Action',
                'Entity Type',
                'Entity ID',
                'Summary',
            ], ',', '"', '', "\r\n");

            $logs = $this->auditLogQueryService
                ->filteredQuery($filters)
                ->with('actor:id,name,email')
                ->lazyById(500);

            foreach ($logs as $log) {
                fputcsv($stream, [
                    $log->created_at?->toIso8601String(),
                    $this->sanitizeCell($log->actor?->name ?? 'System'),
                    $this->sanitizeCell($log->actor?->email ?? ''),
                    $log->action,
                    $log->entity_type,
                    $log->auditable_id,
                    $this->sanitizeCell($log->summary),
                ], ',', '"', '', "\r\n");
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function sanitizeCell(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
