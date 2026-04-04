<?php

declare(strict_types=1);

if (!function_exists('loadAppEnv')) {
    function loadAppEnv(?string $rootPath = null): void
    {
        static $loadedPaths = [];

        $root = $rootPath !== null ? rtrim($rootPath, DIRECTORY_SEPARATOR) : dirname(__DIR__, 2);
        if (isset($loadedPaths[$root])) {
            return;
        }

        $envPath = $root . DIRECTORY_SEPARATOR . '.env';
        $loadedPaths[$root] = true;

        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            $separatorPosition = strpos($trimmedLine, '=');
            if ($separatorPosition === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $separatorPosition));
            if ($key === '') {
                continue;
            }

            $value = trim(substr($trimmedLine, $separatorPosition + 1));
            if (
                strlen($value) >= 2
                && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
                    || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

if (!function_exists('appEnv')) {
    function appEnv(string $key, mixed $default = null, ?string $rootPath = null): mixed
    {
        loadAppEnv($rootPath);

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}
