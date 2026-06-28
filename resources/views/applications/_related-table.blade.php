<div class="table-responsive">
    <table class="table related-application-table align-middle mb-0">
        <thead>
            <tr>
                <th scope="col">{{ $context === 'candidate' ? 'Job posting' : 'Candidate' }}</th>
                <th scope="col">Status</th>
                <th scope="col">Source</th>
                <th scope="col">Applied</th>
                <th class="text-end" scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $relatedApplication)
                <tr>
                    <td>
                        @if ($context === 'candidate')
                            <span class="table-primary-value">{{ $relatedApplication->jobPosting->title }}</span>
                            <small>{{ $relatedApplication->jobPosting->company->name }}</small>
                        @else
                            <span class="table-primary-value">{{ $relatedApplication->candidate->full_name }}</span>
                            <small>{{ $relatedApplication->candidate->email }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="application-badge application-status-{{ $relatedApplication->current_status }}">
                            {{ Illuminate\Support\Str::headline($relatedApplication->current_status) }}
                        </span>
                    </td>
                    <td>{{ $relatedApplication->source ? Illuminate\Support\Str::headline($relatedApplication->source) : 'Not provided' }}</td>
                    <td class="text-nowrap">{{ $relatedApplication->applied_date->format('M j, Y') }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('applications.show', $relatedApplication) }}">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="empty-table-state" colspan="5">No applications have been recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
