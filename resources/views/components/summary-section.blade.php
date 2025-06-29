<div class="section">
    <h2>📋 Résumé de l'Analyse</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Élément</th>
                <th>Statut</th>
                <th>Détails</th>
                <th>Action requise</th>
            </tr>
        </thead>
        <tbody>
            @php
            $items = [
                [
                    'element' => 'Bootstrap Version',
                    'status' => $analysis['general']['bootstrap_version'],
                    'details' => str_contains($analysis['general']['bootstrap_version'], '4.') ? '🔄 Migration requise' : (str_contains($analysis['general']['bootstrap_version'], '5.') ? '✅ Bootstrap 5 détecté' : '❓ Version inconnue'),
                    'action' => str_contains($analysis['general']['bootstrap_version'], '4.') ? 'Migration requise' : 'OK'
                ],
                [
                    'element' => 'jQuery Usage',
                    'status' => $analysis['general']['jquery_usage'] ? 'Détecté' : 'Non détecté',
                    'details' => $analysis['general']['jquery_usage'] ? 'Vérifier la compatibilité' : 'Aucune dépendance',
                    'action' => $analysis['general']['jquery_usage'] ? 'Évaluer la nécessité' : 'OK'
                ],
                [
                    'element' => 'Classes obsolètes',
                    'status' => count($analysis['deprecated_classes']['classes']) . ' trouvées',
                    'details' => $this->getSeverityBreakdown($analysis['deprecated_classes']['classes'] ?? []),
                    'action' => count($analysis['deprecated_classes']['classes']) > 0 ? 'Remplacement automatique' : 'OK'
                ],
                [
                    'element' => 'Liens CDN',
                    'status' => count($analysis['cdn_links']) . ' obsolètes',
                    'details' => count($analysis['cdn_links']) > 0 ? 'Bootstrap 4 détecté' : 'À jour',
                    'action' => count($analysis['cdn_links']) > 0 ? 'Mise à jour requise' : 'OK'
                ]
            ];
            @endphp

            @foreach($items as $item)
            <tr>
                <td><strong>{{ $item['element'] }}</strong></td>
                <td>{{ $item['status'] }}</td>
                <td>{{ $item['details'] }}</td>
                <td>{{ $item['action'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
