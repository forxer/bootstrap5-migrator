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