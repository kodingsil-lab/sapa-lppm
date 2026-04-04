<?php

declare(strict_types=1);

class BaseController
{
    protected array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config.php';

        // Global helper untuk utilitas terbilang.
        $helperFile = __DIR__ . '/../Helpers/terbilang_helper.php';
        if (is_file($helperFile)) {
            require_once $helperFile;
            if (function_exists('helper')) {
                helper('terbilang');
            }
        }

        // Global helper utilitas kontrak (format rupiah/tahap/tanggal).
        $contractHelperFile = __DIR__ . '/../Helpers/contract_pdf_helper.php';
        if (is_file($contractHelperFile)) {
            require_once $contractHelperFile;
            if (function_exists('helper')) {
                helper('contract_pdf');
            }
        }

        $fileAccessHelperFile = __DIR__ . '/../Helpers/FileAccessHelper.php';
        if (is_file($fileAccessHelperFile)) {
            require_once $fileAccessHelperFile;
        }

        $ciValidationHelperFile = __DIR__ . '/../Helpers/CiValidationHelper.php';
        if (is_file($ciValidationHelperFile)) {
            require_once $ciValidationHelperFile;
        }
    }

    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        require __DIR__ . '/../Views/layouts/header.php';
        require $viewFile;
        require __DIR__ . '/../Views/layouts/footer.php';
    }

    /**
     * @return array{valid: bool, errors: array<string, string>, validated: array<string, mixed>}
     */
    protected function validatePayload(array $data, array $rules, array $messages = []): array
    {
        return ciValidateData($data, $rules, $messages);
    }

    protected function firstValidationError(array $errors, string $fallback = 'Input tidak valid.'): string
    {
        if ($errors === []) {
            return $fallback;
        }

        $first = array_values($errors)[0] ?? $fallback;
        return trim((string) $first) !== '' ? (string) $first : $fallback;
    }

    protected function appBasePath(): string
    {
        return rtrim((string) (parse_url((string) ($this->config['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
    }

    protected function redirectToPath(string $path, array $query = [], int $statusCode = 302): void
    {
        $basePath = $this->appBasePath();
        $normalizedPath = '/' . ltrim($path, '/');
        $target = ($basePath !== '' ? $basePath : '') . ($path === '' ? '' : $normalizedPath);

        if ($query !== []) {
            $target .= '?' . http_build_query($query);
        }

        header('Location: ' . $target, true, $statusCode);
        exit;
    }

    protected function adminDashboardPath(): string
    {
        return normalizeRoleName((string) authRole()) === 'kepala_lppm'
            ? 'dashboard-kepala-lppm'
            : 'dashboard-admin';
    }
}
