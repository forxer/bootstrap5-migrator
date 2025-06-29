<?php

namespace Bootstrap5Migrator\Tests\Feature;

use Bootstrap5Migrator\ServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class ReportCommandTest extends TestCase
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
    public function it_generates_html_report_by_default()
    {
        $this->artisan('bootstrap:report')
            ->expectsOutputToContain('Génération du rapport HTML')
            ->assertExitCode(0);

        $defaultPath = storage_path('app/bootstrap-migration-reports/migration-report.html');
        $this->assertFileExists($defaultPath);
    }

    /** @test */
    public function it_generates_html_report_with_custom_output()
    {
        $customPath = storage_path('custom-report.html');

        $this->artisan('bootstrap:report', [
            '--format' => 'html',
            '--output' => $customPath
        ])->assertExitCode(0);

        $this->assertFileExists($customPath);
        
        // Vérifier que c'est du HTML valide
        $content = File::get($customPath);
        $this->assertStringContains('<html', $content);
        $this->assertStringContains('Bootstrap 5 Migration', $content);
    }

    /** @test */
    public function it_generates_markdown_report()
    {
        $markdownPath = storage_path('report.md');

        $this->artisan('bootstrap:report', [
            '--format' => 'markdown',
            '--output' => $markdownPath
        ])->assertExitCode(0);

        $this->assertFileExists($markdownPath);
        
        // Vérifier que c'est du Markdown valide
        $content = File::get($markdownPath);
        $this->assertStringContains('# 📊 Rapport de Migration Bootstrap 5', $content);
        $this->assertStringContains('## 🎯 Score de Migration', $content);
    }

    /** @test */
    public function it_generates_pdf_report_with_fallback()
    {
        $pdfPath = storage_path('report.pdf');

        $this->artisan('bootstrap:report', [
            '--format' => 'pdf',
            '--output' => $pdfPath
        ])->expectsOutputToContain('PDF')
            ->assertExitCode(0);

        // En l'absence de DomPDF, doit fallback vers HTML
        $htmlPath = str_replace('.pdf', '.html', $pdfPath);
        $this->assertFileExists($htmlPath);
    }

    /** @test */
    public function it_includes_all_analysis_data_in_report()
    {
        // Créer des données d'analyse
        $this->createAnalysisData();

        $reportPath = storage_path('full-report.html');

        $this->artisan('bootstrap:report', [
            '--format' => 'html',
            '--output' => $reportPath
        ])->assertExitCode(0);

        $content = File::get($reportPath);
        
        // Vérifier que toutes les sections sont présentes
        $this->assertStringContains('Score de Migration', $content);
        $this->assertStringContains('Classes obsolètes', $content);
        $this->assertStringContains('Liens CDN', $content);
        $this->assertStringContains('Cas spéciaux', $content);
    }

    /** @test */
    public function it_generates_comparison_report()
    {
        // Créer des données avant/après migration
        $beforeData = ['validation' => ['score' => 45]];
        $beforePath = storage_path('before.json');
        File::put($beforePath, json_encode($beforeData));

        $this->artisan('bootstrap:report', [
            '--comparison',
            '--before-data' => $beforePath
        ])->expectsOutputToContain('Rapport de comparaison')
          ->assertExitCode(0);
    }

    /** @test */
    public function it_generates_performance_report()
    {
        $this->artisan('bootstrap:report', [
            '--performance'
        ])->expectsOutputToContain('Rapport de performance')
          ->assertExitCode(0);

        $performancePath = storage_path('app/bootstrap-migration-reports/performance-report.html');
        $this->assertFileExists($performancePath);
    }

    /** @test */
    public function it_generates_monitoring_script()
    {
        $this->artisan('bootstrap:report', [
            '--monitoring-script'
        ])->expectsOutputToContain('Script de surveillance')
          ->assertExitCode(0);

        $scriptPath = base_path('monitor-bootstrap.sh');
        $this->assertFileExists($scriptPath);
        
        // Vérifier que c'est un script bash exécutable
        $content = File::get($scriptPath);
        $this->assertStringStartsWith('#!/bin/bash', $content);
        $this->assertStringContains('grep -r -n', $content);
    }

    /** @test */
    public function it_warns_about_missing_screenshots_dependency()
    {
        $this->artisan('bootstrap:report', [
            '--include-screenshots'
        ])->expectsOutputToContain('Puppeteer')
          ->expectsOutputToContain('npm install')
          ->assertExitCode(0);
    }

    /** @test */
    public function it_creates_output_directory_if_missing()
    {
        $deepPath = storage_path('deep/nested/path/report.html');
        
        $this->artisan('bootstrap:report', [
            '--output' => $deepPath
        ])->assertExitCode(0);

        $this->assertFileExists($deepPath);
        $this->assertDirectoryExists(dirname($deepPath));
    }

    /** @test */
    public function it_includes_meta_information_in_reports()
    {
        $reportPath = storage_path('meta-report.html');

        $this->artisan('bootstrap:report', [
            '--output' => $reportPath
        ])->assertExitCode(0);

        $content = File::get($reportPath);
        
        // Vérifier les méta-informations
        $this->assertStringContains('Laravel', $content);
        $this->assertStringContains('PHP', $content);
        $this->assertStringContains(date('Y'), $content);
    }

    /** @test */
    public function it_includes_migration_checklist_in_reports()
    {
        $reportPath = storage_path('checklist-report.html');

        $this->artisan('bootstrap:report', [
            '--output' => $reportPath
        ])->assertExitCode(0);

        $content = File::get($reportPath);
        $this->assertStringContains('Checklist', $content);
        $this->assertStringContains('package.json', $content);
        $this->assertStringContains('Classes CSS', $content);
    }

    /** @test */
    public function it_includes_recommendations_in_reports()
    {
        $reportPath = storage_path('recommendations-report.html');

        $this->artisan('bootstrap:report', [
            '--output' => $reportPath
        ])->assertExitCode(0);

        $content = File::get($reportPath);
        $this->assertStringContains('Recommandations', $content);
        $this->assertStringContains('Test', $content);
    }

    /** @test */
    public function it_handles_large_projects_gracefully()
    {
        // Créer un grand nombre de fichiers
        for ($i = 1; $i <= 100; $i++) {
            File::put(resource_path("views/file{$i}.blade.php"), '<div class="ml-2">Content</div>');
        }

        $this->artisan('bootstrap:report')
            ->expectsOutputToContain('Rapport généré')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_progress_during_report_generation()
    {
        $this->artisan('bootstrap:report')
            ->expectsOutputToContain('🔍')
            ->expectsOutputToContain('📊')
            ->expectsOutputToContain('✅')
            ->assertExitCode(0);
    }

    /** @test */
    public function markdown_report_contains_proper_structure()
    {
        $markdownPath = storage_path('structured-report.md');

        $this->artisan('bootstrap:report', [
            '--format' => 'markdown',
            '--output' => $markdownPath
        ])->assertExitCode(0);

        $content = File::get($markdownPath);
        
        // Vérifier la structure Markdown
        $this->assertStringContains('# 📊 Rapport de Migration Bootstrap 5', $content);
        $this->assertStringContains('## 🎯 Score de Migration', $content);
        $this->assertStringContains('## 📈 Statistiques', $content);
        $this->assertStringContains('## ✅ Checklist de Migration', $content);
        $this->assertStringContains('## 💡 Recommandations', $content);
        $this->assertStringContains('| Métrique | Valeur | Statut |', $content);
    }

    private function createTestEnvironment(): void
    {
        $directories = [
            resource_path('views'),
            resource_path('css'),
            resource_path('js'),
            storage_path('app'),
            storage_path('app/bootstrap-migration-reports')
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }

        // Créer un package.json basique
        $packageJson = [
            'name' => 'test-project',
            'dependencies' => [
                'bootstrap' => '^4.6.0'
            ]
        ];
        File::put(base_path('package.json'), json_encode($packageJson, JSON_PRETTY_PRINT));
    }

    private function createAnalysisData(): void
    {
        // Créer des fichiers avec différents types de problèmes
        File::put(resource_path('views/deprecated.blade.php'), 
            '<div class="ml-2 text-left badge-primary">Deprecated classes</div>');
        
        File::put(resource_path('views/data-attrs.blade.php'), 
            '<button data-toggle="modal" data-target="#modal">Old attributes</button>');
        
        File::put(public_path('cdn.html'), 
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        
        File::put(resource_path('js/jquery.js'), 
            '$(".modal").modal("show");');
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = [
            base_path('package.json'),
            base_path('monitor-bootstrap.sh'),
            storage_path('custom-report.html'),
            storage_path('report.md'),
            storage_path('report.pdf'),
            storage_path('report.html'),
            storage_path('full-report.html'),
            storage_path('meta-report.html'),
            storage_path('checklist-report.html'),
            storage_path('recommendations-report.html'),
            storage_path('structured-report.md'),
            storage_path('before.json')
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Nettoyer les répertoires de test
        if (File::exists(storage_path('app/bootstrap-migration-reports'))) {
            File::deleteDirectory(storage_path('app/bootstrap-migration-reports'));
        }

        // Nettoyer les fichiers de test générés en masse
        $testViews = File::glob(resource_path('views/file*.blade.php'));
        foreach ($testViews as $file) {
            File::delete($file);
        }

        $testFiles = [
            resource_path('views/deprecated.blade.php'),
            resource_path('views/data-attrs.blade.php'),
            resource_path('js/jquery.js'),
            public_path('cdn.html')
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}