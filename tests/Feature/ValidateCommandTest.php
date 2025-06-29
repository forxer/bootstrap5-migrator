<?php

namespace Bootstrap5Migrator\Tests\Feature;

use Bootstrap5Migrator\ServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class ValidateCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestEnvironment();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /** @test */
    public function it_validates_migration_successfully()
    {
        // Créer un projet déjà migré (Bootstrap 5)
        $this->createMigratedProject();

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Score de migration')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_detects_remaining_bootstrap_4_issues()
    {
        // Créer un projet avec des problèmes Bootstrap 4
        $this->createProblematicProject();

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Problèmes détectés')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_auto_fix_simple_issues()
    {
        // Créer des problèmes auto-corrigeables
        $this->createFixableIssues();

        $this->artisan('bootstrap:validate', ['--fix'])
            ->expectsOutputToContain('Correction automatique')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_fails_in_strict_mode_with_critical_issues()
    {
        // Créer des problèmes critiques
        $this->createCriticalIssues();

        $this->artisan('bootstrap:validate', ['--strict'])
            ->expectsOutputToContain('Problèmes critiques détectés')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_validates_npm_dependencies()
    {
        // package.json avec Bootstrap 4
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0',
                'popper.js' => '^1.16.1'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('NPM Dependencies')
            ->expectsOutputToContain('Bootstrap 4 encore présent')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_validates_css_classes()
    {
        // Créer des fichiers avec des classes obsolètes
        File::put(resource_path('views/test.blade.php'), '<div class="ml-2 text-left sr-only">Test</div>');

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Classes CSS')
            ->expectsOutputToContain('Classes obsolètes détectées')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_validates_data_attributes()
    {
        // Créer des fichiers avec des attributs obsolètes
        File::put(resource_path('views/modal.blade.php'), 
            '<button data-toggle="modal" data-target="#modal">Open</button>');

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Attributs data-*')
            ->expectsOutputToContain('Attributs obsolètes')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_validates_cdn_links()
    {
        // Créer un fichier avec des liens CDN Bootstrap 4
        File::put(public_path('index.html'), 
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">');

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Liens CDN')
            ->expectsOutputToContain('Liens CDN obsolètes')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_validates_javascript_usage()
    {
        // Créer un fichier JS avec usage jQuery Bootstrap
        File::put(resource_path('js/app.js'), '$(".modal").modal("show");');

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('JavaScript')
            ->expectsOutputToContain('jQuery Bootstrap')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_calculates_migration_score()
    {
        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Score:')
            ->expectsOutputToContain('/100')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_color_coded_score()
    {
        // Test avec un bon score (projet migré)
        $this->createMigratedProject();

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('✅')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_categorizes_issues_by_severity()
    {
        $this->createProblematicProject();

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Problèmes critiques')
            ->expectsOutputToContain('Avertissements')
            ->expectsOutputToContain('Recommandations')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_provides_fix_suggestions()
    {
        $this->createFixableIssues();

        $this->artisan('bootstrap:validate')
            ->expectsOutputToContain('Corrections possibles')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_auto_fixes_bootstrap_version_in_package_json()
    {
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->artisan('bootstrap:validate', ['--fix'])
            ->expectsOutputToContain('Bootstrap version corrigée')
            ->assertExitCode(0);

        // Vérifier que la version a été mise à jour
        $updated = json_decode(File::get(base_path('package.json')), true);
        $this->assertStringContains('5.', $updated['dependencies']['bootstrap']);
    }

    /** @test */
    public function it_auto_fixes_deprecated_classes()
    {
        File::put(resource_path('views/test.blade.php'), '<div class="ml-2">Test</div>');

        $this->artisan('bootstrap:validate', ['--fix'])
            ->expectsOutputToContain('Classes corrigées')
            ->assertExitCode(0);

        // Vérifier que la classe a été mise à jour
        $content = File::get(resource_path('views/test.blade.php'));
        $this->assertStringContains('ms-2', $content);
        $this->assertStringNotContains('ml-2', $content);
    }

    /** @test */
    public function it_auto_fixes_data_attributes()
    {
        File::put(resource_path('views/modal.blade.php'), 
            '<button data-toggle="modal">Open</button>');

        $this->artisan('bootstrap:validate', ['--fix'])
            ->expectsOutputToContain('Attributs corrigés')
            ->assertExitCode(0);

        // Vérifier que l'attribut a été mis à jour
        $content = File::get(resource_path('views/modal.blade.php'));
        $this->assertStringContains('data-bs-toggle', $content);
        $this->assertStringNotContains('data-toggle=', $content);
    }

    /** @test */
    public function it_tracks_fix_progress()
    {
        $this->createFixableIssues();

        $this->artisan('bootstrap:validate', ['--fix'])
            ->expectsOutputToContain('corrections appliquées')
            ->assertExitCode(0);
    }

    private function createTestEnvironment(): void
    {
        $directories = [
            resource_path('views'),
            resource_path('css'),
            resource_path('js'),
            public_path(),
            storage_path('app')
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }

    private function createMigratedProject(): void
    {
        // package.json avec Bootstrap 5
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^5.3.2',
                '@popperjs/core' => '^2.11.8'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        // Fichier sans classes obsolètes
        File::put(resource_path('views/clean.blade.php'), '<div class="ms-2 text-start">Clean</div>');
    }

    private function createProblematicProject(): void
    {
        // package.json avec Bootstrap 4
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0',
                'popper.js' => '^1.16.1'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        // Fichiers avec problèmes
        File::put(resource_path('views/problems.blade.php'), 
            '<div class="ml-2 text-left" data-toggle="modal">Problems</div>');
    }

    private function createFixableIssues(): void
    {
        // Problèmes auto-corrigeables
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        File::put(resource_path('views/fixable.blade.php'), '<div class="ml-2" data-toggle="modal">Fixable</div>');
    }

    private function createCriticalIssues(): void
    {
        // Problèmes critiques non auto-corrigeables
        File::put(resource_path('views/critical.blade.php'), '<div class="jumbotron">Critical</div>');
        File::put(resource_path('js/critical.js'), '$(".modal").modal("show");');
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = [
            base_path('package.json'),
            resource_path('views/test.blade.php'),
            resource_path('views/clean.blade.php'),
            resource_path('views/problems.blade.php'),
            resource_path('views/modal.blade.php'),
            resource_path('views/fixable.blade.php'),
            resource_path('views/critical.blade.php'),
            resource_path('js/app.js'),
            resource_path('js/critical.js'),
            public_path('index.html')
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}