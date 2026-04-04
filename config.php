<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Helpers/EnvHelper.php';

if (!function_exists('config_env')) {
    function config_env(string $key, mixed $default = null): mixed
    {
        return appEnv($key, $default, __DIR__);
    }
}

if (!function_exists('config_env_any')) {
    function config_env_any(array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = appEnv($key, null, __DIR__);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}

return [
    'app' => [
        'name' => (string) config_env('APP_NAME', 'SAPA LPPM'),
        'url' => (string) config_env('APP_URL', 'http://localhost/sapa-lppm'),
        'timezone' => (string) config_env('APP_TIMEZONE', 'Asia/Makassar'),
    ],
    'db' => [
        'host' => (string) config_env_any(['DB_HOST', 'database.default.hostname'], 'localhost'),
        'port' => (string) config_env_any(['DB_PORT', 'database.default.port'], '3306'),
        'database' => (string) config_env_any(['DB_DATABASE', 'database.default.database'], 'sapa_lppm'),
        'username' => (string) config_env_any(['DB_USERNAME', 'database.default.username'], 'root'),
        'password' => (string) config_env_any(['DB_PASSWORD', 'database.default.password'], ''),
        'charset' => (string) config_env_any(['DB_CHARSET', 'database.default.charset'], 'utf8mb4'),
    ],
];
