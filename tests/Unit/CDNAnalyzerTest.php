<?php

namespace Bootstrap5Migrator\Tests\Unit;

use Bootstrap5Migrator\Analyzers\CDNAnalyzer;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class CDNAnalyzerTest extends TestCase
{
    private CDNAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new CDNAnalyzer();
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /** @test */
    public function it_detects_jsdelivr_cdn_links()
    {
        $htmlContent = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        File::put(resource_path('views/jsdelivr.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertNotEmpty($results);
        $this->assertEquals('jsdelivr.net', $results[0]['provider']);
        $this->assertEquals('4.6.0', $results[0]['current_version']);
        $this->assertTrue($results[0]['is_bootstrap_4']);
        $this->assertStringContains('5.3.2', $results[0]['suggested_v5_link']);
    }

    /** @test */
    public function it_detects_stackpath_cdn_links()
    {
        $htmlContent = '<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">';
        File::put(resource_path('views/stackpath.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertNotEmpty($results);
        $this->assertEquals('stackpath.bootstrapcdn.com', $results[0]['provider']);
        $this->assertEquals('4.6.1', $results[0]['current_version']);
        $this->assertTrue($results[0]['is_bootstrap_4']);
    }

    /** @test */
    public function it_detects_cloudflare_cdn_links()
    {
        $htmlContent = '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>';
        File::put(public_path('cloudflare.html'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertNotEmpty($results);
        $this->assertEquals('cdnjs.cloudflare.com', $results[0]['provider']);
        $this->assertEquals('4.6.0', $results[0]['current_version']);
    }

    /** @test */
    public function it_detects_unpkg_cdn_links()
    {
        $htmlContent = '<link rel="stylesheet" href="https://unpkg.com/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
        File::put(resource_path('views/unpkg.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertNotEmpty($results);
        $this->assertEquals('unpkg.com', $results[0]['provider']);
        $this->assertEquals('4.6.2', $results[0]['current_version']);
    }

    /** @test */
    public function it_ignores_bootstrap_5_cdn_links()
    {
        $htmlContent = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
        File::put(resource_path('views/bootstrap5.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_detects_multiple_cdn_links_in_same_file()
    {
        $htmlContent = '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
        ';
        File::put(resource_path('views/multiple.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertCount(2, $results);
        $this->assertEquals('4.6.0', $results[0]['current_version']);
        $this->assertEquals('4.6.0', $results[1]['current_version']);
    }

    /** @test */
    public function it_detects_cdn_links_across_multiple_files()
    {
        File::put(resource_path('views/file1.blade.php'), 
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        
        File::put(public_path('file2.html'), 
            '<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">');

        $results = $this->analyzer->findCDNLinks();

        $this->assertCount(2, $results);
        $this->assertEquals('jsdelivr.net', $results[0]['provider']);
        $this->assertEquals('stackpath.bootstrapcdn.com', $results[1]['provider']);
    }

    /** @test */
    public function it_provides_correct_upgrade_suggestions()
    {
        $htmlContent = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        File::put(resource_path('views/upgrade.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertNotEmpty($results);
        $suggestedLink = $results[0]['suggested_v5_link'];
        
        $this->assertStringContains('5.3.2', $suggestedLink);
        $this->assertStringContains('cdn.jsdelivr.net', $suggestedLink);
        $this->assertStringNotContains('4.6.0', $suggestedLink);
    }

    /** @test */
    public function it_handles_different_file_extensions()
    {
        $extensions = [
            'test.blade.php' => resource_path('views/test.blade.php'),
            'test.php' => resource_path('views/test.php'),
            'test.html' => public_path('test.html'),
            'test.htm' => public_path('test.htm')
        ];

        $htmlContent = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">';

        foreach ($extensions as $filename => $path) {
            File::put($path, $htmlContent);
        }

        $results = $this->analyzer->findCDNLinks();

        $this->assertCount(4, $results);
    }

    /** @test */
    public function it_returns_correct_data_structure()
    {
        $htmlContent = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        File::put(resource_path('views/structure.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertNotEmpty($results);
        
        $result = $results[0];
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('provider', $result);
        $this->assertArrayHasKey('current_link', $result);
        $this->assertArrayHasKey('current_version', $result);
        $this->assertArrayHasKey('suggested_v5_link', $result);
        $this->assertArrayHasKey('is_bootstrap_4', $result);
        
        $this->assertIsString($result['file']);
        $this->assertIsString($result['provider']);
        $this->assertIsString($result['current_link']);
        $this->assertIsString($result['current_version']);
        $this->assertIsString($result['suggested_v5_link']);
        $this->assertIsBool($result['is_bootstrap_4']);
    }

    /** @test */
    public function it_handles_files_without_cdn_links()
    {
        File::put(resource_path('views/no-cdn.blade.php'), '<div>No CDN links here</div>');

        $results = $this->analyzer->findCDNLinks();

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_handles_malformed_html()
    {
        $malformedHtml = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"'; // missing closing >
        File::put(resource_path('views/malformed.blade.php'), $malformedHtml);

        $results = $this->analyzer->findCDNLinks();

        // Should still detect the link despite malformed HTML
        $this->assertNotEmpty($results);
    }

    /** @test */
    public function it_ignores_non_bootstrap_cdn_links()
    {
        $htmlContent = '
            <link href="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        ';
        File::put(resource_path('views/other-libs.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_detects_bootstrap_css_and_js_separately()
    {
        $htmlContent = '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
        ';
        File::put(resource_path('views/css-and-js.blade.php'), $htmlContent);

        $results = $this->analyzer->findCDNLinks();

        $this->assertCount(2, $results);
        
        $cssLink = collect($results)->firstWhere('current_link', 'like', '%css%');
        $jsLink = collect($results)->firstWhere('current_link', 'like', '%js%');
        
        $this->assertNotNull($cssLink);
        $this->assertNotNull($jsLink);
    }

    /** @test */
    public function it_handles_empty_directories()
    {
        // Create empty directories
        File::makeDirectory(resource_path('views/empty'), 0755, true, true);
        File::makeDirectory(public_path('empty'), 0755, true, true);

        $results = $this->analyzer->findCDNLinks();

        $this->assertEmpty($results);
    }

    private function createTestFiles(): void
    {
        $directories = [
            resource_path('views'),
            public_path()
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = [
            resource_path('views/jsdelivr.blade.php'),
            resource_path('views/stackpath.blade.php'),
            resource_path('views/unpkg.blade.php'),
            resource_path('views/bootstrap5.blade.php'),
            resource_path('views/multiple.blade.php'),
            resource_path('views/file1.blade.php'),
            resource_path('views/upgrade.blade.php'),
            resource_path('views/test.blade.php'),
            resource_path('views/test.php'),
            resource_path('views/structure.blade.php'),
            resource_path('views/no-cdn.blade.php'),
            resource_path('views/malformed.blade.php'),
            resource_path('views/other-libs.blade.php'),
            resource_path('views/css-and-js.blade.php'),
            public_path('cloudflare.html'),
            public_path('file2.html'),
            public_path('test.html'),
            public_path('test.htm')
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up empty directories
        $emptyDirs = [
            resource_path('views/empty'),
            public_path('empty')
        ];

        foreach ($emptyDirs as $dir) {
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }
}