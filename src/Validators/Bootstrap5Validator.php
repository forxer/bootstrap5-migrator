<?php

namespace Bootstrap5Migrator\Validators;

use Exception;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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

        if (! File::exists($packageJsonPath)) {
            $results['npm_status'] = '⚠️ package.json non trouvé';
            $results['warnings'][] = [
                'description' => 'Fichier package.json non trouvé',
                'file' => $packageJsonPath,
            ];

            return $results;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);

        // Vérifier Bootstrap 5
        $bootstrapVersion = $packageJson['dependencies']['bootstrap'] ?? $packageJson['devDependencies']['bootstrap'] ?? null;

        if (! $bootstrapVersion) {
            $results['critical_issues'][] = [
                'description' => 'Bootstrap non trouvé dans package.json',
                'file' => $packageJsonPath,
            ];
            $results['has_critical_issues'] = true;
        } elseif (str_contains((string) $bootstrapVersion, '4.')) {
            $results['critical_issues'][] = [
                'description' => 'Bootstrap 4 encore présent dans package.json',
                'file' => $packageJsonPath,
            ];
            $results['has_critical_issues'] = true;
            $results['fixable_issues'][] = [
                'type' => 'npm_bootstrap_version',
                'description' => 'Mettre à jour Bootstrap vers la version 5',
                'file' => $packageJsonPath,
                'action' => 'update_bootstrap_version',
            ];
        } elseif (str_contains((string) $bootstrapVersion, '5.')) {
            $results['npm_status'] = '✅ Bootstrap 5 détecté';
            $results['npm_details'] = 'Version: '.$bootstrapVersion;
        }

        // Vérifier Popper.js
        $popperVersion = $packageJson['dependencies']['@popperjs/core'] ?? $packageJson['devDependencies']['@popperjs/core'] ?? null;
        $oldPopper = $packageJson['dependencies']['popper.js'] ?? $packageJson['devDependencies']['popper.js'] ?? null;

        if ($oldPopper) {
            $results['fixable_issues'][] = [
                'type' => 'old_popper',
                'description' => 'Ancienne version de Popper.js détectée',
                'file' => $packageJsonPath,
                'action' => 'replace_popper',
            ];
        }

        if (! $popperVersion && ! $oldPopper) {
            $results['warnings'][] = [
                'description' => '@popperjs/core non trouvé (requis pour Bootstrap 5)',
                'file' => $packageJsonPath,
            ];
        }

        return $results;
    }

    private function validateCSSClasses(): array
    {
        $results = ['css_status' => '✅ OK', 'css_details' => 'Aucune classe obsolète détectée'];

        $deprecatedClasses = [
            'jumbotron', 'media', 'form-group', 'ml-', 'mr-', 'pl-', 'pr-',
            'text-left', 'text-right', 'badge-primary', 'sr-only', 'close',
        ];

        $files = $this->getTemplateFiles();
        $foundIssues = [];

        foreach ($files as $file) {
            $content = File::get($file);

            foreach ($deprecatedClasses as $class) {
                if (str_contains($class, '-') && str_ends_with($class, '-')) {
                    // Classes avec variants numériques
                    for ($i = 0; $i <= 5; $i++) {
                        $fullClass = $class.$i;

                        if (preg_match('/\b'.preg_quote($fullClass, '/').'\b/', $content)) {
                            $foundIssues[] = [
                                'class' => $fullClass,
                                'file' => $file,
                            ];
                        }
                    }

                    // Auto variant
                    $autoClass = $class.'auto';

                    if (preg_match('/\b'.preg_quote($autoClass, '/').'\b/', $content)) {
                        $foundIssues[] = [
                            'class' => $autoClass,
                            'file' => $file,
                        ];
                    }
                } elseif (preg_match('/\b'.preg_quote($class, '/').'\b/', $content)) {
                    $foundIssues[] = [
                        'class' => $class,
                        'file' => $file,
                    ];
                }
            }
        }

        if ($foundIssues !== []) {
            $results['css_status'] = '❌ Classes obsolètes détectées';
            $results['css_details'] = \count($foundIssues).' classes obsolètes trouvées';

            foreach ($foundIssues as $issue) {
                $results['critical_issues'][] = [
                    'description' => \sprintf("Classe obsolète '%s' trouvée", $issue['class']),
                    'file' => $issue['file'],
                ];

                $results['fixable_issues'][] = [
                    'type' => 'deprecated_class',
                    'description' => \sprintf("Remplacer la classe '%s'", $issue['class']),
                    'file' => $issue['file'],
                    'action' => 'replace_class',
                    'class' => $issue['class'],
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
            'data-toggle', 'data-target', 'data-dismiss', 'data-slide', 'data-ride',
        ];

        $files = $this->getTemplateFiles();
        $foundIssues = [];

        foreach ($files as $file) {
            $content = File::get($file);

            foreach ($oldAttributes as $attr) {
                if (preg_match('/'.preg_quote($attr, '/').'=/', $content)) {
                    $foundIssues[] = [
                        'attribute' => $attr,
                        'file' => $file,
                    ];
                }
            }
        }

        if ($foundIssues !== []) {
            $results['data_attributes_status'] = '❌ Attributs obsolètes';
            $results['data_attributes_details'] = \count($foundIssues).' attributs à mettre à jour';

            foreach ($foundIssues as $issue) {
                $results['fixable_issues'][] = [
                    'type' => 'data_attribute',
                    'description' => \sprintf("Mettre à jour l'attribut '%s'", $issue['attribute']),
                    'file' => $issue['file'],
                    'action' => 'update_data_attribute',
                    'attribute' => $issue['attribute'],
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
                            'file' => $file,
                        ];
                    }
                }
            }
        }

        if ($foundIssues !== []) {
            $results['cdn_status'] = '❌ Liens CDN obsolètes';
            $results['cdn_details'] = \count($foundIssues).' liens à mettre à jour';

            foreach ($foundIssues as $issue) {
                $results['fixable_issues'][] = [
                    'type' => 'cdn_link',
                    'description' => 'Mettre à jour le lien CDN Bootstrap',
                    'file' => $issue['file'],
                    'action' => 'update_cdn_link',
                    'old_link' => $issue['link'],
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
                        'description' => 'Usage jQuery des plugins Bootstrap détecté',
                    ];
                    break; // Un seul signalement par fichier
                }
            }
        }

        if ($foundIssues !== []) {
            $results['js_status'] = '⚠️ JavaScript nécessite attention';
            $results['js_details'] = \count($foundIssues).' fichiers avec du code jQuery Bootstrap';

            foreach ($foundIssues as $issue) {
                $results['warnings'][] = [
                    'description' => $issue['description'],
                    'file' => $issue['file'],
                ];
            }

            $results['recommendations'][] = "Migrer le code jQuery Bootstrap vers l'API JavaScript vanilla de Bootstrap 5";
        }

        return $results;
    }

    public function fixIssue(array $issue): bool
    {
        return match ($issue['action']) {
            'update_bootstrap_version' => $this->fixBootstrapVersion($issue['file']),
            'replace_popper' => $this->fixPopperDependency($issue['file']),
            'replace_class' => $this->fixDeprecatedClass($issue['file'], $issue['class']),
            'update_data_attribute' => $this->fixDataAttribute($issue['file'], $issue['attribute']),
            'update_cdn_link' => $this->fixCDNLink($issue['file'], $issue['old_link']),
            default => false,
        };
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
        } catch (Exception) {
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
            if (! isset($packageJson['dependencies']['@popperjs/core']) && ! isset($packageJson['devDependencies']['@popperjs/core'])) {
                $packageJson['devDependencies']['@popperjs/core'] = '^2.11.8';
            }

            File::put($file, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return true;
        } catch (Exception) {
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

                $replacements[$oldClass] = $newPrefix.'-'.$suffix;
            }

            if (isset($replacements[$oldClass])) {
                $content = preg_replace(
                    '/\bclass="([^"]*)\b'.preg_quote($oldClass, '/').'\b([^"]*)"/',
                    'class="$1'.$replacements[$oldClass].'$2"',
                    $content
                );

                File::put($file, $content);

                return true;
            }

            return false;
        } catch (Exception) {
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
                $content = str_replace($oldAttribute.'=', $replacements[$oldAttribute].'=', $content);
                File::put($file, $content);

                return true;
            }

            return false;
        } catch (Exception) {
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
        } catch (Exception) {
            return false;
        }
    }

    private function calculateScore(array $results): int
    {
        $score = 100;

        // Pénalités
        $score -= \count($results['critical_issues']) * 15;
        $score -= \count($results['warnings']) * 5;
        $score -= \count($results['fixable_issues']) * 2;

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
