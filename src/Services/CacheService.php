<?php

namespace Bootstrap5Migrator\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CacheService
{
    protected string $cachePrefix = 'bootstrap_migrator_';

    protected string $cacheDir;

    protected int $defaultTtl;

    protected array $memoryCache = [];

    protected array $config;

    public function __construct()
    {
        $this->config = config('bootstrap5-migrator', []);
        $this->defaultTtl = $this->config['performance']['cache_ttl'] ?? 3600;
        $this->cacheDir = storage_path('app/bootstrap-migration-cache');
        $this->ensureCacheDirectory();
    }

    /**
     * Met en cache le résultat d'une analyse de fichier
     */
    public function cacheFileAnalysis(string $filePath, array $result): void
    {
        $key = $this->getFileKey($filePath);
        $cacheData = [
            'result' => $result,
            'file_hash' => $this->getFileHash($filePath),
            'cached_at' => time(),
        ];

        $this->setCache($key, $cacheData);
    }

    /**
     * Récupère le résultat d'analyse en cache
     */
    public function getCachedFileAnalysis(string $filePath): ?array
    {
        $key = $this->getFileKey($filePath);
        $cached = $this->getCache($key);

        if ($cached === null) {
            return null;
        }

        // Vérifier si le fichier a changé
        if ($cached['file_hash'] !== $this->getFileHash($filePath)) {
            $this->forgetCache($key);

            return null;
        }

        return $cached['result'];
    }

    /**
     * Met en cache les métadonnées d'un projet
     */
    public function cacheProjectMetadata(array $metadata): void
    {
        $key = $this->cachePrefix.'project_metadata';
        $this->setCache($key, $metadata, 86400); // 24h
    }

    /**
     * Récupère les métadonnées du projet
     */
    public function getCachedProjectMetadata(): ?array
    {
        $key = $this->cachePrefix.'project_metadata';

        return $this->getCache($key);
    }

    /**
     * Cache les résultats d'analyse par répertoire
     */
    public function cacheDirectoryAnalysis(string $directory, array $results): void
    {
        $key = $this->getDirectoryKey($directory);
        $cacheData = [
            'results' => $results,
            'directory_hash' => $this->getDirectoryHash($directory),
            'files_count' => \count($results),
            'cached_at' => time(),
        ];

        $this->setCache($key, $cacheData, 7200); // 2h
    }

    /**
     * Récupère les résultats d'analyse d'un répertoire
     */
    public function getCachedDirectoryAnalysis(string $directory): ?array
    {
        $key = $this->getDirectoryKey($directory);
        $cached = $this->getCache($key);

        if ($cached === null) {
            return null;
        }

        // Vérification rapide si le répertoire a changé
        if ($cached['directory_hash'] !== $this->getDirectoryHash($directory)) {
            $this->forgetCache($key);

            return null;
        }

        return $cached['results'];
    }

    /**
     * Cache les patterns de détection compilés
     */
    public function cacheCompiledPatterns(array $patterns): void
    {
        $key = $this->cachePrefix.'compiled_patterns';
        $this->setCache($key, $patterns, 86400); // 24h
    }

    /**
     * Récupère les patterns compilés
     */
    public function getCachedCompiledPatterns(): ?array
    {
        $key = $this->cachePrefix.'compiled_patterns';

        return $this->getCache($key);
    }

    /**
     * Cache une analyse complète du projet
     */
    public function cacheFullAnalysis(array $analysis): string
    {
        $analysisId = uniqid('analysis_', true);
        $key = $this->cachePrefix.'full_analysis_'.$analysisId;

        $cacheData = [
            'analysis' => $analysis,
            'project_signature' => $this->getProjectSignature(),
            'cached_at' => time(),
        ];

        $this->setCache($key, $cacheData, 3600); // 1h

        return $analysisId;
    }

    /**
     * Récupère une analyse complète cachée
     */
    public function getCachedFullAnalysis(string $analysisId): ?array
    {
        $key = $this->cachePrefix.'full_analysis_'.$analysisId;
        $cached = $this->getCache($key);

        if ($cached === null) {
            return null;
        }

        // Vérifier si le projet a changé de façon significative
        if ($cached['project_signature'] !== $this->getProjectSignature()) {
            $this->forgetCache($key);

            return null;
        }

        return $cached['analysis'];
    }

    /**
     * Optimisation: pré-charge les caches les plus utilisés
     */
    public function warmupCache(array $commonFiles = []): void
    {
        // Pré-compiler les patterns fréquents
        $patterns = $this->getCommonPatterns();
        $this->cacheCompiledPatterns($patterns);

        // Pré-analyser les fichiers les plus communs
        foreach ($commonFiles as $file) {
            if (File::exists($file)) {
                $this->warmupFileCache($file);
            }
        }
    }

