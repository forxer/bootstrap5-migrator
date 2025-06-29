<?php

namespace Bootstrap5Migrator\Tests\Unit;

use Bootstrap5Migrator\Analyzers\DeprecatedClassAnalyzer;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class DeprecatedClassAnalyzerTest extends TestCase
{
    private DeprecatedClassAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new DeprecatedClassAnalyzer();
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /** @test */
    public function it_detects_margin_left_classes()
    {
        File::put(resource_path('views/margins.blade.php'),
            '<div class="ml-2 ml-auto">Margins</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('ml-2', $results['classes']);
        $this->assertArrayHasKey('ml-auto', $results['classes']);
        $this->assertEquals('ms-2', $results['classes']['ml-2']['replacement']);
        $this->assertEquals('ms-auto', $results['classes']['ml-auto']['replacement']);
        $this->assertEquals('low', $results['classes']['ml-2']['severity']);
    }

    /** @test */
    public function it_detects_margin_right_classes()
    {
        File::put(resource_path('views/margins.blade.php'),
            '<div class="mr-1 mr-3 mr-5">Margins</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('mr-1', $results['classes']);
        $this->assertArrayHasKey('mr-3', $results['classes']);
        $this->assertArrayHasKey('mr-5', $results['classes']);
        $this->assertEquals('me-1', $results['classes']['mr-1']['replacement']);
        $this->assertEquals('me-3', $results['classes']['mr-3']['replacement']);
        $this->assertEquals('me-5', $results['classes']['mr-5']['replacement']);
    }

    /** @test */
    public function it_detects_padding_classes()
    {
        File::put(resource_path('views/padding.blade.php'),
            '<div class="pl-0 pr-4">Padding</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('pl-0', $results['classes']);
        $this->assertArrayHasKey('pr-4', $results['classes']);
        $this->assertEquals('ps-0', $results['classes']['pl-0']['replacement']);
        $this->assertEquals('pe-4', $results['classes']['pr-4']['replacement']);
    }

    /** @test */
    public function it_detects_text_alignment_classes()
    {
        File::put(resource_path('views/text.blade.php'),
            '<div class="text-left text-right">Text alignment</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('text-left', $results['classes']);
        $this->assertArrayHasKey('text-right', $results['classes']);
        $this->assertEquals('text-start', $results['classes']['text-left']['replacement']);
        $this->assertEquals('text-end', $results['classes']['text-right']['replacement']);
        $this->assertEquals('low', $results['classes']['text-left']['severity']);
    }

    /** @test */
    public function it_detects_form_classes()
    {
        File::put(resource_path('views/forms.blade.php'),
            '<div class="form-group"><select class="custom-select"></select></div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('form-group', $results['classes']);
        $this->assertArrayHasKey('custom-select', $results['classes']);
        $this->assertEquals('mb-3', $results['classes']['form-group']['replacement']);
        $this->assertEquals('form-select', $results['classes']['custom-select']['replacement']);
        $this->assertEquals('medium', $results['classes']['form-group']['severity']);
    }

    /** @test */
    public function it_detects_badge_classes()
    {
        File::put(resource_path('views/badges.blade.php'),
            '<span class="badge badge-primary badge-warning badge-dark">Badges</span>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('badge-primary', $results['classes']);
        $this->assertArrayHasKey('badge-warning', $results['classes']);
        $this->assertArrayHasKey('badge-dark', $results['classes']);
        $this->assertEquals('bg-primary', $results['classes']['badge-primary']['replacement']);
        $this->assertEquals('bg-warning text-dark', $results['classes']['badge-warning']['replacement']);
        $this->assertEquals('bg-dark', $results['classes']['badge-dark']['replacement']);
    }

    /** @test */
    public function it_detects_removed_components()
    {
        File::put(resource_path('views/removed.blade.php'),
            '<div class="jumbotron media"><div class="media-object"></div></div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('jumbotron', $results['classes']);
        $this->assertArrayHasKey('media', $results['classes']);
        $this->assertArrayHasKey('media-object', $results['classes']);
        $this->assertEquals('bg-light p-5 rounded-3', $results['classes']['jumbotron']['replacement']);
        $this->assertEquals('d-flex', $results['classes']['media']['replacement']);
        $this->assertEquals('flex-shrink-0', $results['classes']['media-object']['replacement']);
        $this->assertEquals('high', $results['classes']['jumbotron']['severity']);
    }

    /** @test */
    public function it_detects_screen_reader_classes()
    {
        File::put(resource_path('views/sr.blade.php'),
            '<span class="sr-only sr-only-focusable">Screen reader</span>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('sr-only', $results['classes']);
        $this->assertArrayHasKey('sr-only-focusable', $results['classes']);
        $this->assertEquals('visually-hidden', $results['classes']['sr-only']['replacement']);
        $this->assertEquals('visually-hidden-focusable', $results['classes']['sr-only-focusable']['replacement']);
    }

    /** @test */
    public function it_detects_close_button_class()
    {
        File::put(resource_path('views/close.blade.php'),
            '<button class="close">×</button>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('close', $results['classes']);
        $this->assertEquals('btn-close', $results['classes']['close']['replacement']);
        $this->assertEquals('medium', $results['classes']['close']['severity']);
    }

    /** @test */
    public function it_counts_class_occurrences()
    {
        File::put(resource_path('views/count.blade.php'),
            '<div class="ml-2">First</div><div class="ml-2">Second</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('ml-2', $results['classes']);
        $this->assertEquals(2, $results['classes']['ml-2']['count']);
    }

    /** @test */
    public function it_tracks_files_containing_classes()
    {
        File::put(resource_path('views/file1.blade.php'), '<div class="ml-2">File 1</div>');
        File::put(resource_path('views/file2.blade.php'), '<div class="ml-2">File 2</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('ml-2', $results['classes']);
        $this->assertCount(2, $results['classes']['ml-2']['files']);
        $this->assertContains(resource_path('views/file1.blade.php'), $results['classes']['ml-2']['files']);
        $this->assertContains(resource_path('views/file2.blade.php'), $results['classes']['ml-2']['files']);
    }

    /** @test */
    public function it_provides_detailed_locations_when_requested()
    {
        File::put(resource_path('views/detailed.blade.php'),
            "Line 1\n<div class=\"ml-2\">Line 2</div>\nLine 3");

        $results = $this->analyzer->findDeprecatedClasses(true);

        $this->assertArrayHasKey('ml-2', $results['classes']);
        $this->assertArrayHasKey('locations', $results['classes']['ml-2']);
        $this->assertNotEmpty($results['classes']['ml-2']['locations']);

        $location = $results['classes']['ml-2']['locations'][0];
        $this->assertArrayHasKey('line', $location);
        $this->assertArrayHasKey('content', $location);
        $this->assertEquals(2, $location['line']);
        $this->assertStringContains('ml-2', $location['content']);
    }

    /** @test */
    public function it_detects_classes_in_css_files()
    {
        File::put(resource_path('css/app.css'),
            '.my-component .ml-2 { color: red; }');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('ml-2', $results['classes']);
    }

    /** @test */
    public function it_detects_classes_in_scss_files()
    {
        File::put(resource_path('sass/app.scss'),
            '.navbar { .text-left { font-weight: bold; } }');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('text-left', $results['classes']);
    }

    /** @test */
    public function it_provides_correct_summary_statistics()
    {
        File::put(resource_path('views/summary.blade.php'),
            '<div class="jumbotron form-group ml-2">Mixed severities</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('summary', $results);
        $this->assertEquals(3, $results['summary']['total_deprecated_classes']);
        $this->assertEquals(1, $results['summary']['high_severity']);    // jumbotron
        $this->assertEquals(1, $results['summary']['medium_severity']);  // form-group
        $this->assertEquals(1, $results['summary']['low_severity']);     // ml-2
    }

    /** @test */
    public function it_ignores_partial_matches()
    {
        File::put(resource_path('views/partial.blade.php'),
            '<div class="my-ml-2-custom some-text-left-thing">Partial matches</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertEmpty($results['classes']);
    }

    /** @test */
    public function it_handles_single_and_double_quotes()
    {
        File::put(resource_path('views/quotes.blade.php'),
            '<div class="ml-2" data-class=\'text-left\'>Quotes</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('ml-2', $results['classes']);
        $this->assertArrayHasKey('text-left', $results['classes']);
    }

    /** @test */
    public function it_detects_all_margin_and_padding_variants()
    {
        $classes = [];

        for ($i = 0; $i <= 5; $i++) {
            $classes[] = "ml-{$i}";
            $classes[] = "mr-{$i}";
            $classes[] = "pl-{$i}";
            $classes[] = "pr-{$i}";
        }
        $classes[] = 'ml-auto';
        $classes[] = 'mr-auto';

        $html = '<div class="'.implode(' ', $classes).'">All variants</div>';
        File::put(resource_path('views/variants.blade.php'), $html);

        $results = $this->analyzer->findDeprecatedClasses();

        foreach ($classes as $class) {
            $this->assertArrayHasKey($class, $results['classes'], "Class {$class} should be detected");
        }
    }

    /** @test */
    public function it_handles_multiline_class_attributes()
    {
        File::put(resource_path('views/multiline.blade.php'),
            '<div class="
                ml-2
                text-left
                form-group
            ">Multiline</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertArrayHasKey('ml-2', $results['classes']);
        $this->assertArrayHasKey('text-left', $results['classes']);
        $this->assertArrayHasKey('form-group', $results['classes']);
    }

    /** @test */
    public function it_handles_files_without_deprecated_classes()
    {
        File::put(resource_path('views/clean.blade.php'),
            '<div class="ms-2 text-start bg-primary">Clean Bootstrap 5</div>');

        $results = $this->analyzer->findDeprecatedClasses();

        $this->assertEmpty($results['classes']);
        $this->assertEquals(0, $results['summary']['total_deprecated_classes']);
    }

    private function createTestFiles(): void
    {
        $directories = [
            resource_path('views'),
            resource_path('css'),
            resource_path('sass'),
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = [
            resource_path('views/margins.blade.php'),
            resource_path('views/padding.blade.php'),
            resource_path('views/text.blade.php'),
            resource_path('views/forms.blade.php'),
            resource_path('views/badges.blade.php'),
            resource_path('views/removed.blade.php'),
            resource_path('views/sr.blade.php'),
            resource_path('views/close.blade.php'),
            resource_path('views/count.blade.php'),
            resource_path('views/file1.blade.php'),
            resource_path('views/file2.blade.php'),
            resource_path('views/detailed.blade.php'),
            resource_path('views/summary.blade.php'),
            resource_path('views/partial.blade.php'),
            resource_path('views/quotes.blade.php'),
            resource_path('views/variants.blade.php'),
            resource_path('views/multiline.blade.php'),
            resource_path('views/clean.blade.php'),
            resource_path('css/app.css'),
            resource_path('sass/app.scss'),
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}
