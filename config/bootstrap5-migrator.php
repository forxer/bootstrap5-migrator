<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Répertoires à analyser
    |--------------------------------------------------------------------------
    |
    | Définit les répertoires que l'outil va scanner pour détecter
    | les fichiers à migrer vers Bootstrap 5.
    |
    */
    'scan_directories' => [
        'css' => [
            resource_path('css'),
            resource_path('sass'),
            resource_path('scss'),
        ],
        'js' => [
            resource_path('js'),
        ],
        'views' => [
            resource_path('views'),
            // Ajoutez d'autres répertoires si nécessaire
            // public_path('templates'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensions de fichiers supportées
    |--------------------------------------------------------------------------
    |
    | Extensions de fichiers que l'outil va traiter lors de la migration.
    |
    */
    'file_extensions' => [
        'css' => ['css', 'scss', 'sass', 'less'],
        'js' => ['js', 'ts', 'jsx', 'tsx'],
        'views' => ['php', 'blade.php', 'html', 'htm', 'twig', 'vue', 'ejs', 'erb', 'hbs', 'jsp', 'asp', 'aspx', 'cshtml'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Versions des packages NPM
    |--------------------------------------------------------------------------
    |
    | Versions cibles pour la migration vers Bootstrap 5.
    |
    */
    'npm_packages' => [
        'bootstrap' => '^5.3.2',
        '@popperjs/core' => '^2.11.8',
    ],

    /*
    |--------------------------------------------------------------------------
    | Packages à supprimer
    |--------------------------------------------------------------------------
    |
    | Packages NPM obsolètes à supprimer lors de la migration.
    |
    */
    'packages_to_remove' => [
        'popper.js', // Remplacé par @popperjs/core
        // 'jquery', // Décommentez si vous voulez supprimer jQuery automatiquement
    ],

    /*
    |--------------------------------------------------------------------------
    | Mappings des classes CSS Bootstrap 4 → Bootstrap 5
    |--------------------------------------------------------------------------
    |
    | Correspondances pour la migration automatique des classes CSS.
    | Vous pouvez ajouter vos propres mappings personnalisés.
    |
    */
    'class_mappings' => [
        // Spacing utilities (direction-aware)
        'ml-0' => 'ms-0', 'ml-1' => 'ms-1', 'ml-2' => 'ms-2', 'ml-3' => 'ms-3', 'ml-4' => 'ms-4', 'ml-5' => 'ms-5', 'ml-auto' => 'ms-auto',
        'mr-0' => 'me-0', 'mr-1' => 'me-1', 'mr-2' => 'me-2', 'mr-3' => 'me-3', 'mr-4' => 'me-4', 'mr-5' => 'me-5', 'mr-auto' => 'me-auto',
        'pl-0' => 'ps-0', 'pl-1' => 'ps-1', 'pl-2' => 'ps-2', 'pl-3' => 'ps-3', 'pl-4' => 'ps-4', 'pl-5' => 'ps-5',
        'pr-0' => 'pe-0', 'pr-1' => 'pe-1', 'pr-2' => 'pe-2', 'pr-3' => 'pe-3', 'pr-4' => 'pe-4', 'pr-5' => 'pe-5',

        // Text alignment
        'text-left' => 'text-start',
        'text-right' => 'text-end',

        // Float utilities
        'float-left' => 'float-start',
        'float-right' => 'float-end',

        // Border radius
        'rounded-left' => 'rounded-start',
        'rounded-right' => 'rounded-end',

        // Forms - Major changes in Bootstrap 5
        'form-group' => 'mb-3',
        'form-row' => 'row g-3',
        'form-control-file' => 'form-control',
        'form-control-range' => 'form-range',
        'custom-select' => 'form-select',
        'custom-file' => 'form-control',
        'custom-control' => 'form-check',
        'custom-control-input' => 'form-check-input',
        'custom-control-label' => 'form-check-label',
        'custom-checkbox' => 'form-check',
        'custom-radio' => 'form-check',
        'custom-switch' => 'form-check form-switch',

        // Cards
        'card-deck' => 'row row-cols-1 row-cols-md-3 g-4',
        'card-columns' => 'row row-cols-1 row-cols-md-2 row-cols-xl-3',

        // Media object (removed in Bootstrap 5)
        'media' => 'd-flex',
        'media-object' => 'flex-shrink-0',
        'media-body' => 'flex-grow-1 ms-3',

        // Jumbotron (removed in Bootstrap 5)
        'jumbotron' => 'bg-light p-5 rounded-3',
        'jumbotron-fluid' => 'bg-light p-5',

        // Close button
        'close' => 'btn-close',

        // Badges - New background utilities
        'badge-primary' => 'bg-primary',
        'badge-secondary' => 'bg-secondary',
        'badge-success' => 'bg-success',
        'badge-danger' => 'bg-danger',
        'badge-warning' => 'bg-warning text-dark',
        'badge-info' => 'bg-info text-dark',
        'badge-light' => 'bg-light text-dark',
        'badge-dark' => 'bg-dark',

        // Buttons
        'btn-block' => 'd-grid',

        // Screen readers
        'sr-only' => 'visually-hidden',
        'sr-only-focusable' => 'visually-hidden-focusable',

        // Ajoutez vos mappings personnalisés ici...
        // 'my-custom-class-v4' => 'my-custom-class-v5',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mappings des attributs data-*
    |--------------------------------------------------------------------------
    |
    | Bootstrap 5 préfixe tous les attributs data-* avec 'data-bs-'
    |
    */
    'data_attribute_mappings' => [
        'data-toggle' => 'data-bs-toggle',
        'data-target' => 'data-bs-target',
        'data-dismiss' => 'data-bs-dismiss',
        'data-slide' => 'data-bs-slide',
        'data-slide-to' => 'data-bs-slide-to',
        'data-ride' => 'data-bs-ride',
        'data-interval' => 'data-bs-interval',
        'data-pause' => 'data-bs-pause',
        'data-wrap' => 'data-bs-wrap',
        'data-keyboard' => 'data-bs-keyboard',
        'data-backdrop' => 'data-bs-backdrop',
        'data-focus' => 'data-bs-focus',
        'data-offset' => 'data-bs-offset',
        'data-reference' => 'data-bs-reference',
        'data-boundary' => 'data-bs-boundary',
        'data-display' => 'data-bs-display',
    ],

    /*
    |--------------------------------------------------------------------------
    | Variables SCSS Bootstrap 5
    |--------------------------------------------------------------------------
    |
    | Nouvelles variables SCSS introduites dans Bootstrap 5
    |
    */
    'scss_variables' => [
        '$enable-rounded' => '$enable-rounded: true',
        '$enable-shadows' => '$enable-shadows: false',
        '$enable-gradients' => '$enable-gradients: false',
        '$enable-transitions' => '$enable-transitions: true',
        '$enable-reduced-motion' => '$enable-reduced-motion: true',
        '$enable-smooth-scroll' => '$enable-smooth-scroll: true',
        '$enable-grid-classes' => '$enable-grid-classes: true',
        '$enable-button-pointers' => '$enable-button-pointers: true',
        '$enable-rfs' => '$enable-rfs: true',
        '$enable-validation-icons' => '$enable-validation-icons: true',
        '$enable-negative-margins' => '$enable-negative-margins: false',
        '$enable-deprecation-messages' => '$enable-deprecation-messages: true',
        '$enable-important-utilities' => '$enable-important-utilities: true',
    ],

    /*
    |--------------------------------------------------------------------------
    | Commandes de compilation
    |--------------------------------------------------------------------------
    |
    | Commandes NPM à exécuter après la migration pour compiler les assets.
    |
    */
    'build_commands' => [
        'dev' => ['npm', 'run', 'dev'],
        'build' => ['npm', 'run', 'build'],
        'production' => ['npm', 'run', 'production'],
        'watch' => ['npm', 'run', 'watch'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des sauvegardes
    |--------------------------------------------------------------------------
    |
    | Paramètres pour les sauvegardes automatiques avant migration.
    |
    */
    'backup' => [
        'enabled' => true,
        'path' => storage_path('app/bootstrap-migration-backups'),
        'keep_days' => 30,
        'compress' => true, // Compresser les sauvegardes en ZIP
    ],

    /*
    |--------------------------------------------------------------------------
    | Gestion de jQuery
    |--------------------------------------------------------------------------
    |
    | Bootstrap 5 ne dépend plus de jQuery. Configurez ici comment gérer
    | cette transition dans votre projet.
    |
    */
    'jquery' => [
        'remove_automatically' => false, // Supprimer jQuery automatiquement
        'show_warnings' => true, // Afficher des avertissements sur l'usage de jQuery
        'detect_usage' => true, // Détecter l'usage de jQuery dans le code
    ],

    /*
    |--------------------------------------------------------------------------
    | Analyse et validation
    |--------------------------------------------------------------------------
    |
    | Options pour l'analyse et la validation de la migration.
    |
    */
    'analysis' => [
        'detailed_reports' => true,
        'include_file_locations' => true,
        'severity_levels' => ['low', 'medium', 'high'],
        'export_formats' => ['json', 'html', 'csv'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cas spéciaux à détecter
    |--------------------------------------------------------------------------
    |
    | Patterns et situations spécifiques nécessitant une attention manuelle.
    |
    */
    'special_cases' => [
        'negative_margins' => [
            'pattern' => '/\b[mp][tblrxy]?-n[0-5]\b/',
            'severity' => 'medium',
            'description' => 'Classes à marges/padding négatives non incluses dans le CDN Bootstrap 5',
        ],
        'print_styles' => [
            'pattern' => '/@media\s+print\s*{|\.d-print-/',
            'severity' => 'low',
            'description' => 'Styles d\'impression (non inclus dans Bootstrap 5)',
        ],
        'jquery_bootstrap_plugins' => [
            'pattern' => '/\$\([^)]+\)\.(modal|dropdown|tooltip|popover|collapse|carousel|tab)\s*\(/',
            'severity' => 'high',
            'description' => 'Utilisation de plugins Bootstrap via jQuery',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclusions
    |--------------------------------------------------------------------------
    |
    | Fichiers et répertoires à ignorer lors de la migration.
    |
    */
    'exclude' => [
        'directories' => [
            'node_modules',
            'vendor',
            '.git',
            'storage/framework',
            'bootstrap/cache',
        ],
        'files' => [
            '*.min.js',
            '*.min.css',
            'package-lock.json',
            'composer.lock',
        ],
        'patterns' => [
            '/\/\*.*?\*\//', // Commentaires CSS
            '/<!--.*?-->/', // Commentaires HTML
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation et scoring
    |--------------------------------------------------------------------------
    |
    | Configuration du système de scoring et validation.
    |
    */
    'scoring' => [
        'weights' => [
            'critical_issues' => -15,
            'warnings' => -5,
            'deprecated_classes' => -10,
            'cdn_links' => -5,
            'special_cases' => -2,
        ],
        'thresholds' => [
            'excellent' => 90,
            'good' => 70,
            'needs_attention' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rapports
    |--------------------------------------------------------------------------
    |
    | Configuration pour la génération de rapports.
    |
    */
    'reports' => [
        'default_format' => 'html',
        'include_screenshots' => false,
        'templates' => [
            'html' => 'bootstrap5-migrator::report',
            'comparison' => 'bootstrap5-migrator::comparison',
            'performance' => 'bootstrap5-migrator::performance',
        ],
        'export_path' => storage_path('app/bootstrap-migration-reports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Développement et débogage
    |--------------------------------------------------------------------------
    |
    | Options utiles pendant le développement et le débogage.
    |
    */
    'debug' => [
        'verbose_output' => false,
        'log_all_changes' => false,
        'preserve_formatting' => true,
        'backup_before_each_step' => false,
    ],
];