    /**
     * Nettoie le cache expiré
     */
    public function cleanupExpiredCache(): int
    {
        $cleaned = 0;

        if (File::exists($this->cacheDir)) {
            $files = File::allFiles($this->cacheDir);

            foreach ($files as $file) {
                $content = File::get($file->getPathname());
                $data = json_decode($content, true);

                if ($data && isset($data['expires_at']) && $data['expires_at'] < time()) {
                    File::delete($file->getPathname());
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Invalide tout le cache
     */
    public function invalidateAll(): void
    {
        $this->memoryCache = [];

        if (File::exists($this->cacheDir)) {
            File::deleteDirectory($this->cacheDir);
            $this->ensureCacheDirectory();
        }
    }

    /**
     * Obtient des statistiques sur le cache
     */
    public function getCacheStats(): array
    {
        $stats = [
            'memory_cache_entries' => \count($this->memoryCache),
            'disk_cache_files' => 0,
            'total_cache_size' => 0,
            'oldest_entry' => null,
            'newest_entry' => null,
        ];

        if (File::exists($this->cacheDir)) {
            $files = File::allFiles($this->cacheDir);
            $stats['disk_cache_files'] = \count($files);

            $timestamps = [];

            foreach ($files as $file) {
                $stats['total_cache_size'] += $file->getSize();
                $timestamps[] = $file->getMTime();
            }

            if ($timestamps !== []) {
                $stats['oldest_entry'] = date('Y-m-d H:i:s', min($timestamps));
                $stats['newest_entry'] = date('Y-m-d H:i:s', max($timestamps));
            }
        }

        $stats['total_cache_size'] = $this->formatBytes($stats['total_cache_size']);

        return $stats;
    }

    /**
     * Méthodes privées
     */
    protected function getFileKey(string $filePath): string
    {
        return $this->cachePrefix.'file_'.md5($filePath);
    }

    protected function getDirectoryKey(string $directory): string
    {
        return $this->cachePrefix.'dir_'.md5($directory);
    }

    protected function getFileHash(string $filePath): string
    {
        if (! File::exists($filePath)) {
            return '';
        }

        return md5(File::lastModified($filePath).File::size($filePath));
    }

    protected function getDirectoryHash(string $directory): string
    {
        if (! is_dir($directory)) {
            return '';
        }

        // Hash basé sur le nombre de fichiers et dernière modification
        $files = File::allFiles($directory);
        $hash = \count($files);

        foreach (\array_slice($files, 0, 10) as $file) { // Limite pour performance
            $hash .= $file->getMTime();
        }

        return md5($hash);
    }

    protected function getProjectSignature(): string
    {
        $signature = '';

        // Signature basée sur package.json et quelques fichiers clés
        $keyFiles = [
            base_path('package.json'),
            base_path('composer.json'),
            resource_path('js/app.js'),
            resource_path('css/app.css'),
        ];

        foreach ($keyFiles as $file) {
            if (File::exists($file)) {
                $signature .= $this->getFileHash($file);
            }
        }

        return md5($signature);
    }

    protected function setCache(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl ??= $this->defaultTtl;

        // Cache en mémoire
        $this->memoryCache[$key] = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];

        // Cache sur disque pour persistance
        $cacheData = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
        ];

        $cacheFile = $this->cacheDir.'/'.$key.'.cache';
        File::put($cacheFile, json_encode($cacheData));
    }

    protected function getCache(string $key): mixed
    {
        // Vérifier d'abord le cache mémoire
        if (isset($this->memoryCache[$key])) {
            $cached = $this->memoryCache[$key];

            if ($cached['expires_at'] > time()) {
                return $cached['value'];
            }

            unset($this->memoryCache[$key]);
        }

        // Vérifier le cache disque
        $cacheFile = $this->cacheDir.'/'.$key.'.cache';

        if (File::exists($cacheFile)) {
            $content = File::get($cacheFile);
            $data = json_decode($content, true);

            if ($data && $data['expires_at'] > time()) {
                // Remettre en cache mémoire
                $this->memoryCache[$key] = [
                    'value' => $data['value'],
                    'expires_at' => $data['expires_at'],
                ];

                return $data['value'];
            }

            File::delete($cacheFile);
        }

        return null;
    }

    protected function forgetCache(string $key): void
    {
        unset($this->memoryCache[$key]);

        $cacheFile = $this->cacheDir.'/'.$key.'.cache';

        if (File::exists($cacheFile)) {
            File::delete($cacheFile);
        }
    }

    protected function ensureCacheDirectory(): void
    {
        if (! File::exists($this->cacheDir)) {
            File::makeDirectory($this->cacheDir, 0755, true);
        }
    }

    protected function warmupFileCache(string $file): void
    {
        // Exemple de pré-chargement basique
        if (File::exists($file)) {
            $key = $this->getFileKey($file);

            if ($this->getCache($key) === null) {
                // Pré-analyse simple
                $analysis = [
                    'size' => File::size($file),
                    'modified' => File::lastModified($file),
                    'extension' => pathinfo($file, PATHINFO_EXTENSION),
                ];
                $this->cacheFileAnalysis($file, $analysis);
            }
        }
    }

    protected function getCommonPatterns(): array
    {
        return [
            'bootstrap_classes' => '/\b(ml-|mr-|pl-|pr-|text-left|text-right|form-group|badge-)\w*/i',
            'data_attributes' => '/data-(toggle|target|dismiss|slide|ride)=/i',
            'cdn_links' => '/https:\/\/[^"\']*bootstrap.*4\.[0-9.]+/i',
            'jquery_bootstrap' => '/\$\([^)]+\)\.(modal|dropdown|tooltip|popover|collapse|carousel|tab)\s*\(/i',
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, \count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, 2).' '.$units[$pow];
    }
}
