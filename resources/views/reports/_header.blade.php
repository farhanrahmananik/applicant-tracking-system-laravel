<div class="report-page-header">
    <div>
        <div class="page-kicker">Recruitment intelligence</div>
        <h1 class="page-title">{{ $title }}</h1>
        <p class="page-subtitle">{{ $subtitle }}</p>
    </div>

    <div class="report-header-actions">
        <a class="btn btn-outline-secondary" href="{{ route('reports.index') }}">All reports</a>
        @isset($exportRoute)
            <a class="btn btn-primary" href="{{ route($exportRoute, $filters) }}">Export CSV</a>
        @endisset
    </div>
</div>
