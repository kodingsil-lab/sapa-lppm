<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Makassar');

$autoloadFile = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
}
require_once __DIR__ . '/app/Helpers/DatabaseHelper.php';
require_once __DIR__ . '/app/Helpers/AuthHelper.php';
require_once __DIR__ . '/app/Helpers/AssetHelper.php';
require_once __DIR__ . '/app/Helpers/RateLimitHelper.php';
require_once __DIR__ . '/app/Helpers/SecurityLogHelper.php';
require_once __DIR__ . '/app/Middleware/RoleMiddleware.php';
ensureSessionStarted();

function redirectToPrettyPath(string $basePath, string $path, array $query = [], int $statusCode = 302): never
{
    $normalizedPath = '/' . ltrim($path, '/');
    $target = ($basePath !== '' ? $basePath : '') . ($path === '' ? '' : $normalizedPath);
    if ($query !== []) {
        $target .= '?' . http_build_query($query);
    }

    header('Location: ' . $target, true, $statusCode);
    exit;
}

$appConfig = require __DIR__ . '/config.php';
$environment = strtolower((string) appEnv('CI_ENVIRONMENT', 'development', __DIR__));

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$requestSchemeHeader = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
$isHttps = (
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
    || $requestSchemeHeader === 'https'
);

$appUrl = (string) ($appConfig['app']['url'] ?? '');
$appUrlUsesHttps = str_starts_with(strtolower($appUrl), 'https://');
$shouldForceHttps = $environment === 'production' || $appUrlUsesHttps;

if ($shouldForceHttps && !$isHttps && !is_cli()) {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($host !== '') {
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
}

$requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$basePath = (string) (parse_url((string) ($appConfig['app']['url'] ?? ''), PHP_URL_PATH) ?? '');
$basePath = rtrim($basePath, '/');

$relativePath = $requestPath;
if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $relativePath = substr($requestPath, strlen($basePath));
}
$relativePath = trim($relativePath, '/');

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($requestMethod, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, POST, PUT, PATCH, DELETE, HEAD');
    echo '405 - Method not allowed';
    exit;
}

if (
    !isset($_GET['route'])
    && $relativePath === 'dashboard-admin'
    && authRole() === 'kepala_lppm'
) {
    $target = ($basePath !== '' ? $basePath : '') . '/dashboard-kepala-lppm';
    $queryString = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 302);
    exit;
}

// Redirect backward-compatible old pretty path ke pretty path baru.
if (
    !isset($_GET['route'])
    && ($relativePath === 'my-letters' || str_starts_with($relativePath, 'my-letters/'))
) {
    $suffix = substr($relativePath, strlen('my-letters'));
    $target = ($basePath !== '' ? $basePath : '') . '/surat-saya' . ($suffix !== false ? $suffix : '');
    $queryString = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 302);
    exit;
}

