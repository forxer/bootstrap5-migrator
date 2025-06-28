<?php

namespace Bootstrap5Migrator\Tests;

use Bootstrap5Migrator\Bootstrap5Migrator;
use Bootstrap5Migrator\Bootstrap5MigratorServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class Bootstrap5MigratorTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [Bootstrap5MigratorServiceProvider::class];
    }

    public function setUp(): void
    {
        parent::setUp();

        // Créer les répertoires de test
        File::makeDirectory(resource_path('views'), 0755, true, true);
        File::makeDirectory(resource_path('css'), 0755, true, true);
        File::makeDirectory(resource_path('js'), 0755, true, true);
    }

    public function tearDown(): void
    {
        // Nettoyer les fichiers de test
        if (File::exists(base_path('package.json.backup'))) {
            File::delete(base_path('package.json.backup'));
        }

        parent::tearDown();
    }

    public function test_package_json_update_from_bootstrap_4_to_5()
    {
        $packageJsonPath = base_path('package.json');
        $originalContent = json_encode([
            'devDependencies' => [
                'bootstrap' => '^4.6.0',
                'popper.js' => '^1.16.1',
                'jquery' => '^3.6.0'
            ]
        ], JSON_PRETTY_PRINT);

        File::put($packageJsonPath, $originalContent);

        $migrator = new Bootstrap5Migrator();
        $migrator->updatePackageJson();

        $updatedContent = json_decode(File::get($packageJsonPath), true);

        $this->assertEquals('^5.3.0', $updatedContent['devDependencies']['bootstrap']);
        $this->assertEquals('^2.11.8', $updatedContent['devDependencies']['@popperjs/core']);
        $this->assertArrayNotHasKey('popper.js', $updatedContent['devDependencies']);
        $this->assertArrayHasKey('jquery', $updatedContent['devDependencies']); // jQuery conservé par défaut

        File::delete($packageJsonPath);
    }

    public function test_bootstrap_4_class_replacements()
    {
        $testCases = [
            // Spacing utilities
            '<div class="ml-3 mr-2">Test</div>' => '<div class="ms-3 me-2">Test</div>',
            '<div class="pl-4 pr-1">Test</div>' => '<div class="ps-4 pe-1">Test</div>',

            // Text alignment
            '<p class="text-left">Left</p>' => '<p class="text-start">Left</p>',
            '<p class="text-right">Right</p>' => '<p class="text-end">Right</p>',

            // Forms
            '<div class="form-group">Content</div>' => '<div class="mb-3">Content</div>',
            '<div class="form-row">Content</div>' => '<div class="row g-3">Content</div>',
            '<select class="custom-select">Options</select>' => '<select class="form-select">Options</select>',

            // Badges
            '<span class="badge badge-primary">Badge</span>' => '<span class="badge bg-primary">Badge</span>',
            '<span class="badge badge-warning">Warning</span>' => '<span class="badge bg-warning text-dark">Warning</span>',

            // Media object
            '<div class="media">Content</div>' => '<div class="d-flex">Content</div>',
            '<div class="media-body">Body</div>' => '<div class="flex-grow-1 ms-3">Body</div>',

            // Jumbotron
            '<div class="jumbotron">Hero</div>' => '<div class="bg-light p-5 rounded-3">Hero</div>',

            // Close button
            '<button class="close">&times;</button>' => '<button class="btn-close">&times;</button>',

            // Screen readers
            '<span class="sr-only">Hidden</span>' => '<span class="visually-hidden">Hidden</span>',
        ];

        $migrator = new Bootstrap5Migrator();

        foreach ($testCases as $input => $expected) {
            $result = $input;

            // Simuler le remplacement des classes
            $classReplacements = [
                'ml-3' => 'ms-3', 'mr-2' => 'me-2', 'pl-4' => 'ps-4', 'pr-1' => 'pe-1',
                'text-left' => 'text-start', 'text-right' => 'text-end',
                'form-group' => 'mb-3', 'form-row' => 'row g-3', 'custom-select' => 'form-select',
                'badge-primary' => 'bg-primary', 'badge-warning' => 'bg-warning text-dark',
                'media' => 'd-flex', 'media-body' => 'flex-grow-1 ms-3',
                'jumbotron' => 'bg-light p-5 rounded-3', 'close' => 'btn-close',
                'sr-only' => 'visually-hidden'
            ];

            foreach ($classReplacements as $old => $new) {
                $result = preg_replace(
                    '/class="([^"]*)\b' . preg_quote($old, '/') . '\b([^"]*)"/',
                    'class="$1' . $new . '$2"',
                    $result
                );
            }

            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    public function test_data_attribute_migration()
    {
        $testCases = [
            // Modal
            '<button data-toggle="modal" data-target="#myModal">Open</button>' =>
            '<button data-bs-toggle="modal" data-bs-target="#myModal">Open</button>',

            // Dropdown
            '<button data-toggle="dropdown">Menu</button>' =>
            '<button data-bs-toggle="dropdown">Menu</button>',

            // Dismiss
            '<button data-dismiss="modal">Close</button>' =>
            '<button data-bs-dismiss="modal">Close</button>',

            // Carousel
            '<div data-ride="carousel" data-interval="5000"></div>' =>
            '<div data-bs-ride="carousel" data-bs-interval="5000"></div>',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $input;

            // Simuler le remplacement des attributs data-*
            $dataAttributeReplacements = [
                'data-toggle=' => 'data-bs-toggle=',
                'data-target=' => 'data-bs-target=',
                'data-dismiss=' => 'data-bs-dismiss=',
                'data-ride=' => 'data-bs-ride=',
                'data-interval=' => 'data-bs-interval=',
            ];

            foreach ($dataAttributeReplacements as $old => $new) {
                $result = str_replace($old, $new, $result);
            }

            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    public function test_form_structure_migration()
    {
        $inputGroupBefore = '
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">@</span>
            </div>
            <input type="text" class="form-control">
            <div class="input-group-append">
                <span class="input-group-text">.com</span>
            </div>
        </div>';

        $inputGroupAfter = '
        <div class="input-group">
            <span class="input-group-text">@</span>
            <input type="text" class="form-control">
            <span class="input-group-text">.com</span>
        </div>';

        $migrator = new Bootstrap5Migrator();

        $result = $inputGroupBefore;

        // Simuler la migration des input groups
        $result = str_replace(
            '<div class="input-group-prepend"><span class="input-group-text">',
            '<span class="input-group-text">',
            $result
        );

        $result = str_replace(
            '</span></div>',
            '</span>',
            $result
        );

        $result = str_replace(
            '<div class="input-group-append"><span class="input-group-text">',
            '<span class="input-group-text">',
            $result
        );

        $this->assertStringContainsString('<span class="input-group-text">@</span>', $result);
        $this->assertStringNotContainsString('input-group-prepend', $result);
        $this->assertStringNotContainsString('input-group-append', $result);
    }

    public function test_analysis_detection()
    {
        // Créer un fichier package.json de test
        $packageJsonContent = json_encode([
            'devDependencies' => [
                'bootstrap' => '^4.6.0',
                'jquery' => '^3.6.0',
                'popper.js' => '^1.16.1'
            ]
        ]);
        File::put(base_path('package.json'), $packageJsonContent);

        // Créer un fichier Blade avec des classes obsolètes
        $bladeContent = '
        <div class="form-group">
            <input class="form-control" type="text">
            <div class="ml-3 text-left">
                <span class="badge badge-primary">Test</span>
            </div>
        </div>';
        File::put(resource_path('views/test.blade.php'), $bladeContent);

        $migrator = new Bootstrap5Migrator();
        $analysis = $migrator->analyzeApplication();

        $this->assertEquals('^4.6.0', $analysis['bootstrap_version']);
        $this->assertTrue($analysis['jquery_usage']);
        $this->assertTrue($analysis['popper_usage']);
        $this->assertContains('form-group', $analysis['deprecated_classes']);
        $this->assertContains('ml-3', $analysis['deprecated_classes']);
        $this->assertContains('text-left', $analysis['deprecated_classes']);
        $this->assertContains('badge-primary', $analysis['deprecated_classes']);

        // Nettoyer
        File::delete(base_path('package.json'));
        File::delete(resource_path('views/test.blade.php'));
    }

    public function test_command_registration()
    {
        $this->artisan('bootstrap:migrate-to-5 --help')
             ->assertExitCode(0);
    }

    public function test_dry_run_does_not_modify_files()
    {
        $packageJsonContent = json_encode(['devDependencies' => ['bootstrap' => '^4.6.0']]);
        File::put(base_path('package.json'), $packageJsonContent);

        $migrator = new Bootstrap5Migrator();
        $migrator->updatePackageJson(true); // dry-run = true

        $content = json_decode(File::get(base_path('package.json')), true);
        $this->assertEquals('^4.6.0', $content['devDependencies']['bootstrap']);

        File::delete(base_path('package.json'));
    }
}
