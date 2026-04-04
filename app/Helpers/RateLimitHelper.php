<?php

declare(strict_types=1);

if (!function_exists('rateLimitConsume')) {
    function rateLimitConsume(string $key, int $maxAttempts, int $windowSeconds): array
    {
        $maxAttempts = max(1, $maxAttempts);
        $windowSeconds = max(1, $windowSeconds);
        $now = time();
        $windowStart = $now - $windowSeconds;

        $state = rateLimitReadState();
        $bucket = is_array($state[$key] ?? null) ? $state[$key] : [];
        $hits = array_values(array_filter(
            array_map('intval', (array) ($bucket['hits'] ?? [])),
            static fn (int $hit): bool => $hit >= $windowStart
        ));

        if (count($hits) >= $maxAttempts) {
            $oldest = min($hits);
            $retryAfter = max(1, ($oldest + $windowSeconds) - $now);
            $bucket['hits'] = $hits;
            $state[$key] = $bucket;
            rateLimitWriteState($state);

            return [
                'allowed' => false,
                'retry_after' => $retryAfter,
                'remaining' => 0,
            ];
        }

        $hits[] = $now;
        $bucket['hits'] = $hits;
        $state[$key] = $bucket;
        rateLimitWriteState($state);

        return [
            'allowed' => true,
            'retry_after' => 0,
            'remaining' => max(0, $maxAttempts - count($hits)),
        ];
    }
}

if (!function_exists('rateLimitReadState')) {
    function rateLimitReadState(): array
    {
        $file = rateLimitStateFile();
        if (!is_file($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('rateLimitWriteState')) {
    function rateLimitWriteState(array $state): void
    {
        $file = rateLimitStateFile();
        @file_put_contents($file, json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
}

if (!function_exists('rateLimitStateFile')) {
    function rateLimitStateFile(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/runtime/security';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . '/rate_limits.json';
    }
}

