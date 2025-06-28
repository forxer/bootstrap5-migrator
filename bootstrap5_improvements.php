<?php

// src/Commands/AnalyzeBootstrap4Command.php
<?php

namespace Bootstrap5Migrator\Commands;

use Illuminate\Console\Command;
use Bootstrap5Migrator\Bootstrap5Migrator;
use Bootstrap5Migrator\Analyzers\DeprecatedClassAnalyzer;
use Bootstrap5Migrator\Analyzers\CDNAnalyzer;
use Bootstrap5Migrator\Analyzers\SpecialCaseAnalyzer;

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
    ) {
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

        switch ($format) {
            case 'json':
                $this->line(json_encode($analysis, JSON_PRETTY_PRINT));
                break;
            case 'html':
                $this->generateHTMLReport($analysis);
                break;
            default:
                $this->displayTableFormat($analysis);
        }
    }

    private function displayTableFormat(array $analysis): void
    {
        // Résumé général
        $this->info('📊 Résumé de l\'analyse');
        $this->table(['Élément', 'Statut', 'Détails'], [
            ['Bootstrap Version', $analysis['general']['bootstrap_version'], $this->getVersionStatus($analysis['general']['bootstrap_version'])],
            ['jQuery Usage', $analysis['general']['jquery_usage'] ? 'Détecté' : 'Non détecté', $analysis['general']['jquery_usage'] ? '⚠️ À vérifier' : '✅ OK'],
            ['Classes obsolètes', count($analysis['deprecated_classes']['classes']), count($analysis['deprecated_classes']['classes']) . ' trouvées'],
            ['Liens CDN', count($analysis['cdn_links']), count($analysis['cdn_links']) . ' à mettre à jour'],
            ['Cas spéciaux', count($analysis['special_cases']['issues']), count($analysis['special_cases']['issues']) . ' problèmes détectés'],
        ]);

        // Classes obsolètes détaillées
        if (!empty($analysis['deprecated_classes']['classes'])) {
            $this->warn('📝 Classes obsolètes trouvées :');
            $classData = [];
            foreach ($analysis['deprecated_classes']['classes'] as $class => $details) {
                $classData[] = [
                    $class,
                    $details['replacement'] ?? 'Supprimée',
                    $details['count'] ?? 0,
                    $this->option('detailed') ? implode(', ', array_slice($details['files'] ?? [], 0, 3)) : 'Multiple fichiers'
                ];
            }
            $this->table(['Classe', 'Remplacement', 'Occurrences', 'Fichiers'], $classData);
        }

        // CDN Links
        if (!empty($analysis['cdn_links'])) {
            $this->warn('🔗 Liens CDN Bootstrap 4 détectés :');
            $cdnData = [];
            foreach ($analysis['cdn_links'] as $link) {
                $cdnData[] = [
                    $link['file'],
                    $link['current_version'],
                    $link['provider'],
                    $link['suggested_v5_link']
                ];
            }
            $this->table(['Fichier', 'Version actuelle', 'CDN', 'Lien Bootstrap 5 suggéré'], $cdnData);
        }

        // Cas spéciaux
        if (!empty($analysis['special_cases']['issues'])) {
            $this->error('⚠️ Cas spéciaux nécessitant une attention manuelle :');
            foreach ($analysis['special_cases']['issues'] as $issue) {
                $this->line("• {$issue['type']}: {$issue['description']}");
                if (!empty($issue['files'])) {
                    $this->line("  Fichiers affectés: " . implode(', ', array_slice($issue['files'], 0, 3)));
                }
            }
        }
    }

    private function generateHTMLReport(array $analysis): void
    {
        $reportPath = storage_path('app/bootstrap-analysis-report.html');
        
        $html = view('bootstrap5-migrator::analysis-report', compact('analysis'))->render();
        
        file_put_contents($reportPath, $html);
        
        $this->info("📄 Rapport HTML généré : {$reportPath}");
    }

    private function getVersionStatus(string $version): string
    {
        if (str_contains($version, '^4.') || str_contains($version, '4.')) {
            return '🔄 Migration recommandée';
        } elseif (str_contains($version, '^5.') || str_contains($version, '5.')) {
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
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && str_ends_with($file->getFilename(), $extension)) {
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
        
        switch ($format) {
            case 'json':
                file_put_contents($filePath, json_encode($analysis, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->exportToCSV($analysis, $filePath);
                break;
            default:
                file_put_contents($filePath, print_r($analysis, true));
        }
        
        $this->info("📁 Analyse exportée vers : {$filePath}");
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
                implode(';', $details['files'] ?? [])
            ]);
        }
        
        // CDN Links
        foreach ($analysis['cdn_links'] as $link) {
            fputcsv($fp, [
                'CDN Link',
                $link['current_version'],
                'À mettre à jour',
                $link['suggested_v5_link'],
                $link['file']
            ]);
        }
        
        fclose($fp);
    }
}

// src/Commands/ValidateBootstrap5Command.php
<?php

namespace Bootstrap5Migrator\Commands;

use Illuminate\Console\Command;
use Bootstrap5Migrator\Validators\Bootstrap5Validator;

class ValidateBootstrap5Command extends Command
{
    protected $signature = 'bootstrap:validate 
                            {--fix : Tenter de corriger automatiquement les problèmes mineurs}
                            {--strict : Mode strict (échec si des problèmes sont trouvés)}';

    protected $description = 'Validate your Bootstrap 5 migration and detect remaining issues';

