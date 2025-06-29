<?php

namespace Bootstrap5Migrator\Commands;

use Bootstrap5Migrator\Analyzers\CDNAnalyzer;
use Bootstrap5Migrator\Analyzers\DeprecatedClassAnalyzer;
use Bootstrap5Migrator\Analyzers\SpecialCaseAnalyzer;
use Bootstrap5Migrator\Bootstrap5Migrator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AnalyzeBootstrap4Command extends Command
{
    protected $signature = 'bootstrap:analyze
                            {--format=table : Format de sortie (table, json, html)}
                            {--export= : Exporter vers un fichier}
                            {--detailed : Analyse détaillée avec localisation des problèmes}';

    protected $description = 'Analyze your Laravel application for Bootstrap 4 usage and migration readiness';

    public function handle(
        Bootstrap5Migrator $migrator,
        DeprecatedClassAnalyzer $classAnalyzer,
        CDNAnalyzer $cdnAnalyzer,
        SpecialCaseAnalyzer $specialAnalyzer
    ): int {
        $this->info('🔍 Analyse de votre application Bootstrap 4...');

        $analysis = [
            'general' => $migrator->analyzeApplication(),
            'deprecated_classes' => $classAnalyzer->findDeprecatedClasses($this->option('detailed')),
            'cdn_links' => $cdnAnalyzer->findCDNLinks(),
            'special_cases' => $specialAnalyzer->findSpecialCases(),
            'file_support' => $this->analyzeFileSupport(),
        ];

        $this->displayAnalysis($analysis);

        if ($this->option('export')) {
            $this->exportAnalysis($analysis, $this->option('export'));
        }

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

    private function displayTableFormat(array $analysis): void
    {
        // Résumé général
        $this->info('📊 Résumé de l\'analyse');
        $this->table(['Élément', 'Statut', 'Détails'], [
            ['Bootstrap Version', $analysis['general']['bootstrap_version'], $this->getVersionStatus($analysis['general']['bootstrap_version'])],
            ['jQuery Usage', $analysis['general']['jquery_usage'] ? 'Détecté' : 'Non détecté', $analysis['general']['jquery_usage'] ? '⚠️ À vérifier' : '✅ OK'],
            ['Classes obsolètes', \count($analysis['deprecated_classes']['classes']), \count($analysis['deprecated_classes']['classes']).' trouvées'],
            ['Liens CDN', \count($analysis['cdn_links']), \count($analysis['cdn_links']).' à mettre à jour'],
            ['Cas spéciaux', \count($analysis['special_cases']['issues']), \count($analysis['special_cases']['issues']).' problèmes détectés'],
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
            }

            $this->table(['Classe', 'Remplacement', 'Occurrences', 'Fichiers'], $classData);
        }

        // CDN Links
        if (! empty($analysis['cdn_links'])) {
            $this->warn('🔗 Liens CDN Bootstrap 4 détectés :');
            $cdnData = [];

            foreach ($analysis['cdn_links'] as $link) {
                $cdnData[] = [
                    $link['file'],
                    $link['current_version'],
                    $link['provider'],
                    $link['suggested_v5_link'],
                ];
            }

            $this->table(['Fichier', 'Version actuelle', 'CDN', 'Lien Bootstrap 5 suggéré'], $cdnData);
        }

        // Cas spéciaux
        if (! empty($analysis['special_cases']['issues'])) {
            $this->error('⚠️ Cas spéciaux nécessitant une attention manuelle :');

            foreach ($analysis['special_cases']['issues'] as $issue) {
                $this->line(\sprintf('• %s: %s', $issue['type'], $issue['description']));

                if (! empty($issue['files'])) {
                    $this->line('  Fichiers affectés: '.implode(', ', \array_slice($issue['files'], 0, 3)));
                }
            }
        }
    }

    private function generateHTMLReport(array $analysis): void
    {
        $reportPath = storage_path('app/bootstrap-analysis-report.html');

        $html = view('bootstrap5-migrator::analysis-report', ['analysis' => $analysis])->render();

        file_put_contents($reportPath, $html);

        $this->info('📄 Rapport HTML généré : '.$reportPath);
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
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && str_ends_with((string) $file->getFilename(), $extension)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    private function exportAnalysis(array $analysis, string $filePath): void
    {
        $format = pathinfo($filePath, PATHINFO_EXTENSION);

        match ($format) {
            'json' => file_put_contents($filePath, json_encode($analysis, JSON_PRETTY_PRINT)),
            'csv' => $this->exportToCSV($analysis, $filePath),
            default => file_put_contents($filePath, print_r($analysis, true)),
        };

        $this->info('📁 Analyse exportée vers : '.$filePath);
    }

    private function exportToCSV(array $analysis, string $filePath): void
    {
        $fp = fopen($filePath, 'w');

        // En-têtes
        fputcsv($fp, ['Type', 'Élément', 'Statut', 'Détails', 'Fichiers']);

        // Classes obsolètes
        foreach ($analysis['deprecated_classes']['classes'] as $class => $details) {
            fputcsv($fp, [
                'Classe obsolète',
                $class,
                'À remplacer',
                $details['replacement'] ?? 'Supprimée',
                implode(';', $details['files'] ?? []),
            ]);
        }

        // CDN Links
        foreach ($analysis['cdn_links'] as $link) {
            fputcsv($fp, [
                'CDN Link',
                $link['current_version'],
                'À mettre à jour',
                $link['suggested_v5_link'],
                $link['file'],
            ]);
        }

        fclose($fp);
    }
}
