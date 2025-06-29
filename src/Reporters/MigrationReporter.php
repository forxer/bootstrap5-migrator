<?php

namespace Bootstrap5Migrator\Reporters;

use Bootstrap5Migrator\Analyzers\CDNAnalyzer;
use Bootstrap5Migrator\Analyzers\DeprecatedClassAnalyzer;
use Bootstrap5Migrator\Analyzers\SpecialCaseAnalyzer;
use Bootstrap5Migrator\Bootstrap5Migrator;
use Bootstrap5Migrator\Validators\Bootstrap5Validator;
use Exception;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MigrationReporter
{
    public function __construct(
        protected Bootstrap5Migrator $migrator,
        protected DeprecatedClassAnalyzer $classAnalyzer,
        protected CDNAnalyzer $cdnAnalyzer,
        protected SpecialCaseAnalyzer $specialAnalyzer,
        protected Bootstrap5Validator $validator,
    ) {}

    public function generateReportData(): array
    {
        return [
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
                'app_name' => config('app.name', 'Laravel Application'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'package_version' => $this->getPackageVersion(),
            ],
            'analysis' => [
                'general' => $this->migrator->analyzeApplication(),
                'deprecated_classes' => $this->classAnalyzer->findDeprecatedClasses(true),
                'cdn_links' => $this->cdnAnalyzer->findCDNLinks(),
                'special_cases' => $this->specialAnalyzer->findSpecialCases(),
                'file_stats' => $this->generateFileStats(),
            ],
            'validation' => $this->validator->validateMigration(),
            'recommendations' => $this->generateRecommendations(),
            'migration_checklist' => $this->generateMigrationChecklist(),
        ];
    }

    /**
     * Génère un rapport de comparaison avant/après migration
     */
    public function generateComparisonReport(array $beforeData, array $afterData, string $outputPath): bool
    {
        $comparison = [
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
                'comparison_type' => 'before_after_migration',
            ],
            'score_improvement' => [
                'before' => $beforeData['validation']['score'] ?? 0,
                'after' => $afterData['validation']['score'] ?? 0,
                'improvement' => ($afterData['validation']['score'] ?? 0) - ($beforeData['validation']['score'] ?? 0),
            ],
            'issues_resolved' => [
                'deprecated_classes' => [
                    'before' => \count($beforeData['analysis']['deprecated_classes']['classes'] ?? []),
                    'after' => \count($afterData['analysis']['deprecated_classes']['classes'] ?? []),
                    'resolved' => \count($beforeData['analysis']['deprecated_classes']['classes'] ?? []) - \count($afterData['analysis']['deprecated_classes']['classes'] ?? []),
                ],
                'cdn_links' => [
                    'before' => \count($beforeData['analysis']['cdn_links'] ?? []),
                    'after' => \count($afterData['analysis']['cdn_links'] ?? []),
                    'resolved' => \count($beforeData['analysis']['cdn_links'] ?? []) - \count($afterData['analysis']['cdn_links'] ?? []),
                ],
                'special_cases' => [
                    'before' => \count($beforeData['analysis']['special_cases']['issues'] ?? []),
                    'after' => \count($afterData['analysis']['special_cases']['issues'] ?? []),
                    'resolved' => \count($beforeData['analysis']['special_cases']['issues'] ?? []) - \count($afterData['analysis']['special_cases']['issues'] ?? []),
                ],
            ],
            'remaining_issues' => $afterData['analysis'] ?? [],
            'recommendations' => $this->generatePostMigrationRecommendations($afterData),
        ];

        $html = $this->generateComparisonHTML($comparison);

        return File::put($outputPath, $html) !== false;
    }

    private function generateComparisonHTML(array $comparison): string
    {
        $scoreImprovement = $comparison['score_improvement'];

        return "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Rapport de Comparaison - Migration Bootstrap 5</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 2rem; border-radius: 8px 8px 0 0; text-align: center; }
        .content { padding: 2rem; }
        .comparison-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 2rem 0; }
        .before-after { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: center; }
        .score-comparison { text-align: center; padding: 2rem; background: linear-gradient(45deg, #f8f9fa, #e9ecef); border-radius: 12px; }
        .score-big { font-size: 3rem; font-weight: bold; }
        .improvement { font-size: 1.5rem; color: #28a745; }
        .metrics-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .metrics-table th, .metrics-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef; }
        .metrics-table th { background: #6f42c1; color: white; }
        .resolved { color: #28a745; font-weight: bold; }
        .remaining { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>📊 Rapport de Comparaison Migration</h1>
            <p>Analyse avant/après migration Bootstrap 5</p>
            <p>Généré le {$comparison['meta']['generated_at']}</p>
        </div>

        <div class='content'>
            <div class='score-comparison'>
                <h2>Amélioration du Score</h2>
                <div class='before-after'>
                    <div>
                        <div class='score-big' style='color: #dc3545;'>{$scoreImprovement['before']}</div>
                        <div>Avant Migration</div>
                    </div>
                    <div>
                        <div class='score-big' style='color: #28a745;'>{$scoreImprovement['after']}</div>
                        <div>Après Migration</div>
                    </div>
                </div>
                <div class='improvement'>Amélioration: +{$scoreImprovement['improvement']} points</div>
            </div>

            <h2>Problèmes Résolus</h2>
            <table class='metrics-table'>
                <thead>
                    <tr>
                        <th>Type de Problème</th>
                        <th>Avant</th>
                        <th>Après</th>
                        <th>Résolus</th>
                    </tr>
                </thead>
                <tbody>";
    }

    private function generatePostMigrationRecommendations(array $afterData): array
    {
        $recommendations = [];

        // Recommandations basées sur les problèmes restants
        if (! empty($afterData['analysis']['deprecated_classes']['classes'])) {
            $recommendations[] = 'Il reste des classes obsolètes à traiter manuellement';
        }

        if (! empty($afterData['analysis']['special_cases']['issues'])) {
            $recommendations[] = 'Attention aux cas spéciaux qui nécessitent une intervention manuelle';
        }

        if ($afterData['validation']['score'] < 90) {
            $recommendations[] = 'Score de migration inférieur à 90% - révision recommandée';
        }

        // Recommandations générales post-migration
        $recommendations = array_merge($recommendations, [
            'Effectuer des tests complets sur tous les navigateurs supportés',
            'Vérifier les performances après migration',
            'Mettre à jour la documentation du projet',
            "Former l'équipe aux nouveautés Bootstrap 5",
            'Planifier une revue de code pour valider les changements',
        ]);

        return $recommendations;
    }

    /**
     * Génère un script de surveillance continue
     */
    public function generateMonitoringScript(string $outputPath): bool
    {
        $script = '#!/bin/bash
# Script de surveillance Bootstrap 5 Migration
# Généré automatiquement par Bootstrap5Migrator

echo "🔍 Surveillance de la migration Bootstrap 5..."

# Vérification des classes obsolètes
echo "Recherche de classes obsolètes..."
DEPRECATED_CLASSES=$(grep -r -n "\\(ml-\\|mr-\\|pl-\\|pr-\\|text-left\\|text-right\\|form-group\\|sr-only\\)" resources/views/ || true)

if [ ! -z "$DEPRECATED_CLASSES" ]; then
    echo "⚠️ Classes obsolètes détectées:"
    echo "$DEPRECATED_CLASSES"
    exit 1
fi

# Vérification des attributs data-*
echo "Recherche d\'attributs data-* obsolètes..."
OLD_ATTRIBUTES=$(grep -r -n "data-\\(toggle\\|target\\|dismiss\\)=" resources/views/ || true)

if [ ! -z "$OLD_ATTRIBUTES" ]; then
    echo "⚠️ Attributs obsolètes détectés:"
    echo "$OLD_ATTRIBUTES"
    exit 1
fi

# Vérification des liens CDN
echo "Vérification des liens CDN..."
CDN_BOOTSTRAP4=$(grep -r -n "bootstrap.*4\\." resources/views/ public/ || true)

if [ ! -z "$CDN_BOOTSTRAP4" ]; then
    echo "⚠️ Liens CDN Bootstrap 4 détectés:"
    echo "$CDN_BOOTSTRAP4"
    exit 1
fi

echo "✅ Aucun problème détecté - Migration Bootstrap 5 OK"
exit 0
';

        return File::put($outputPath, $script) !== false;
    }

    /**
     * Génère un fichier de configuration pour l'IDE
     */
    public function generateIDEConfig(string $outputPath): bool
    {
        $config = [
            'bootstrap5-migration' => [
                'deprecated_classes' => array_keys($this->getDeprecatedClassesList()),
                'new_classes' => array_values($this->getDeprecatedClassesList()),
                'data_attributes' => [
                    'old' => ['data-toggle', 'data-target', 'data-dismiss', 'data-slide', 'data-ride'],
                    'new' => ['data-bs-toggle', 'data-bs-target', 'data-bs-dismiss', 'data-bs-slide', 'data-bs-ride'],
                ],
                'inspection_rules' => [
                    'warn_on_deprecated_classes' => true,
                    'warn_on_old_data_attributes' => true,
                    'suggest_replacements' => true,
                ],
            ],
        ];

        return File::put($outputPath, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }

    private function getDeprecatedClassesList(): array
    {
        return [
            'ml-0' => 'ms-0', 'ml-1' => 'ms-1', 'ml-2' => 'ms-2', 'ml-3' => 'ms-3', 'ml-4' => 'ms-4', 'ml-5' => 'ms-5', 'ml-auto' => 'ms-auto',
            'mr-0' => 'me-0', 'mr-1' => 'me-1', 'mr-2' => 'me-2', 'mr-3' => 'me-3', 'mr-4' => 'me-4', 'mr-5' => 'me-5', 'mr-auto' => 'me-auto',
            'pl-0' => 'ps-0', 'pl-1' => 'ps-1', 'pl-2' => 'ps-2', 'pl-3' => 'ps-3', 'pl-4' => 'ps-4', 'pl-5' => 'ps-5',
            'pr-0' => 'pe-0', 'pr-1' => 'pe-1', 'pr-2' => 'pe-2', 'pr-3' => 'pe-3', 'pr-4' => 'pe-4', 'pr-5' => 'pe-5',
            'text-left' => 'text-start', 'text-right' => 'text-end',
            'float-left' => 'float-start', 'float-right' => 'float-end',
            'form-group' => 'mb-3', 'form-row' => 'row g-3',
            'custom-select' => 'form-select', 'custom-file' => 'form-control',
            'jumbotron' => 'bg-light p-5 rounded-3', 'media' => 'd-flex',
            'sr-only' => 'visually-hidden', 'close' => 'btn-close',
            'badge-primary' => 'bg-primary', 'badge-secondary' => 'bg-secondary',
            'badge-success' => 'bg-success', 'badge-danger' => 'bg-danger',
            'badge-warning' => 'bg-warning text-dark', 'badge-info' => 'bg-info text-dark',
            'badge-light' => 'bg-light text-dark', 'badge-dark' => 'bg-dark',
        ];
    }

    /**
     * Génère un rapport de performance de migration
     */
    public function generatePerformanceReport(array $migrationData, string $outputPath): bool
    {
        $performanceMetrics = [
            'migration_time' => $migrationData['execution_time'] ?? 0,
            'files_processed' => $migrationData['files_processed'] ?? 0,
            'changes_made' => $migrationData['total_changes'] ?? 0,
            'success_rate' => $migrationData['success_rate'] ?? 0,
            'memory_usage' => $migrationData['memory_peak'] ?? 0,
        ];

        $html = "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Rapport de Performance - Migration Bootstrap 5</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .metric { display: inline-block; margin: 10px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px; }
        .metric-value { font-size: 2em; font-weight: bold; color: #007bff; }
        .metric-label { color: #666; }
    </style>
</head>
<body>
    <h1>📈 Rapport de Performance Migration</h1>

    <div class='metrics-container'>";

        foreach ($performanceMetrics as $key => $value) {
            $label = match ($key) {
                'migration_time' => 'Temps d\'exécution (s)',
                'files_processed' => 'Fichiers traités',
                'changes_made' => 'Modifications effectuées',
                'success_rate' => 'Taux de réussite (%)',
                'memory_usage' => 'Mémoire utilisée (MB)',
                default => $key
            };

            $displayValue = $key === 'memory_usage' ? round($value / 1024 / 1024, 2) : $value;

            $html .= "
            <div class='metric'>
                <div class='metric-value'>{$displayValue}</div>
                <div class='metric-label'>{$label}</div>
            </div>";
        }

        $html .= '</div></body></html>';

        return File::put($outputPath, $html) !== false;
    }

    /**
     * Sauvegarde l'état actuel pour rollback éventuel
     */
    public function createRollbackPoint(string $identifier): bool
    {
        $rollbackDir = storage_path('app/bootstrap-migration-rollback/'.$identifier);

        if (! File::exists($rollbackDir)) {
            File::makeDirectory($rollbackDir, 0755, true);
        }

        // Sauvegarder les fichiers critiques
        $criticalPaths = [
            base_path('package.json'),
            resource_path('views'),
            resource_path('css'),
            resource_path('sass'),
            resource_path('scss'),
            resource_path('js'),
        ];

        try {
            foreach ($criticalPaths as $path) {
                if (File::exists($path)) {
                    $relativePath = str_replace(base_path().'/', '', $path);
                    $backupPath = $rollbackDir.'/'.$relativePath;

                    if (File::isDirectory($path)) {
                        File::copyDirectory($path, $backupPath);
                    } else {
                        File::ensureDirectoryExists(\dirname($backupPath));
                        File::copy($path, $backupPath);
                    }
                }
            }

            // Créer un fichier de métadonnées
            $metadata = [
                'created_at' => now()->toDateTimeString(),
                'identifier' => $identifier,
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'backed_up_paths' => $criticalPaths,
            ];

            File::put($rollbackDir.'/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Liste les points de rollback disponibles
     */
    public function listRollbackPoints(): array
    {
        $rollbackDir = storage_path('app/bootstrap-migration-rollback');
        $points = [];

        if (! File::exists($rollbackDir)) {
            return $points;
        }

        $directories = File::directories($rollbackDir);

        foreach ($directories as $dir) {
            $metadataPath = $dir.'/metadata.json';

            if (File::exists($metadataPath)) {
                $metadata = json_decode(File::get($metadataPath), true);
                $points[] = [
                    'identifier' => basename((string) $dir),
                    'created_at' => $metadata['created_at'] ?? 'Unknown',
                    'size' => $this->getDirectorySize($dir),
                    'path' => $dir,
                ];
            }
        }

        return $points;
    }

    private function getDirectorySize(string $directory): string
    {
        $size = 0;

        if (File::isDirectory($directory)) {
            foreach (File::allFiles($directory) as $file) {
                $size += $file->getSize();
            }
        }

        return $this->formatBytes($size);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, \count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, 2).' '.$units[$pow];
    }

    public function generateHTMLReport(array $data): string
    {
        $reportContent = $this->generateReportContent($data);

        return "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Rapport de Migration Bootstrap 5 - {$data['meta']['app_name']}</title>
    <style>
        :root {
            --bs-primary: #6f42c1;
            --bs-secondary: #6c757d;
            --bs-success: #198754;
            --bs-info: #0dcaf0;
            --bs-warning: #ffc107;
            --bs-danger: #dc3545;
            --bs-light: #f8f9fa;
            --bs-dark: #212529;
        }

        * { box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--bs-light);
            color: var(--bs-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--bs-primary), #e83e8c);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .header .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .header .meta div {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
        }

        .content {
            padding: 2rem;
        }

        .score-section {
            text-align: center;
            padding: 2rem;
            margin: 2rem 0;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
        }

        .score {
            font-size: 4rem;
            font-weight: bold;
            margin: 1rem 0;
        }

        .score.high { color: var(--bs-success); }
        .score.medium { color: var(--bs-warning); }
        .score.low { color: var(--bs-danger); }

        .score-description {
            font-size: 1.1rem;
            color: var(--bs-secondary);
        }

        .section {
            margin: 3rem 0;
            padding: 2rem;
            border-left: 4px solid var(--bs-primary);
            background: var(--bs-light);
            border-radius: 0 8px 8px 0;
        }

        .section h2 {
            margin-top: 0;
            color: var(--bs-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .grid {
            display: grid;
            gap: 1rem;
        }

        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--bs-primary);
        }

        .card.danger { border-left-color: var(--bs-danger); }
        .card.warning { border-left-color: var(--bs-warning); }
        .card.success { border-left-color: var(--bs-success); }
        .card.info { border-left-color: var(--bs-info); }

        .issue {
            background: white;
            margin: 1rem 0;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--bs-danger);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .issue.warning { border-left-color: var(--bs-warning); }
        .issue.success { border-left-color: var(--bs-success); }
        .issue.info { border-left-color: var(--bs-info); }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: var(--bs-primary);
            color: white;
            font-weight: 600;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-high { background: var(--bs-danger); color: white; }
        .badge-medium { background: var(--bs-warning); color: var(--bs-dark); }
        .badge-low { background: var(--bs-success); color: white; }
        .badge-info { background: var(--bs-info); color: var(--bs-dark); }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--bs-primary);
            transition: width 0.3s ease;
        }

        .progress-fill.success { background: var(--bs-success); }
        .progress-fill.warning { background: var(--bs-warning); }
        .progress-fill.danger { background: var(--bs-danger); }

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
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--bs-primary);
        }

        .stat-label {
            color: var(--bs-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .checklist {
            list-style: none;
            padding: 0;
        }

        .checklist li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .checklist li:last-child {
            border-bottom: none;
        }

        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--bs-primary);
            border-radius: 4px;
            flex-shrink: 0;
        }

        .checkbox.checked {
            background: var(--bs-success);
            border-color: var(--bs-success);
            position: relative;
        }

        .checkbox.checked::after {
            content: '✓';
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
        }

        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        .highlight {
            background: #fff3cd;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                border-radius: 8px;
            }

            .header {
                padding: 2rem 1rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .content {
                padding: 1rem;
            }

            .section {
                padding: 1rem;
            }

            .header .meta {
                grid-template-columns: 1fr;
            }
        }

        .print-only { display: none; }

        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }

            body { background: white; }
            .container { box-shadow: none; margin: 0; }
            .header { background: var(--bs-primary) !important; }
            .section, .card, .issue { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>📊 Rapport de Migration Bootstrap 5</h1>
            <div class='meta'>
                <div>
                    <strong>Application</strong><br>
                    {$data['meta']['app_name']}
                </div>
                <div>
                    <strong>Généré le</strong><br>
                    {$data['meta']['generated_at']}
                </div>
                <div>
                    <strong>Laravel</strong><br>
                    {$data['meta']['laravel_version']}
                </div>
                <div>
                    <strong>PHP</strong><br>
                    {$data['meta']['php_version']}
                </div>
            </div>
        </div>

        <div class='content'>
            {$reportContent}
        </div>
    </div>
</body>
</html>";
    }

    public function generateMarkdownReport(array $data): string
    {
        $markdown = "# 📊 Rapport de Migration Bootstrap 5\n\n";
        $markdown .= \sprintf('**Application:** %s%s', $data['meta']['app_name'], PHP_EOL);
        $markdown .= \sprintf('**Généré le:** %s%s', $data['meta']['generated_at'], PHP_EOL);
        $markdown .= \sprintf('**Laravel:** %s%s', $data['meta']['laravel_version'], PHP_EOL);
        $markdown .= "**PHP:** {$data['meta']['php_version']}\n\n";

        // Score
        $score = $data['validation']['score'];
        $markdown .= "## 🎯 Score de Migration: {$score}/100\n\n";

        if ($score >= 90) {
            $markdown .= "🎉 **Excellent !** Votre migration est pratiquement terminée.\n\n";
        } elseif ($score >= 70) {
            $markdown .= "👍 **Bon progrès !** Quelques ajustements sont encore nécessaires.\n\n";
        } else {
            $markdown .= "⚠️ **Attention !** Plusieurs problèmes doivent être résolus.\n\n";
        }

        // Résumé statistiques
        $markdown .= "## 📈 Statistiques\n\n";
        $markdown .= "| Métrique | Valeur | Statut |\n";
        $markdown .= "|----------|--------|--------|\n";
        $markdown .= \sprintf('| Bootstrap Version | %s | ', $data['analysis']['general']['bootstrap_version']).
                    ($data['analysis']['general']['bootstrap_version'] === '4.6.x' ? '🔄 Migration requise' : '✅ OK')." |\n";
        $markdown .= '| jQuery Usage | '.($data['analysis']['general']['jquery_usage'] ? 'Détecté' : 'Non détecté').' | '.
                    ($data['analysis']['general']['jquery_usage'] ? '⚠️ À vérifier' : '✅ OK')." |\n";
        $markdown .= '| Classes obsolètes | '.\count($data['analysis']['deprecated_classes']['classes']).' | '.
                    (\count($data['analysis']['deprecated_classes']['classes']) > 0 ? '🔄 À remplacer' : '✅ OK')." |\n";
        $markdown .= '| Liens CDN | '.\count($data['analysis']['cdn_links']).' | '.
                    (\count($data['analysis']['cdn_links']) > 0 ? '🔄 À mettre à jour' : '✅ OK')." |\n";
        $markdown .= '| Cas spéciaux | '.\count($data['analysis']['special_cases']['issues']).' | '.
                    (\count($data['analysis']['special_cases']['issues']) > 0 ? '⚠️ Attention manuelle' : '✅ OK')." |\n\n";

        // Classes obsolètes détaillées
        if (! empty($data['analysis']['deprecated_classes']['classes'])) {
            $markdown .= "## 🔄 Classes Obsolètes Détectées\n\n";
            $markdown .= "| Classe | Remplacement | Occurrences | Sévérité |\n";
            $markdown .= "|--------|--------------|-------------|----------|\n";

            foreach ($data['analysis']['deprecated_classes']['classes'] as $class => $details) {
                $severity = $details['severity'] === 'high' ? '🔴 Haute' :
                           ($details['severity'] === 'medium' ? '🟡 Moyenne' : '🟢 Faible');
                $markdown .= "| `{$class}` | `{$details['replacement']}` | {$details['count']} | {$severity} |\n";
            }

            $markdown .= "\n";
        }

        // Cas spéciaux
        if (! empty($data['analysis']['special_cases']['issues'])) {
            $markdown .= "## ⚠️ Cas Spéciaux Nécessitant une Attention Manuelle\n\n";

            foreach ($data['analysis']['special_cases']['issues'] as $issue) {
                $severity = $issue['severity'] === 'high' ? '🔴' :
                           ($issue['severity'] === 'medium' ? '🟡' : '🟢');
                $markdown .= "### {$severity} {$issue['description']}\n\n";
                $markdown .= "**Solution :** {$issue['solution']}\n\n";
                $markdown .= "**Fichier :** `{$issue['file']}`\n\n";

                if (! empty($issue['examples'])) {
                    $markdown .= "**Exemples trouvés :**\n";

                    foreach ($issue['examples'] as $example) {
                        $markdown .= "- `{$example}`\n";
                    }

                    $markdown .= "\n";
                }
            }
        }

        // Checklist de migration
        $markdown .= "## ✅ Checklist de Migration\n\n";

        foreach ($data['migration_checklist'] as $item) {
            $status = $item['completed'] ? '✅' : '⬜';
            $markdown .= "- {$status} **{$item['title']}**\n";
            $markdown .= "  {$item['description']}\n\n";
        }

        // Recommandations
        $markdown .= "## 💡 Recommandations\n\n";

        foreach ($data['recommendations'] as $recommendation) {
            $markdown .= \sprintf('- %s%s', $recommendation, PHP_EOL);
        }

        $markdown .= "\n";

        // Commandes utiles
        $markdown .= "## 🛠️ Commandes Utiles\n\n";
        $markdown .= "```bash\n";
        $markdown .= "# Migration complète avec sauvegarde\n";
        $markdown .= "php artisan bootstrap:migrate-to-5 --backup\n\n";
        $markdown .= "# Validation post-migration\n";
        $markdown .= "php artisan bootstrap:validate --fix\n\n";
        $markdown .= "# Analyse détaillée\n";
        $markdown .= "php artisan bootstrap:analyze --detailed\n";
        $markdown .= "```\n\n";

        $markdown .= "---\n";

        return $markdown."*Rapport généré par [Bootstrap 5 Migrator](https://github.com/forxer/bootstrap5-migrator)*\n";
    }

    private function generateReportContent(array $data): string
    {
        $content = $this->generateScoreSection($data['validation']);
        $content .= $this->generateStatsSection($data['analysis']);
        $content .= $this->generateSummarySection($data['analysis']);
        $content .= $this->generateIssuesSection($data['analysis']);
        $content .= $this->generateValidationSection($data['validation']);
        $content .= $this->generateChecklistSection($data['migration_checklist']);

        return $content.$this->generateRecommendationsSection($data['recommendations']);
    }

    private function generateScoreSection(array $validation): string
    {
        $score = $validation['score'];
        $scoreClass = $score >= 90 ? 'high' : ($score >= 70 ? 'medium' : 'low');

        $description = match (true) {
            $score >= 90 => 'Excellent ! Votre migration est pratiquement terminée.',
            $score >= 70 => 'Bon progrès ! Quelques ajustements sont encore nécessaires.',
            $score >= 50 => 'Votre migration est en cours. Plusieurs éléments nécessitent votre attention.',
            default => 'Attention ! De nombreux problèmes doivent être résolus avant de finaliser la migration.'
        };

        return "
        <div class='score-section'>
            <h2>🎯 Score de Migration</h2>
            <div class='score {$scoreClass}'>{$score}/100</div>
            <div class='score-description'>{$description}</div>
            <div class='progress-bar'>
                <div class='progress-fill {$scoreClass}' style='width: {$score}%'></div>
            </div>
        </div>";
    }

    private function generateStatsSection(array $analysis): string
    {
        $stats = [
            [
                'number' => \count($analysis['deprecated_classes']['classes']),
                'label' => 'Classes obsolètes',
                'color' => \count($analysis['deprecated_classes']['classes']) > 0 ? 'danger' : 'success',
            ],
            [
                'number' => \count($analysis['cdn_links']),
                'label' => 'Liens CDN à mettre à jour',
                'color' => \count($analysis['cdn_links']) > 0 ? 'warning' : 'success',
            ],
            [
                'number' => \count($analysis['special_cases']['issues']),
                'label' => 'Cas spéciaux',
                'color' => \count($analysis['special_cases']['issues']) > 0 ? 'danger' : 'success',
            ],
            [
                'number' => $analysis['file_stats']['total_files'],
                'label' => 'Fichiers analysés',
                'color' => 'info',
            ],
        ];

        $html = "<div class='section'><h2>📈 Statistiques de l'Analyse</h2><div class='stats-grid'>";

        foreach ($stats as $stat) {
            $html .= "
            <div class='stat-card'>
                <div class='stat-number' style='color: var(--bs-{$stat['color']})'>{$stat['number']}</div>
                <div class='stat-label'>{$stat['label']}</div>
            </div>";
        }

        return $html.'</div></div>';
    }

    private function generateSummarySection(array $analysis): string
    {
        $html = "
        <div class='section'>
            <h2>📋 Résumé de l'Analyse</h2>
            <table class='table'>
                <thead>
                    <tr>
                        <th>Élément</th>
                        <th>Statut</th>
                        <th>Détails</th>
                        <th>Action requise</th>
                    </tr>
                </thead>
                <tbody>";

        $items = [
            [
                'element' => 'Bootstrap Version',
                'status' => $analysis['general']['bootstrap_version'],
                'details' => $this->getVersionStatus($analysis['general']['bootstrap_version']),
                'action' => str_contains((string) $analysis['general']['bootstrap_version'], '4.') ? 'Migration requise' : 'OK',
            ],
            [
                'element' => 'jQuery Usage',
                'status' => $analysis['general']['jquery_usage'] ? 'Détecté' : 'Non détecté',
                'details' => $analysis['general']['jquery_usage'] ? 'Vérifier la compatibilité' : 'Aucune dépendance',
                'action' => $analysis['general']['jquery_usage'] ? 'Évaluer la nécessité' : 'OK',
            ],
            [
                'element' => 'Classes obsolètes',
                'status' => \count($analysis['deprecated_classes']['classes']).' trouvées',
                'details' => $this->getSeverityBreakdown($analysis['deprecated_classes']['classes']),
                'action' => \count($analysis['deprecated_classes']['classes']) > 0 ? 'Remplacement automatique' : 'OK',
            ],
            [
                'element' => 'Liens CDN',
                'status' => \count($analysis['cdn_links']).' obsolètes',
                'details' => \count($analysis['cdn_links']) > 0 ? 'Bootstrap 4 détecté' : 'À jour',
                'action' => \count($analysis['cdn_links']) > 0 ? 'Mise à jour requise' : 'OK',
            ],
        ];

        foreach ($items as $item) {
            $html .= "
            <tr>
                <td><strong>{$item['element']}</strong></td>
                <td>{$item['status']}</td>
                <td>{$item['details']}</td>
                <td>{$item['action']}</td>
            </tr>";
        }

        return $html.'</tbody></table></div>';
    }

    private function generateIssuesSection(array $analysis): string
    {
        $html = "<div class='section'><h2>⚠️ Problèmes Détectés</h2>";

        // Classes obsolètes
        if (! empty($analysis['deprecated_classes']['classes'])) {
            $html .= "<h3>🔄 Classes CSS Obsolètes</h3><div class='grid grid-2'>";

            foreach ($analysis['deprecated_classes']['classes'] as $class => $details) {
                $cardClass = $this->getSeverityCardClass($details['severity']);
                $badge = $this->getSeverityBadge($details['severity']);

                $html .= "
                <div class='card {$cardClass}'>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;'>
                        <strong>{$class}</strong>
                        {$badge}
                    </div>
                    <div class='code-block'>Remplacer par: <span class='highlight'>{$details['replacement']}</span></div>
                    <small>{$details['count']} occurrences trouvées</small>
                </div>";
            }

            $html .= '</div>';
        }

        // Cas spéciaux
        if (! empty($analysis['special_cases']['issues'])) {
            $html .= '<h3>🔧 Cas Spéciaux</h3>';

            foreach ($analysis['special_cases']['issues'] as $issue) {
                $cardClass = $this->getSeverityCardClass($issue['severity']);
                $badge = $this->getSeverityBadge($issue['severity']);

                $html .= "
                <div class='issue {$cardClass}'>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;'>
                        <strong>{$issue['description']}</strong>
                        {$badge}
                    </div>
                    <p><strong>Solution :</strong> {$issue['solution']}</p>
                    <p><strong>Fichier :</strong> <code>{$issue['file']}</code></p>
                    <p><strong>Occurrences :</strong> {$issue['matches']}</p>";

                if (! empty($issue['examples'])) {
                    $html .= "<p><strong>Exemples :</strong></p><div class='code-block'>";

                    foreach (\array_slice($issue['examples'], 0, 3) as $example) {
                        $html .= \sprintf('<code>%s</code><br>', $example);
                    }

                    $html .= '</div>';
                }

                $html .= '</div>';
            }
        }

        return $html.'</div>';
    }

    private function generateValidationSection(array $validation): string
    {
        $html = "<div class='section'><h2>✅ Validation de la Migration</h2>";

        // Problèmes critiques
        if (! empty($validation['critical_issues'])) {
            $html .= '<h3>🚨 Problèmes Critiques</h3>';
            $html .= '<p>Ces problèmes doivent être résolus immédiatement :</p>';

            foreach ($validation['critical_issues'] as $issue) {
                $html .= "
                <div class='issue danger'>
                    <strong>{$issue['description']}</strong>
                    <br><small>Fichier: <code>{$issue['file']}</code></small>
                </div>";
            }
        }

        // Avertissements
        if (! empty($validation['warnings'])) {
            $html .= '<h3>⚠️ Avertissements</h3>';
            $html .= '<p>Ces éléments nécessitent votre attention :</p>';

            foreach ($validation['warnings'] as $warning) {
                $html .= "
                <div class='issue warning'>
                    <strong>{$warning['description']}</strong>
                    <br><small>Fichier: <code>{$warning['file']}</code></small>
                </div>";
            }
        }

        // Problèmes corrigeables automatiquement
        if (! empty($validation['fixable_issues'])) {
            $html .= '<h3>🔧 Problèmes Corrigeables Automatiquement</h3>';
            $html .= '<p>Ces problèmes peuvent être corrigés automatiquement avec la commande :</p>';
            $html .= "<div class='code-block'>php artisan bootstrap:validate --fix</div>";

            // Grouper par type d'action
            $groupedIssues = [];

            foreach ($validation['fixable_issues'] as $issue) {
                $action = $issue['action'] ?? 'unknown';
                $groupedIssues[$action][] = $issue;
            }

            foreach ($groupedIssues as $action => $issues) {
                $actionName = $this->getActionDisplayName($action);
                $html .= \sprintf('<h4>%s (', $actionName).\count($issues).' problèmes)</h4>';

                foreach (\array_slice($issues, 0, 5) as $issue) {
                    $html .= "
                    <div class='issue info'>
                        <strong>{$issue['description']}</strong>
                        <br><small>Fichier: <code>{$issue['file']}</code></small>
                    </div>";
                }

                if (\count($issues) > 5) {
                    $remaining = \count($issues) - 5;
                    $html .= \sprintf('<p><em>... et %d autres problèmes similaires</em></p>', $remaining);
                }
            }
        }

        // Statut par catégorie
        $html .= '<h3>📊 Statut par Catégorie</h3>';
        $html .= "<table class='table'>
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Détails</th>
                    <th>Action Recommandée</th>
                </tr>
            </thead>
            <tbody>";

        $categories = [
            ['name' => 'Classes CSS', 'status' => $validation['css_status'] ?? '❓', 'details' => $validation['css_details'] ?? 'Non analysé'],
            ['name' => 'JavaScript', 'status' => $validation['js_status'] ?? '❓', 'details' => $validation['js_details'] ?? 'Non analysé'],
            ['name' => 'Attributs data-*', 'status' => $validation['data_attributes_status'] ?? '❓', 'details' => $validation['data_attributes_details'] ?? 'Non analysé'],
            ['name' => 'Liens CDN', 'status' => $validation['cdn_status'] ?? '❓', 'details' => $validation['cdn_details'] ?? 'Non analysé'],
            ['name' => 'Dépendances NPM', 'status' => $validation['npm_status'] ?? '❓', 'details' => $validation['npm_details'] ?? 'Non analysé'],
        ];

        foreach ($categories as $category) {
            $action = $this->getRecommendedAction($category['status']);
            $statusClass = $this->getStatusClass($category['status']);

            $html .= "
            <tr>
                <td><strong>{$category['name']}</strong></td>
                <td><span class='badge badge-{$statusClass}'>{$category['status']}</span></td>
                <td>{$category['details']}</td>
                <td>{$action}</td>
            </tr>";
        }

        $html .= '</tbody></table>';

        // Recommandations spécifiques à la validation
        if (! empty($validation['recommendations'])) {
            $html .= '<h3>💡 Recommandations de Validation</h3>';
            $html .= '<ul>';

            foreach ($validation['recommendations'] as $recommendation) {
                $html .= \sprintf('<li>%s</li>', $recommendation);
            }

            $html .= '</ul>';
        }

        // Commandes utiles
        $html .= '<h3>🛠️ Commandes Utiles</h3>';
        $html .= "<div class='grid grid-2'>";

        $commands = [
            [
                'title' => 'Validation complète',
                'command' => 'php artisan bootstrap:validate',
                'description' => 'Effectue une validation complète sans modifications',
            ],
            [
                'title' => 'Correction automatique',
                'command' => 'php artisan bootstrap:validate --fix',
                'description' => 'Corrige automatiquement les problèmes mineurs',
            ],
            [
                'title' => 'Mode strict',
                'command' => 'php artisan bootstrap:validate --strict',
                'description' => 'Échoue si des problèmes critiques sont détectés',
            ],
            [
                'title' => 'Analyse détaillée',
                'command' => 'php artisan bootstrap:analyze --detailed',
                'description' => 'Analyse approfondie avec localisation des problèmes',
            ],
        ];

        foreach ($commands as $cmd) {
            $html .= "
            <div class='card info'>
                <h4>{$cmd['title']}</h4>
                <div class='code-block'>{$cmd['command']}</div>
                <p><small>{$cmd['description']}</small></p>
            </div>";
        }

        $html .= '</div>';

        return $html.'</div>';
    }

    private function getActionDisplayName(string $action): string
    {
        return match ($action) {
            'update_bootstrap_version' => '🔄 Mise à jour version Bootstrap',
            'replace_popper' => '🔄 Remplacement Popper.js',
            'replace_class' => '🎨 Remplacement classes CSS',
            'update_data_attribute' => '🏷️ Mise à jour attributs data-*',
            'update_cdn_link' => '🔗 Mise à jour liens CDN',
            default => '🔧 '.ucfirst(str_replace('_', ' ', $action))
        };
    }

    private function getRecommendedAction(string $status): string
    {
        if (str_contains($status, '✅')) {
            return 'Aucune action requise';
        }

        if (str_contains($status, '❌')) {
            return 'Action immédiate requise';
        }

        if (str_contains($status, '⚠️')) {
            return 'Vérification recommandée';
        }

        return 'À évaluer';
    }

    private function getStatusClass(string $status): string
    {
        if (str_contains($status, '✅')) {
            return 'success';
        }

        if (str_contains($status, '❌')) {
            return 'danger';
        }

        if (str_contains($status, '⚠️')) {
            return 'warning';
        }

        return 'info';
    }

    private function getPackageVersion(): string
    {
        $composerPath = base_path('vendor/forxer/bootstrap5-migrator/composer.json');

        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);

            return $composer['version'] ?? 'dev-develop';
        }

        return 'dev-develop';
    }

    private function generateFileStats(): array
    {
        $stats = [
            'total_files' => 0,
            'by_type' => [],
            'by_extension' => [],
        ];

        $paths = [
            resource_path('views'),
            resource_path('css'),
            resource_path('sass'),
            resource_path('scss'),
            resource_path('js'),
            public_path(),
        ];

        $typeMapping = [
            'php' => 'template', 'blade.php' => 'template', 'html' => 'template', 'htm' => 'template',
            'twig' => 'template', 'vue' => 'template',
            'css' => 'style', 'scss' => 'style', 'sass' => 'style',
            'js' => 'script', 'ts' => 'script',
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $stats['total_files']++;

                        $filename = $file->getFilename();
                        $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);

                        if (str_ends_with((string) $filename, '.blade.php')) {
                            $extension = 'blade.php';
                        }

                        if (isset($typeMapping[$extension])) {
                            $type = $typeMapping[$extension];
                            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
                            $stats['by_extension'][$extension] = ($stats['by_extension'][$extension] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        return $stats;
    }

    private function generateRecommendations(): array
    {
        return [
            'Testez tous vos composants interactifs (modals, dropdowns, tooltips) après migration',
            'Vérifiez le comportement des formulaires, particulièrement les contrôles customisés',
            'Testez la responsivité sur différents appareils et tailles d\'écran',
            'Si vous utilisez jQuery avec Bootstrap, évaluez la migration vers JavaScript vanilla',
            'Considérez l\'utilisation de Bootstrap Icons pour remplacer les icônes obsolètes',
            'Vérifiez vos surcharges CSS customisées et adaptez-les si nécessaire',
            'Testez les plugins tiers pour s\'assurer de leur compatibilité avec Bootstrap 5',
            'Consultez la documentation officielle Bootstrap 5 pour les cas complexes',
            'Mettez à jour vos processus de build (Webpack, Vite) si nécessaire',
            'Documentez les changements pour votre équipe de développement',
        ];
    }

    private function generateMigrationChecklist(): array
    {
        return [
            [
                'title' => 'Mise à jour du package.json',
                'description' => 'Bootstrap mis à jour vers la version 5.x et Popper.js remplacé par @popperjs/core',
                'completed' => $this->isPackageJsonUpdated(),
            ],
            [
                'title' => 'Migration des classes CSS',
                'description' => 'Toutes les classes obsolètes ont été remplacées par leurs équivalents Bootstrap 5',
                'completed' => $this->areClassesMigrated(),
            ],
            [
                'title' => 'Mise à jour des attributs data-*',
                'description' => 'Tous les attributs data-* ont été préfixés avec data-bs-',
                'completed' => $this->areDataAttributesUpdated(),
            ],
            [
                'title' => 'Migration des structures HTML',
                'description' => 'Input groups, form groups et autres structures ont été simplifiées',
                'completed' => $this->areStructuresMigrated(),
            ],
            [
                'title' => 'Mise à jour des liens CDN',
                'description' => 'Tous les liens CDN pointent vers Bootstrap 5',
                'completed' => $this->areCDNLinksUpdated(),
            ],
            [
                'title' => 'Tests des composants interactifs',
                'description' => 'Modals, dropdowns, tooltips et autres composants fonctionnent correctement',
                'completed' => false,
            ],
            [
                'title' => 'Validation des formulaires',
                'description' => 'Tous les formulaires et contrôles customisés fonctionnent comme attendu',
                'completed' => false,
            ],
            [
                'title' => 'Tests de responsivité',
                'description' => 'Le design responsive fonctionne sur tous les appareils',
                'completed' => false,
            ],
            [
                'title' => 'Migration du code JavaScript',
                'description' => 'Code jQuery Bootstrap migré vers l\'API JavaScript vanilla',
                'completed' => $this->isJavaScriptMigrated(),
            ],
            [
                'title' => 'Documentation mise à jour',
                'description' => 'Documentation développeur et guides utilisateur mis à jour',
                'completed' => false,
            ],
        ];
    }

    private function generateChecklistSection(array $checklist): string
    {
        $html = "
        <div class='section'>
            <h2>📋 Checklist de Migration</h2>
            <p>Utilisez cette checklist pour vous assurer que tous les aspects de la migration ont été pris en compte :</p>
            <ul class='checklist'>";

        foreach ($checklist as $item) {
            $checkboxClass = $item['completed'] ? 'checked' : '';
            $html .= "
            <li>
                <div class='checkbox {$checkboxClass}'></div>
                <div>
                    <strong>{$item['title']}</strong>
                    <br><small>{$item['description']}</small>
                </div>
            </li>";
        }

        return $html.'</ul></div>';
    }

    private function generateRecommendationsSection(array $recommendations): string
    {
        $html = "
        <div class='section'>
            <h2>💡 Recommandations</h2>
            <div class='grid grid-2'>";

        foreach ($recommendations as $index => $recommendation) {
            $html .= "
            <div class='card info'>
                <strong>".($index + 1).".</strong> {$recommendation}
            </div>";
        }

        return $html.'</div></div>';
    }

    private function getVersionStatus(string $version): string
    {
        if (str_contains($version, '4.')) {
            return '🔄 Migration requise';
        }

        if (str_contains($version, '5.')) {
            return '✅ Bootstrap 5 détecté';
        }

        return '❓ Version inconnue';
    }

    private function getSeverityBreakdown(array $classes): string
    {
        $high = \count(array_filter($classes, fn ($c): bool => $c['severity'] === 'high'));
        $medium = \count(array_filter($classes, fn ($c): bool => $c['severity'] === 'medium'));
        $low = \count(array_filter($classes, fn ($c): bool => $c['severity'] === 'low'));

        $parts = [];

        if ($high > 0) {
            $parts[] = $high.' haute';
        }

        if ($medium > 0) {
            $parts[] = $medium.' moyenne';
        }

        if ($low > 0) {
            $parts[] = $low.' faible';
        }

        return implode(', ', $parts);
    }

    private function getSeverityCardClass(string $severity): string
    {
        return match ($severity) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
            default => 'info'
        };
    }

    private function getSeverityBadge(string $severity): string
    {
        $text = match ($severity) {
            'high' => 'Haute',
            'medium' => 'Moyenne',
            'low' => 'Faible',
            default => 'Info'
        };

        return \sprintf("<span class='badge badge-%s'>%s</span>", $severity, $text);
    }

    private function isPackageJsonUpdated(): bool
    {
        $packageJsonPath = base_path('package.json');

        if (! File::exists($packageJsonPath)) {
            return false;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);
        $bootstrapVersion = $packageJson['dependencies']['bootstrap'] ?? $packageJson['devDependencies']['bootstrap'] ?? null;

        return $bootstrapVersion && str_contains((string) $bootstrapVersion, '5.');
    }

    private function areClassesMigrated(): bool
    {
        $analysis = $this->classAnalyzer->findDeprecatedClasses();

        return empty($analysis['classes']);
    }

    private function areDataAttributesUpdated(): bool
    {
        $files = $this->getAllTemplateFiles();
        $oldAttributes = ['data-toggle=', 'data-target=', 'data-dismiss='];

        foreach ($files as $file) {
            $content = File::get($file);

            foreach ($oldAttributes as $attr) {
                if (str_contains($content, $attr)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function areStructuresMigrated(): bool
    {
        $files = $this->getAllTemplateFiles();
        $oldStructures = ['input-group-prepend', 'input-group-append', 'form-group'];

        foreach ($files as $file) {
            $content = File::get($file);

            foreach ($oldStructures as $structure) {
                if (str_contains($content, $structure)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function areCDNLinksUpdated(): bool
    {
        $cdnLinks = $this->cdnAnalyzer->findCDNLinks();

        return $cdnLinks === [];
    }

    private function isJavaScriptMigrated(): bool
    {
        $files = $this->getAllJavaScriptFiles();
        $jqueryPatterns = [
            '/\$\([^)]+\)\.modal\s*\(/',
            '/\$\([^)]+\)\.dropdown\s*\(/',
            '/\$\([^)]+\)\.tooltip\s*\(/',
        ];

        foreach ($files as $file) {
            $content = File::get($file);

            foreach ($jqueryPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function getAllTemplateFiles(): array
    {
        $files = [];
        $paths = [resource_path('views'), public_path()];
        $extensions = ['php', 'blade.php', 'html', 'htm', 'twig', 'vue'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();

                        foreach ($extensions as $ext) {
                            if (str_ends_with((string) $filename, $ext)) {
                                $files[] = $file->getPathname();
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $files;
    }

    private function getAllJavaScriptFiles(): array
    {
        $files = [];
        $paths = [resource_path('js'), public_path('js')];
        $extensions = ['js', 'ts'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();

                        foreach ($extensions as $ext) {
                            if (str_ends_with((string) $filename, $ext)) {
                                $files[] = $file->getPathname();
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $files;
    }
}
