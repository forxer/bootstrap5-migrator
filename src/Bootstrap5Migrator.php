<?php

namespace Bootstrap5Migrator;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class Bootstrap5Migrator
{
    protected array $packageJsonChanges = [
        'bootstrap' => '^5.3.0',
        '@popperjs/core' => '^2.11.8'
    ];

    protected array $packagesToRemove = [
        'popper.js' // Remplacé par @popperjs/core
    ];

    // Mappings des classes Bootstrap 4 → Bootstrap 5
    protected array $classReplacements = [
        // Spacing utilities
        'ml-' => 'ms-',
        'mr-' => 'me-',
        'pl-' => 'ps-',
        'pr-' => 'pe-',

        // Text alignment
        'text-left' => 'text-start',
        'text-right' => 'text-end',

        // Float utilities
        'float-left' => 'float-start',
        'float-right' => 'float-end',

        // Border radius
        'rounded-left' => 'rounded-start',
        'rounded-right' => 'rounded-end',

        // Forms
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

        // Media object (supprimé)
        'media' => 'd-flex',
        'media-object' => 'flex-shrink-0',
        'media-body' => 'flex-grow-1 ms-3',

        // Jumbotron (supprimé)
        'jumbotron' => 'bg-light p-5 rounded-3',
        'jumbotron-fluid' => 'bg-light p-5',

        // Close button
        'close' => 'btn-close',

        // Input groups
        'input-group-prepend' => 'input-group-text',
        'input-group-append' => 'input-group-text',

        // Badges
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

        // Navbar
        'navbar-expand-*' => 'navbar-expand-*', // Pas de changement mais à vérifier

        // Screen readers
        'sr-only' => 'visually-hidden',
        'sr-only-focusable' => 'visually-hidden-focusable',
    ];

    // Variables SCSS qui ont changé
    protected array $scssVariableChanges = [
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
    ];

    public function analyzeApplication(): array
    {
        $analysis = [
            'bootstrap_version' => $this->detectBootstrapVersion(),
            'jquery_usage' => $this->detectJQueryUsage(),
            'popper_usage' => $this->detectPopperUsage(),
            'deprecated_classes' => $this->findDeprecatedClasses(),
            'js_components' => $this->findJSComponents(),
        ];

        return $analysis;
    }

    public function createBackup(): void
    {
        $backupDir = base_path('bootstrap-migration-backup-' . date('Y-m-d-H-i-s'));

        File::makeDirectory($backupDir, 0755, true);
        File::copyDirectory(resource_path(), $backupDir . '/resources');
        File::copy(base_path('package.json'), $backupDir . '/package.json');

        if (File::exists(base_path('webpack.mix.js'))) {
            File::copy(base_path('webpack.mix.js'), $backupDir . '/webpack.mix.js');
        }

        if (File::exists(base_path('vite.config.js'))) {
            File::copy(base_path('vite.config.js'), $backupDir . '/vite.config.js');
        }
    }

