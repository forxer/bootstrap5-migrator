<?php

namespace Bootstrap5Migrator\Analyzers;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
                    if (! isset($results['classes'][$class])) {
                        $results['classes'][$class] = [
                            'replacement' => $info['replacement'],
                            'severity' => $info['severity'],
                            'count' => 0,
                            'files' => [],
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
            'total_deprecated_classes' => \count($results['classes']),
            'high_severity' => \count(array_filter($results['classes'], fn ($c): bool => $c['severity'] === 'high')),
            'medium_severity' => \count(array_filter($results['classes'], fn ($c): bool => $c['severity'] === 'medium')),
            'low_severity' => \count(array_filter($results['classes'], fn ($c): bool => $c['severity'] === 'low')),
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

    private function findClassInContent(string $content, string $class): int
    {
        // Recherche les classes dans les attributs class="..." et class='...'
        $patterns = [
            '/class="[^"]*\b'.preg_quote($class, '/').'\b[^"]*"/',
            "/class='[^']*\b".preg_quote($class, '/')."\b[^']*'/",
            '/\.'.preg_quote($class, '/').'\b/', // CSS selectors
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
                    'content' => trim($line),
                ];
            }
        }

        return $locations;
    }
}
