<?php

namespace Bootstrap5Migrator\Traits;

use Bootstrap5Migrator\Services\CacheService;
use Bootstrap5Migrator\Services\FileProcessorService;
use Bootstrap5Migrator\Services\ProgressService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait UsesPerformanceServices
{
    protected ?ProgressService $progressService = null;

    protected ?CacheService $cacheService = null;

    protected ?FileProcessorService $fileProcessorService = null;

    /**
     * Initialise les services de performance
     */
    protected function initializePerformanceServices(): void
    {
        if ($this->progressService === null) {
            $this->progressService = new ProgressService($this->output);
        }

        if ($this->cacheService === null) {
            $this->cacheService = app(CacheService::class);
        }

        if ($this->fileProcessorService === null) {
            $this->fileProcessorService = app(FileProcessorService::class);
        }
    }

    /**
     * Traite les fichiers avec optimisations de performance
     */
    protected function processFilesOptimized(
        array $directories,
        array $extensions,
        callable $processor,
        bool $useCache = true,
        bool $useParallel = false
    ): array {
        $this->initializePerformanceServices();

        // Vérifier le cache si activé
        if ($useCache) {
            $cachedResults = $this->getCachedResults($directories, $extensions);

            if ($cachedResults !== null) {
                $this->progressService->info('Résultats récupérés du cache');

                return $cachedResults;
            }
        }

        // Traitement optimisé
        if ($useParallel) {
            $results = $this->fileProcessorService->processFilesInParallel(
                $directories,
                $extensions,
                $processor
            );
        } else {
            $results = $this->fileProcessorService->processFilesInChunks(
                $directories,
                $extensions,
                $processor
            );
        }

        // Mettre en cache les résultats
        if ($useCache) {
            $this->cacheResults($directories, $extensions, $results);
        }

        return $results;
    }

    /**
     * Affiche les statistiques de performance
     */
    protected function displayPerformanceStats(): void
    {
        if ($this->fileProcessorService !== null) {
            $stats = $this->fileProcessorService->formatStats();

            if (! empty($stats)) {
                $this->progressService->displayStats($stats);
            }
        }

        if ($this->cacheService !== null) {
            $cacheStats = $this->cacheService->getCacheStats();

            $this->progressService->info(
                \sprintf('Cache: %s fichiers, %s', $cacheStats['disk_cache_files'], $cacheStats['total_cache_size'])
            );
        }
    }

    /**
     * Nettoie les services après utilisation
     */
    protected function cleanupPerformanceServices(): void
    {
        if ($this->fileProcessorService !== null) {
            $this->fileProcessorService->cleanup();
        }

        if ($this->cacheService !== null) {
            $this->cacheService->cleanupExpiredCache();
        }
    }

    /**
     * Créé une barre de progression pour un traitement
     */
    protected function createProgressBar(int $max, string $task = ''): void
    {
        $this->initializePerformanceServices();
        $this->progressService->start($max, $task);
    }

    /**
     * Avance la barre de progression
     */
    protected function advanceProgress(int $step = 1, string $message = ''): void
    {
        if ($this->progressService !== null) {
            $this->progressService->advance($step, $message);
        }
    }

    /**
     * Termine la barre de progression
     */
    protected function finishProgress(string $message = ''): void
    {
        if ($this->progressService !== null) {
            $this->progressService->finish($message);
        }
    }

    /**
     * Affiche un message d'étape
     */
    protected function progressStep(string $message, string $emoji = '🔄'): void
    {
        if ($this->progressService !== null) {
            $this->progressService->step($message, $emoji);
        } else {
            $this->showInfo(\sprintf('%s %s', $emoji, $message));
        }
    }

    /**
     * Affiche un message de succès
     */
    protected function showSuccess(string $message): void
    {
        if ($this->progressService !== null) {
            $this->progressService->success($message);
        } else {
            $this->showInfo('✅ '.$message);
        }
    }

    /**
     * Affiche un message d'erreur
     */
    protected function showError(string $message): void
    {
        if ($this->progressService !== null) {
            $this->progressService->error($message);
        } else {
            $this->line('<fg=red>❌ '.$message.'</>');
        }
    }

    /**
     * Affiche un avertissement
     */
    protected function showWarning(string $message): void
    {
        if ($this->progressService !== null) {
            $this->progressService->warning($message);
        } else {
            $this->line('<fg=yellow>⚠️ '.$message.'</>');
        }
    }

    /**
     * Affiche des informations
     */
    protected function showInfo(string $message): void
    {
        if ($this->progressService !== null) {
            $this->progressService->info($message);
        } else {
            $this->line('<fg=blue>ℹ️ '.$message.'</>');
        }
    }

    /**
     * Affiche un tableau formaté
     */
    protected function displayTable(array $headers, array $rows): void
    {
        if ($this->progressService !== null) {
            $this->progressService->table($headers, $rows);
        } else {
            $this->table($headers, $rows);
        }
    }

    /**
     * Récupère les résultats en cache
     */
    protected function getCachedResults(array $directories, array $extensions): ?array
    {
        if ($this->cacheService === null) {
            return null;
        }

        $cacheKey = md5(serialize($directories).serialize($extensions));

        return $this->cacheService->getCachedDirectoryAnalysis($cacheKey);
    }

    /**
     * Met les résultats en cache
     */
    protected function cacheResults(array $directories, array $extensions, array $results): void
    {
        if ($this->cacheService === null) {
            return;
        }

        $cacheKey = md5(serialize($directories).serialize($extensions));
        $this->cacheService->cacheDirectoryAnalysis($cacheKey, $results);
    }

    /**
     * Vérifie si on doit utiliser le mode parallèle
     */
    protected function shouldUseParallel(): bool
    {
        // Utiliser le parallélisme seulement si:
        // 1. L'option est activée
        // 2. Le système le supporte
        // 3. Il y a assez de fichiers pour que ça vaille le coup

        return $this->option('parallel') &&
               \function_exists('pcntl_fork') &&
               $this->getEstimatedFileCount() > 100;
    }

    /**
     * Estime le nombre de fichiers à traiter
     */
    protected function getEstimatedFileCount(): int
    {
        $directories = [
            resource_path('views'),
            resource_path('css'),
            resource_path('js'),
            public_path(),
        ];

        $count = 0;

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $count += iterator_count(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                    )
                );
            }
        }

        return $count;
    }

    /**
     * Optimise les analyseurs avec les services de performance
     */
    protected function optimizeAnalyzer($analyzer): void
    {
        // Si l'analyseur supporte la mise en cache
        if (method_exists($analyzer, 'setCacheService')) {
            $analyzer->setCacheService($this->cacheService);
        }

        // Si l'analyseur supporte le traitement par chunks
        if (method_exists($analyzer, 'setFileProcessor')) {
            $analyzer->setFileProcessor($this->fileProcessorService);
        }

        // Si l'analyseur supporte les callbacks de progression
        if (method_exists($analyzer, 'setProgressCallback')) {
            $analyzer->setProgressCallback(function ($message): void {
                $this->progressStep($message, '⚙️');
            });
        }
    }
}
