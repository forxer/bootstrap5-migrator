<?php

namespace Bootstrap5Migrator\Commands;

use Bootstrap5Migrator\Analyzers\CDNAnalyzer;
use Bootstrap5Migrator\Analyzers\DeprecatedClassAnalyzer;
use Bootstrap5Migrator\Analyzers\SpecialCaseAnalyzer;
use Bootstrap5Migrator\Bootstrap5Migrator;
use Bootstrap5Migrator\Traits\UsesPerformanceServices;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AnalyzeBootstrap4Command extends Command
{
    use UsesPerformanceServices;

    protected $signature = 'bootstrap:analyze
                            {--format=table : Format de sortie (table, json, html)}
                            {--export= : Exporter vers un fichier}
                            {--detailed : Analyse détaillée avec localisation des problèmes}
                            {--no-cache : Désactiver le cache pour cette analyse}
                            {--parallel : Utiliser le traitement parallèle (si disponible)}';

    protected $description = 'Analyze your Laravel application for Bootstrap 4 usage and migration readiness';

    public function handle(
        Bootstrap5Migrator $migrator,
        DeprecatedClassAnalyzer $classAnalyzer,
        CDNAnalyzer $cdnAnalyzer,
        SpecialCaseAnalyzer $specialAnalyzer
    ): int {
        $this->initializePerformanceServices();

        $useCache = ! $this->option('no-cache');
        $useParallel = $this->option('parallel');

        if ($useCache) {
            $this->showInfo('Cache activé - les analyses précédentes seront réutilisées');
        }

        if ($useParallel) {
            $this->showInfo('Mode parallèle activé pour de meilleures performances');
        }

        // Démarrer l'analyse avec progression multi-étapes
        $steps = [
            ['title' => 'Analyse générale', 'description' => 'Détection de Bootstrap, jQuery et Popper.js'],
            ['title' => 'Classes obsolètes', 'description' => 'Recherche des classes Bootstrap 4 à migrer'],
            ['title' => 'Liens CDN', 'description' => 'Détection des liens CDN Bootstrap 4'],
            ['title' => 'Cas spéciaux', 'description' => 'Identification des cas complexes'],
            ['title' => 'Statistiques fichiers', 'description' => 'Analyse de la structure du projet'],
            ['title' => 'Génération rapport', 'description' => 'Compilation des résultats'],
        ];

        $multiStep = $this->progressService->multiStep($steps);
        $multiStep->start();

        // Optimiser les analyseurs avec les services de performance
        $this->optimizeAnalyzer($classAnalyzer);
        $this->optimizeAnalyzer($cdnAnalyzer);
        $this->optimizeAnalyzer($specialAnalyzer);

        $analysis = [];

        $multiStep->nextStep(function () use ($migrator, &$analysis): void {
            $analysis['general'] = $migrator->analyzeApplication();
        });

        $multiStep->nextStep(function () use ($classAnalyzer, &$analysis): void {
            $analysis['deprecated_classes'] = $classAnalyzer->findDeprecatedClasses($this->option('detailed'));
        });

        $multiStep->nextStep(function () use ($cdnAnalyzer, &$analysis): void {
            $analysis['cdn_links'] = $cdnAnalyzer->findCDNLinks();
        });

        $multiStep->nextStep(function () use ($specialAnalyzer, &$analysis): void {
            $analysis['special_cases'] = $specialAnalyzer->findSpecialCases();
        });

        $multiStep->nextStep(function () use (&$analysis): void {
            $analysis['file_support'] = $this->analyzeFileSupport();
        });

        $multiStep->nextStep(function (): void {
            // Génération rapport - étape vide pour le moment
        });

        $multiStep->finish();

        $this->displayAnalysis($analysis);

        if ($this->option('export')) {
            $this->exportAnalysis($analysis, $this->option('export'));
        }

        $this->displayPerformanceStats();
        $this->cleanupPerformanceServices();

        return 0;
    }

    private function displayAnalysis(array $analysis): void
    {
        $format = $this->option('format');

        match ($format) {
            'json' => $this->line(json_encode($analysis, JSON_PRETTY_PRINT)),
            'html' => $this->generateHTMLReport($analysis),
            default => $this->displayTableFormat($analysis),
        };
    }

    private function generateHTMLReport(array $analysis): void
    {
        try {
            $html = view('bootstrap5-migrator::analysis-report', ['analysis' => $analysis])->render();

            if ($this->option('export')) {
                $outputPath = $this->option('export');
                File::put($outputPath, $html);
                $this->showInfo('📄 Rapport HTML généré : '.$outputPath);
            } else {
                $this->showInfo('📄 Rapport HTML généré (utilisez --export pour sauvegarder)');
                $this->displayTableFormat($analysis);
            }
        } catch (Exception $exception) {
            $this->showError('❌ Erreur lors de la génération du rapport HTML : '.$exception->getMessage());
            $this->warn('Utilisation du format table à la place...');
            $this->displayTableFormat($analysis);
        }
    }

    private function displayTableFormat(array $analysis): void
    {
        // Résumé général
        $this->showInfo('📊 Résumé de l\'analyse');
        $this->table(['Élément', 'Statut', 'Détails'], [
            ['Bootstrap Version', $analysis['general']['bootstrap_version'] ?? 'Non détecté', $this->getVersionStatus($analysis['general']['bootstrap_version'] ?? '')],
            ['jQuery Usage', $analysis['general']['jquery_usage'] ? 'Détecté' : 'Non détecté', $analysis['general']['jquery_usage'] ? '⚠️ À vérifier' : '✅ OK'],
            ['Classes obsolètes', \count($analysis['deprecated_classes']['classes'] ?? []), \count($analysis['deprecated_classes']['classes'] ?? []).' trouvées'],
            ['Liens CDN', \count($analysis['cdn_links'] ?? []), \count($analysis['cdn_links'] ?? []).' à mettre à jour'],
            ['Cas spéciaux', \count($analysis['special_cases']['issues'] ?? []), \count($analysis['special_cases']['issues'] ?? []).' problèmes détectés'],
        ]);

        // Classes obsolètes détaillées
        if (! empty($analysis['deprecated_classes']['classes'])) {
            $this->warn('📝 Classes obsolètes trouvées :');
            $classData = [];

            foreach ($analysis['deprecated_classes']['classes'] as $class => $details) {
                $classData[] = [
                    $class,
                    $details['replacement'] ?? 'Supprimée',
                    $details['count'] ?? 0,
                    $this->option('detailed') ? implode(', ', \array_slice($details['files'] ?? [], 0, 3)) : 'Multiple fichiers',
                ];

                // Limiter l'affichage pour éviter la surcharge
                if (\count($classData) >= 10) {
                    break;
                }
            }

            $this->table(['Classe', 'Remplacement', 'Occurrences', 'Fichiers'], $classData);

            if (\count($analysis['deprecated_classes']['classes']) > 10) {
                $remaining = \count($analysis['deprecated_classes']['classes']) - 10;
                $this->showInfo(\sprintf('... et %d autres classes obsolètes (utilisez --format=html pour voir toutes)', $remaining));
            }
        }

        // CDN Links
        if (! empty($analysis['cdn_links'])) {
            $this->warn('🔗 Liens CDN Bootstrap 4 détectés :');
            $cdnData = [];

            foreach ($analysis['cdn_links'] as $link) {
                $cdnData[] = [
                    basename($link['file'] ?? ''),
                    $link['current_version'] ?? 'N/A',
                    $link['provider'] ?? 'N/A',
                    $link['suggested_v5_link'] ?? 'N/A',
                ];
            }

            $this->table(['Fichier', 'Version actuelle', 'CDN', 'Lien Bootstrap 5 suggéré'], $cdnData);
        }

        // Cas spéciaux (résumé)
        if (! empty($analysis['special_cases']['issues'])) {
            $this->showError('⚠️ Cas spéciaux nécessitant une attention manuelle :');
            $issuesSummary = [];

            foreach ($analysis['special_cases']['issues'] as $issue) {
                $issuesSummary[$issue['type']] = ($issuesSummary[$issue['type']] ?? 0) + 1;
            }

            foreach ($issuesSummary as $type => $count) {
                $this->line(\sprintf('  • %s: %d occurrence(s)', $type, $count));
            }

            $this->showInfo('💡 Utilisez --format=html pour voir les détails complets');
        }
    }

    private function exportAnalysis(array $analysis, string $filePath): void
    {
        $format = pathinfo($filePath, PATHINFO_EXTENSION);

        try {
            switch ($format) {
                case 'json':
                    File::put($filePath, json_encode($analysis, JSON_PRETTY_PRINT));
                    break;

                case 'html':
                    $html = view('bootstrap5-migrator::analysis-report', ['analysis' => $analysis])->render();
                    File::put($filePath, $html);
                    break;

                case 'csv':
                    $this->exportToCSV($analysis, $filePath);
                    break;

                default:
                    // Format texte par défaut
                    $content = "RAPPORT D'ANALYSE BOOTSTRAP 4\n";
                    $content .= "================================\n\n";
                    $content .= 'Généré le : '.now()->format('d/m/Y H:i:s')."\n\n";
                    $content .= print_r($analysis, true);
                    File::put($filePath, $content);
            }

            $this->showInfo('📁 Analyse exportée vers : '.$filePath);

        } catch (Exception $exception) {
            $this->showError("❌ Erreur lors de l'export : ".$exception->getMessage());
        }
    }

    private function exportToCSV(array $analysis, string $filePath): void
    {
        $handle = fopen($filePath, 'w');

        if (! $handle) {
            throw new Exception('Impossible de créer le fichier CSV : '.$filePath);
        }

        // En-têtes
        fputcsv($handle, ['Type', 'Élément', 'Statut', 'Détails', 'Fichiers']);

        // Informations générales
        fputcsv($handle, ['Général', 'Bootstrap Version', $analysis['general']['bootstrap_version'] ?? 'N/A', '', '']);
        fputcsv($handle, ['Général', 'jQuery Usage', $analysis['general']['jquery_usage'] ? 'Oui' : 'Non', '', '']);

        // Classes obsolètes
        foreach ($analysis['deprecated_classes']['classes'] ?? [] as $class => $details) {
            fputcsv($handle, [
                'Classe obsolète',
                $class,
                'À remplacer',
                $details['replacement'] ?? 'Supprimée',
                implode(';', $details['files'] ?? []),
            ]);
        }

        // CDN Links
        foreach ($analysis['cdn_links'] ?? [] as $link) {
            fputcsv($handle, [
                'CDN Link',
                $link['current_version'] ?? 'N/A',
                'À mettre à jour',
                $link['suggested_v5_link'] ?? 'N/A',
                $link['file'] ?? '',
            ]);
        }

        // Cas spéciaux
        foreach ($analysis['special_cases']['issues'] ?? [] as $issue) {
            fputcsv($handle, [
                'Cas spécial',
                $issue['description'] ?? 'N/A',
                $issue['severity'] ?? 'N/A',
                $issue['solution'] ?? 'N/A',
                $issue['file'] ?? '',
            ]);
        }

        fclose($handle);
    }

    private function getVersionStatus(string $version): string
    {
        if (str_contains($version, '^4.') || str_contains($version, '4.')) {
            return '🔄 Migration recommandée';
        }

        if (str_contains($version, '^5.') || str_contains($version, '5.')) {
            return '✅ Déjà Bootstrap 5';
        }

        return '❓ Version inconnue';
    }

    private function analyzeFileSupport(): array
    {
        $supportedExtensions = ['php', 'blade.php', 'html', 'htm', 'vue', 'twig', 'css', 'scss', 'sass', 'js', 'ts'];
        $foundFiles = [];

        foreach ($supportedExtensions as $ext) {
            $count = $this->countFilesByExtension($ext);

            if ($count > 0) {
                $foundFiles[$ext] = $count;
            }
        }

        return $foundFiles;
    }

    private function countFilesByExtension(string $extension): int
    {
        $paths = [
            resource_path('views'),
            resource_path('css'),
            resource_path('sass'),
            resource_path('scss'),
            resource_path('js'),
            public_path(),
        ];

        $count = 0;

        foreach ($paths as $path) {
            if (is_dir($path)) {
                try {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
                    );

                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            if ($extension === 'blade.php' && str_ends_with((string) $file->getFilename(), '.blade.php')) {
                                $count++;
                            } elseif ($extension !== 'blade.php' && str_ends_with((string) $file->getFilename(), '.'.$extension)) {
                                $count++;
                            }
                        }
                    }
                } catch (Exception) {
                    // Ignorer les erreurs de permissions sur certains répertoires
                    continue;
                }
            }
        }

        return $count;
    }
}
