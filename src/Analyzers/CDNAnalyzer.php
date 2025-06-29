<?php

namespace Bootstrap5Migrator\Analyzers;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CDNAnalyzer
{
    protected array $cdnProviders = [
        'jsdelivr.net' => [
            'pattern' => '/https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@([0-9.]+)\/dist\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        ],
        'stackpath.bootstrapcdn.com' => [
            'pattern' => '/https:\/\/stackpath\.bootstrapcdn\.com\/bootstrap\/([0-9.]+)\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        ],
        'cloudflare.com' => [
            'pattern' => '/https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/bootstrap\/([0-9.]+)\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css',
        ],
        'unpkg.com' => [
            'pattern' => '/https:\/\/unpkg\.com\/bootstrap@([0-9.]+)\/dist\/css\/bootstrap\.min\.css/',
            'v5_template' => 'https://unpkg.com/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        ],
    ];

    public function findCDNLinks(): array
    {
        $results = [];
        $files = $this->getHTMLFiles();

        foreach ($files as $file) {
            $content = File::get($file);

            foreach ($this->cdnProviders as $provider => $config) {
                preg_match_all($config['pattern'], $content, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $results[] = [
                        'file' => $file,
                        'provider' => $provider,
                        'current_link' => $match[0],
                        'current_version' => $match[1],
                        'suggested_v5_link' => $config['v5_template'],
                        'is_bootstrap_4' => str_starts_with($match[1], '4.'),
                    ];
                }
            }
        }

        return $results;
    }

    private function getHTMLFiles(): array
    {
        $files = [];
        $paths = [
            resource_path('views'),
            public_path(),
        ];

        $extensions = ['php', 'blade.php', 'html', 'htm'];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $filename = $file->getFilename();

                        foreach ($extensions as $ext) {
                            if (str_ends_with((string) $filename, $ext)) {
                                $files[] = $file->getPathname();
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $files;
    }
}
