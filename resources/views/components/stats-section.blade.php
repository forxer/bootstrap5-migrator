<div class="section">
    <h2>📈 Statistiques de l'Analyse</h2>
    <div class="stats-grid">
        @php
        $stats = [
            [
                'number' => count($analysis['deprecated_classes']['classes']),
                'label' => 'Classes obsolètes',
                'color' => count($analysis['deprecated_classes']['classes']) > 0 ? 'danger' : 'success'
            ],
            [
                'number' => count($analysis['cdn_links']),
                'label' => 'Liens CDN à mettre à jour',
                'color' => count($analysis['cdn_links']) > 0 ? 'warning' : 'success'
            ],
            [
                'number' => count($analysis['special_cases']['issues']),
                'label' => 'Cas spéciaux',
                'color' => count($analysis['special_cases']['issues']) > 0 ? 'danger' : 'success'
            ],
            [
                'number' => $analysis['file_stats']['total_files'],
                'label' => 'Fichiers analysés',
                'color' => 'info'
            ]
        ];
        @endphp

        @foreach($stats as $stat)
        <div class="stat-card">
            <div class="stat-number" style="color: var(--bs-{{ $stat['color'] }})">{{ $stat['number'] }}</div>
            <div class="stat-label">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>
</div>