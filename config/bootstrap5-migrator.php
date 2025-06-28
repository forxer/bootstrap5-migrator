<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Répertoires à analyser
    |--------------------------------------------------------------------------
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
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensions de fichiers à traiter
    |--------------------------------------------------------------------------
    */
    'file_extensions' => [
        'css' => ['css', 'scss', 'sass'],
        'js' => ['js', 'ts'],
        'views' => ['php', 'blade.php'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Versions des packages NPM pour Bootstrap 5
    |--------------------------------------------------------------------------
    */
    'npm_packages' => [
        'bootstrap' => '^5.3.0',
        '@popperjs/core' => '^2.11.8',
    ],

    /*
    |--------------------------------------------------------------------------
    | Packages à supprimer (Bootstrap 4)
    |--------------------------------------------------------------------------
    */
    'packages_to_remove' => [
        'popper.js', // Remplacé par @popperjs/core dans Bootstrap 5
    ],

    /*
    |--------------------------------------------------------------------------
    | Mappings des classes CSS Bootstrap 4 → Bootstrap 5
    |--------------------------------------------------------------------------
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Attributs data-* à migrer
    |--------------------------------------------------------------------------
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Variables SCSS Bootstrap 5
    |--------------------------------------------------------------------------
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
    */
    'build_commands' => [
        'dev' => ['npm', 'run', 'dev'],
        'build' => ['npm', 'run', 'build'],
        'production' => ['npm', 'run', 'production'],
        'watch' => ['npm', 'run', 'watch'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sauvegarde automatique
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => true,
        'path' => base_path('backups'),
        'keep_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | jQuery - Gestion optionnelle
    |--------------------------------------------------------------------------
    */
    'jquery' => [
        'remove_automatically' => false, // Si true, supprime jQuery du package.json
        'show_warnings' => true, // Affiche des avertissements sur l'usage de jQuery
    ],
];
