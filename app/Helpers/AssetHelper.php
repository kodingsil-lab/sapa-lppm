<?php

declare(strict_types=1);

if (!function_exists('appBasePath')) {
    function appBasePath(): string
    {
        static $basePath = null;

        if ($basePath !== null) {
            return $basePath;
        }

        $config = require __DIR__ . '/../../config.php';
        $basePath = rtrim((string) (parse_url((string) ($config['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');

        return $basePath;
    }
}

if (!function_exists('appPublicBasePath')) {
    function appPublicBasePath(): string
    {
        static $publicBasePath = null;

        if ($publicBasePath !== null) {
            return $publicBasePath;
        }

        $basePath = appBasePath();

        if ($basePath !== '' && str_ends_with($basePath, '/public')) {
            $publicBasePath = $basePath;
            return $publicBasePath;
        }

        $documentRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $assetAtRoot = $documentRoot !== '' && is_dir($documentRoot . '/assets');
        $faviconAtRoot = $documentRoot !== '' && is_file($documentRoot . '/unisap_favicon.ico');

        if ($assetAtRoot || $faviconAtRoot) {
            $publicBasePath = $basePath;
            return $publicBasePath;
        }

        $publicBasePath = $basePath . '/public';

        return $publicBasePath;
    }
}

if (!function_exists('appAssetUrl')) {
    function appAssetUrl(string $path): string
    {
        return rtrim(appPublicBasePath(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('appPublicUrl')) {
    function appPublicUrl(string $path = ''): string
    {
        $base = rtrim(appPublicBasePath(), '/');
        $path = ltrim($path, '/');

        return $path === '' ? $base : $base . '/' . $path;
    }
}
