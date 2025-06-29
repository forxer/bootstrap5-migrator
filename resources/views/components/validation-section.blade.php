<div class="section">
    <h2>✅ Validation de la Migration</h2>

    @if (!empty($validation['critical_issues']))
        <h3>🚨 Problèmes Critiques</h3>
        <p>Ces problèmes doivent être résolus immédiatement :</p>

        @foreach($validation['critical_issues'] as $issue)
        <div class="issue danger">
            <strong>{{ $issue['description'] }}</strong>
            <br><small>Fichier: <code>{{ $issue['file'] }}</code></small>
        </div>
        @endforeach
    @endif

    @if (!empty($validation['warnings']))
        <h3>⚠️ Avertissements</h3>
        <p>Ces éléments nécessitent votre attention :</p>

        @foreach($validation['warnings'] as $warning)
        <div class="issue warning">
            <strong>{{ $warning['description'] }}</strong>
            <br><small>Fichier: <code>{{ $warning['file'] }}</code></small>
        </div>
        @endforeach
    @endif

    @if (!empty($validation['fixable_issues']))
        <h3>🔧 Problèmes Corrigeables Automatiquement</h3>
        <p>Ces problèmes peuvent être corrigés automatiquement avec la commande :</p>
        <div class="code-block">php artisan bootstrap:validate --fix</div>

        @php
        $groupedIssues = [];
        foreach ($validation['fixable_issues'] as $issue) {
            $action = $issue['action'] ?? 'unknown';
            $groupedIssues[$action][] = $issue;
        }
        @endphp

        @foreach($groupedIssues as $action => $issues)
            @php
            $actionName = match($action) {
                'update_bootstrap_version' => '🔄 Mise à jour version Bootstrap',
                'replace_popper' => '🔄 Remplacement Popper.js',
                'replace_class' => '🎨 Remplacement classes CSS',
                'update_data_attribute' => '🏷️ Mise à jour attributs data-*',
                'update_cdn_link' => '🔗 Mise à jour liens CDN',
                default => '🔧 ' . ucfirst(str_replace('_', ' ', $action))
            };
            @endphp

            <h4>{{ $actionName }} ({{ count($issues) }} problèmes)</h4>

            @foreach(array_slice($issues, 0, 5) as $issue)
            <div class="issue info">
                <strong>{{ $issue['description'] }}</strong>
                <br><small>Fichier: <code>{{ $issue['file'] }}</code></small>
            </div>
            @endforeach

            @if (count($issues) > 5)
                @php $remaining = count($issues) - 5; @endphp
                <p><em>... et {{ $remaining }} autres problèmes similaires</em></p>
            @endif
        @endforeach
    @endif

    <h3>📊 Statut par Catégorie</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th>Statut</th>
                <th>Détails</th>
                <th>Action Recommandée</th>
            </tr>
        </thead>
        <tbody>
            @php
            $categories = [
                ['name' => 'Classes CSS', 'status' => $validation['css_status'] ?? '❓', 'details' => $validation['css_details'] ?? 'Non analysé'],
                ['name' => 'JavaScript', 'status' => $validation['js_status'] ?? '❓', 'details' => $validation['js_details'] ?? 'Non analysé'],
                ['name' => 'Attributs data-*', 'status' => $validation['data_attributes_status'] ?? '❓', 'details' => $validation['data_attributes_details'] ?? 'Non analysé'],
                ['name' => 'Liens CDN', 'status' => $validation['cdn_status'] ?? '❓', 'details' => $validation['cdn_details'] ?? 'Non analysé'],
                ['name' => 'Dépendances NPM', 'status' => $validation['npm_status'] ?? '❓', 'details' => $validation['npm_details'] ?? 'Non analysé']
            ];
            @endphp

            @foreach($categories as $category)
                @php
                $action = str_contains($category['status'], '✅') ? 'Aucune action requise' : (str_contains($category['status'], '❌') ? 'Action immédiate requise' : (str_contains($category['status'], '⚠️') ? 'Vérification recommandée' : 'À évaluer'));
                $statusClass = str_contains($category['status'], '✅') ? 'success' : (str_contains($category['status'], '❌') ? 'danger' : (str_contains($category['status'], '⚠️') ? 'warning' : 'info'));
                @endphp

                <tr>
                    <td><strong>{{ $category['name'] }}</strong></td>
                    <td><span class="badge badge-{{ $statusClass }}">{{ $category['status'] }}</span></td>
                    <td>{{ $category['details'] }}</td>
                    <td>{{ $action }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (!empty($validation['recommendations']))
        <h3>💡 Recommandations de Validation</h3>
        <ul>
            @foreach($validation['recommendations'] as $recommendation)
            <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
    @endif

    <h3>🛠️ Commandes Utiles</h3>
    <div class="grid grid-2">
        @php
        $commands = [
            [
                'title' => 'Validation complète',
                'command' => 'php artisan bootstrap:validate',
                'description' => 'Effectue une validation complète sans modifications'
            ],
            [
                'title' => 'Correction automatique',
                'command' => 'php artisan bootstrap:validate --fix',
                'description' => 'Corrige automatiquement les problèmes mineurs'
            ],
            [
                'title' => 'Mode strict',
                'command' => 'php artisan bootstrap:validate --strict',
                'description' => 'Échoue si des problèmes critiques sont détectés'
            ],
            [
                'title' => 'Analyse détaillée',
                'command' => 'php artisan bootstrap:analyze --detailed',
                'description' => 'Analyse approfondie avec localisation des problèmes'
            ]
        ];
        @endphp

        @foreach($commands as $cmd)
        <div class="card info">
            <h4>{{ $cmd['title'] }}</h4>
            <div class="code-block">{{ $cmd['command'] }}</div>
            <p><small>{{ $cmd['description'] }}</small></p>
        </div>
        @endforeach
    </div>
</div>