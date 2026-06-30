<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditLogFilterRequest;
use App\Models\AuditLog;
use App\Services\AuditLogExportService;
use App\Services\AuditLogQueryService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogQueryService,
        private readonly AuditLogExportService $auditLogExportService,
    ) {}

    public function index(AuditLogFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('audit-logs.index', [
            'auditLogs' => $this->auditLogQueryService->paginate($filters),
            ...$this->auditLogQueryService->filterOptions(),
            'filters' => $filters,
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        return view('audit-logs.show', [
            'auditLog' => $auditLog->load('actor:id,name,email'),
        ]);
    }

    public function export(AuditLogFilterRequest $request): StreamedResponse
    {
        return $this->auditLogExportService->download($request->validated());
    }
}
