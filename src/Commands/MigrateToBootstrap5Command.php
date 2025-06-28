<?php

namespace Bootstrap5Migrator\Commands;

use Bootstrap5Migrator\Bootstrap5Migrator;
use Illuminate\Console\Command;

class MigrateToBootstrap5Command extends Command
{
    protected $signature = 'bootstrap:migrate-to-5
                            {--dry-run : Afficher les changements sans les appliquer}
                            {--backup : Créer une sauvegarde avant migration}
                            {--force : Forcer la migration sans confirmation}
                            {--skip-jquery : Ne pas supprimer jQuery (à gérer manuellement)}';

    protected $description = 'Migrate your Laravel application from Bootstrap 4.6 to Bootstrap 5.x';

    public function handle(Bootstrap5Migrator $migrator)
    {
        $this->info('🚀 Début de la migration vers Bootstrap 5...');
        $this->warn('⚠️  ATTENTION: Bootstrap 5 supprime jQuery comme dépendance !');

        if ($this->option('dry-run')) {
            $this->warn('Mode dry-run activé - Aucun fichier ne sera modifié');
        }

        if ($this->option('backup')) {
            $this->info('📦 Création d\'une sauvegarde...');
            $migrator->createBackup();
        }

        // Analyse préliminaire
        $this->info('🔍 Analyse de votre application...');
        $analysis = $migrator->analyzeApplication();

        $this->displayAnalysis($analysis);

        if (!$this->option('force') && !$this->confirm('Voulez-vous continuer avec la migration ?')) {
            $this->info('Migration annulée.');
            return;
        }

        try {
            // 1. Mise à jour du package.json
            $this->info('📝 Mise à jour du package.json...');
            $migrator->updatePackageJson($this->option('dry-run'), $this->option('skip-jquery'));

            // 2. Installation des dépendances
            if (!$this->option('dry-run')) {
                $this->info('📦 Installation des nouvelles dépendances...');
                $migrator->installDependencies();
            }

            // 3. Migration des fichiers CSS/SCSS
            $this->info('🎨 Migration des fichiers de style...');
            $migrator->migrateStyleFiles($this->option('dry-run'));

            // 4. Migration des fichiers JavaScript
            $this->info('⚡ Migration des fichiers JavaScript...');
            $migrator->migrateJavaScriptFiles($this->option('dry-run'));

            // 5. Migration des templates Blade
            $this->info('🔧 Migration des templates Blade...');
            $migrator->migrateBladeTemplates($this->option('dry-run'));

            // 6. Compilation des assets
            if (!$this->option('dry-run')) {
                $this->info('🔨 Compilation des assets...');
                $migrator->compileAssets();
            }

            $this->info('✅ Migration terminée avec succès !');
            $this->displayPostMigrationInstructions();

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la migration : ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function displayAnalysis(array $analysis): void
    {
        $this->table(['Élément', 'Statut', 'Action requise'], [
            ['Bootstrap Version', $analysis['bootstrap_version'], $analysis['bootstrap_version'] === '4.6.x' ? '✅ Compatible' : '⚠️ Vérifier'],
            ['jQuery Usage', $analysis['jquery_usage'] ? 'Détecté' : 'Non détecté', $analysis['jquery_usage'] ? '⚠️ À vérifier' : '✅ OK'],
            ['Popper.js', $analysis['popper_usage'] ? 'Détecté' : 'Non détecté', 'ℹ️ Mise à jour requise'],
            ['Classes obsolètes', count($analysis['deprecated_classes']), count($analysis['deprecated_classes']) > 0 ? '🔄 Remplacement auto' : '✅ OK'],
            ['JS Components', count($analysis['js_components']), count($analysis['js_components']) > 0 ? '🔄 Mise à jour requise' : '✅ OK'],
        ]);

        if (!empty($analysis['deprecated_classes'])) {
            $this->warn('Classes obsolètes détectées : ' . implode(', ', array_slice($analysis['deprecated_classes'], 0, 10)));
            if (count($analysis['deprecated_classes']) > 10) {
                $this->info('... et ' . (count($analysis['deprecated_classes']) - 10) . ' autres');
            }
        }
    }

    private function displayPostMigrationInstructions(): void
    {
        $this->newLine();
        $this->info('📋 Instructions post-migration :');
        $this->line('1. ⚠️  Testez tous vos composants Bootstrap (modals, dropdowns, tooltips)');
        $this->line('2. 🔍 Vérifiez les formulaires (form-group → mb-3, form-control-file supprimé)');
        $this->line('3. 📱 Testez la responsivité (gutter classes changées)');
        $this->line('4. ⚡ Si vous utilisez jQuery, assurez-vous de sa compatibilité');
        $this->line('5. 🎨 Vérifiez les icônes (Bootstrap Icons recommandé)');
        $this->line('6. 📖 Consultez https://getbootstrap.com/docs/5.0/migration/');
    }
}