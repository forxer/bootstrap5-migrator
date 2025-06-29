@extends('bootstrap5-migrator::layout')

@section('title', 'Rapport d\'Analyse Bootstrap 4')

@section('header-title', '🔍 Rapport d\'Analyse Bootstrap 4')

@section('header-content')
<p>Analyse de votre application avant migration</p>
<p>Généré le {{ now()->format('d/m/Y H:i:s') }}</p>
@endsection

@section('content')
@if(isset($analysis))
    {{-- Résumé général --}}
    <div class="section">
        <h2>📊 Résumé de l'Analyse</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: var(--bs-info)">{{ $analysis['general']['bootstrap_version'] ?? 'N/A' }}</div>
                <div class="stat-label">Version Bootstrap</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--bs-{{ $analysis['general']['jquery_usage'] ? 'warning' : 'success' }})">
                    {{ $analysis['general']['jquery_usage'] ? 'Oui' : 'Non' }}
                </div>
                <div class="stat-label">jQuery Détecté</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--bs-danger)">{{ count($analysis['deprecated_classes']['classes'] ?? []) }}</div>
                <div class="stat-label">Classes Obsolètes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--bs-warning)">{{ count($analysis['cdn_links'] ?? []) }}</div>
                <div class="stat-label">Liens CDN</div>
            </div>
        </div>
    </div>

    {{-- Classes obsolètes --}}
    @if(!empty($analysis['deprecated_classes']['classes']))
        <div class="section">
            <h2>🔄 Classes CSS Obsolètes Détectées</h2>
            <p>Ces classes Bootstrap 4 doivent être remplacées :</p>
            <div class="grid grid-2">
                @foreach($analysis['deprecated_classes']['classes'] as $class => $details)
                    @php
                    $cardClass = match($details['severity'] ?? 'low') {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'info'
                    };
                    $badgeText = match($details['severity'] ?? 'low') {
                        'high' => 'Haute',
                        'medium' => 'Moyenne',
                        'low' => 'Faible',
                        default => 'Info'
                    };
                    @endphp

                    <div class="card {{ $cardClass }}">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong>{{ $class }}</strong>
                            <span class="badge badge-{{ $details['severity'] ?? 'low' }}">{{ $badgeText }}</span>
                        </div>
                        <div class="code-block">Remplacer par: <span class="highlight">{{ $details['replacement'] ?? 'À définir' }}</span></div>
                        <small>{{ $details['count'] ?? 0 }} occurrences trouvées</small>
                        @if(!empty($details['files']) && count($details['files']) > 0)
                            <details style="margin-top: 0.5rem;">
                                <summary style="cursor: pointer; color: var(--bs-secondary);">Fichiers concernés</summary>
                                <ul style="margin: 0.5rem 0; padding-left: 1rem;">
                                    @foreach(array_slice($details['files'], 0, 5) as $file)
                                        <li><code>{{ basename($file) }}</code></li>
                                    @endforeach
                                    @if(count($details['files']) > 5)
                                        <li><em>... et {{ count($details['files']) - 5 }} autres</em></li>
                                    @endif
                                </ul>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Liens CDN --}}
    @if(!empty($analysis['cdn_links']))
        <div class="section">
            <h2>🔗 Liens CDN Bootstrap 4 Détectés</h2>
            <p>Ces liens CDN doivent être mis à jour vers Bootstrap 5 :</p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Fichier</th>
                        <th>Version Actuelle</th>
                        <th>Fournisseur</th>
                        <th>Lien Bootstrap 5 Suggéré</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analysis['cdn_links'] as $link)
                        <tr>
                            <td><code>{{ basename($link['file'] ?? '') }}</code></td>
                            <td>{{ $link['current_version'] ?? 'N/A' }}</td>
                            <td>{{ $link['provider'] ?? 'N/A' }}</td>
                            <td><code style="font-size: 0.8rem;">{{ $link['suggested_v5_link'] ?? 'N/A' }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Cas spéciaux --}}
    @if(!empty($analysis['special_cases']['issues']))
        <div class="section">
            <h2>⚠️ Cas Spéciaux Nécessitant une Attention Manuelle</h2>
            @foreach($analysis['special_cases']['issues'] as $issue)
                @php
                $cardClass = match($issue['severity'] ?? 'low') {
                    'high' => 'danger',
                    'medium' => 'warning',
                    'low' => 'success',
                    default => 'info'
                };
                $badgeText = match($issue['severity'] ?? 'low') {
                    'high' => 'Haute',
                    'medium' => 'Moyenne',
                    'low' => 'Faible',
                    default => 'Info'
                };
                @endphp

                <div class="issue {{ $cardClass }}">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <strong>{{ $issue['description'] ?? 'Problème détecté' }}</strong>
                        <span class="badge badge-{{ $issue['severity'] ?? 'low' }}">{{ $badgeText }}</span>
                    </div>
                    <p><strong>Solution :</strong> {{ $issue['solution'] ?? 'À définir manuellement' }}</p>
                    <p><strong>Fichier :</strong> <code>{{ $issue['file'] ?? 'Multiple fichiers' }}</code></p>
                    <p><strong>Occurrences :</strong> {{ $issue['matches'] ?? 0 }}</p>

                    @if(!empty($issue['examples']))
                        <p><strong>Exemples :</strong></p>
                        <div class="code-block">
                            @foreach(array_slice($issue['examples'], 0, 3) as $example)
                                <code>{{ $example }}</code><br>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Recommandations --}}
    <div class="section">
        <h2>💡 Recommandations</h2>
        <div class="grid grid-2">
            <div class="card info">
                <h4>1. Sauvegarde</h4>
                <p>Créez une sauvegarde complète avant de commencer la migration.</p>
                <div class="code-block">php artisan bootstrap:migrate-to-5 --backup</div>
            </div>
            <div class="card info">
                <h4>2. Migration par étapes</h4>
                <p>Commencez par un mode dry-run pour voir les changements.</p>
                <div class="code-block">php artisan bootstrap:migrate-to-5 --dry-run</div>
            </div>
            <div class="card info">
                <h4>3. Tests après migration</h4>
                <p>Testez tous vos composants interactifs après la migration.</p>
            </div>
            <div class="card info">
                <h4>4. Validation finale</h4>
                <p>Utilisez la validation pour détecter les problèmes restants.</p>
                <div class="code-block">php artisan bootstrap:validate</div>
            </div>
        </div>
    </div>

@else
    <div class="section">
        <h2>❌ Aucune donnée d'analyse</h2>
        <p>Aucune donnée d'analyse n'a été fournie. Lancez d'abord une analyse :</p>
        <div class="code-block">php artisan bootstrap:analyze --detailed</div>
    </div>
@endif
@endsection