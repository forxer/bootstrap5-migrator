@extends('bootstrap5-migrator::layout')

@section('title', 'Rapport de Performance - Migration Bootstrap 5')

@section('header-title', '📈 Rapport de Performance Migration')

@section('header-content')
<p>Analyse des performances de la migration Bootstrap 5</p>
<p>Généré le {{ now()->format('d/m/Y H:i:s') }}</p>
@endsection

@section('content')
@php
$performanceMetrics = [
    'migration_time' => $migrationData['execution_time'] ?? 0,
    'files_processed' => $migrationData['files_processed'] ?? 0,
    'changes_made' => $migrationData['total_changes'] ?? 0,
    'success_rate' => $migrationData['success_rate'] ?? 0,
    'memory_usage' => $migrationData['memory_peak'] ?? 0
];
@endphp

<div class="section">
    <h2>📊 Métriques de Performance</h2>
    <div class="stats-grid">
        @foreach($performanceMetrics as $key => $value)
            @php
            $label = match($key) {
                'migration_time' => 'Temps d\'exécution',
                'files_processed' => 'Fichiers traités',
                'changes_made' => 'Modifications effectuées',
                'success_rate' => 'Taux de réussite',
                'memory_usage' => 'Mémoire utilisée',
                default => $key
            };

            $unit = match($key) {
                'migration_time' => 's',
                'success_rate' => '%',
                'memory_usage' => 'MB',
                default => ''
            };

            $displayValue = $key === 'memory_usage' ?
                round($value / 1024 / 1024, 2) :
                ($key === 'migration_time' ? round($value, 2) : $value);

            $color = match($key) {
                'success_rate' => $value >= 90 ? 'success' : ($value >= 70 ? 'warning' : 'danger'),
                'migration_time' => $value <= 30 ? 'success' : ($value <= 60 ? 'warning' : 'info'),
                'memory_usage' => $displayValue <= 128 ? 'success' : ($displayValue <= 256 ? 'warning' : 'info'),
                default => 'primary'
            };
            @endphp

            <div class="stat-card">
                <div class="stat-number" style="color: var(--bs-{{ $color }})">
                    {{ $displayValue }}{{ $unit }}
                </div>
                <div class="stat-label">{{ $label }}</div>
            </div>
        @endforeach
    </div>
</div>

@if (isset($migrationData['detailed_stats']))
<div class="section">
    <h2>📈 Détails par Catégorie</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Fichiers traités</th>
                <th>Modifications</th>
                <th>Temps (s)</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($migrationData['detailed_stats'] as $category => $stats)
            <tr>
                <td><strong>{{ ucfirst(str_replace('_', ' ', $category)) }}</strong></td>
                <td>{{ $stats['files'] ?? 0 }}</td>
                <td>{{ $stats['changes'] ?? 0 }}</td>
                <td>{{ round($stats['time'] ?? 0, 2) }}</td>
                <td>
                    @php
                    $status = $stats['success'] ?? true;
                    $statusClass = $status ? 'success' : 'danger';
                    $statusText = $status ? '✅ Réussi' : '❌ Échoué';
                    @endphp
                    <span class="badge badge-{{ $statusClass }}">{{ $statusText }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if (isset($migrationData['errors']) && !empty($migrationData['errors']))
<div class="section">
    <h2>⚠️ Erreurs Rencontrées</h2>
    @foreach($migrationData['errors'] as $error)
    <div class="issue danger">
        <strong>{{ $error['message'] ?? 'Erreur inconnue' }}</strong>
        @if (isset($error['file']))
            <br><small>Fichier: <code>{{ $error['file'] }}</code></small>
        @endif
        @if (isset($error['line']))
            <br><small>Ligne: {{ $error['line'] }}</small>
        @endif
    </div>
    @endforeach
</div>
@endif

<div class="section">
    <h2>💡 Analyse des Performances</h2>
    <div class="grid grid-2">
        @php
        $recommendations = [];

        if (($migrationData['execution_time'] ?? 0) > 60) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Temps d\'exécution élevé',
                'message' => 'La migration a pris plus d\'une minute. Considérez l\'optimisation pour les futurs projets.'
            ];
        }

        if (($migrationData['memory_peak'] ?? 0) > 268435456) { // 256MB
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Utilisation mémoire élevée',
                'message' => 'Plus de 256MB utilisés. Envisagez le traitement par batches pour les gros projets.'
            ];
        }

        if (($migrationData['success_rate'] ?? 100) < 90) {
            $recommendations[] = [
                'type' => 'danger',
                'title' => 'Taux de réussite faible',
                'message' => 'Moins de 90% de réussite. Vérifiez les erreurs ci-dessus.'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'title' => 'Performances optimales',
                'message' => 'La migration s\'est déroulée dans des conditions optimales.'
            ];
        }
        @endphp

        @foreach($recommendations as $rec)
        <div class="card {{ $rec['type'] }}">
            <h4>{{ $rec['title'] }}</h4>
            <p>{{ $rec['message'] }}</p>
        </div>
        @endforeach
    </div>
</div>

<div class="section">
    <h2>🔧 Optimisations Suggérées</h2>
    <ul>
        <li><strong>Cache des analyses :</strong> Implémentez un cache pour éviter de re-analyser les mêmes fichiers</li>
        <li><strong>Traitement parallèle :</strong> Utilisez des workers pour traiter plusieurs fichiers simultanément</li>
        <li><strong>Exclusions intelligentes :</strong> Ignorez les fichiers vendor et node_modules automatiquement</li>
        <li><strong>Sauvegarde incrémentale :</strong> Ne sauvegardez que les fichiers modifiés</li>
        <li><strong>Validation en amont :</strong> Validez la structure avant de commencer la migration</li>
    </ul>
</div>
@endsection

@push('styles')
<style>
.metrics-container {
    padding: 2rem 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.stat-card {
    text-align: center;
    padding: 2rem 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-left: 4px solid var(--bs-primary);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--bs-secondary);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>
@endpush