<div class="section">
    <h2>💡 Recommandations</h2>
    <div class="grid grid-2">
        @foreach($recommendations as $index => $recommendation)
        <div class="card info">
            <strong>{{ $index + 1 }}.</strong> {{ $recommendation }}
        </div>
        @endforeach
    </div>
</div>