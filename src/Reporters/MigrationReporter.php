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
        return view('bootstrap5-migrator::comparison', ['comparison' => $comparison])->render();
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
        $html = view('bootstrap5-migrator::performance', ['migrationData' => $migrationData])->render();

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
        return view('bootstrap5-migrator::report', ['data' => $data])->render();
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

    public function getSeverityBreakdown(array $classes): string
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
