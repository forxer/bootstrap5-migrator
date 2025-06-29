<?php

namespace Bootstrap5Migrator\Tests\Unit;

use Bootstrap5Migrator\Analyzers\SpecialCaseAnalyzer;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class SpecialCaseAnalyzerTest extends TestCase
{
    private SpecialCaseAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new SpecialCaseAnalyzer();
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /** @test */
    public function it_detects_jquery_bootstrap_modal_usage()
    {
        File::put(resource_path('js/modal.js'),
            '$(".modal").modal("show"); $("#myModal").modal("hide");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertEquals('high', $jqueryIssue['severity']);
        $this->assertEquals(2, $jqueryIssue['matches']);
        $this->assertStringContains('jQuery', $jqueryIssue['description']);
        $this->assertStringContains('vanilla', $jqueryIssue['solution']);
    }

    /** @test */
    public function it_detects_jquery_bootstrap_dropdown_usage()
    {
        File::put(resource_path('js/dropdown.js'),
            '$(".dropdown-toggle").dropdown(); $("[data-toggle=dropdown]").dropdown("toggle");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertEquals('high', $jqueryIssue['severity']);
        $this->assertEquals(2, $jqueryIssue['matches']);
    }

    /** @test */
    public function it_detects_jquery_bootstrap_tooltip_usage()
    {
        File::put(resource_path('js/tooltip.js'),
            '$("[data-toggle=tooltip]").tooltip(); $(".btn").tooltip("dispose");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertEquals(2, $jqueryIssue['matches']);
    }

    /** @test */
    public function it_detects_jquery_bootstrap_popover_usage()
    {
        File::put(resource_path('js/popover.js'),
            '$(".popover-trigger").popover(); $("#example").popover("enable");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertEquals(2, $jqueryIssue['matches']);
    }

    /** @test */
    public function it_detects_jquery_bootstrap_collapse_usage()
    {
        File::put(resource_path('js/collapse.js'),
            '$(".collapse").collapse("show"); $("#accordion").collapse("hide");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertEquals(2, $jqueryIssue['matches']);
    }

    /** @test */
    public function it_detects_jquery_bootstrap_carousel_usage()
    {
        File::put(resource_path('js/carousel.js'),
            '$(".carousel").carousel(); $("#myCarousel").carousel("next");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertEquals(2, $jqueryIssue['matches']);
    }

    /** @test */
    public function it_detects_jquery_bootstrap_tab_usage()
    {
        File::put(resource_path('js/tabs.js'),
            '$(".nav-tabs a").tab("show"); $("#myTab").tab("dispose");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertEquals(2, $jqueryIssue['matches']);
    }

    /** @test */
    public function it_detects_old_data_toggle_attributes()
    {
        File::put(resource_path('views/data-attrs.blade.php'),
            '<button data-toggle="modal" data-target="#modal">Open</button>');

        $results = $this->analyzer->findSpecialCases();

        $dataIssue = collect($results['issues'])->firstWhere('type', 'data_attributes_v4');

        $this->assertNotNull($dataIssue);
        $this->assertEquals('high', $dataIssue['severity']);
        $this->assertEquals(2, $dataIssue['matches']);
        $this->assertStringContains('data-bs-', $dataIssue['solution']);
    }

    /** @test */
    public function it_detects_all_old_data_attributes()
    {
        File::put(resource_path('views/all-data.blade.php'),
            '<div data-toggle="collapse" data-target="#collapse" data-dismiss="modal" data-slide="next" data-ride="carousel"></div>');

        $results = $this->analyzer->findSpecialCases();

        $dataIssue = collect($results['issues'])->firstWhere('type', 'data_attributes_v4');

        $this->assertNotNull($dataIssue);
        $this->assertEquals(5, $dataIssue['matches']);
    }

    /** @test */
    public function it_detects_negative_margin_classes()
    {
        File::put(resource_path('views/negative.blade.php'),
            '<div class="mt-n2 mb-n3 ml-n1">Negative margins</div>');

        $results = $this->analyzer->findSpecialCases();

        $negativeIssue = collect($results['issues'])->firstWhere('type', 'negative_margins');

        $this->assertNotNull($negativeIssue);
        $this->assertEquals('medium', $negativeIssue['severity']);
        $this->assertEquals(3, $negativeIssue['matches']);
        $this->assertStringContains('CDN', $negativeIssue['description']);
    }

    /** @test */
    public function it_detects_input_group_structures()
    {
        File::put(resource_path('views/input-group.blade.php'),
            '<div class="input-group-prepend"><span>Before</span></div>
             <div class="input-group-append"><span>After</span></div>');

        $results = $this->analyzer->findSpecialCases();

        $inputGroupIssue = collect($results['issues'])->firstWhere('type', 'input_group_structure');

        $this->assertNotNull($inputGroupIssue);
        $this->assertEquals('medium', $inputGroupIssue['severity']);
        $this->assertEquals(2, $inputGroupIssue['matches']);
        $this->assertStringContains('simplifi', $inputGroupIssue['solution']);
    }

    /** @test */
    public function it_detects_card_layout_classes()
    {
        File::put(resource_path('views/cards.blade.php'),
            '<div class="card-deck"><div class="card-columns">Cards</div></div>');

        $results = $this->analyzer->findSpecialCases();

        $cardIssue = collect($results['issues'])->firstWhere('type', 'card_layouts');

        $this->assertNotNull($cardIssue);
        $this->assertEquals('medium', $cardIssue['severity']);
        $this->assertEquals(2, $cardIssue['matches']);
        $this->assertStringContains('grille', $cardIssue['solution']);
    }

    /** @test */
    public function it_detects_custom_form_controls()
    {
        File::put(resource_path('views/custom-forms.blade.php'),
            '<div class="custom-control custom-checkbox custom-radio custom-switch">Forms</div>');

        $results = $this->analyzer->findSpecialCases();

        $customIssue = collect($results['issues'])->firstWhere('type', 'custom_form_controls');

        $this->assertNotNull($customIssue);
        $this->assertEquals('medium', $customIssue['severity']);
        $this->assertEquals(4, $customIssue['matches']);
        $this->assertStringContains('form-check', $customIssue['solution']);
    }

    /** @test */
    public function it_detects_print_styles()
    {
        File::put(resource_path('css/print.css'),
            '@media print { .d-print-none { display: none; } }');

        $results = $this->analyzer->findSpecialCases();

        $printIssue = collect($results['issues'])->firstWhere('type', 'print_styles');

        $this->assertNotNull($printIssue);
        $this->assertEquals('low', $printIssue['severity']);
        $this->assertEquals(2, $printIssue['matches']); // @media print + .d-print-
        $this->assertStringContains('manuel', $printIssue['solution']);
    }

    /** @test */
    public function it_provides_correct_summary_statistics()
    {
        // High severity: jQuery + data attributes
        File::put(resource_path('js/jquery.js'), '$(".modal").modal("show");');
        File::put(resource_path('views/data.blade.php'), '<div data-toggle="modal">Modal</div>');

        // Medium severity: negative margins + input groups
        File::put(resource_path('views/medium.blade.php'),
            '<div class="mt-n2 input-group-prepend">Medium</div>');

        // Low severity: print styles
        File::put(resource_path('css/print.css'), '@media print { }');

        $results = $this->analyzer->findSpecialCases();

        $this->assertArrayHasKey('summary', $results);
        $this->assertEquals(5, $results['summary']['total_issues']);
        $this->assertEquals(2, $results['summary']['high_severity']);
        $this->assertEquals(2, $results['summary']['medium_severity']);
        $this->assertEquals(1, $results['summary']['low_severity']);
    }

    /** @test */
    public function it_includes_example_matches()
    {
        File::put(resource_path('js/examples.js'),
            '$(".modal").modal("show"); $(".dropdown").dropdown("toggle");');

        $results = $this->analyzer->findSpecialCases();

        $jqueryIssue = collect($results['issues'])->firstWhere('type', 'jquery_bootstrap_plugins');

        $this->assertNotNull($jqueryIssue);
        $this->assertArrayHasKey('examples', $jqueryIssue);
        $this->assertNotEmpty($jqueryIssue['examples']);
        $this->assertContains('$(".modal").modal("show")', $jqueryIssue['examples']);
        $this->assertContains('$(".dropdown").dropdown("toggle")', $jqueryIssue['examples']);
    }

    /** @test */
    public function it_handles_mixed_severity_issues_in_same_file()
    {
        File::put(resource_path('views/mixed.blade.php'),
            '<div data-toggle="modal" class="input-group-prepend mt-n2">Mixed</div>');

        $results = $this->analyzer->findSpecialCases();

        $this->assertGreaterThanOrEqual(3, \count($results['issues']));

        $severities = collect($results['issues'])->pluck('severity')->toArray();
        $this->assertContains('high', $severities);
        $this->assertContains('medium', $severities);
    }

    /** @test */
    public function it_detects_issues_across_multiple_file_types()
    {
        File::put(resource_path('views/template.blade.php'), '<div data-toggle="modal">Template</div>');
        File::put(resource_path('css/styles.css'), '.component { @media print { } }');
        File::put(resource_path('js/script.js'), '$(".modal").modal("show");');

        $results = $this->analyzer->findSpecialCases();

        $this->assertGreaterThanOrEqual(3, \count($results['issues']));

        $files = collect($results['issues'])->pluck('file')->toArray();
        $this->assertContains(resource_path('views/template.blade.php'), $files);
        $this->assertContains(resource_path('css/styles.css'), $files);
        $this->assertContains(resource_path('js/script.js'), $files);
    }

    /** @test */
    public function it_ignores_bootstrap_5_compatible_code()
    {
        File::put(resource_path('js/bs5.js'),
            'const modal = new bootstrap.Modal(document.getElementById("myModal"));');
        File::put(resource_path('views/bs5.blade.php'),
            '<button data-bs-toggle="modal" data-bs-target="#modal">BS5</button>');

        $results = $this->analyzer->findSpecialCases();

        $this->assertEmpty($results['issues']);
        $this->assertEquals(0, $results['summary']['total_issues']);
    }

    /** @test */
    public function it_handles_empty_files()
    {
        File::put(resource_path('js/empty.js'), '');
        File::put(resource_path('views/empty.blade.php'), '');

        $results = $this->analyzer->findSpecialCases();

        $this->assertEmpty($results['issues']);
    }

    /** @test */
    public function it_provides_proper_issue_structure()
    {
        File::put(resource_path('js/structure.js'), '$(".modal").modal("show");');

        $results = $this->analyzer->findSpecialCases();

        $this->assertArrayHasKey('issues', $results);
        $this->assertArrayHasKey('summary', $results);

        $issue = $results['issues'][0];
        $this->assertArrayHasKey('type', $issue);
        $this->assertArrayHasKey('description', $issue);
        $this->assertArrayHasKey('severity', $issue);
        $this->assertArrayHasKey('solution', $issue);
        $this->assertArrayHasKey('file', $issue);
        $this->assertArrayHasKey('matches', $issue);
        $this->assertArrayHasKey('examples', $issue);

        $this->assertIsString($issue['type']);
        $this->assertIsString($issue['description']);
        $this->assertIsString($issue['severity']);
        $this->assertIsString($issue['solution']);
        $this->assertIsString($issue['file']);
        $this->assertIsInt($issue['matches']);
        $this->assertIsArray($issue['examples']);
    }

    private function createTestFiles(): void
    {
        $directories = [
            resource_path('views'),
            resource_path('css'),
            resource_path('js'),
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($dir, 0755, true, true);
        }
    }

    private function cleanupTestFiles(): void
    {
        $testFiles = [
            resource_path('js/modal.js'),
            resource_path('js/dropdown.js'),
            resource_path('js/tooltip.js'),
            resource_path('js/popover.js'),
            resource_path('js/collapse.js'),
            resource_path('js/carousel.js'),
            resource_path('js/tabs.js'),
            resource_path('js/jquery.js'),
            resource_path('js/examples.js'),
            resource_path('js/script.js'),
            resource_path('js/bs5.js'),
            resource_path('js/empty.js'),
            resource_path('js/structure.js'),
            resource_path('views/data-attrs.blade.php'),
            resource_path('views/all-data.blade.php'),
            resource_path('views/negative.blade.php'),
            resource_path('views/input-group.blade.php'),
            resource_path('views/cards.blade.php'),
            resource_path('views/custom-forms.blade.php'),
            resource_path('views/data.blade.php'),
            resource_path('views/medium.blade.php'),
            resource_path('views/mixed.blade.php'),
            resource_path('views/template.blade.php'),
            resource_path('views/bs5.blade.php'),
            resource_path('views/empty.blade.php'),
            resource_path('css/print.css'),
            resource_path('css/styles.css'),
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }
}
