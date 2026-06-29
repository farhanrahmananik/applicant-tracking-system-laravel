@extends('layouts.app')

@section('title', 'Job Posting Performance Report')

@section('content')
    @include('reports._header', [
        'title' => 'Job Posting Performance',
        'subtitle' => 'Compare candidate reach and downstream recruiting activity by job posting.',
        'exportRoute' => 'reports.job-postings.export',
    ])

    @include('reports._filters', [
        'routeName' => 'reports.job-postings',
        'fields' => ['dates', 'company', 'department', 'job'],
    ])

    @include('reports._metrics', ['items' => [
        ['label' => 'Job postings', 'value' => $metrics['job_postings']],
        ['label' => 'Applications', 'value' => $metrics['applications']],
        ['label' => 'Interviews', 'value' => $metrics['interviews']],
        ['label' => 'Offers', 'value' => $metrics['offers']],
    ]])

    <section class="report-table-section">
        <div class="report-section-heading">
            <div>
                <h2>Posting performance</h2>
                <p>Date filters apply to each activity event: application, interview, and offer date.</p>
            </div>
            <span>{{ number_format($rows->count()) }} postings</span>
        </div>

        <div class="table-responsive">
            <table class="table report-table report-performance-table mb-0">
                <thead>
                    <tr>
                        <th scope="col">Job posting</th>
                        <th scope="col">Organization</th>
                        <th class="text-end" scope="col">Applications</th>
                        <th class="text-end" scope="col">Candidates</th>
                        <th class="text-end" scope="col">Interviews</th>
                        <th class="text-end" scope="col">Offers</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['jobPosting']->title }}</strong>
                                <small>
                                    {{ \Illuminate\Support\Str::headline($row['jobPosting']->status) }}
                                    / {{ number_format($row['jobPosting']->openings) }} openings
                                </small>
                            </td>
                            <td>
                                {{ $row['jobPosting']->company?->name ?? 'Unavailable' }}
                                <small>{{ $row['jobPosting']->department?->name ?? 'No department' }}</small>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($row['applications']) }}</td>
                            <td class="text-end">{{ number_format($row['candidates']) }}</td>
                            <td class="text-end">{{ number_format($row['interviews']) }}</td>
                            <td class="text-end">{{ number_format($row['offers']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="report-empty-state" colspan="6">No job postings match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