// Canonical redirect: normalisasi URL query route lama ke pretty URL.
$routeQuery = trim((string) ($_GET['route'] ?? ''));
$prettyPathByRoute = [
    'login' => 'login',
    'register' => 'register',
    'forgot-password' => 'forgot-password',
    'reset-password' => 'reset-password',
    'dashboard' => 'dashboard',
    'dashboard-dosen' => 'dashboard-dosen',
    'dashboard-admin' => 'dashboard-admin',
    'ajukan-surat' => 'ajukan-surat',
    'my-letters' => 'surat-saya',
    'my-letters-detail' => 'surat-saya',
    'data-penelitian' => 'data/penelitian',
    'data-penelitian-create' => 'data/penelitian/create',
    'data-penelitian-show' => 'data/penelitian',
    'data-penelitian-edit' => 'data/penelitian',
    'data-pengabdian' => 'data/pengabdian',
    'data-pengabdian-create' => 'data/pengabdian/create',
    'data-pengabdian-show' => 'data/pengabdian',
    'data-pengabdian-edit' => 'data/pengabdian',
    'data-hilirisasi' => 'data/hilirisasi',
    'data-hilirisasi-create' => 'data/hilirisasi/create',
    'data-hilirisasi-show' => 'data/hilirisasi',
    'data-hilirisasi-edit' => 'data/hilirisasi',
    'status-luaran' => 'status-luaran',
    'status-luaran-detail' => 'status-luaran',
    'status-luaran-save' => 'status-luaran/simpan',
    'profile' => 'profil',
    'profile-update' => 'profil/simpan',
    'letters' => 'persuratan',
    'letters-show' => 'persuratan',
    'archives' => 'arsip-surat',
    'archives-delete' => 'arsip-surat/hapus',
    'users' => 'pengguna',
    'users-dosen-show' => 'pengguna/dosen',
    'users-dosen-edit' => 'pengguna/dosen',
    'users-dosen-update' => 'pengguna/dosen/simpan',
    'users-dosen-delete' => 'pengguna/dosen/hapus',
    'users-dosen-bulk-delete' => 'pengguna/dosen/hapus-terpilih',
    'users-change-role' => 'pengguna/ganti-role',
    'users-import' => 'pengguna/impor',
    'users-import-template' => 'pengguna/impor/template',
    'users-import-preview' => 'pengguna/impor/preview',
    'users-import-store' => 'pengguna/impor/simpan',
    'users-export' => 'pengguna/ekspor',
    'users-export-download' => 'pengguna/ekspor/unduh',
    'users-export-download-pdf' => 'pengguna/ekspor/pdf',
    'logs' => 'log-aktivitas',
    'logs-bulk-delete' => 'log-aktivitas/hapus',
    'master-data-outputs' => 'master-data/luaran',
    'master-data-outputs-create' => 'master-data/luaran/create',
    'master-data-outputs-edit' => 'master-data/luaran',
    'master-data-outputs-save' => 'master-data/luaran/simpan',
    'master-data-outputs-delete' => 'master-data/luaran/hapus',
    'master-data-schemes' => 'master-data/skema',
    'master-data-schemes-create' => 'master-data/skema/create',
    'master-data-schemes-edit' => 'master-data/skema',
    'master-data-schemes-save' => 'master-data/skema/simpan',
    'master-data-schemes-delete' => 'master-data/skema/hapus',
    'master-data-scopes' => 'master-data/ruang-lingkup',
    'master-data-scopes-create' => 'master-data/ruang-lingkup/create',
    'master-data-scopes-edit' => 'master-data/ruang-lingkup',
    'master-data-scopes-save' => 'master-data/ruang-lingkup/simpan',
    'master-data-scopes-delete' => 'master-data/ruang-lingkup/hapus',
    'master-data-funding-sources' => 'master-data/sumber-dana',
    'master-data-funding-sources-create' => 'master-data/sumber-dana/create',
    'master-data-funding-sources-edit' => 'master-data/sumber-dana',
    'master-data-funding-sources-save' => 'master-data/sumber-dana/simpan',
    'master-data-funding-sources-delete' => 'master-data/sumber-dana/hapus',
    'users-profile' => 'profil-admin',
    'users-upload-signature' => 'profil-admin/tanda-tangan',
    'settings-letter-number' => 'pengaturan/nomor-surat',
    'settings-contract' => 'pengaturan/kontrak',
    'settings-contract-detail' => 'pengaturan/kontrak/detail',
    'settings-contract-delete' => 'pengaturan/kontrak/hapus',
    'letters-store' => 'letters/store',
    'surat-kontrak-submit' => 'surat-kontrak/submit',
    'auth-switch-role' => 'auth/ganti-role',
    'auth-impersonate' => 'auth/impersonate',
    'auth-impersonate-exit' => 'auth/impersonate-exit',
    'data-penelitian-store' => 'data/penelitian/simpan',
    'data-penelitian-update' => 'data/penelitian/perbarui',
    'data-penelitian-delete' => 'data/penelitian/hapus',
    'data-pengabdian-store' => 'data/pengabdian/simpan',
    'data-pengabdian-update' => 'data/pengabdian/perbarui',
    'data-pengabdian-delete' => 'data/pengabdian/hapus',
    'data-hilirisasi-store' => 'data/hilirisasi/simpan',
    'data-hilirisasi-update' => 'data/hilirisasi/perbarui',
    'data-hilirisasi-delete' => 'data/hilirisasi/hapus',
    'letters-approve' => 'persuratan',
    'letters-reject' => 'persuratan',
    'letters-head-direct-update' => 'persuratan',
    'letters-generate-pdf' => 'persuratan',
    'letters-download-pdf' => 'persuratan',
];
if ($routeQuery !== '' && isset($prettyPathByRoute[$routeQuery])) {
    $targetPath = $prettyPathByRoute[$routeQuery];
    if ($routeQuery === 'dashboard-admin') {
        $targetPath = authRole() === 'kepala_lppm' ? 'dashboard-kepala-lppm' : 'dashboard-admin';
    }
    if ($relativePath !== $targetPath || isset($_GET['route'])) {
        $query = $_GET;
        unset($query['route']);

        // Pretty khusus ajukan-surat dengan kategori.
        if ($routeQuery === 'ajukan-surat' && isset($query['activity_type'])) {
            $activityType = strtolower(trim((string) $query['activity_type']));
            if (in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
                $targetPath = 'ajukan-surat/' . $activityType;
                unset($query['activity_type']);
            }
        }

        if ($routeQuery === 'my-letters-detail') {
            $number = trim((string) ($query['number'] ?? ''));
            if ($number !== '') {
                $targetPath = 'surat-saya/number/' . rawurlencode($number);
                unset($query['number']);
            } elseif (isset($query['id']) && (int) $query['id'] > 0) {
                $targetPath = 'surat-saya/' . (int) $query['id'];
                unset($query['id']);
            }
        }

        if ($routeQuery === 'letters-show' && isset($query['id']) && (int) $query['id'] > 0) {
            $targetPath = 'persuratan/' . (int) $query['id'];
            unset($query['id']);
        }

        if (in_array($routeQuery, ['letters-approve', 'letters-reject', 'letters-head-direct-update', 'letters-generate-pdf', 'letters-download-pdf'], true) && isset($query['id']) && (int) $query['id'] > 0) {
            $suffixMap = [
                'letters-approve' => '/approve',
                'letters-reject' => '/reject',
                'letters-head-direct-update' => '/head-update',
                'letters-generate-pdf' => '/generate-pdf',
                'letters-download-pdf' => '/download',
            ];
            $targetPath = 'persuratan/' . (int) $query['id'] . ($suffixMap[$routeQuery] ?? '');
            unset($query['id']);
        }

        if (in_array($routeQuery, ['users-dosen-show', 'users-dosen-edit'], true) && isset($query['id']) && (int) $query['id'] > 0) {
            $targetPath = 'pengguna/dosen/' . (int) $query['id'] . ($routeQuery === 'users-dosen-edit' ? '/edit' : '');
            unset($query['id']);
        }

        if (in_array($routeQuery, ['data-penelitian-show', 'data-penelitian-edit'], true) && isset($query['id']) && (int) $query['id'] > 0) {
            $targetPath = 'data/penelitian/' . (int) $query['id'] . ($routeQuery === 'data-penelitian-edit' ? '/edit' : '');
            unset($query['id']);
        }

        if (in_array($routeQuery, ['data-pengabdian-show', 'data-pengabdian-edit'], true) && isset($query['id']) && (int) $query['id'] > 0) {
            $targetPath = 'data/pengabdian/' . (int) $query['id'] . ($routeQuery === 'data-pengabdian-edit' ? '/edit' : '');
            unset($query['id']);
        }

        if (
            in_array($routeQuery, ['data-hilirisasi-show', 'data-hilirisasi-edit'], true)
            && isset($query['id'])
            && (int) $query['id'] > 0
        ) {
            $isEditRoute = $routeQuery === 'data-hilirisasi-edit';
            $targetPath = 'data/hilirisasi/' . (int) $query['id'] . ($isEditRoute ? '/edit' : '');
            unset($query['id']);
        }

        if (
            $routeQuery === 'status-luaran-detail'
            && isset($query['activity_type'], $query['activity_id'])
            && in_array(strtolower((string) $query['activity_type']), ['penelitian', 'pengabdian', 'hilirisasi'], true)
            && (int) $query['activity_id'] > 0
        ) {
            $activityType = strtolower((string) $query['activity_type']);
            $targetPath = 'status-luaran/' . $activityType . '/' . (int) $query['activity_id'];
            unset($query['activity_type'], $query['activity_id']);
        }

        if (in_array($routeQuery, ['master-data-outputs-edit', 'master-data-schemes-edit', 'master-data-scopes-edit', 'master-data-funding-sources-edit'], true) && isset($query['id']) && (int) $query['id'] > 0) {
            $prefixMap = [
                'master-data-outputs-edit' => 'master-data/luaran/',
                'master-data-schemes-edit' => 'master-data/skema/',
                'master-data-scopes-edit' => 'master-data/ruang-lingkup/',
                'master-data-funding-sources-edit' => 'master-data/sumber-dana/',
            ];
            $targetPath = ($prefixMap[$routeQuery] ?? '') . (int) $query['id'] . '/edit';
            unset($query['id']);
        }

        $target = ($basePath !== '' ? $basePath : '') . '/' . $targetPath;
        if (!empty($query)) {
            $target .= '?' . http_build_query($query);
        }

        header('Location: ' . $target, true, 302);
        exit;
    }
}

