<div class="report-metric-grid">
    @foreach ($items as $item)
        <div class="report-metric-card">
            <span>{{ $item['label'] }}</span>
            <strong>{{ number_format($item['value']) }}</strong>
            @isset($item['context'])
                <small>{{ $item['context'] }}</small>
            @endisset
        </div>
    @endforeach
</div>
