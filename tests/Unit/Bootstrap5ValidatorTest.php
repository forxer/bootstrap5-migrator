<?php

namespace Bootstrap5Migrator\Tests\Unit;

use Bootstrap5Migrator\Validators\Bootstrap5Validator;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class Bootstrap5ValidatorTest extends TestCase
{
    private Bootstrap5Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Bootstrap5Validator();
        $this->createTestEnvironment();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /** @test */
    public function it_validates_npm_dependencies_successfully()
    {
        $this->createBootstrap5PackageJson();

        $results = $this->validator->validateMigration();

        $this->assertEquals('✅ Bootstrap 5 détecté', $results['npm_status']);
        $this->assertStringContains('5.3', $results['npm_details']);
        $this->assertFalse($results['has_critical_issues']);
    }

    /** @test */
    public function it_detects_bootstrap_4_in_dependencies()
    {
        $this->createBootstrap4PackageJson();

        $results = $this->validator->validateMigration();

        $this->assertTrue($results['has_critical_issues']);
        $this->assertNotEmpty($results['critical_issues']);
        $this->assertStringContains('Bootstrap 4', $results['critical_issues'][0]['description']);
    }

    /** @test */
    public function it_detects_missing_package_json()
    {
        // No package.json file

        $results = $this->validator->validateMigration();

        $this->assertEquals('⚠️ package.json non trouvé', $results['npm_status']);
        $this->assertNotEmpty($results['warnings']);
    }

    /** @test */
    public function it_detects_old_popper_dependency()
    {
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^5.3.2',
                'popper.js' => '^1.16.1',
            ],
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $results = $this->validator->validateMigration();

        $oldPopperIssue = collect($results['fixable_issues'])->firstWhere('type', 'old_popper');
        $this->assertNotNull($oldPopperIssue);
        $this->assertEquals('replace_popper', $oldPopperIssue['action']);
    }

    /** @test */
    public function it_validates_css_classes_successfully()
    {
        File::put(resource_path('views/clean.blade.php'), '<div class="ms-2 text-start">Clean</div>');

        $results = $this->validator->validateMigration();

        $this->assertEquals('✅ OK', $results['css_status']);
        $this->assertStringContains('Aucune classe obsolète', $results['css_details']);
    }

    /** @test */
    public function it_detects_deprecated_css_classes()
    {
        File::put(resource_path('views/deprecated.blade.php'), '<div class="ml-2 text-left sr-only">Deprecated</div>');

        $results = $this->validator->validateMigration();

        $this->assertEquals('❌ Classes obsolètes détectées', $results['css_status']);
        $this->assertStringContains('3 classes obsolètes', $results['css_details']);
        $this->assertTrue($results['has_critical_issues']);

        $criticalIssues = collect($results['critical_issues'])->where('description', 'like', '%ml-2%');
        $this->assertNotEmpty($criticalIssues);
    }

    /** @test */
    public function it_detects_margin_padding_variants()
    {
        File::put(resource_path('views/margins.blade.php'), '<div class="ml-0 mr-3 pl-5 pr-auto">Margins</div>');

        $results = $this->validator->validateMigration();

        $this->assertEquals('❌ Classes obsolètes détectées', $results['css_status']);
        $this->assertStringContains('4 classes obsolètes', $results['css_details']);
    }

    /** @test */
    public function it_validates_data_attributes_successfully()
    {
        File::put(resource_path('views/clean-attrs.blade.php'),
            '<button data-bs-toggle="modal" data-bs-target="#modal">Clean</button>');

        $results = $this->validator->validateMigration();

        $this->assertEquals('✅ OK', $results['data_attributes_status']);
        $this->assertStringContains('Attributs à jour', $results['data_attributes_details']);
    }

    /** @test */
    public function it_detects_old_data_attributes()
    {
        File::put(resource_path('views/old-attrs.blade.php'),
            '<button data-toggle="modal" data-target="#modal" data-dismiss="alert">Old</button>');

        $results = $this->validator->validateMigration();

        $this->assertEquals('❌ Attributs obsolètes', $results['data_attributes_status']);
        $this->assertStringContains('3 attributs', $results['data_attributes_details']);

        $fixableIssues = collect($results['fixable_issues'])->where('type', 'data_attribute');
        $this->assertCount(3, $fixableIssues);
    }

    /** @test */
    public function it_validates_cdn_links_successfully()
    {
        File::put(public_path('clean-cdn.html'),
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">');

        $results = $this->validator->validateMigration();

        $this->assertEquals('✅ OK', $results['cdn_status']);
        $this->assertStringContains('Aucun lien CDN obsolète', $results['cdn_details']);
    }

    /** @test */
    public function it_detects_old_cdn_links()
    {
        File::put(public_path('old-cdn.html'),
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">');

        $results = $this->validator->validateMigration();

        $this->assertEquals('❌ Liens CDN obsolètes', $results['cdn_status']);
        $this->assertStringContains('1 liens', $results['cdn_details']);

        $fixableIssues = collect($results['fixable_issues'])->where('type', 'cdn_link');
        $this->assertNotEmpty($fixableIssues);
    }

    /** @test */
    public function it_validates_javascript_successfully()
    {
        File::put(resource_path('js/clean.js'),
            'const modal = new bootstrap.Modal(document.getElementById("modal"));');

        $results = $this->validator->validateMigration();

        $this->assertEquals('✅ OK', $results['js_status']);
        $this->assertStringContains('JavaScript à jour', $results['js_details']);
    }

    /** @test */
    public function it_detects_jquery_bootstrap_usage()
    {
        File::put(resource_path('js/jquery.js'),
            '$(".modal").modal("show"); $(".dropdown").dropdown("toggle");');

        $results = $this->validator->validateMigration();

        $this->assertEquals('⚠️ JavaScript nécessite attention', $results['js_status']);
        $this->assertStringContains('1 fichiers avec du code jQuery', $results['js_details']);
        $this->assertNotEmpty($results['warnings']);
        $this->assertNotEmpty($results['recommendations']);
    }

    /** @test */
    public function it_calculates_migration_score_correctly()
    {
        // Perfect project
        $this->createBootstrap5PackageJson();
        File::put(resource_path('views/perfect.blade.php'), '<div class="ms-2 text-start">Perfect</div>');

        $results = $this->validator->validateMigration();

        $this->assertEquals(100, $results['score']);
    }

    /** @test */
    public function it_applies_score_penalties_correctly()
    {
        $this->createBootstrap4PackageJson(); // Critical issue: -15
        File::put(resource_path('views/deprecated.blade.php'), '<div class="ml-2">Deprecated</div>'); // Critical: -15
        File::put(resource_path('js/jquery.js'), '$(".modal").modal();'); // Warning: -5
        File::put(resource_path('views/fixable.blade.php'), '<div data-toggle="modal">Fixable</div>'); // Fixable: -2

        $results = $this->validator->validateMigration();

        // 100 - 15 - 15 - 5 - 2 = 63
        $this->assertEquals(63, $results['score']);
    }

    /** @test */
    public function it_can_fix_bootstrap_version()
    {
        $this->createBootstrap4PackageJson();

        $issue = [
            'action' => 'update_bootstrap_version',
            'file' => base_path('package.json'),
        ];

        $result = $this->validator->fixIssue($issue);

        $this->assertTrue($result);

        $packageJson = json_decode(File::get(base_path('package.json')), true);
        $this->assertStringContains('5.3.2', $packageJson['dependencies']['bootstrap']);
    }

    /** @test */
    public function it_can_fix_popper_dependency()
    {
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^5.3.2',
                'popper.js' => '^1.16.1',
            ],
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $issue = [
            'action' => 'replace_popper',
            'file' => base_path('package.json'),
        ];

        $result = $this->validator->fixIssue($issue);

        $this->assertTrue($result);

        $updated = json_decode(File::get(base_path('package.json')), true);
        $this->assertArrayNotHasKey('popper.js', $updated['dependencies']);
        $this->assertArrayHasKey('@popperjs/core', $updated['devDependencies']);
    }

    /** @test */
    public function it_can_fix_deprecated_classes()
    {
        File::put(resource_path('views/fixable.blade.php'), '<div class="ml-2 text-left">Fixable</div>');

        $mlIssue = [
            'action' => 'replace_class',
            'file' => resource_path('views/fixable.blade.php'),
            'class' => 'ml-2',
        ];

        $textIssue = [
            'action' => 'replace_class',
            'file' => resource_path('views/fixable.blade.php'),
            'class' => 'text-left',
        ];

        $this->assertTrue($this->validator->fixIssue($mlIssue));
        $this->assertTrue($this->validator->fixIssue($textIssue));

        $content = File::get(resource_path('views/fixable.blade.php'));
        $this->assertStringContains('ms-2', $content);
        $this->assertStringContains('text-start', $content);
        $this->assertStringNotContains('ml-2', $content);
        $this->assertStringNotContains('text-left', $content);
    }

    /** @test */
    public function it_can_fix_data_attributes()
    {
        File::put(resource_path('views/data-fix.blade.php'),
            '<button data-toggle="modal" data-target="#modal">Fix</button>');

        $toggleIssue = [
            'action' => 'update_data_attribute',
            'file' => resource_path('views/data-fix.blade.php'),
            'attribute' => 'data-toggle',
        ];

        $targetIssue = [
            'action' => 'update_data_attribute',
            'file' => resource_path('views/data-fix.blade.php'),
            'attribute' => 'data-target',
        ];

        $this->assertTrue($this->validator->fixIssue($toggleIssue));
        $this->assertTrue($this->validator->fixIssue($targetIssue));

        $content = File::get(resource_path('views/data-fix.blade.php'));
        $this->assertStringContains('data-bs-toggle', $content);
        $this->assertStringContains('data-bs-target', $content);
        $this->assertStringNotContains('data-toggle=', $content);
        $this->assertStringNotContains('data-target=', $content);
    }

    /** @test */
    public function it_can_fix_cdn_links()
    {
        File::put(public_path('cdn-fix.html'),
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">');

        $issue = [
            'action' => 'update_cdn_link',
            'file' => public_path('cdn-fix.html'),
            'old_link' => 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css',
        ];

        $result = $this->validator->fixIssue($issue);

        $this->assertTrue($result);

        $content = File::get(public_path('cdn-fix.html'));
        $this->assertStringContains('5.3.2', $content);
        $this->assertStringNotContains('4.6.0', $content);
    }

    /** @test */
    public function it_handles_fix_failures_gracefully()
    {
        $invalidIssue = [
            'action' => 'invalid_action',
            'file' => 'nonexistent.file',
        ];

        $result = $this->validator->fixIssue($invalidIssue);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_fixes_margin_padding_variants_correctly()
    {
        File::put(resource_path('views/variants.blade.php'),
            '<div class="ml-3 mr-auto pl-1 pr-5">Variants</div>');

        $issues = [
            ['action' => 'replace_class', 'file' => resource_path('views/variants.blade.php'), 'class' => 'ml-3'],
            ['action' => 'replace_class', 'file' => resource_path('views/variants.blade.php'), 'class' => 'mr-auto'],
            ['action' => 'replace_class', 'file' => resource_path('views/variants.blade.php'), 'class' => 'pl-1'],
            ['action' => 'replace_class', 'file' => resource_path('views/variants.blade.php'), 'class' => 'pr-5'],
        ];

        foreach ($issues as $issue) {
            $this->assertTrue($this->validator->fixIssue($issue));
        }

        $content = File::get(resource_path('views/variants.blade.php'));
        $this->assertStringContains('ms-3', $content);
        $this->assertStringContains('me-auto', $content);
        $this->assertStringContains('ps-1', $content);
        $this->assertStringContains('pe-5', $content);
    }

    /** @test */
    public function it_provides_complete_validation_structure()
    {
        $results = $this->validator->validateMigration();

        $this->assertArrayHasKey('score', $results);
        $this->assertArrayHasKey('has_critical_issues', $results);
        $this->assertArrayHasKey('critical_issues', $results);
        $this->assertArrayHasKey('warnings', $results);
        $this->assertArrayHasKey('recommendations', $results);
        $this->assertArrayHasKey('fixable_issues', $results);
        $this->assertArrayHasKey('npm_status', $results);
        $this->assertArrayHasKey('css_status', $results);
        $this->assertArrayHasKey('data_attributes_status', $results);
        $this->assertArrayHasKey('cdn_status', $results);
        $this->assertArrayHasKey('js_status', $results);

        $this->assertIsInt($results['score']);
        $this->assertIsBool($results['has_critical_issues']);
        $this->assertIsArray($results['critical_issues']);
        $this->assertIsArray($results['warnings']);
        $this->assertIsArray($results['recommendations']);
        $this->assertIsArray($results['fixable_issues']);
    }

    private function createTestEnvironment(): void
    {
        $directories = [
            resource_path('views'),
            resource_path('css'),
            resource_path('js'),
            public_path(),
            storage_path('app'),
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }

    private function createBootstrap5PackageJson(): void
    {
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^5.3.2',
                '@popperjs/core' => '^2.11.8',
            ],
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));
    }

    private function createBootstrap4PackageJson(): void
    {
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0',
                'popper.js' => '^1.16.1',
            ],
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = [
            base_path('package.json'),
            resource_path('views/clean.blade.php'),
            resource_path('views/deprecated.blade.php'),
            resource_path('views/margins.blade.php'),
            resource_path('views/clean-attrs.blade.php'),
            resource_path('views/old-attrs.blade.php'),
            resource_path('views/perfect.blade.php'),
            resource_path('views/fixable.blade.php'),
            resource_path('views/data-fix.blade.php'),
            resource_path('views/variants.blade.php'),
            resource_path('js/clean.js'),
            resource_path('js/jquery.js'),
            public_path('clean-cdn.html'),
            public_path('old-cdn.html'),
            public_path('cdn-fix.html'),
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}
