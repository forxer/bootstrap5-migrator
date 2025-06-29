<?php

namespace Bootstrap5Migrator\Tests\Feature;

use Bootstrap5Migrator\ServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class AnalyzeCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Créer la structure de test
        $this->createTestStructure();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /** @test */
    public function it_can_analyze_bootstrap_4_project()
    {
        $this->artisan('bootstrap:analyze')
            ->expectsOutput('🔍 Analyse de votre application Bootstrap...')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_export_analysis_to_json()
    {
        $exportPath = storage_path('test-analysis.json');

        $this->artisan('bootstrap:analyze', [
            '--export' => $exportPath,
            '--format' => 'json'
        ])->assertExitCode(0);

        $this->assertFileExists($exportPath);
        
        $data = json_decode(File::get($exportPath), true);
        $this->assertArrayHasKey('analysis', $data);
        $this->assertArrayHasKey('deprecated_classes', $data['analysis']);
        
        File::delete($exportPath);
    }

    /** @test */
    public function it_detects_deprecated_classes()
    {
        // Créer un fichier avec des classes obsolètes
        $viewFile = resource_path('views/test.blade.php');
        File::put($viewFile, '<div class="ml-2 text-left badge-primary">Test</div>');

        $this->artisan('bootstrap:analyze', ['--detailed'])
            ->expectsOutput('Classes obsolètes détectées')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_analyze_with_detailed_mode()
    {
        $this->artisan('bootstrap:analyze', ['--detailed'])
            ->expectsOutput('🔍 Analyse de votre application Bootstrap...')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_supports_multiple_output_formats()
    {
        $formats = ['table', 'json', 'html'];

        foreach ($formats as $format) {
            $this->artisan('bootstrap:analyze', ['--format' => $format])
                ->assertExitCode(0);
        }
    }

    /** @test */
    public function it_detects_bootstrap_version_from_package_json()
    {
        // Créer package.json avec Bootstrap 4
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->artisan('bootstrap:analyze')
            ->expectsOutputToContain('Version Bootstrap détectée')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_detects_jquery_usage()
    {
        // Créer package.json avec jQuery
        $packageJson = [
            'dependencies' => [
                'bootstrap' => '^4.6.0',
                'jquery' => '^3.6.0'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));

        $this->artisan('bootstrap:analyze')
            ->expectsOutputToContain('jQuery détecté')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_finds_cdn_links()
    {
        // Créer un fichier HTML avec lien CDN Bootstrap 4
        $htmlFile = public_path('test.html');
        File::put($htmlFile, '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">');

        $this->artisan('bootstrap:analyze')
            ->expectsOutputToContain('Liens CDN détectés')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_identifies_special_cases()
    {
        // Créer un fichier JS avec jQuery Bootstrap
        $jsFile = resource_path('js/test.js');
        File::put($jsFile, '$(".modal").modal("show");');

        $this->artisan('bootstrap:analyze')
            ->expectsOutputToContain('Cas spéciaux détectés')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_exports_to_different_formats()
    {
        $exportPath = storage_path('test-export');
        
        // Test JSON export
        $this->artisan('bootstrap:analyze', [
            '--export' => $exportPath . '.json',
            '--format' => 'json'
        ])->assertExitCode(0);
        
        $this->assertFileExists($exportPath . '.json');
        
        // Test HTML export
        $this->artisan('bootstrap:analyze', [
            '--export' => $exportPath . '.html', 
            '--format' => 'html'
        ])->assertExitCode(0);
        
        $this->assertFileExists($exportPath . '.html');

        // Cleanup
        File::delete([$exportPath . '.json', $exportPath . '.html']);
    }

    private function createTestStructure(): void
    {
        // Créer les répertoires de test
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

        // Créer un package.json basique
        $packageJson = [
            'name' => 'test-app',
            'dependencies' => [
                'bootstrap' => '^4.6.0'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = [
            base_path('package.json'),
            resource_path('views/test.blade.php'),
            resource_path('js/test.js'),
            public_path('test.html')
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}