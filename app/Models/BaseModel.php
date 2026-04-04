<?php

declare(strict_types=1);

class BaseModel
{
    protected array $config;
    private ?bool $runtimeSchemaSyncAllowed = null;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config.php';
    }

    protected function shouldAutoManageSchema(): bool
    {
        if ($this->runtimeSchemaSyncAllowed !== null) {
            return $this->runtimeSchemaSyncAllowed;
        }

        require_once __DIR__ . '/../Helpers/EnvHelper.php';

        $rootPath = dirname(__DIR__, 2);
        $environment = strtolower((string) appEnv('CI_ENVIRONMENT', 'development', $rootPath));
        $override = strtolower((string) appEnv('APP_ALLOW_RUNTIME_SCHEMA_SYNC', '', $rootPath));

        if (in_array($override, ['1', 'true', 'yes', 'on'], true)) {
            return $this->runtimeSchemaSyncAllowed = true;
        }

        return $this->runtimeSchemaSyncAllowed = ($environment !== 'production');
    }

    protected function requireKeys(array $payload, array $requiredKeys, string $context): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new InvalidArgumentException($context . ': field "' . $key . '" wajib diisi.');
            }
        }
    }

    protected function readString(array $payload, string $key, string $default = ''): string
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return $default;
        }

        return trim((string) $payload[$key]);
    }

    protected function readNullableString(array $payload, string $key): ?string
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        $value = trim((string) $payload[$key]);

        return $value === '' ? null : $value;
    }

    protected function readInt(array $payload, string $key, int $default = 0): int
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
            return $default;
        }

        return (int) $payload[$key];
    }

    protected function normalizeEnum(string $value, array $allowed, string $fallback): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, $allowed, true) ? $normalized : $fallback;
    }
}