    public function handle(Bootstrap5Validator $validator)
    {
        $this->info('✅ Validation de votre migration Bootstrap 5...');

        $results = $validator->validateMigration();

        $this->displayValidationResults($results);

        if ($this->option('fix') && !empty($results['fixable_issues'])) {
            $this->fixIssues($validator, $results['fixable_issues']);
        }

        if ($this->option('strict') && $results['has_critical_issues']) {
            $this->error('❌ Validation échouée en mode strict');
            return 1;
        }

        return 0;
    }

    private function displayValidationResults(array $results): void
    {
        // Score global
        $score = $results['score'];
        $scoreColor = $score >= 90 ? 'info' : ($score >= 70 ? 'comment' : 'error');
        
        $this->newLine();
        $this->$scoreColor("📊 Score de migration : {$score}/100");
        
        // Problèmes critiques
        if (!empty($results['critical_issues'])) {
            $this->error('🚨 Problèmes critiques :');
            foreach ($results['critical_issues'] as $issue) {
                $this->line("  • {$issue['description']} ({$issue['file']})");
            }
        }

        // Avertissements
        if (!empty($results['warnings'])) {
            $this->warn('⚠️ Avertissements :');
            foreach ($results['warnings'] as $warning) {
                $this->line("  • {$warning['description']} ({$warning['file']})");
            }
        }

        // Recommandations
        if (!empty($results['recommendations'])) {
            $this->info('💡 Recommandations :');
            foreach ($results['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }

        // Résumé par catégorie
        $this->newLine();
        $this->table(['Catégorie', 'Statut', 'Détails'], [
            ['Classes CSS', $results['css_status'], $results['css_details']],
            ['JavaScript', $results['js_status'], $results['js_details']],
            ['Attributs data-*', $results['data_attributes_status'], $results['data_attributes_details']],
            ['CDN Links', $results['cdn_status'], $results['cdn_details']],
            ['Dépendances NPM', $results['npm_status'], $results['npm_details']],
        ]);
    }

    private function fixIssues(Bootstrap5Validator $validator, array $fixableIssues): void
    {
        $this->info('🔧 Correction automatique des problèmes mineurs...');
        
        $fixed = 0;
        foreach ($fixableIssues as $issue) {
            if ($validator->fixIssue($issue)) {
                $this->line("  ✅ Corrigé : {$issue['description']}");
                $fixed++;
            } else {
                $this->line("  ❌ Échec : {$issue['description']}");
            }
        }
        
        $this->info("🎉 {$fixed} problème(s) corrigé(s) automatiquement");
    }
}

// src/Commands/GenerateReportCommand.php
<?php

namespace Bootstrap5Migrator\Commands;

use Illuminate\Console\Command;
use Bootstrap5Migrator\Reporters\MigrationReporter;

class GenerateReportCommand extends Command
{
    protected $signature = 'bootstrap:report 
                            {--format=html : Format du rapport (html, pdf, markdown)}
                            {--output= : Chemin de sortie du rapport}
                            {--include-screenshots : Inclure des captures d\'écran (nécessite puppeteer)}';

    protected $description = 'Generate a comprehensive migration report';

    public function handle(MigrationReporter $reporter)
    {
        $this->info('📄 Génération du rapport de migration...');

        $format = $this->option('format');
        $outputPath = $this->option('output') ?: storage_path("app/bootstrap-migration-report.{$format}");

        $reportData = $reporter->generateReportData();

        switch ($format) {
            case 'pdf':
                $this->generatePDFReport($reporter, $reportData, $outputPath);
                break;
            case 'markdown':
                $this->generateMarkdownReport($reporter, $reportData, $outputPath);
                break;
            default:
                $this->generateHTMLReport($reporter, $reportData, $outputPath);
        }

        $this->info("✅ Rapport généré : {$outputPath}");
        
        if ($this->option('include-screenshots')) {
            $this->generateScreenshots($outputPath);
        }

        return 0;
    }

    private function generateHTMLReport(MigrationReporter $reporter, array $data, string $path): void
    {
        $html = $reporter->generateHTMLReport($data);
        file_put_contents($path, $html);
    }

    private function generatePDFReport(MigrationReporter $reporter, array $data, string $path): void
    {
        // Nécessite une librairie PDF comme DomPDF ou wkhtmltopdf
        $html = $reporter->generateHTMLReport($data);
        
        // Exemple avec DomPDF (à installer séparément)
        /*
        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        file_put_contents($path, $pdf->output());
        */
        
        $this->warn('⚠️ Génération PDF nécessite l\'installation de DomPDF ou wkhtmltopdf');
        
        // Fallback vers HTML
        $htmlPath = str_replace('.pdf', '.html', $path);
        file_put_contents($htmlPath, $html);
        $this->info("📄 Rapport HTML généré à la place : {$htmlPath}");
    }

    private function generateMarkdownReport(MigrationReporter $reporter, array $data, string $path): void
    {
        $markdown = $reporter->generateMarkdownReport($data);
        file_put_contents($path, $markdown);
    }

    private function generateScreenshots(string $reportPath): void
    {
        $this->info('📸 Génération des captures d\'écran...');
        $this->warn('⚠️ Fonctionnalité nécessitant Puppeteer ou un outil de capture similaire');
        
        // Cette fonctionnalité nécessiterait l'intégration avec un outil comme :
        // - Puppeteer (Node.js)
        // - Chrome/Chromium headless
        // - Selenium WebDriver
        
        $this->comment('💡 Pour activer les captures d\'écran, installez puppeteer :');
        $this->line('npm install -g puppeteer');
    }
}

// src/Analyzers/DeprecatedClassAnalyzer.php
<?php

namespace Bootstrap5Migrator\Analyzers;

use Illuminate\Support\Facades\File;

class DeprecatedClassAnalyzer
{
    protected array $deprecatedClasses = [
        // Classes supprimées
        'jumbotron' => ['replacement' => 'bg-light p-5 rounded-3', 'severity' => 'high'],
        'jumbotron-fluid' => ['replacement' => 'bg-light p-5', 'severity' => 'high'],
        'media' => ['replacement' => 'd-flex', 'severity' => 'high'],
        'media-object' => ['replacement' => 'flex-shrink-0', 'severity' => 'high'],
        'media-body' => ['replacement' => 'flex-grow-1 ms-3', 'severity' => 'high'],
        
        // Classes renommées
        'form-group' => ['replacement' => 'mb-3', 'severity' => 'medium'],
        'form-row' => ['replacement' => 'row g-3', 'severity' => 'medium'],
        'custom-select' => ['replacement' => 'form-select', 'severity' => 'medium'],
        
        // Spacing utilities
        'ml-0' => ['replacement' => 'ms-0', 'severity' => 'low'],
        'ml-1' => ['replacement' => 'ms-1', 'severity' => 'low'],
        'ml-2' => ['replacement' => 'ms-2', 'severity' => 'low'],
        'ml-3' => ['replacement' => 'ms-3', 'severity' => 'low'],
        'ml-4' => ['replacement' => 'ms-4', 'severity' => 'low'],
        'ml-5' => ['replacement' => 'ms-5', 'severity' => 'low'],
        'ml-auto' => ['replacement' => 'ms-auto', 'severity' => 'low'],
        
        'mr-0' => ['replacement' => 'me-0', 'severity' => 'low'],
        'mr-1' => ['replacement' => 'me-1', 'severity' => 'low'],
        'mr-2' => ['replacement' => 'me-2', 'severity' => 'low'],
        'mr-3' => ['replacement' => 'me-3', 'severity' => 'low'],
        'mr-4' => ['replacement' => 'me-4', 'severity' => 'low'],
        'mr-5' => ['replacement' => 'me-5', 'severity' => 'low'],
        'mr-auto' => ['replacement' => 'me-auto', 'severity' => 'low'],
        
        // Text alignment
        'text-left' => ['replacement' => 'text-start', 'severity' => 'low'],
        'text-right' => ['replacement' => 'text-end', 'severity' => 'low'],
        
        // Badges
        'badge-primary' => ['replacement' => 'bg-primary', 'severity' => 'medium'],
        'badge-secondary' => ['replacement' => 'bg-secondary', 'severity' => 'medium'],
        'badge-success' => ['replacement' => 'bg-success', 'severity' => 'medium'],
        'badge-danger' => ['replacement' => 'bg-danger', 'severity' => 'medium'],
        'badge-warning' => ['replacement' => 'bg-warning text-dark', 'severity' => 'medium'],
        'badge-info' => ['replacement' => 'bg-info text-dark', 'severity' => 'medium'],
        'badge-light' => ['replacement' => 'bg-light text-dark', 'severity' => 'medium'],
        'badge-dark' => ['replacement' => 'bg-dark', 'severity' => 'medium'],
        
        // Screen readers
        'sr-only' => ['replacement' => 'visually-hidden', 'severity' => 'medium'],
        'sr-only-focusable' => ['replacement' => 'visually-hidden-focusable', 'severity' => 'medium'],
        
        // Close button
        'close' => ['replacement' => 'btn-close', 'severity' => 'medium'],
    ];

    public function findDeprecatedClasses(bool $detailed = false): array
    {
        $results = ['classes' => [], 'summary' => []];
        $files = $this->getAllRelevantFiles();

        foreach ($files as $file) {
            $content = File::get($file);
            
            foreach ($this->deprecatedClasses as $class => $info) {
                $matches = $this->findClassInContent($content, $class);
                
                if ($matches > 0) {
                    if (!isset($results['classes'][$class])) {
                        $results['classes'][$class] = [
                            'replacement' => $info['replacement'],
                            'severity' => $info['severity'],
                            'count' => 0,
                            'files' => []
                        ];
                    }
                    
                    $results['classes'][$class]['count'] += $matches;
                    
                    if ($detailed) {
                        $results['classes'][$class]['files'][] = $file;
                        $results['classes'][$class]['locations'] = $this->findClassLocations($content, $class);
                    }
                }
            }
        }

        // Générer le résumé
        $results['summary'] = [
            'total_deprecated_classes' => count($results['classes']),
            'high_severity' => count(array_filter($results['classes'], fn($c) => $c['severity'] === 'high')),
            'medium_severity' => count(array_filter($results['classes'], fn($c) => $c['severity'] === 'medium')),
            'low_severity' => count(array_filter($results['classes'], fn($c) => $c['severity'] === 'low')),
        ];

        return $results;
    }

    private function getAllRelevantFiles(): array
    {
        $files = [];
        $paths = [
            resource_path('views'),
            resource_path('css'),
            resource_path('sass'),
            resource_path('scss'),
            public_path(),
        ];

        $extensions = ['php', 'blade.php', 'html', 'htm', 'vue', 'twig', 'css', 'scss', 'sass'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();
                        foreach ($extensions as $ext) {
                            if (str_ends_with($filename, $ext)) {
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

    private function findClassInContent(string $content, string $class): int
    {
        // Recherche les classes dans les attributs class="..." et class='...'
        $patterns = [
            '/class="[^"]*\b' . preg_quote($class, '/') . '\b[^"]*"/',
            "/class='[^']*\b" . preg_quote($class, '/') . "\b[^']*'/",
            '/\.' . preg_quote($class, '/') . '\b/', // CSS selectors
        ];

        $totalMatches = 0;
        foreach ($patterns as $pattern) {
            $totalMatches += preg_match_all($pattern, $content);
        }

        return $totalMatches;
    }

    private function findClassLocations(string $content, string $class): array
    {
        $lines = explode("\n", $content);
        $locations = [];

        foreach ($lines as $lineNumber => $line) {
            if (str_contains($line, $class)) {
                $locations[] = [
                    'line' => $lineNumber + 1,
                    'content' => trim($line)
                ];
            }
        }

        return $locations;
    }
}

// src/Analyzers/CDNAnalyzer.php
<?php

namespace Bootstrap5Migrator\Analyzers;

use Illuminate\Support\Facades\File;

class CDNAnalyzer
{
    protected array $cdnProviders = [
        'jsdelivr.net' => [
            'pattern' => '/https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@([0-9.]+)\/dist\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'
        ],
        'stackpath.bootstrapcdn.com' => [
            'pattern' => '/https:\/\/stackpath\.bootstrapcdn\.com\/bootstrap\/([0-9.]+)\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'
        ],
        'cloudflare.com' => [
            'pattern' => '/https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/bootstrap\/([0-9.]+)\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css'
        ],
        'unpkg.com' => [
            'pattern' => '/https:\/\/unpkg\.com\/bootstrap@([0-9.]+)\/dist\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://unpkg.com/bootstrap@5.3.2/dist/css/bootstrap.min.css'
        ]
    ];

    public function findCDNLinks(): array
    {
        $results = [];
        $files = $this->getHTMLFiles();

        foreach ($files as $file) {
            $content = File::get($file);
            
            foreach ($this->cdnProviders as $provider => $config) {
                preg_match_all($config['pattern'], $content, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $match) {
                    $results[] = [
                        'file' => $file,
                        'provider' => $provider,
                        'current_link' => $match[0],
                        'current_version' => $match[1],
                        'suggested_v5_link' => $config['v5_template'],
                        'is_bootstrap_4' => str_starts_with($match[1], '4.'),
                    ];
                }
            }
        }

        return $results;
    }

    private function getHTMLFiles(): array
    {
        $files = [];
        $paths = [
            resource_path('views'),
            public_path(),
        ];

        $extensions = ['php', 'blade.php', 'html', 'htm'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();
                        foreach ($extensions as $ext) {
                            if (str_ends_with($filename, $ext)) {
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

// src/Analyzers/SpecialCaseAnalyzer.php
<?php

namespace Bootstrap5Migrator\Analyzers;

use Illuminate\Support\Facades\File;

class SpecialCaseAnalyzer
{
    protected array $specialCases = [
        'negative_margins' => [
            'pattern' => '/\b[mp][tblrxy]?-n[0-5]\b/',
            'description' => 'Classes à marges/padding négatives non incluses dans le CDN Bootstrap 5',
            'severity' => 'medium',
            'solution' => 'Inclure les utilitaires de marges négatives ou créer des classes CSS customisées'
        ],
        'print_styles' => [
            'pattern' => '/@media\s+print\s*{|\.d-print-/',
            'description' => 'Styles d\'impression (non inclus dans Bootstrap 5)',
            'severity' => 'low',
            'solution' => 'Utiliser Bootstrap Print CSS ou créer des styles d\'impression customisés'
        ],
        'jquery_bootstrap_plugins' => [
            'pattern' => '/\$\([^)]+\)\.(modal|dropdown|tooltip|popover|collapse|carousel|tab)\s*\(/',
            'description' => 'Utilisation de plugins Bootstrap via jQuery',
            'severity' => 'high',
            'solution' => 'Migrer vers l\'API JavaScript vanilla de Bootstrap 5'
        ],
        'data_attributes_v4' => [
            'pattern' => '/data-(toggle|target|dismiss|slide|ride|interval|pause|wrap|keyboard|backdrop|focus)=/',
            'description' => 'Attributs data-* Bootstrap 4 (sans préfixe data-bs-)',
            'severity' => 'high',
            'solution' => 'Ajouter le préfixe data-bs- aux attributs'
        ],
        'input_group_structure' => [
            'pattern' => '/input-group-(prepend|append)/',
            'description' => 'Structure input-group Bootstrap 4 (simplifiée dans Bootstrap 5)',
            'severity' => 'medium',
            'solution' => 'Supprimer les divs input-group-prepend/append'
        ],
        'card_deck_columns' => [
            'pattern' => '/\b(card-deck|card-columns)\b/',
            'description' => 'Card deck/columns supprimés dans Bootstrap 5',
            'severity' => 'medium',
            'solution' => 'Utiliser le système de grille avec row et row-cols-*'
        ],
        'custom_form_controls' => [
            'pattern' => '/\b(custom-control|custom-checkbox|custom-radio|custom-switch|custom-select|custom-file)\b/',
            'description' => 'Contrôles de formulaire customisés Bootstrap 4',
            'severity' => 'medium',
            'solution' => 'Migrer vers les nouvelles classes form-check et form-select'
        ]
    ];

    public function findSpecialCases(): array
    {
        $results = ['issues' => [], 'summary' => []];
        $files = $this->getAllRelevantFiles();

        foreach ($files as $file) {
            $content = File::get($file);
            
            foreach ($this->specialCases as $caseType => $config) {
                if (preg_match_all($config['pattern'], $content, $matches)) {
                    $results['issues'][] = [
                        'type' => $caseType,
                        'description' => $config['description'],
                        'severity' => $config['severity'],
                        'solution' => $config['solution'],
                        'file' => $file,
                        'matches' => count($matches[0]),
                        'examples' => array_slice($matches[0], 0, 3) // Premiers 3 exemples
                    ];
                }
            }
        }

        // Résumé par sévérité
        $results['summary'] = [
            'total_issues' => count($results['issues']),
            'high_severity' => count(array_filter($results['issues'], fn($i) => $i['severity'] === 'high')),
            'medium_severity' => count(array_filter($results['issues'], fn($i) => $i['severity'] === 'medium')),
            'low_severity' => count(array_filter($results['issues'], fn($i) => $i['severity'] === 'low')),
        ];

        return $results;
    }

    private function getAllRelevantFiles(): array
    {
        $files = [];
        $paths = [
            resource_path('views'),
            resource_path('css'),
            resource_path('sass'),
            resource_path('scss'),
            resource_path('js'),
            public_path(),
        ];

        $extensions = ['php', 'blade.php', 'html', 'htm', 'vue', 'twig', 'css', 'scss', 'sass', 'js', 'ts'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();
                        foreach ($extensions as $ext) {
                            if (str_ends_with($filename, $ext)) {
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

// src/Validators/Bootstrap5Validator.php
<?php

namespace Bootstrap5Migrator\Validators;

use Illuminate\Support\Facades\File;

class Bootstrap5Validator
{
    public function validateMigration(): array
    {
        $results = [
            'score' => 0,
            'has_critical_issues' => false,
            'critical_issues' => [],
            'warnings' => [],
            'recommendations' => [],
            'fixable_issues' => [],
        ];

        // Validation des dépendances NPM
        $npmValidation = $this->validateNPMDependencies();
        $results = array_merge($results, $npmValidation);

        // Validation des classes CSS
        $cssValidation = $this->validateCSSClasses();
        $results = array_merge($results, $cssValidation);

        // Validation des attributs data-*
        $dataValidation = $this->validateDataAttributes();
        $results = array_merge($results, $dataValidation);

        // Validation des liens CDN
        $cdnValidation = $this->validateCDNLinks();
        $results = array_merge($results, $cdnValidation);

        // Validation JavaScript
        $jsValidation = $this->validateJavaScript();
        $results = array_merge($results, $jsValidation);

        // Calcul du score final
        $results['score'] = $this->calculateScore($results);

        return $results;
    }

    private function validateNPMDependencies(): array
    {
        $packageJsonPath = base_path('package.json');
        $results = ['npm_status' => '❌ Non vérifié', 'npm_details' => ''];

        if (!File::exists($packageJsonPath)) {
            $results['npm_status'] = '⚠️ package.json non trouvé';
            $results['warnings'][] = [
                'description' => 'Fichier package.json non trouvé',
                'file' => $packageJsonPath
            ];
            return $results;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);
        
        // Vérifier Bootstrap 5
        $bootstrapVersion = $packageJson['dependencies']['bootstrap'] ?? $packageJson['devDependencies']['bootstrap'] ?? null;
        
        if (!$bootstrapVersion) {
            $results['critical_issues'][] = [
                'description' => 'Bootstrap non trouvé dans package.json',
                'file' => $packageJsonPath
            ];
            $results['has_critical_issues'] = true;
        } elseif (str_contains($bootstrapVersion, '4.')) {
            $results['critical_issues'][] = [
                'description' => 'Bootstrap 4 encore présent dans package.json',
                'file' => $packageJsonPath
            ];
            $results['has_critical_issues'] = true;
            $results['fixable_issues'][] = [
                'type' => 'npm_bootstrap_version',
                'description' => 'Mettre à jour Bootstrap vers la version 5',
                'file' => $packageJsonPath,
                'action' => 'update_bootstrap_version'
            ];
        } elseif (str_contains($bootstrapVersion, '5.')) {
            $results['npm_status'] = '✅ Bootstrap 5 détecté';
            $results['npm_details'] = "Version: {$bootstrapVersion}";
        }

        // Vérifier Popper.js
        $popperVersion = $packageJson['dependencies']['@popperjs/core'] ?? $packageJson['devDependencies']['@popperjs/core'] ?? null;
        $oldPopper = $packageJson['dependencies']['popper.js'] ?? $packageJson['devDependencies']['popper.js'] ?? null;

        if ($oldPopper) {
            $results['fixable_issues'][] = [
                'type' => 'old_popper',
                'description' => 'Ancienne version de Popper.js détectée',
                'file' => $packageJsonPath,
                'action' => 'replace_popper'
            ];
        }

        if (!$popperVersion && !$oldPopper) {
            $results['warnings'][] = [
                'description' => '@popperjs/core non trouvé (requis pour Bootstrap 5)',
                'file' => $packageJsonPath
            ];
        }

        return $results;
    }

    private function validateCSSClasses(): array
    {
        $results = ['css_status' => '✅ OK', 'css_details' => 'Aucune classe obsolète détectée'];
        
        $deprecatedClasses = [
            'jumbotron', 'media', 'form-group', 'ml-', 'mr-', 'pl-', 'pr-',
            'text-left', 'text-right', 'badge-primary', 'sr-only', 'close'
        ];

        $files = $this->getTemplateFiles();
        $foundIssues = [];

        foreach ($files as $file) {
            $content = File::get($file);
            
            foreach ($deprecatedClasses as $class) {
                if (str_contains($class, '-') && str_ends_with($class, '-')) {
                    // Classes avec variants numériques
                    for ($i = 0; $i <= 5; $i++) {
                        $fullClass = $class . $i;
                        if (preg_match('/\b' . preg_quote($fullClass, '/') . '\b/', $content)) {
                            $foundIssues[] = [
                                'class' => $fullClass,
                                'file' => $file
                            ];
                        }
                    }
                    // Auto variant
                    $autoClass = $class . 'auto';
                    if (preg_match('/\b' . preg_quote($autoClass, '/') . '\b/', $content)) {
                        $foundIssues[] = [
                            'class' => $autoClass,
                            'file' => $file
                        ];
                    }
                } else {
                    if (preg_match('/\b' . preg_quote($class, '/') . '\b/', $content)) {
                        $foundIssues[] = [
                            'class' => $class,
                            'file' => $file
                        ];
                    }
                }
            }
        }

        if (!empty($foundIssues)) {
            $results['css_status'] = '❌ Classes obsolètes détectées';
            $results['css_details'] = count($foundIssues) . ' classes obsolètes trouvées';
            
            foreach ($foundIssues as $issue) {
                $results['critical_issues'][] = [
                    'description' => "Classe obsolète '{$issue['class']}' trouvée",
                    'file' => $issue['file']
                ];
                
                $results['fixable_issues'][] = [
                    'type' => 'deprecated_class',
                    'description' => "Remplacer la classe '{$issue['class']}'",
                    'file' => $issue['file'],
                    'action' => 'replace_class',
                    'class' => $issue['class']
                ];
            }
            
            $results['has_critical_issues'] = true;
        }

        return $results;
    }

    private function validateDataAttributes(): array
    {
        $results = ['data_attributes_status' => '✅ OK', 'data_attributes_details' => 'Attributs à jour'];
        
        $oldAttributes = [
            'data-toggle', 'data-target', 'data-dismiss', 'data-slide', 'data-ride'
        ];

        $files = $this->getTemplateFiles();
        $foundIssues = [];

        foreach ($files as $file) {
            $content = File::get($file);
            
            foreach ($oldAttributes as $attr) {
                if (preg_match('/' . preg_quote($attr, '/') . '=/', $content)) {
                    $foundIssues[] = [
                        'attribute' => $attr,
                        'file' => $file
                    ];
                }
            }
        }

        if (!empty($foundIssues)) {
            $results['data_attributes_status'] = '❌ Attributs obsolètes';
            $results['data_attributes_details'] = count($foundIssues) . ' attributs à mettre à jour';
            
            foreach ($foundIssues as $issue) {
                $results['fixable_issues'][] = [
                    'type' => 'data_attribute',
                    'description' => "Mettre à jour l'attribut '{$issue['attribute']}'",
                    'file' => $issue['file'],
                    'action' => 'update_data_attribute',
                    'attribute' => $issue['attribute']
                ];
            }
        }

        return $results;
    }

    private function validateCDNLinks(): array
    {
        $results = ['cdn_status' => '✅ OK', 'cdn_details' => 'Aucun lien CDN obsolète'];
        
        $files = $this->getTemplateFiles();
        $foundIssues = [];

        foreach ($files as $file) {
            $content = File::get($file);
            
            // Rechercher les liens CDN Bootstrap 4
            $patterns = [
                '/https:\/\/[^"\']*bootstrap.*4\.[0-9.]+.*\.css/',
                '/https:\/\/[^"\']*bootstrap.*4\.[0-9.]+.*\.js/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $foundIssues[] = [
                            'link' => $match,
                            'file' => $file
                        ];
                    }
                }
            }
        }

        if (!empty($foundIssues)) {
            $results['cdn_status'] = '❌ Liens CDN obsolètes';
            $results['cdn_details'] = count($foundIssues) . ' liens à mettre à jour';
            
            foreach ($foundIssues as $issue) {
                $results['fixable_issues'][] = [
                    'type' => 'cdn_link',
                    'description' => "Mettre à jour le lien CDN Bootstrap",
                    'file' => $issue['file'],
                    'action' => 'update_cdn_link',
                    'old_link' => $issue['link']
                ];
            }
        }

        return $results;
    }

    private function validateJavaScript(): array
    {
        $results = ['js_status' => '✅ OK', 'js_details' => 'JavaScript à jour'];
        
        $files = $this->getJavaScriptFiles();
        $foundIssues = [];

        foreach ($files as $file) {
            $content = File::get($file);
            
            // Rechercher l'usage jQuery des plugins Bootstrap
            $jqueryPatterns = [
                '/\$\([^)]+\)\.modal\s*\(/',
                '/\$\([^)]+\)\.dropdown\s*\(/',
                '/\$\([^)]+\)\.tooltip\s*\(/',
                '/\$\([^)]+\)\.popover\s*\(/',
            ];

            foreach ($jqueryPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $foundIssues[] = [
                        'type' => 'jquery_plugin',
                        'file' => $file,
                        'description' => 'Usage jQuery des plugins Bootstrap détecté'
                    ];
                    break; // Un seul signalement par fichier
                }
            }
        }

        if (!empty($foundIssues)) {
            $results['js_status'] = '⚠️ JavaScript nécessite attention';
            $results['js_details'] = count($foundIssues) . ' fichiers avec du code jQuery Bootstrap';
            
            foreach ($foundIssues as $issue) {
                $results['warnings'][] = [
                    'description' => $issue['description'],
                    'file' => $issue['file']
                ];
            }
            
            $results['recommendations'][] = 'Migrer le code jQuery Bootstrap vers l\'API JavaScript vanilla de Bootstrap 5';
        }

        return $results;
    }

    public function fixIssue(array $issue): bool
    {
        switch ($issue['action']) {
            case 'update_bootstrap_version':
                return $this->fixBootstrapVersion($issue['file']);
            
            case 'replace_popper':
                return $this->fixPopperDependency($issue['file']);
            
            case 'replace_class':
                return $this->fixDeprecatedClass($issue['file'], $issue['class']);
            
            case 'update_data_attribute':
                return $this->fixDataAttribute($issue['file'], $issue['attribute']);
            
            case 'update_cdn_link':
                return $this->fixCDNLink($issue['file'], $issue['old_link']);
            
            default:
                return false;
        }
    }

    private function fixBootstrapVersion(string $file): bool
    {
        try {
            $content = File::get($file);
            $packageJson = json_decode($content, true);
            
            if (isset($packageJson['dependencies']['bootstrap'])) {
                $packageJson['dependencies']['bootstrap'] = '^5.3.2';
            }
            if (isset($packageJson['devDependencies']['bootstrap'])) {
                $packageJson['devDependencies']['bootstrap'] = '^5.3.2';
            }
            
            File::put($file, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function fixPopperDependency(string $file): bool
    {
        try {
            $content = File::get($file);
            $packageJson = json_decode($content, true);
            
            // Supprimer l'ancienne version
            unset($packageJson['dependencies']['popper.js']);
            unset($packageJson['devDependencies']['popper.js']);
            
            // Ajouter la nouvelle version
            if (!isset($packageJson['dependencies']['@popperjs/core']) && !isset($packageJson['devDependencies']['@popperjs/core'])) {
                $packageJson['devDependencies']['@popperjs/core'] = '^2.11.8';
            }
            
            File::put($file, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function fixDeprecatedClass(string $file, string $oldClass): bool
    {
        try {
            $content = File::get($file);
            
            $replacements = [
                'jumbotron' => 'bg-light p-5 rounded-3',
                'media' => 'd-flex',
                'form-group' => 'mb-3',
                'text-left' => 'text-start',
                'text-right' => 'text-end',
                'sr-only' => 'visually-hidden',
                'close' => 'btn-close',
            ];
            
            // Gestion des classes avec variants numériques
            if (preg_match('/^(ml|mr|pl|pr)-(\d+|auto)$/', $oldClass, $matches)) {
                $prefix = $matches[1];
                $suffix = $matches[2];
                
                $newPrefix = [
                    'ml' => 'ms',
                    'mr' => 'me',
                    'pl' => 'ps',
                    'pr' => 'pe',
                ][$prefix];
                
                $replacements[$oldClass] = $newPrefix . '-' . $suffix;
            }
            
            if (isset($replacements[$oldClass])) {
                $content = preg_replace(
                    '/\bclass="([^"]*)\b' . preg_quote($oldClass, '/') . '\b([^"]*)"/',
                    'class="$1' . $replacements[$oldClass] . '$2"',
                    $content
                );
                
                File::put($file, $content);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function fixDataAttribute(string $file, string $oldAttribute): bool
    {
        try {
            $content = File::get($file);
            
            $replacements = [
                'data-toggle' => 'data-bs-toggle',
                'data-target' => 'data-bs-target',
                'data-dismiss' => 'data-bs-dismiss',
                'data-slide' => 'data-bs-slide',
                'data-ride' => 'data-bs-ride',
            ];
            
            if (isset($replacements[$oldAttribute])) {
                $content = str_replace($oldAttribute . '=', $replacements[$oldAttribute] . '=', $content);
                File::put($file, $content);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function fixCDNLink(string $file, string $oldLink): bool
    {
        try {
            $content = File::get($file);
            
            // Mapper vers les nouveaux liens Bootstrap 5
            $newLink = str_replace(
                ['4.6.0', '4.6.1', '4.6.2'],
                '5.3.2',
                $oldLink
            );
            
            $content = str_replace($oldLink, $newLink, $content);
            File::put($file, $content);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function calculateScore(array $results): int
    {
        $score = 100;
        
        // Pénalités
        $score -= count($results['critical_issues']) * 15;
        $score -= count($results['warnings']) * 5;
        $score -= count($results['fixable_issues']) * 2;
        
        return max(0, $score);
    }

    private function getTemplateFiles(): array
    {
        $files = [];
        $paths = [
            resource_path('views'),
            public_path(),
        ];

        $extensions = ['php', 'blade.php', 'html', 'htm', 'vue', 'twig'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();
                        foreach ($extensions as $ext) {
                            if (str_ends_with($filename, $ext)) {
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

    private function getJavaScriptFiles(): array
    {
        $files = [];
        $paths = [
            resource_path('js'),
            public_path('js'),
        ];

        $extensions = ['js', 'ts'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();
                        foreach ($extensions as $ext) {
                            if (str_ends_with($filename, $ext)) {
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

// src/Reporters/MigrationReporter.php
<?php

namespace Bootstrap5Migrator\Reporters;

use Bootstrap5Migrator\Bootstrap5Migrator;
use Bootstrap5Migrator\Analyzers\DeprecatedClassAnalyzer;
use Bootstrap5Migrator\Analyzers\CDNAnalyzer;
use Bootstrap5Migrator\Analyzers\SpecialCaseAnalyzer;
use Bootstrap5Migrator\Validators\Bootstrap5Validator;

class MigrationReporter
{
    protected $migrator;
    protected $classAnalyzer;
    protected $cdnAnalyzer;
    protected $specialAnalyzer;
    protected $validator;

    public function __construct(
        Bootstrap5Migrator $migrator,
        DeprecatedClassAnalyzer $classAnalyzer,
        CDNAnalyzer $cdnAnalyzer,
        SpecialCaseAnalyzer $specialAnalyzer,
        Bootstrap5Validator $validator
    ) {
        $this->migrator = $migrator;
        $this->classAnalyzer = $classAnalyzer;
        $this->cdnAnalyzer = $cdnAnalyzer;
        $this->specialAnalyzer = $specialAnalyzer;
        $this->validator = $validator;
    }

    public function generateReportData(): array
    {
        return [
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
                'app_name' => config('app.name'),
                'laravel_version' => app()->version(),
            ],
            'analysis' => [
                'general' => $this->migrator->analyzeApplication(),
                'deprecated_classes' => $this->classAnalyzer->findDeprecatedClasses(true),
                'cdn_links' => $this->cdnAnalyzer->findCDNLinks(),
                'special_cases' => $this->specialAnalyzer->findSpecialCases(),
            ],
            'validation' => $this->validator->validateMigration(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    public function generateHTMLReport(array $data): string
    {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Rapport de Migration Bootstrap 5 - {$data['meta']['app_name']}</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
                .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #6f42c1, #e83e8c); color: white; padding: 2rem; border-radius: 8px 8px 0 0; }
                .content { padding: 2rem; }
                .score { font-size: 3rem; font-weight: bold; text-align: center; margin: 1rem 0; }
                .score.high { color: #28a745; }
                .score.medium { color: #ffc107; }
                .score.low { color: #dc3545; }
                .section { margin: 2rem 0; padding: 1rem; border-left: 4px solid #6f42c1; background: #f8f9fa; }
                .issue { background: white; margin: 0.5rem 0; padding: 1rem; border-radius: 4px; border-left: 4px solid #dc3545; }
                .warning { border-left-color: #ffc107; }
                .success { border-left-color: #28a745; }
                .table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
                .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #dee2e6; }
                .table th { background: #e9ecef; font-weight: bold; }
                .badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: bold; border-radius: 0.25rem; }
                .badge-high { background: #dc3545; color: white; }
                .badge-medium { background: #ffc107; color: #212529; }
                .badge-low { background: #28a745; color: white; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📊 Rapport de Migration Bootstrap 5</h1>
                    <p><strong>Application:</strong> {$data['meta']['app_name']}</p>
                    <p><strong>Généré le:</strong> {$data['meta']['generated_at']}</p>
                    <p><strong>Laravel:</strong> {$data['meta']['laravel_version']}</p>
                </div>
                
                <div class='content'>
                    " . $this->generateScoreSection($data['validation']) . "
                    " . $this->generateSummarySection($data['analysis']) . "
                    " . $this->generateIssuesSection($data['analysis']) . "
                    " . $this->generateRecommendationsSection($data['recommendations']) . "
                </div>
            </div>
        </body>
        </html>";
    }

    public function generateMarkdownReport(array $data): string
    {
        $markdown = "# 📊 Rapport de Migration Bootstrap 5\n\n";
        $markdown .= "**Application:** {$data['meta']['app_name']}\n";
        $markdown .= "**Généré le:** {$data['meta']['generated_at']}\n";
        $markdown .= "**Laravel:** {$data['meta']['laravel_version']}\n\n";
        
        // Score
        $score = $data['validation']['score'];
        $markdown .= "## Score de Migration: {$score}/100\n\n";
        
        // Résumé
        $markdown .= "## 📋 Résumé\n\n";
        $markdown .= "| Élément | Statut | Détails |\n";
        $markdown .= "|---------|--------|----------|\n";
        $markdown .= "| Bootstrap Version | {$data['analysis']['general']['bootstrap_version']} | - |\n";
        $markdown .= "| jQuery Usage | " . ($data['analysis']['general']['jquery_usage'] ? 'Détecté' : 'Non détecté') . " | - |\n";
        $markdown .= "| Classes obsolètes | " . count($data['analysis']['deprecated_classes']['classes']) . " | À remplacer |\n";
        $markdown .= "| Liens CDN | " . count($data['analysis']['cdn_links']) . " | À mettre à jour |\n";
        $markdown .= "| Cas spéciaux | " . count($data['analysis']['special_cases']['issues']) . " | Attention manuelle |\n\n";
        
        // Classes obsolètes
        if (!empty($data['analysis']['deprecated_classes']['classes'])) {
            $markdown .= "## 🔄 Classes Obsolètes\n\n";
            foreach ($data['analysis']['deprecated_classes']['classes'] as $class => $details) {
                $markdown .= "- **{$class}** → `{$details['replacement']}` ({$details['count']} occurrences)\n";
            }
            $markdown .= "\n";
        }
        
        // Recommandations
        $markdown .= "## 💡 Recommandations\n\n";
        foreach ($data
            '