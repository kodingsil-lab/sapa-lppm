<?php

declare(strict_types=1);

require_once __DIR__ . '/EnvHelper.php';

if (!function_exists('ensureSessionStarted')) {
    function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $isHttps = (
                (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
                || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
            );
            $params = session_get_cookie_params();
            session_set_cookie_params([
                'lifetime' => (int) ($params['lifetime'] ?? 0),
                'path' => (string) ($params['path'] ?? '/'),
                'domain' => (string) ($params['domain'] ?? ''),
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }
}

if (!function_exists('authIsHttpsRequest')) {
    function authIsHttpsRequest(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
        );
    }
}

if (!function_exists('authClientIp')) {
    function authClientIp(): string
    {
        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remoteAddr !== '' && filter_var($remoteAddr, FILTER_VALIDATE_IP) !== false) {
            return $remoteAddr;
        }

        return 'unknown';
    }
}

if (!function_exists('authSessionIdleTimeoutSeconds')) {
    function authSessionIdleTimeoutSeconds(): int
    {
        $raw = (int) appEnv('SESSION_IDLE_TIMEOUT', 7200, dirname(__DIR__, 2));
        return $raw > 0 ? $raw : 7200;
    }
}

if (!function_exists('authApplySessionIdleTimeout')) {
    function authApplySessionIdleTimeout(): void
    {
        ensureSessionStarted();
        if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
            return;
        }

        $now = time();
        $last = (int) ($_SESSION['auth_last_activity'] ?? 0);
        $timeout = authSessionIdleTimeoutSeconds();
        if ($last > 0 && ($now - $last) > $timeout) {
            $_SESSION = [];
            session_destroy();
            return;
        }

        $_SESSION['auth_last_activity'] = $now;
    }
}

if (!function_exists('authRememberSuccessfulLogin')) {
    function authRememberSuccessfulLogin(): void
    {
        ensureSessionStarted();
        $_SESSION['auth_last_activity'] = time();
    }
}

if (!function_exists('authLoginThrottleConfig')) {
    function authLoginThrottleConfig(): array
    {
        $maxAttempts = (int) appEnv('AUTH_LOGIN_MAX_ATTEMPTS', 5, dirname(__DIR__, 2));
        $windowSeconds = (int) appEnv('AUTH_LOGIN_ATTEMPT_WINDOW', 900, dirname(__DIR__, 2));
        $lockSeconds = (int) appEnv('AUTH_LOGIN_LOCK_SECONDS', 900, dirname(__DIR__, 2));

        return [
            'max_attempts' => $maxAttempts > 0 ? $maxAttempts : 5,
            'window_seconds' => $windowSeconds > 0 ? $windowSeconds : 900,
            'lock_seconds' => $lockSeconds > 0 ? $lockSeconds : 900,
        ];
    }
}

if (!function_exists('authLoginThrottleFile')) {
    function authLoginThrottleFile(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/runtime/security';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir . '/login_attempts.json';
    }
}

