<div class="section">
    <h2>⚠️ Problèmes Détectés</h2>

    @if (!empty($analysis['deprecated_classes']['classes']))
        <h3>🔄 Classes CSS Obsolètes</h3>
        <div class="grid grid-2">
            @foreach($analysis['deprecated_classes']['classes'] as $class => $details)
                @php
                $cardClass = match($details['severity']) {
                    'high' => 'danger',
                    'medium' => 'warning',
                    'low' => 'success',
                    default => 'info'
                };
                $badgeText = match($details['severity']) {
                    'high' => 'Haute',
                    'medium' => 'Moyenne',
                    'low' => 'Faible',
                    default => 'Info'
                };
                @endphp

                <div class="card {{ $cardClass }}">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong>{{ $class }}</strong>
                        <span class="badge badge-{{ $details['severity'] }}">{{ $badgeText }}</span>
                    </div>
                    <div class="code-block">Remplacer par: <span class="highlight">{{ $details['replacement'] }}</span></div>
                    <small>{{ $details['count'] }} occurrences trouvées</small>
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($analysis['special_cases']['issues']))
        <h3>🔧 Cas Spéciaux</h3>
        @foreach($analysis['special_cases']['issues'] as $issue)
            @php
            $cardClass = match($issue['severity']) {
                'high' => 'danger',
                'medium' => 'warning',
                'low' => 'success',
                default => 'info'
            };
            $badgeText = match($issue['severity']) {
                'high' => 'Haute',
                'medium' => 'Moyenne',
                'low' => 'Faible',
                default => 'Info'
            };
            @endphp

            <div class="issue {{ $cardClass }}">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <strong>{{ $issue['description'] }}</strong>
                    <span class="badge badge-{{ $issue['severity'] }}">{{ $badgeText }}</span>
                </div>
                <p><strong>Solution :</strong> {{ $issue['solution'] }}</p>
                <p><strong>Fichier :</strong> <code>{{ $issue['file'] }}</code></p>
                <p><strong>Occurrences :</strong> {{ $issue['matches'] }}</p>

                @if (!empty($issue['examples']))
                    <p><strong>Exemples :</strong></p>
                    <div class="code-block">
                        @foreach(array_slice($issue['examples'], 0, 3) as $example)
                            <code>{{ $example }}</code><br>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    @endif
</div>