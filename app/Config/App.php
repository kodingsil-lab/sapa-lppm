<?php

declare(strict_types=1);

if (!function_exists('app_config')) {
    function app_config(): array
    {
        return require __DIR__ . '/../../config.php';
    }
}
