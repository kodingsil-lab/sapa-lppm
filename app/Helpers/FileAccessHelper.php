<?php

declare(strict_types=1);

if (!function_exists('isExternalFileUrl')) {
    function isExternalFileUrl(string $value): bool
    {
        return preg_match('#^https?://#i', trim($value)) === 1;
    }
}

if (!function_exists('isAllowedExternalFileUrl')) {
    function isAllowedExternalFileUrl(string $value): bool
    {
        if (!isExternalFileUrl($value)) {
            return false;
        }

        $host = strtolower(trim((string) parse_url(trim($value), PHP_URL_HOST)));
        if ($host === '') {
            return false;
        }

        $allowedHosts = [
            'drive.google.com',
            'docs.google.com',
            'lh3.googleusercontent.com',
            'storage.googleapis.com',
        ];

        foreach ($allowedHosts as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('normalizeProjectRelativePath')) {
    function normalizeProjectRelativePath(string $value): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($value)), '/');
        if ($normalized === '' || str_contains($normalized, '../')) {
            return '';
        }

        return $normalized;
    }
}

if (!function_exists('isSafeProjectRelativePathUnder')) {
    function isSafeProjectRelativePathUnder(string $value, string $allowedPrefix): bool
    {
        $normalized = normalizeProjectRelativePath($value);
        $prefix = trim(str_replace('\\', '/', $allowedPrefix), '/');
        if ($normalized === '' || $prefix === '') {
            return false;
        }

        return str_starts_with($normalized, $prefix . '/');
    }
}

if (!function_exists('buildLetterAttachmentUrl')) {
    function buildLetterAttachmentUrl(string $basePath, int $letterId, string $slot, string $path): string
    {
        $trimmedPath = trim($path);
        if ($trimmedPath === '') {
            return '';
        }

        return $basePath . '/?route=letters-attachment&id=' . $letterId . '&slot=' . urlencode($slot);
    }
}

if (!function_exists('buildStatusEvidenceUrl')) {
    function buildStatusEvidenceUrl(string $basePath, string $activityType, int $activityId, int $outputTypeId, string $path): string
    {
        $trimmedPath = trim($path);
        if ($trimmedPath === '') {
            return '';
        }

        return $basePath
            . '/?route=status-luaran-file&activity_type=' . urlencode($activityType)
            . '&activity_id=' . $activityId
            . '&output_type_id=' . $outputTypeId;
    }
}

if (!function_exists('buildUserAvatarUrl')) {
    function buildUserAvatarUrl(string $basePath, int $userId, string $avatar): string
    {
        if ($userId <= 0 || trim($avatar) === '') {
            return '';
        }

        return $basePath . '/?route=users-avatar&id=' . $userId;
    }
}
