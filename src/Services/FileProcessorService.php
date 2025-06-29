<?php

namespace Bootstrap5Migrator\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileProcessorService
{
    protected int $chunkSize;
    protected int $memoryLimit;
    protected array $processedFiles = [];
    protected array $stats = [];

    public function __construct(int $chunkSize = 100, int $memoryLimit = 128)
    {
        $this->chunkSize = $chunkSize;
        $this->memoryLimit = $memoryLimit * 1024 * 1024; // Convert MB to bytes
    }

    /**
     * Traite les fichiers par chunks pour éviter les problèmes de mémoire
     */
    public function processFilesInChunks(array $directories, array $extensions, callable $processor): array
    {
        $allFiles = $this->gatherFiles($directories, $extensions);
        $totalFiles = count($allFiles);
        $chunks = array_chunk($allFiles, $this->chunkSize);
        $results = [];

        $this->initializeStats($totalFiles);

        foreach ($chunks as $chunkIndex => $chunk) {
            $this->checkMemoryUsage();
            
            $chunkResults = $this->processChunk($chunk, $processor, $chunkIndex + 1, count($chunks));
            $results = array_merge($results, $chunkResults);
            
            // Force garbage collection après chaque chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $this->finalizeStats();
        
        return $results;
    }

    /**
     * Rassemble tous les fichiers de façon optimisée
     */
    protected function gatherFiles(array $directories, array $extensions): array
    {
        $files = [];
        $extensionPattern = '/\.(' . implode('|', array_map('preg_quote', $extensions)) . ')$/i';

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && preg_match($extensionPattern, $file->getFilename())) {
                        $files[] = $file->getPathname();
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue processing
                continue;
            }
        }

        return $files;
    }

    /**
     * Traite un chunk de fichiers
     */
    protected function processChunk(array $files, callable $processor, int $chunkNumber, int $totalChunks): array
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($files as $file) {
            try {
                if (!File::exists($file) || !File::isReadable($file)) {
                    $this->stats['skipped']++;
                    continue;
                }

                $fileSize = File::size($file);
                
                // Skip très gros fichiers (> 5MB) pour éviter les problèmes de mémoire
                if ($fileSize > 5 * 1024 * 1024) {
                    $this->stats['too_large']++;
                    continue;
                }

                $result = $processor($file);
                if ($result !== null) {
                    $results[] = $result;
                }
                
                $this->stats['processed']++;
                $this->processedFiles[] = $file;

            } catch (\Exception $e) {
                $this->stats['errors']++;
                // Log error mais continue
                continue;
            }
        }

        $chunkTime = microtime(true) - $startTime;
        $this->stats['chunk_times'][] = $chunkTime;

        return $results;
    }

    /**
     * Vérifie l'utilisation mémoire et libère si nécessaire
     */
    protected function checkMemoryUsage(): void
    {
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);

        $this->stats['memory']['current'] = $currentUsage;
        $this->stats['memory']['peak'] = max($this->stats['memory']['peak'] ?? 0, $peakUsage);

        // Si on approche de la limite, force garbage collection
        if ($currentUsage > ($this->memoryLimit * 0.8)) {
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * Traitement parallèle pour les gros projets (si disponible)
     */
    public function processFilesInParallel(array $directories, array $extensions, callable $processor, int $workers = 4): array
    {
        // Fallback vers traitement séquentiel si pas de support parallèle
        if (!function_exists('pcntl_fork')) {
            return $this->processFilesInChunks($directories, $extensions, $processor);
        }

        $allFiles = $this->gatherFiles($directories, $extensions);
        $chunks = array_chunk($allFiles, ceil(count($allFiles) / $workers));
        $results = [];

        // Pour le moment, implémentation séquentielle 
        // L'implémentation parallèle nécessiterait une gestion plus complexe des processus
        foreach ($chunks as $chunk) {
            $chunkResults = $this->processChunk($chunk, $processor, 1, 1);
            $results = array_merge($results, $chunkResults);
        }

        return $results;
    }

    /**
     * Optimise la lecture de fichiers avec mise en cache
     */
    public function readFileOptimized(string $filePath): ?string
    {
        static $cache = [];
        static $cacheSize = 0;
        const MAX_CACHE_SIZE = 50 * 1024 * 1024; // 50MB max cache

        // Vérification cache
        if (isset($cache[$filePath])) {
            return $cache[$filePath];
        }

        try {
            $content = File::get($filePath);
            $contentSize = strlen($content);

            // Mise en cache seulement pour les petits fichiers
            if ($contentSize < 1024 * 1024 && ($cacheSize + $contentSize) < MAX_CACHE_SIZE) {
                $cache[$filePath] = $content;
                $cacheSize += $contentSize;
            }

            return $content;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Filtrage intelligent des fichiers avant traitement
     */
    public function filterRelevantFiles(array $files, array $patterns = []): array
    {
        if (empty($patterns)) {
            return $files;
        }

        return array_filter($files, function ($file) use ($patterns) {
            $content = $this->readFileOptimized($file);
            if ($content === null) {
                return false;
            }

            // Quick scan pour voir si le fichier contient des patterns pertinents
            foreach ($patterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Initialise les statistiques de traitement
     */
    protected function initializeStats(int $totalFiles): void
    {
        $this->stats = [
            'total_files' => $totalFiles,
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'too_large' => 0,
            'start_time' => microtime(true),
            'memory' => [
                'start' => memory_get_usage(true),
                'peak' => 0,
                'current' => 0
            ],
            'chunk_times' => []
        ];
    }

    /**
     * Finalise les statistiques
     */
    protected function finalizeStats(): void
    {
        $this->stats['end_time'] = microtime(true);
        $this->stats['total_time'] = $this->stats['end_time'] - $this->stats['start_time'];
        $this->stats['memory']['end'] = memory_get_usage(true);
        $this->stats['average_chunk_time'] = !empty($this->stats['chunk_times']) 
            ? array_sum($this->stats['chunk_times']) / count($this->stats['chunk_times'])
            : 0;
    }

    /**
     * Retourne les statistiques de performance
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Retourne la liste des fichiers traités
     */
    public function getProcessedFiles(): array
    {
        return $this->processedFiles;
    }

    /**
     * Nettoie la mémoire et le cache
     */
    public function cleanup(): void
    {
        $this->processedFiles = [];
        $this->stats = [];
        
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Formate les statistiques pour affichage
     */
    public function formatStats(): array
    {
        if (empty($this->stats)) {
            return [];
        }

        return [
            'files_processed' => $this->stats['processed'],
            'files_skipped' => $this->stats['skipped'],
            'files_errors' => $this->stats['errors'],
            'files_too_large' => $this->stats['too_large'],
            'total_time' => round($this->stats['total_time'], 2) . 's',
            'average_chunk_time' => round($this->stats['average_chunk_time'], 3) . 's',
            'memory_used' => $this->formatBytes($this->stats['memory']['peak']),
            'files_per_second' => $this->stats['total_time'] > 0 
                ? round($this->stats['processed'] / $this->stats['total_time'], 1)
                : 0
        ];
    }

    /**
     * Formate les bytes en unités lisibles
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}