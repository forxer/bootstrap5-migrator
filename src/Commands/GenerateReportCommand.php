<?php

namespace Bootstrap5Migrator\Commands;

use Bootstrap5Migrator\Reporters\MigrationReporter;
use Bootstrap5Migrator\Traits\UsesPerformanceServices;
use Illuminate\Console\Command;

class GenerateReportCommand extends Command
{
    use UsesPerformanceServices;

    protected $signature = 'bootstrap:report
                            {--format=html : Format du rapport (html, pdf, markdown)}
                            {--output= : Chemin de sortie du rapport}
                            {--include-screenshots : Inclure des captures d\'écran (nécessite puppeteer)}
                            {--no-cache : Désactiver le cache pour cette génération}
                            {--parallel : Utiliser le traitement parallèle (si disponible)}';

    protected $description = 'Generate a comprehensive migration report';

    public function handle(MigrationReporter $reporter): int
    {
        $this->initializePerformanceServices();

        $this->progressStep('Génération du rapport de migration', '📄');

        $format = $this->option('format');
        $outputPath = $this->option('output') ?: storage_path('app/bootstrap-migration-report.'.$format);

        $this->progressStep('Collecte des données de rapport', '📊');
        $reportData = $reporter->generateReportData();

        $this->progressStep('Génération du rapport '.$format, '⚙️');
        match ($format) {
            'pdf' => $this->generatePDFReport($reporter, $reportData, $outputPath),
            'markdown' => $this->generateMarkdownReport($reporter, $reportData, $outputPath),
            default => $this->generateHTMLReport($reporter, $reportData, $outputPath),
        };

        $this->success('Rapport généré : '.$outputPath);

        if ($this->option('include-screenshots')) {
            $this->generateScreenshots();
        }

        $this->displayPerformanceStats();
        $this->cleanupPerformanceServices();

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
        $this->info('📄 Rapport HTML généré à la place : '.$htmlPath);
    }

    private function generateMarkdownReport(MigrationReporter $reporter, array $data, string $path): void
    {
        $markdown = $reporter->generateMarkdownReport($data);
        file_put_contents($path, $markdown);
    }

    private function generateScreenshots(): void
    {
        $this->progressStep('Génération des captures d\'écran', '📸');
        $this->warning('Fonctionnalité nécessitant Puppeteer ou un outil de capture similaire');
        // Cette fonctionnalité nécessiterait l'intégration avec un outil comme :
        // - Puppeteer (Node.js)
        // - Chrome/Chromium headless
        // - Selenium WebDriver
        $this->info('Pour activer les captures d\'écran, installez puppeteer :');
        $this->line('npm install -g puppeteer');
    }
}
