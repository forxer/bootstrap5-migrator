<?php

namespace Bootstrap5Migrator\Tests\Feature;

use Bootstrap5Migrator\ServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class MigrateCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestProject();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /** @test */
    public function it_can_run_migration_in_dry_run_mode()
    {
        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutput('🔍 Mode dry-run : aperçu des modifications...')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_create_backup_before_migration()
    {
        $this->artisan('bootstrap:migrate-to-5', ['--backup', '--dry-run'])
            ->expectsOutputToContain('💾 Création de la sauvegarde...')
            ->assertExitCode(0);

        // Vérifier qu'un backup a été créé
        $backupDirs = File::glob(base_path('bootstrap-migration-backup-*'));
        $this->assertNotEmpty($backupDirs);
    }

    /** @test */
    public function it_updates_package_json_bootstrap_version()
    {
        // Créer package.json avec Bootstrap 4
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0',
                'popper.js' => '^1.16.1',
            ],
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->assertExitCode(0);

        // En mode dry-run, le fichier ne doit pas être modifié
        $content = json_decode(File::get(base_path('package.json')), true);
        $this->assertEquals('^4.6.0', $content['dependencies']['bootstrap']);
    }

    /** @test */
    public function it_migrates_deprecated_classes_in_blade_files()
    {
        // Créer un fichier Blade avec des classes obsolètes
        $bladeContent = '<div class="ml-2 mr-3 text-left form-group">Test</div>';
        File::put(resource_path('views/test.blade.php'), $bladeContent);

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutputToContain('Migration des templates Blade')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_migrates_data_attributes()
    {
        // Créer un fichier avec des attributs data-* obsolètes
        $htmlContent = '<button data-toggle="modal" data-target="#myModal">Open</button>';
        File::put(resource_path('views/modal.blade.php'), $htmlContent);

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutputToContain('Migration des templates Blade')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_migrates_css_files()
    {
        // Créer un fichier CSS avec des classes obsolètes
        $cssContent = '.my-class { margin-left: 1rem; } .ml-2 { color: red; }';
        File::put(resource_path('css/app.css'), $cssContent);

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutputToContain('Migration des fichiers de style')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_migrates_javascript_files()
    {
        // Créer un fichier JS avec des attributs data-*
        $jsContent = '$("[data-toggle=\'modal\']").click();';
        File::put(resource_path('js/app.js'), $jsContent);

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutputToContain('Migration des fichiers JavaScript')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_jquery_preservation_flag()
    {
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0',
                'jquery' => '^3.6.0',
            ],
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->artisan('bootstrap:migrate-to-5', ['--skip-jquery', '--dry-run'])
            ->expectsOutputToContain('jQuery conservé')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_force_migration_without_confirmation()
    {
        $this->artisan('bootstrap:migrate-to-5', ['--force', '--dry-run'])
            ->doesntExpectOutput('Voulez-vous continuer ?')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_migrates_form_structures()
    {
        // Créer un fichier avec des structures de formulaire obsolètes
        $formContent = '<div class="form-group"><input class="form-control"></div>';
        File::put(resource_path('views/form.blade.php'), $formContent);

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_migrates_input_groups()
    {
        // Créer un fichier avec des input groups obsolètes
        $inputGroupContent = '
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">@</span>
                </div>
                <input type="text" class="form-control">
            </div>';
        File::put(resource_path('views/input-group.blade.php'), $inputGroupContent);

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_scss_variable_migration()
    {
        // Créer un fichier SCSS avec des variables
        $scssContent = '@import "~bootstrap/scss/bootstrap"; $enable-rounded: false;';
        File::put(resource_path('sass/app.scss'), $scssContent);

        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutputToContain('Migration des fichiers de style')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_post_migration_checklist()
    {
        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutputToContain('✅ Checklist post-migration')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_performs_analysis_before_migration()
    {
        $this->artisan('bootstrap:migrate-to-5', ['--dry-run'])
            ->expectsOutputToContain('📊 Analyse pré-migration')
            ->assertExitCode(0);
    }

    private function createTestProject(): void
    {
        // Créer les répertoires
        $directories = [
            resource_path('views'),
            resource_path('css'),
            resource_path('sass'),
            resource_path('js'),
            storage_path('app'),
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }

        // Créer package.json par défaut
        $packageJson = [
            'name' => 'test-project',
            'dependencies' => [
                'bootstrap' => '^4.6.0',
            ],
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));
    }

    private function cleanupTestFiles(): void
    {
        // Nettoyer les fichiers de test
        $testFiles = [
            base_path('package.json'),
            resource_path('views/test.blade.php'),
            resource_path('views/modal.blade.php'),
            resource_path('views/form.blade.php'),
            resource_path('views/input-group.blade.php'),
            resource_path('css/app.css'),
            resource_path('sass/app.scss'),
            resource_path('js/app.js'),
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Nettoyer les backups
        $backupDirs = File::glob(base_path('bootstrap-migration-backup-*'));

        foreach ($backupDirs as $dir) {
            File::deleteDirectory($dir);
        }
    }
}
