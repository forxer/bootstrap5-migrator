<?php

namespace Bootstrap5Migrator\Commands;

use Bootstrap5Migrator\Reporters\MigrationReporter;
use Illuminate\Console\Command;

class GenerateReportCommand extends Command
{
    protected $signature = 'bootstrap:report
                            {--format=html : Format du rapport (html, pdf, markdown)}
                            {--output= : Chemin de sortie du rapport}
                            {--include-screenshots : Inclure des captures d\'écran (nécessite puppeteer)}';

    protected $description = 'Generate a comprehensive migration report';

    public function handle(MigrationReporter $reporter)
    {
        $this->info('📄 Génération du rapport de migration...');

        $format = $this->option('format');
        $outputPath = $this->option('output') ?: storage_path("app/bootstrap-migration-report.{$format}");

        $reportData = $reporter->generateReportData();

        switch ($format) {
            case 'pdf':
                $this->generatePDFReport($reporter, $reportData, $outputPath);
                break;
            case 'markdown':
                $this->generateMarkdownReport($reporter, $reportData, $outputPath);
                break;
            default:
                $this->generateHTMLReport($reporter, $reportData, $outputPath);
        }

        $this->info("✅ Rapport généré : {$outputPath}");

        if ($this->option('include-screenshots')) {
            $this->generateScreenshots($outputPath);
        }

        return 0;
    }

    private function generateHTMLReport(MigrationReporter $reporter, array $data, string $path): void
    {
        $html = $reporter->generateHTMLReport($data);
        file_put_contents($path, $html);
    }

    private function generatePDFReport(MigrationReporter $reporter, array $data, string $path): void
    {
        // Nécessite une librairie PDF comme DomPDF ou wkhtmltopdf
        $html = $reporter->generateHTMLReport($data);

        // Exemple avec DomPDF (à installer séparément)
        /*
        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        file_put_contents($path, $pdf->output());
        */

        $this->warn('⚠️ Génération PDF nécessite l\'installation de DomPDF ou wkhtmltopdf');

        // Fallback vers HTML
        $htmlPath = str_replace('.pdf', '.html', $path);
        file_put_contents($htmlPath, $html);
        $this->info("📄 Rapport HTML généré à la place : {$htmlPath}");
    }

    private function generateMarkdownReport(MigrationReporter $reporter, array $data, string $path): void
    {
        $markdown = $reporter->generateMarkdownReport($data);
        file_put_contents($path, $markdown);
    }

    private function generateScreenshots(string $reportPath): void
    {
        $this->info('📸 Génération des captures d\'écran...');
        $this->warn('⚠️ Fonctionnalité nécessitant Puppeteer ou un outil de capture similaire');

        // Cette fonctionnalité nécessiterait l'intégration avec un outil comme :
        // - Puppeteer (Node.js)
        // - Chrome/Chromium headless
        // - Selenium WebDriver

        $this->comment('💡 Pour activer les captures d\'écran, installez puppeteer :');
        $this->line('npm install -g puppeteer');
    }
}
