<section class="report-table-section">
    <div class="report-section-heading">
        <div>
            <h2>{{ $title }}</h2>
            <p>{{ $description }}</p>
        </div>
        <span>{{ number_format($rows->sum('count')) }} records</span>
    </div>

    <div class="table-responsive">
        <table class="table report-table mb-0">
            <thead>
                <tr>
                    <th scope="col">{{ $groupLabel }}</th>
                    <th scope="col">Distribution</th>
                    <th class="text-end" scope="col">Count</th>
                    <th class="text-end" scope="col">Share</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>
                            <span class="report-status-badge report-status-{{ $row['key'] }}">
                                {{ $row['label'] }}
                            </span>
                        </td>
                        <td>
                            <div class="report-progress" aria-label="{{ $row['percentage'] }} percent">
                                <span style="width: {{ $row['percentage'] }}%"></span>
                            </div>
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($row['count']) }}</td>
                        <td class="text-end text-secondary">{{ number_format($row['percentage'], 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
