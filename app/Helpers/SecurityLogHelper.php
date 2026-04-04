<?php

declare(strict_types=1);

if (!function_exists('securityLog')) {
    function securityLog(string $event, array $context = []): void
    {
        $event = trim($event);
        if ($event === '') {
            return;
        }

        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'time' => date('c'),
            'event' => $event,
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'route' => (string) ($_GET['route'] ?? ''),
            'context' => securityLogSanitizeContext($context),
        ];

        $line = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($line)) {
            return;
        }

        @file_put_contents($dir . '/security.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('securityLogSanitizeContext')) {
    function securityLogSanitizeContext(array $context): array
    {
        $masked = [];
        $sensitiveKeyPattern = '/pass|password|token|secret|key|cookie|csrf/i';

        foreach ($context as $key => $value) {
            $stringKey = (string) $key;

            if (preg_match($sensitiveKeyPattern, $stringKey) === 1) {
                $masked[$stringKey] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $masked[$stringKey] = securityLogSanitizeContext($value);
                continue;
            }

            if (is_object($value)) {
                $masked[$stringKey] = '[object]';
                continue;
            }

            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                $masked[$stringKey] = $stringValue;
                continue;
            }

            if ($stringKey === 'email' || str_ends_with($stringKey, '_email')) {
                $masked[$stringKey] = securityMaskEmail($stringValue);
                continue;
            }

            if (preg_match('/nidn|nuptk|nik|identity|identifier/i', $stringKey) === 1) {
                $masked[$stringKey] = securityMaskIdentity($stringValue);
                continue;
            }

            $masked[$stringKey] = mb_strimwidth($stringValue, 0, 220, '...');
        }

        return $masked;
    }
}

if (!function_exists('securityMaskEmail')) {
    function securityMaskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '***';
        }

        [$name, $domain] = explode('@', $email, 2);
        $name = trim($name);
        if ($name === '') {
            return '***@' . $domain;
        }

        if (strlen($name) <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2));
        }

        return $maskedName . '@' . $domain;
    }
}

if (!function_exists('securityMaskIdentity')) {
    function securityMaskIdentity(string $value): string
    {
        $value = trim($value);
        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($value, -4);
    }
}