// Important: if query already has ?route=..., do not override it from pretty-path mapping.
$hasRouteQuery = isset($_GET['route']) && trim((string) $_GET['route']) !== '';
if (!$hasRouteQuery) {
    if ($relativePath === 'login') {
        $_GET['route'] = $requestMethod === 'POST' ? 'login-submit' : 'login';
    }

    if ($relativePath === 'register') {
        $_GET['route'] = $requestMethod === 'POST' ? 'register-submit' : 'register';
    }

    if (preg_match('#^verify/([A-Za-z0-9_-]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'verify';
        $_GET['token'] = $matches[1];
    }

    if (preg_match('#^letters/([0-9]+)/preview$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'letters-preview';
        $_GET['id'] = $matches[1];
    }

    if ($relativePath === 'dashboard') {
        $_GET['route'] = 'dashboard';
    }

    if ($relativePath === 'dashboard-dosen') {
        $_GET['route'] = 'dashboard-dosen';
    }

    if ($relativePath === 'dashboard-admin') {
        $_GET['route'] = 'dashboard-admin';
    }

    if ($relativePath === 'dashboard-kepala-lppm') {
        $_GET['route'] = 'dashboard-admin';
    }

    if (preg_match('#^ajukan-surat/(penelitian|pengabdian|hilirisasi)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'ajukan-surat';
        $_GET['activity_type'] = strtolower((string) $matches[1]);
    }

    if ($relativePath === 'ajukan-surat') {
        $_GET['route'] = 'ajukan-surat';
    }

    if ($relativePath === 'letters/store') {
        $_GET['route'] = 'letters-store';
    }

    if ($relativePath === 'surat-kontrak/submit') {
        $_GET['route'] = 'surat-kontrak-submit';
    }

    if ($relativePath === 'surat-saya') {
        $_GET['route'] = 'my-letters';
    }

    if ($relativePath === 'my-letters') {
        $_GET['route'] = 'my-letters';
    }

    if (preg_match('#^my-letters/number/(.+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'my-letters-detail';
        $_GET['number'] = urldecode((string) $matches[1]);
    }

    if (preg_match('#^my-letters/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'my-letters-detail';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^surat-saya/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'my-letters-detail';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^surat-saya/number/(.+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'my-letters-detail';
        $_GET['number'] = urldecode((string) $matches[1]);
    }

    if ($relativePath === 'forgot-password') {
        $_GET['route'] = $requestMethod === 'POST' ? 'forgot-password-submit' : 'forgot-password';
    }

    if (preg_match('#^reset-password/([A-Za-z0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = $requestMethod === 'POST' ? 'reset-password-submit' : 'reset-password';
        $_GET['token'] = $matches[1];
    }

    if ($relativePath === 'reset-password') {
        $_GET['route'] = $requestMethod === 'POST' ? 'reset-password-submit' : 'reset-password';
    }

    if ($relativePath === 'data/penelitian') {
        $_GET['route'] = 'data-penelitian';
    }

    if ($relativePath === 'data/penelitian/create') {
        $_GET['route'] = 'data-penelitian-create';
    }

    if ($relativePath === 'data/penelitian/simpan') {
        $_GET['route'] = 'data-penelitian-store';
    }

    if ($relativePath === 'data/penelitian/perbarui') {
        $_GET['route'] = 'data-penelitian-update';
    }

    if ($relativePath === 'data/penelitian/hapus') {
        $_GET['route'] = 'data-penelitian-delete';
    }

    if ($relativePath === 'data/pengabdian') {
        $_GET['route'] = 'data-pengabdian';
    }

    if ($relativePath === 'data/pengabdian/create') {
        $_GET['route'] = 'data-pengabdian-create';
    }

    if ($relativePath === 'data/pengabdian/simpan') {
        $_GET['route'] = 'data-pengabdian-store';
    }

    if ($relativePath === 'data/pengabdian/perbarui') {
        $_GET['route'] = 'data-pengabdian-update';
    }

    if ($relativePath === 'data/pengabdian/hapus') {
        $_GET['route'] = 'data-pengabdian-delete';
    }

    if ($relativePath === 'data/hilirisasi') {
        $_GET['route'] = 'data-hilirisasi';
    }

    if ($relativePath === 'data/hilirisasi/create') {
        $_GET['route'] = 'data-hilirisasi-create';
    }

    if ($relativePath === 'data/hilirisasi/simpan') {
        $_GET['route'] = 'data-hilirisasi-store';
    }

    if ($relativePath === 'data/hilirisasi/perbarui') {
        $_GET['route'] = 'data-hilirisasi-update';
    }

    if ($relativePath === 'data/hilirisasi/hapus') {
        $_GET['route'] = 'data-hilirisasi-delete';
    }

    if (preg_match('#^data/penelitian/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'data-penelitian-show';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^data/penelitian/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'data-penelitian-edit';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^data/pengabdian/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'data-pengabdian-show';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^data/pengabdian/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'data-pengabdian-edit';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^data/hilirisasi/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'data-hilirisasi-show';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^data/hilirisasi/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'data-hilirisasi-edit';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^status-luaran/(penelitian|pengabdian|hilirisasi)/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'status-luaran-detail';
        $_GET['activity_type'] = strtolower((string) $matches[1]);
        $_GET['activity_id'] = $matches[2];
    }

    if ($relativePath === 'status-luaran') {
        $_GET['route'] = 'status-luaran';
    }

    if ($relativePath === 'status-luaran/simpan') {
        $_GET['route'] = 'status-luaran-save';
    }

    if ($relativePath === 'profil') {
        $_GET['route'] = 'profile';
    }

    if ($relativePath === 'profil/simpan') {
        $_GET['route'] = 'profile-update';
    }

    if ($relativePath === 'persuratan') {
        $_GET['route'] = 'letters';
    }

    if (preg_match('#^persuratan/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'letters-show';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^persuratan/([0-9]+)/approve$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'letters-approve';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^persuratan/([0-9]+)/reject$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'letters-reject';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^persuratan/([0-9]+)/head-update$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'letters-head-direct-update';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^persuratan/([0-9]+)/generate-pdf$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'letters-generate-pdf';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^persuratan/([0-9]+)/download$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'letters-download-pdf';
        $_GET['id'] = $matches[1];
    }

    if ($relativePath === 'arsip-surat') {
        $_GET['route'] = 'archives';
    }

    if ($relativePath === 'arsip-surat/hapus') {
        $_GET['route'] = 'archives-delete';
    }

    if ($relativePath === 'pengguna') {
        $_GET['route'] = 'users';
    }

    if (preg_match('#^pengguna/dosen/([0-9]+)$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'users-dosen-show';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^pengguna/dosen/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'users-dosen-edit';
        $_GET['id'] = $matches[1];
    }

    if ($relativePath === 'pengguna/dosen/simpan') {
        $_GET['route'] = 'users-dosen-update';
    }

    if ($relativePath === 'pengguna/dosen/hapus') {
        $_GET['route'] = 'users-dosen-delete';
    }

    if ($relativePath === 'pengguna/dosen/hapus-terpilih') {
        $_GET['route'] = 'users-dosen-bulk-delete';
    }

    if ($relativePath === 'pengguna/ganti-role') {
        $_GET['route'] = 'users-change-role';
    }

    if ($relativePath === 'pengguna/impor') {
        $_GET['route'] = 'users-import';
    }

    if ($relativePath === 'pengguna/impor/template') {
        $_GET['route'] = 'users-import-template';
    }

    if ($relativePath === 'pengguna/impor/preview') {
        $_GET['route'] = 'users-import-preview';
    }

    if ($relativePath === 'pengguna/impor/simpan') {
        $_GET['route'] = 'users-import-store';
    }

    if ($relativePath === 'pengguna/ekspor') {
        $_GET['route'] = 'users-export';
    }

    if ($relativePath === 'pengguna/ekspor/unduh') {
        $_GET['route'] = 'users-export-download';
    }

    if ($relativePath === 'pengguna/ekspor/pdf') {
        $_GET['route'] = 'users-export-download-pdf';
    }

    if ($relativePath === 'log-aktivitas') {
        $_GET['route'] = 'logs';
    }

    if ($relativePath === 'log-aktivitas/hapus') {
        $_GET['route'] = 'logs-bulk-delete';
    }

    if ($relativePath === 'master-data/luaran') {
        $_GET['route'] = 'master-data-outputs';
    }

    if ($relativePath === 'master-data/luaran/create') {
        $_GET['route'] = 'master-data-outputs-create';
    }

    if ($relativePath === 'master-data/luaran/simpan') {
        $_GET['route'] = 'master-data-outputs-save';
    }

    if ($relativePath === 'master-data/luaran/hapus') {
        $_GET['route'] = 'master-data-outputs-delete';
    }

    if ($relativePath === 'master-data/skema') {
        $_GET['route'] = 'master-data-schemes';
    }

    if ($relativePath === 'master-data/skema/create') {
        $_GET['route'] = 'master-data-schemes-create';
    }

    if ($relativePath === 'master-data/skema/simpan') {
        $_GET['route'] = 'master-data-schemes-save';
    }

    if ($relativePath === 'master-data/skema/hapus') {
        $_GET['route'] = 'master-data-schemes-delete';
    }

    if ($relativePath === 'master-data/ruang-lingkup') {
        $_GET['route'] = 'master-data-scopes';
    }

    if ($relativePath === 'master-data/ruang-lingkup/create') {
        $_GET['route'] = 'master-data-scopes-create';
    }

    if ($relativePath === 'master-data/ruang-lingkup/simpan') {
        $_GET['route'] = 'master-data-scopes-save';
    }

    if ($relativePath === 'master-data/ruang-lingkup/hapus') {
        $_GET['route'] = 'master-data-scopes-delete';
    }

    if ($relativePath === 'master-data/sumber-dana') {
        $_GET['route'] = 'master-data-funding-sources';
    }

    if ($relativePath === 'master-data/sumber-dana/create') {
        $_GET['route'] = 'master-data-funding-sources-create';
    }

    if ($relativePath === 'master-data/sumber-dana/simpan') {
        $_GET['route'] = 'master-data-funding-sources-save';
    }

    if ($relativePath === 'master-data/sumber-dana/hapus') {
        $_GET['route'] = 'master-data-funding-sources-delete';
    }

    if (preg_match('#^master-data/luaran/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'master-data-outputs-edit';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^master-data/skema/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'master-data-schemes-edit';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^master-data/ruang-lingkup/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'master-data-scopes-edit';
        $_GET['id'] = $matches[1];
    }

    if (preg_match('#^master-data/sumber-dana/([0-9]+)/edit$#', $relativePath, $matches) === 1) {
        $_GET['route'] = 'master-data-funding-sources-edit';
        $_GET['id'] = $matches[1];
    }

    if ($relativePath === 'profil-admin') {
        $_GET['route'] = 'users-profile';
    }

    if ($relativePath === 'profil-admin/tanda-tangan') {
        $_GET['route'] = 'users-upload-signature';
    }

    if ($relativePath === 'pengaturan/nomor-surat') {
        $_GET['route'] = 'settings-letter-number';
    }

    if ($relativePath === 'pengaturan/kontrak') {
        $_GET['route'] = 'settings-contract';
    }

    if ($relativePath === 'pengaturan/kontrak/detail') {
        $_GET['route'] = 'settings-contract-detail';
    }

    if ($relativePath === 'pengaturan/kontrak/hapus') {
        $_GET['route'] = 'settings-contract-delete';
    }

    if ($relativePath === 'logout') {
        $_GET['route'] = 'logout';
    }

    if ($relativePath === 'auth/ganti-role') {
        $_GET['route'] = 'auth-switch-role';
    }

    if ($relativePath === 'auth/impersonate') {
        $_GET['route'] = 'auth-impersonate';
    }

    if ($relativePath === 'auth/impersonate-exit') {
        $_GET['route'] = 'auth-impersonate-exit';
    }
}

$routes = require __DIR__ . '/routes/web.php';
$routeKey = $_GET['route'] ?? (isLoggedIn() ? 'dashboard' : 'login');
$routeKey = strtolower(trim((string) $routeKey));
if ($routeKey !== '' && !preg_match('/^[a-z0-9-]+$/', $routeKey)) {
    http_response_code(400);
    echo '400 - Route tidak valid';
    exit;
}

if (!isset($routes[$routeKey])) {
    http_response_code(404);
    echo '404 - Route not found';
    exit;
}

$route = $routes[$routeKey];
$isPublic = (bool) ($route['public'] ?? false);
$routeMethods = array_map(
    static fn ($method): string => strtoupper(trim((string) $method)),
    (array) ($route['methods'] ?? [])
);
if ($routeMethods !== []) {
    if (in_array('GET', $routeMethods, true) && !in_array('HEAD', $routeMethods, true)) {
        $routeMethods[] = 'HEAD';
    }
    if (!in_array($requestMethod, $routeMethods, true)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', array_values(array_unique($routeMethods))));
        echo '405 - Method not allowed';
        exit;
    }
}

if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $sensitiveRouteLimits = [
        'login-submit' => ['max' => 12, 'window' => 300],
        'forgot-password-submit' => ['max' => 6, 'window' => 900],
        'reset-password-submit' => ['max' => 10, 'window' => 900],
        'auth-impersonate' => ['max' => 20, 'window' => 300],
        'auth-impersonate-exit' => ['max' => 30, 'window' => 300],
        'users-change-role' => ['max' => 20, 'window' => 300],
        'users-dosen-delete' => [
            'max' => (int) appEnv('RATE_LIMIT_USERS_DOSEN_DELETE_MAX', 40, __DIR__),
            'window' => (int) appEnv('RATE_LIMIT_USERS_DOSEN_DELETE_WINDOW', 300, __DIR__),
        ],
        'users-dosen-bulk-delete' => [
            'max' => (int) appEnv('RATE_LIMIT_USERS_DOSEN_BULK_DELETE_MAX', 10, __DIR__),
            'window' => (int) appEnv('RATE_LIMIT_USERS_DOSEN_BULK_DELETE_WINDOW', 300, __DIR__),
        ],
        'settings-contract-delete' => [
            'max' => (int) appEnv('RATE_LIMIT_SETTINGS_CONTRACT_DELETE_MAX', 30, __DIR__),
            'window' => (int) appEnv('RATE_LIMIT_SETTINGS_CONTRACT_DELETE_WINDOW', 300, __DIR__),
        ],
        'logs-bulk-delete' => ['max' => 10, 'window' => 300],
    ];
    if (isset($sensitiveRouteLimits[$routeKey])) {
        $limitConfig = $sensitiveRouteLimits[$routeKey];
        $rateKey = 'route:' . $routeKey . '|ip:' . authClientIp();
        $rateResult = rateLimitConsume($rateKey, (int) ($limitConfig['max'] ?? 10), (int) ($limitConfig['window'] ?? 300));
        if (!(bool) ($rateResult['allowed'] ?? false)) {
            $retryAfter = max(1, (int) ($rateResult['retry_after'] ?? 1));
            securityLog('ratelimit.blocked', [
                'route' => $routeKey,
                'retry_after' => $retryAfter,
            ]);
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            echo '429 - Terlalu banyak permintaan. Silakan coba lagi beberapa saat.';
            exit;
        }
    }
}

if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $csrfHeader = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $csrfHeader = (string) ($headers['X-CSRF-Token'] ?? $headers['X-CSRF-TOKEN'] ?? '');
    }
    if ($csrfHeader === '') {
        $csrfHeader = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF-TOKEN'] ?? '');
    }

    $csrfTokenFromBody = (string) ($_POST['_csrf'] ?? '');
    if ($csrfTokenFromBody === '') {
        $rawInput = file_get_contents('php://input');
        if (is_string($rawInput) && trim($rawInput) !== '') {
            $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode($rawInput, true);
                if (is_array($decoded)) {
                    $csrfTokenFromBody = (string) ($decoded['_csrf'] ?? '');
                }
            } else {
                parse_str($rawInput, $parsed);
                if (is_array($parsed)) {
                    $csrfTokenFromBody = (string) ($parsed['_csrf'] ?? '');
                }
            }
        }
    }

    $csrfToken = trim($csrfTokenFromBody !== '' ? $csrfTokenFromBody : $csrfHeader);
    if (!isValidCsrfToken($csrfToken)) {
        http_response_code(419);
        echo '419 - CSRF token tidak valid.';
        exit;
    }
}

if (!$isPublic && !isLoggedIn()) {
    redirectToPrettyPath($basePath, 'login');
}

if (
    isLoggedIn()
    && authRole() === 'dosen'
    && !in_array((string) $routeKey, ['profile', 'profile-update', 'logout'], true)
) {
    require_once __DIR__ . '/app/Models/BaseModel.php';
    require_once __DIR__ . '/app/Models/UserModel.php';

    $profileGuardUserModel = new UserModel();
    $profileGuardUser = $profileGuardUserModel->findById((int) (authUserId() ?? 0));
    if ($profileGuardUser !== null && !$profileGuardUserModel->isDosenProfileComplete($profileGuardUser)) {
        redirectToPrettyPath($basePath, 'profil', ['info' => 'Lengkapi profil Anda terlebih dahulu sebelum mengakses menu lain.']);
    }
}

if (isset($route['roles']) && is_array($route['roles']) && !RoleMiddleware::handle($route['roles'])) {
    if (isLoggedIn()) {
        $role = authRole();
        if ($role === 'dosen') {
            redirectToPrettyPath($basePath, 'dashboard-dosen');
        }
        if (isAdminPanelRole($role)) {
            if ($role === 'kepala_lppm') {
                redirectToPrettyPath($basePath, 'dashboard-kepala-lppm');
            }
            redirectToPrettyPath($basePath, 'dashboard-admin');
        }

        $_SESSION = [];
        session_destroy();
        redirectToPrettyPath($basePath, 'login', ['error' => 'Role akun tidak memiliki akses.']);
    }

    redirectToPrettyPath($basePath, 'login');
}
$controllerFile = __DIR__ . '/app/Controllers/' . $route['controller'] . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(500);
    echo 'Controller file not found';
    exit;
}

require_once $controllerFile;

$controllerClass = $route['controller'];
$method = $route['method'];

if (!class_exists($controllerClass)) {
    http_response_code(500);
    echo 'Controller class not found';
    exit;
}

$controller = new $controllerClass();

if (!method_exists($controller, $method)) {
    http_response_code(500);
    echo 'Method not found';
    exit;
}

try {
    $controller->$method();
} catch (Throwable $e) {
    securityLog('app.unhandled_exception', [
        'route' => $routeKey,
        'controller' => $controllerClass,
        'method' => $method,
        'message' => $e->getMessage(),
        'code' => (string) $e->getCode(),
    ]);

    http_response_code(500);
    echo '500 - Terjadi kesalahan pada sistem. Silakan hubungi admin.';
    exit;
}