    public function updatePackageJson(bool $dryRun = false, bool $skipJquery = false): void
    {
        $packageJsonPath = base_path('package.json');

        if (!File::exists($packageJsonPath)) {
            throw new \Exception('Le fichier package.json n\'existe pas.');
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);

        // Mise à jour des packages Bootstrap
        foreach ($this->packageJsonChanges as $package => $version) {
            if (isset($packageJson['dependencies'][$package])) {
                $packageJson['dependencies'][$package] = $version;
            } elseif (isset($packageJson['devDependencies'][$package])) {
                $packageJson['devDependencies'][$package] = $version;
            } else {
                // Ajouter le package s'il n'existe pas
                $packageJson['devDependencies'][$package] = $version;
            }
        }

        // Suppression des packages obsolètes
        foreach ($this->packagesToRemove as $package) {
            unset($packageJson['dependencies'][$package]);
            unset($packageJson['devDependencies'][$package]);
        }

        // Gestion de jQuery (optionnelle)
        if (!$skipJquery) {
            // Maintenir jQuery mais avertir l'utilisateur
            if (isset($packageJson['dependencies']['jquery']) || isset($packageJson['devDependencies']['jquery'])) {
                // Garde jQuery mais on l'indique dans les logs
            }
        }

        if (!$dryRun) {
            File::put($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    public function installDependencies(): void
    {
        $process = new Process(['npm', 'install'], base_path());
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Échec de l\'installation des dépendances NPM: ' . $process->getErrorOutput());
        }
    }

    public function migrateStyleFiles(bool $dryRun = false): void
    {
        $styleFiles = $this->findStyleFiles();

        foreach ($styleFiles as $file) {
            $content = File::get($file);
            $originalContent = $content;

            // Mise à jour des imports Bootstrap
            $content = str_replace(
                '@import "~bootstrap/scss/bootstrap";',
                '@import "bootstrap/scss/bootstrap";',
                $content
            );

            $content = str_replace(
                '@import "bootstrap/dist/css/bootstrap.css";',
                '@import "bootstrap/scss/bootstrap";',
                $content
            );

            // Remplacement des variables SCSS
            foreach ($this->scssVariableChanges as $old => $new) {
                if (strpos($content, $old) !== false && strpos($content, $new) === false) {
                    $content = str_replace($old, $new, $content);
                }
            }

            // Remplacement des classes dans les fichiers SCSS
            foreach ($this->classReplacements as $old => $new) {
                $content = str_replace('.' . $old, '.' . $new, $content);
            }

            if ($content !== $originalContent && !$dryRun) {
                File::put($file, $content);
            }
        }
    }

    public function migrateJavaScriptFiles(bool $dryRun = false): void
    {
        $jsFiles = $this->findJavaScriptFiles();

        foreach ($jsFiles as $file) {
            $content = File::get($file);
            $originalContent = $content;

            // Mise à jour des imports Bootstrap
            $content = str_replace(
                "import 'bootstrap'",
                "import 'bootstrap'",
                $content
            );

            // Mise à jour des imports spécifiques
            $content = str_replace(
                "import { Modal, Tooltip, Popover } from 'bootstrap'",
                "import { Modal, Tooltip, Popover } from 'bootstrap'",
                $content
            );

            // Remplacement des sélecteurs d'attributs data-*
            $content = str_replace('data-toggle', 'data-bs-toggle', $content);
            $content = str_replace('data-target', 'data-bs-target', $content);
            $content = str_replace('data-dismiss', 'data-bs-dismiss', $content);
            $content = str_replace('data-slide', 'data-bs-slide', $content);
            $content = str_replace('data-slide-to', 'data-bs-slide-to', $content);

            // Mise à jour des événements Bootstrap
            $jsEventReplacements = [
                'show.bs.modal' => 'show.bs.modal',
                'shown.bs.modal' => 'shown.bs.modal',
                'hide.bs.modal' => 'hide.bs.modal',
                'hidden.bs.modal' => 'hidden.bs.modal',
                'show.bs.dropdown' => 'show.bs.dropdown',
                'shown.bs.dropdown' => 'shown.bs.dropdown',
                'hide.bs.dropdown' => 'hide.bs.dropdown',
                'hidden.bs.dropdown' => 'hidden.bs.dropdown',
            ];

            foreach ($jsEventReplacements as $old => $new) {
                $content = str_replace($old, $new, $content);
            }

            if ($content !== $originalContent && !$dryRun) {
                File::put($file, $content);
            }
        }
    }

    public function migrateBladeTemplates(bool $dryRun = false): void
    {
        $bladeFiles = $this->findBladeFiles();

        foreach ($bladeFiles as $file) {
            $content = File::get($file);
            $originalContent = $content;

            // Remplacement des attributs data-*
            $content = str_replace('data-toggle=', 'data-bs-toggle=', $content);
            $content = str_replace('data-target=', 'data-bs-target=', $content);
            $content = str_replace('data-dismiss=', 'data-bs-dismiss=', $content);
            $content = str_replace('data-slide=', 'data-bs-slide=', $content);
            $content = str_replace('data-slide-to=', 'data-bs-slide-to=', $content);

            // Remplacement des classes CSS avec gestion des variations numériques
            foreach ($this->classReplacements as $old => $new) {
                // Gestion des classes avec suffixes numériques (ml-1, ml-2, etc.)
                if (str_ends_with($old, '-')) {
                    for ($i = 0; $i <= 5; $i++) {
                        $content = $this->replaceClassInContent($content, $old . $i, $new . $i);
                    }
                    // Auto, n1, n2, etc.
                    $content = $this->replaceClassInContent($content, $old . 'auto', $new . 'auto');
                } else {
                    $content = $this->replaceClassInContent($content, $old, $new);
                }
            }

            // Cas spéciaux pour les formulaires
            $content = $this->migrateFormStructures($content);

            // Cas spéciaux pour les input groups
            $content = $this->migrateInputGroups($content);

            if ($content !== $originalContent && !$dryRun) {
                File::put($file, $content);
            }
        }
    }

    private function replaceClassInContent(string $content, string $oldClass, string $newClass): string
    {
        // Remplacement dans les attributs class="..."
        $content = preg_replace(
            '/class="([^"]*)\b' . preg_quote($oldClass, '/') . '\b([^"]*)"/',
            'class="$1' . $newClass . '$2"',
            $content
        );

        // Remplacement dans les attributs class='...'
        $content = preg_replace(
            "/class='([^']*)\b" . preg_quote($oldClass, '/') . "\b([^']*)'/",
            "class='$1" . $newClass . "$2'",
            $content
        );

        return $content;
    }

    private function migrateFormStructures(string $content): string
    {
        // Migration des form-group vers mb-3
        $content = preg_replace(
            '/<div class="([^"]*\s)?form-group(\s[^"]*)?">/',
            '<div class="$1mb-3$2">',
            $content
        );

        // Migration des form-row vers row g-3
        $content = preg_replace(
            '/<div class="([^"]*\s)?form-row(\s[^"]*)?">/',
            '<div class="$1row g-3$2">',
            $content
        );

        return $content;
    }

    private function migrateInputGroups(string $content): string
    {
        // Simplification des input-group-prepend et input-group-append
        $content = str_replace(
            '<div class="input-group-prepend"><span class="input-group-text">',
            '<span class="input-group-text">',
            $content
        );

        $content = str_replace(
            '</span></div>',
            '</span>',
            $content
        );

        $content = str_replace(
            '<div class="input-group-append"><span class="input-group-text">',
            '<span class="input-group-text">',
            $content
        );

        return $content;
    }

    public function compileAssets(): void
    {
        // Tentative avec npm run dev d'abord
        $process = new Process(['npm', 'run', 'dev'], base_path());
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            // Fallback vers npm run build si dev échoue
            $process = new Process(['npm', 'run', 'build'], base_path());
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception('Échec de la compilation des assets: ' . $process->getErrorOutput());
            }
        }
    }

    // Méthodes d'analyse

    private function detectBootstrapVersion(): string
    {
        $packageJsonPath = base_path('package.json');

        if (File::exists($packageJsonPath)) {
            $packageJson = json_decode(File::get($packageJsonPath), true);

            if (isset($packageJson['dependencies']['bootstrap'])) {
                return $packageJson['dependencies']['bootstrap'];
            }

            if (isset($packageJson['devDependencies']['bootstrap'])) {
                return $packageJson['devDependencies']['bootstrap'];
            }
        }

        return 'Non détecté';
    }

    private function detectJQueryUsage(): bool
    {
        $packageJsonPath = base_path('package.json');

        if (File::exists($packageJsonPath)) {
            $packageJson = json_decode(File::get($packageJsonPath), true);

            return isset($packageJson['dependencies']['jquery']) ||
                   isset($packageJson['devDependencies']['jquery']);
        }

        return false;
    }

    private function detectPopperUsage(): bool
    {
        $packageJsonPath = base_path('package.json');

        if (File::exists($packageJsonPath)) {
            $packageJson = json_decode(File::get($packageJsonPath), true);

            return isset($packageJson['dependencies']['popper.js']) ||
                   isset($packageJson['devDependencies']['popper.js']);
        }

        return false;
    }

    private function findDeprecatedClasses(): array
    {
        $deprecatedClasses = [];
        $bladeFiles = $this->findBladeFiles();

        foreach ($bladeFiles as $file) {
            $content = File::get($file);

            foreach (array_keys($this->classReplacements) as $oldClass) {
                if (strpos($content, $oldClass) !== false) {
                    $deprecatedClasses[] = $oldClass;
                }
            }
        }

        return array_unique($deprecatedClasses);
    }

    private function findJSComponents(): array
    {
        $components = [];
        $jsFiles = $this->findJavaScriptFiles();
        $bladeFiles = $this->findBladeFiles();

        $searchPatterns = [
            'data-toggle' => 'Attributs data-toggle (Bootstrap 4)',
            'data-target' => 'Attributs data-target (Bootstrap 4)',
            '.modal(' => 'Modals jQuery',
            '.dropdown(' => 'Dropdowns jQuery',
            '.tooltip(' => 'Tooltips jQuery',
        ];

        $allFiles = array_merge($jsFiles, $bladeFiles);

        foreach ($allFiles as $file) {
            $content = File::get($file);

            foreach ($searchPatterns as $pattern => $description) {
                if (strpos($content, $pattern) !== false) {
                    $components[] = $description;
                }
            }
        }

        return array_unique($components);
    }

    // Méthodes utilitaires pour trouver les fichiers

    protected function findStyleFiles(): array
    {
        $files = [];
        $directories = [
            resource_path('css'),
            resource_path('sass'),
            resource_path('scss'),
        ];

        foreach ($directories as $directory) {
            if (File::exists($directory)) {
                $files = array_merge($files, File::allFiles($directory));
            }
        }

        return array_filter(array_map(function ($file) {
            return $file->getPathname();
        }, $files), function ($file) {
            return in_array(pathinfo($file, PATHINFO_EXTENSION), ['css', 'scss', 'sass']);
        });
    }

    protected function findJavaScriptFiles(): array
    {
        $files = [];
        $directories = [
            resource_path('js'),
        ];

        foreach ($directories as $directory) {
            if (File::exists($directory)) {
                $files = array_merge($files, File::allFiles($directory));
            }
        }

        return array_filter(array_map(function ($file) {
            return $file->getPathname();
        }, $files), function ($file) {
            return in_array(pathinfo($file, PATHINFO_EXTENSION), ['js', 'ts']);
        });
    }

    protected function findBladeFiles(): array
    {
        $files = File::allFiles(resource_path('views'));

        return array_filter(array_map(function ($file) {
            return $file->getPathname();
        }, $files), function ($file) {
            return str_ends_with($file, '.blade.php') || pathinfo($file, PATHINFO_EXTENSION) === 'php';
        });
    }
}