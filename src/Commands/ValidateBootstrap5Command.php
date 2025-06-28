<?php

namespace Bootstrap5Migrator\Commands;

use Bootstrap5Migrator\Validators\Bootstrap5Validator;
use Illuminate\Console\Command;

class ValidateBootstrap5Command extends Command
{
    protected $signature = 'bootstrap:validate
                            {--fix : Tenter de corriger automatiquement les problèmes mineurs}
                            {--strict : Mode strict (échec si des problèmes sont trouvés)}';

    protected $description = 'Validate your Bootstrap 5 migration and detect remaining issues';

    public function handle(Bootstrap5Validator $validator)
    {
        $this->info('✅ Validation de votre migration Bootstrap 5...');

        $results = $validator->validateMigration();

        $this->displayValidationResults($results);

        if ($this->option('fix') && !empty($results['fixable_issues'])) {
            $this->fixIssues($validator, $results['fixable_issues']);
        }

        if ($this->option('strict') && $results['has_critical_issues']) {
            $this->error('❌ Validation échouée en mode strict');
            return 1;
        }

        return 0;
    }

    private function displayValidationResults(array $results): void
    {
        // Score global
        $score = $results['score'];
        $scoreColor = $score >= 90 ? 'info' : ($score >= 70 ? 'comment' : 'error');

        $this->newLine();
        $this->$scoreColor("📊 Score de migration : {$score}/100");

        // Problèmes critiques
        if (!empty($results['critical_issues'])) {
            $this->error('🚨 Problèmes critiques :');
            foreach ($results['critical_issues'] as $issue) {
                $this->line("  • {$issue['description']} ({$issue['file']})");
            }
        }

        // Avertissements
        if (!empty($results['warnings'])) {
            $this->warn('⚠️ Avertissements :');
            foreach ($results['warnings'] as $warning) {
                $this->line("  • {$warning['description']} ({$warning['file']})");
            }
        }

        // Recommandations
        if (!empty($results['recommendations'])) {
            $this->info('💡 Recommandations :');
            foreach ($results['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }

        // Résumé par catégorie
        $this->newLine();
        $this->table(['Catégorie', 'Statut', 'Détails'], [
            ['Classes CSS', $results['css_status'], $results['css_details']],
            ['JavaScript', $results['js_status'], $results['js_details']],
            ['Attributs data-*', $results['data_attributes_status'], $results['data_attributes_details']],
            ['CDN Links', $results['cdn_status'], $results['cdn_details']],
            ['Dépendances NPM', $results['npm_status'], $results['npm_details']],
        ]);
    }

    private function fixIssues(Bootstrap5Validator $validator, array $fixableIssues): void
    {
        $this->info('🔧 Correction automatique des problèmes mineurs...');

        $fixed = 0;
        foreach ($fixableIssues as $issue) {
            if ($validator->fixIssue($issue)) {
                $this->line("  ✅ Corrigé : {$issue['description']}");
                $fixed++;
            } else {
                $this->line("  ❌ Échec : {$issue['description']}");
            }
        }

        $this->info("🎉 {$fixed} problème(s) corrigé(s) automatiquement");
    }
}