if (!function_exists('authLoginThrottleReadState')) {
    function authLoginThrottleReadState(): array
    {
        $file = authLoginThrottleFile();
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

if (!function_exists('authLoginThrottleWriteState')) {
    function authLoginThrottleWriteState(array $state): void
    {
        $file = authLoginThrottleFile();
        @file_put_contents($file, json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
}

if (!function_exists('authLoginThrottleKey')) {
    function authLoginThrottleKey(string $login): string
    {
        $normalizedLogin = strtolower(trim($login));
        $ip = authClientIp();
        return hash('sha256', $normalizedLogin . '|' . $ip);
    }
}

if (!function_exists('authLoginThrottleStatus')) {
    function authLoginThrottleStatus(string $login): array
    {
        $config = authLoginThrottleConfig();
        $key = authLoginThrottleKey($login);
        $state = authLoginThrottleReadState();
        $entry = is_array($state[$key] ?? null) ? $state[$key] : [];

        $now = time();
        $windowStart = $now - (int) $config['window_seconds'];
        $attempts = array_values(array_filter(
            array_map('intval', (array) ($entry['attempts'] ?? [])),
            static fn (int $ts): bool => $ts >= $windowStart
        ));

        $blockedUntil = (int) ($entry['blocked_until'] ?? 0);
        $isBlocked = $blockedUntil > $now;

        $entry['attempts'] = $attempts;
        if (!$isBlocked) {
            $entry['blocked_until'] = 0;
        }
        $state[$key] = $entry;
        authLoginThrottleWriteState($state);

        return [
            'blocked' => $isBlocked,
            'retry_after' => $isBlocked ? max(1, $blockedUntil - $now) : 0,
            'remaining' => max(0, (int) $config['max_attempts'] - count($attempts)),
        ];
    }
}

if (!function_exists('authLoginThrottleRegisterFailure')) {
    function authLoginThrottleRegisterFailure(string $login): void
    {
        $config = authLoginThrottleConfig();
        $key = authLoginThrottleKey($login);
        $state = authLoginThrottleReadState();
        $entry = is_array($state[$key] ?? null) ? $state[$key] : [];

        $now = time();
        $windowStart = $now - (int) $config['window_seconds'];
        $attempts = array_values(array_filter(
            array_map('intval', (array) ($entry['attempts'] ?? [])),
            static fn (int $ts): bool => $ts >= $windowStart
        ));
        $attempts[] = $now;

        $entry['attempts'] = $attempts;
        if (count($attempts) >= (int) $config['max_attempts']) {
            $entry['blocked_until'] = $now + (int) $config['lock_seconds'];
        }

        $state[$key] = $entry;
        authLoginThrottleWriteState($state);
    }
}

if (!function_exists('authLoginThrottleReset')) {
    function authLoginThrottleReset(string $login): void
    {
        $key = authLoginThrottleKey($login);
        $state = authLoginThrottleReadState();
        unset($state[$key]);
        authLoginThrottleWriteState($state);
    }
}

if (!function_exists('authUser')) {
    function authUser(): ?array
    {
        ensureSessionStarted();
        authApplySessionIdleTimeout();

        return $_SESSION['auth_user'] ?? null;
    }
}

if (!function_exists('authUserId')) {
    function authUserId(): ?int
    {
        $user = authUser();
        if ($user === null) {
            return null;
        }

        return (int) ($user['id'] ?? 0);
    }
}

if (!function_exists('authRole')) {
    function normalizeRoleName(?string $role): string
    {
        $value = strtolower(trim((string) $role));
        if ($value === 'admin_lppm' || $value === 'kepala lppm') {
            return 'kepala_lppm';
        }

        return $value;
    }

    function authBaseRole(): ?string
    {
        $user = authUser();
        if ($user === null) {
            return null;
        }

        return normalizeRoleName((string) ($user['role'] ?? ''));
    }

    function authAvailableRoles(): array
    {
        $baseRole = authBaseRole();
        if ($baseRole === null || $baseRole === '') {
            return [];
        }

        if ($baseRole === 'kepala_lppm') {
            return ['kepala_lppm', 'dosen'];
        }

        return [$baseRole];
    }

    function authRole(): ?string
    {
        ensureSessionStarted();

        $baseRole = authBaseRole();
        if ($baseRole === null) {
            return null;
        }

        $availableRoles = authAvailableRoles();
        $overrideRole = normalizeRoleName((string) ($_SESSION['auth_active_role'] ?? ''));
        if ($overrideRole !== '' && in_array($overrideRole, $availableRoles, true)) {
            return $overrideRole;
        }

        return $baseRole;
    }
}

if (!function_exists('isAdminPanelRole')) {
    function isAdminPanelRole(?string $role = null): bool
    {
        $resolvedRole = $role ?? authRole();
        if ($resolvedRole === null) {
            return false;
        }

        $normalized = normalizeRoleName($resolvedRole);

        return in_array($normalized, ['admin', 'kepala_lppm'], true);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return authUser() !== null;
    }
}

if (!function_exists('isImpersonating')) {
    function isImpersonating(): bool
    {
        ensureSessionStarted();

        return isset($_SESSION['auth_impersonator']) && is_array($_SESSION['auth_impersonator']);
    }
}

if (!function_exists('impersonatorUser')) {
    function impersonatorUser(): ?array
    {
        ensureSessionStarted();
        $data = $_SESSION['auth_impersonator'] ?? null;
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }
}

if (!function_exists('impersonatorRole')) {
    function impersonatorRole(): ?string
    {
        $user = impersonatorUser();
        if ($user === null) {
            return null;
        }

        return normalizeRoleName((string) ($user['role'] ?? ''));
    }
}

if (!function_exists('checkRole')) {
    function checkRole(array $allowedRoles): bool
    {
        $role = authRole();
        if ($role === null) {
            return false;
        }

        $normalizedAllowedRoles = array_map(
            static fn ($allowedRole): string => normalizeRoleName((string) $allowedRole),
            $allowedRoles
        );

        return in_array(normalizeRoleName($role), $normalizedAllowedRoles, true);
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken(): string
    {
        ensureSessionStarted();

        $token = (string) ($_SESSION['csrf_token'] ?? '');
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $token;
        }

        return $token;
    }
}

if (!function_exists('isValidCsrfToken')) {
    function isValidCsrfToken(?string $token): bool
    {
        $provided = trim((string) $token);
        $expected = csrfToken();

        return $provided !== '' && hash_equals($expected, $provided);
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
