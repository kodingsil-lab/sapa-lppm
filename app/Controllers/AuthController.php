<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/CiValidationHelper.php';
require_once __DIR__ . '/../Helpers/RateLimitHelper.php';
require_once __DIR__ . '/../Helpers/SecurityLogHelper.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/PasswordResetTokenModel.php';
require_once __DIR__ . '/../Services/EmailNotificationService.php';

class AuthController
{
    private UserModel $userModel;
    private PasswordResetTokenModel $passwordResetTokenModel;
    private EmailNotificationService $emailNotificationService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->passwordResetTokenModel = new PasswordResetTokenModel();
        $this->emailNotificationService = new EmailNotificationService();
        ensureSessionStarted();
    }

    public function showLogin(): void
    {
        if (isLoggedIn()) {
            $this->redirectAfterLogin();
            return;
        }

        $errorMessage = $_GET['error'] ?? null;
        $infoMessage = $_GET['info'] ?? null;
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function showRegister(): void
    {
        if (isLoggedIn()) {
            $this->redirectAfterLogin();
            return;
        }

        ensureSessionStarted();
        $infoMessage = $_GET['info'] ?? ($_SESSION['register_success'] ?? null);
        $errorMessage = $_SESSION['register_error'] ?? null;
        $oldInput = is_array($_SESSION['register_old'] ?? null) ? $_SESSION['register_old'] : [];
        unset($_SESSION['register_success'], $_SESSION['register_error'], $_SESSION['register_old']);
        require __DIR__ . '/../Views/auth/register.php';
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('register');
        }

        ensureSessionStarted();

        $name = trim((string) ($_POST['name'] ?? ''));
        $nuptk = trim((string) ($_POST['nuptk'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['password_confirmation'] ?? '');

        $_SESSION['register_old'] = [
            'name' => $name,
            'nuptk' => $nuptk,
            'email' => $email,
            'username' => $username,
        ];

        try {
            $validation = ciValidateData(
                [
                    'name' => $name,
                    'nuptk' => $nuptk,
                    'email' => $email,
                    'username' => $username,
                    'password' => $password,
                    'password_confirmation' => $confirmPassword,
                ],
                [
                    'name' => 'required|min_length[3]|max_length[120]',
                    'nuptk' => 'required|numeric|min_length[8]|max_length[30]',
                    'email' => 'required|valid_email|max_length[160]',
                    'username' => 'required|regex_match[/^[A-Za-z0-9_.-]+$/]|min_length[3]|max_length[50]',
                    'password' => 'required|min_length[8]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).+$/]',
                    'password_confirmation' => 'required|matches[password]',
                ],
                [
                    'username' => [
                        'regex_match' => 'Username hanya boleh huruf, angka, titik, garis bawah, atau tanda minus.',
                    ],
                    'password' => [
                        'regex_match' => 'Password wajib mengandung huruf besar, huruf kecil, angka, dan simbol.',
                    ],
                ]
            );
            if (!$validation['valid']) {
                throw new RuntimeException($this->firstValidationError($validation['errors'], 'Data registrasi tidak valid.'));
            }

            $existingByUsername = $this->userModel->findByUsername($username);
            if ($existingByUsername !== null) {
                throw new RuntimeException('Username sudah dipakai akun lain.');
            }

            if ($this->userModel->findByEmail($email) !== null) {
                throw new RuntimeException('Email sudah dipakai akun lain.');
            }

            $newUserId = $this->userModel->createPublicDosen([
                'name' => $name,
                'nidn' => '',
                'nuptk' => $nuptk,
                'email' => $email,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'faculty' => '',
                'study_program' => '',
                'unit' => '',
                'phone' => '',
                'gender' => '',
                'status' => 'aktif',
            ]);

            $createdUser = $this->userModel->findById($newUserId);
            $emailNotice = '';
            if ($createdUser !== null) {
                try {
                    $this->emailNotificationService->sendRegistrationNotifications($createdUser);
                    $emailNotice = ' Notifikasi email registrasi berhasil dikirim.';
                } catch (Throwable $mailException) {
                    $emailNotice = ' Akun berhasil dibuat, tetapi email notifikasi belum terkirim: ' . $mailException->getMessage();
                }
            }

            unset($_SESSION['register_old']);
            $_SESSION['register_success'] = 'Registrasi berhasil. Silakan login menggunakan akun yang baru dibuat.' . $emailNotice;

            $this->redirectToPath('register');
        } catch (Throwable $e) {
            $_SESSION['register_error'] = $e->getMessage();
            $this->redirectToPath('register');
        }
    }

    public function showForgotPassword(): void
    {
        if (isLoggedIn()) {
            $this->redirectAfterLogin();
            return;
        }

        ensureSessionStarted();
        $infoMessage = $_GET['info'] ?? ($_SESSION['forgot_password_success'] ?? null);
        $errorMessage = $_SESSION['forgot_password_error'] ?? null;
        $oldInput = is_array($_SESSION['forgot_password_old'] ?? null) ? $_SESSION['forgot_password_old'] : [];
        unset($_SESSION['forgot_password_success'], $_SESSION['forgot_password_error'], $_SESSION['forgot_password_old']);
        require __DIR__ . '/../Views/auth/forgot_password.php';
    }

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('forgot-password');
        }

        ensureSessionStarted();
        $email = trim((string) ($_POST['email'] ?? ''));
        $_SESSION['forgot_password_old'] = ['email' => $email];
        $rateResult = rateLimitConsume('forgot-password|ip:' . authClientIp(), 6, 900);
        if (!(bool) ($rateResult['allowed'] ?? false)) {
            $retryAfter = max(1, (int) ($rateResult['retry_after'] ?? 1));
            securityLog('auth.forgot_password.blocked', [
                'email' => $email,
                'retry_after' => $retryAfter,
            ]);
            $_SESSION['forgot_password_error'] = 'Terlalu banyak permintaan reset password. Coba lagi beberapa saat.';
            $this->redirectToPath('forgot-password');
        }

        try {
            $validation = ciValidateData(
                ['email' => $email],
                ['email' => 'required|valid_email|max_length[160]']
            );
            if (!$validation['valid']) {
                throw new RuntimeException($this->firstValidationError($validation['errors'], 'Input email tidak valid.'));
            }

            $user = $this->userModel->findByEmail($email);
            if ($user !== null) {
                $rawToken = $this->passwordResetTokenModel->createForUser((int) ($user['id'] ?? 0), (string) ($user['email'] ?? ''));
                $resetUrl = $this->buildResetPasswordUrl($rawToken);
                $this->emailNotificationService->sendPasswordResetLinkNotification($user, $resetUrl);
            }
            securityLog('auth.forgot_password.requested', [
                'email' => $email,
                'found' => $user !== null ? 'yes' : 'no',
            ]);

            unset($_SESSION['forgot_password_old']);
            $_SESSION['forgot_password_success'] = 'Jika email Anda terdaftar, link reset password telah dikirim ke email Anda.';
            $this->redirectToPath('forgot-password');
        } catch (Throwable $e) {
            $_SESSION['forgot_password_error'] = $e->getMessage();
            $this->redirectToPath('forgot-password');
        }
    }

    public function showResetPassword(): void
    {
        if (isLoggedIn()) {
            $this->redirectAfterLogin();
            return;
        }

        ensureSessionStarted();
        $token = trim((string) ($_GET['token'] ?? ''));
        $infoMessage = $_GET['info'] ?? null;
        $errorMessage = $_SESSION['reset_password_error'] ?? null;
        $oldInput = is_array($_SESSION['reset_password_old'] ?? null) ? $_SESSION['reset_password_old'] : [];
        unset($_SESSION['reset_password_error'], $_SESSION['reset_password_old']);

        $isTokenUsable = false;
        if ($token !== '') {
            $isTokenUsable = $this->passwordResetTokenModel->findValidByToken($token) !== null;
        }

        if (!$isTokenUsable && $errorMessage === null) {
            $errorMessage = 'Link reset password tidak valid atau sudah kedaluwarsa.';
        }

        require __DIR__ . '/../Views/auth/reset_password.php';
    }

    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('forgot-password');
        }

        ensureSessionStarted();
        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['password_confirmation'] ?? '');
        $rateResult = rateLimitConsume('reset-password|ip:' . authClientIp(), 10, 900);
        if (!(bool) ($rateResult['allowed'] ?? false)) {
            $_SESSION['reset_password_error'] = 'Terlalu banyak percobaan reset password. Coba lagi beberapa saat.';
            $this->redirectToPath('reset-password', ['token' => $token]);
        }

        $_SESSION['reset_password_old'] = [
            'token' => $token,
        ];

        try {
            $validation = ciValidateData(
                [
                    'token' => $token,
                    'password' => $password,
                    'password_confirmation' => $confirmPassword,
                ],
                [
                    'token' => 'required|regex_match[/^[A-Fa-f0-9]{64}$/]',
                    'password' => 'required|min_length[8]|max_length[72]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).+$/]',
                    'password_confirmation' => 'required|matches[password]',
                ],
                [
                    'token' => [
                        'regex_match' => 'Token reset password tidak valid.',
                    ],
                    'password' => [
                        'regex_match' => 'Password wajib mengandung huruf besar, huruf kecil, angka, dan simbol.',
                    ],
                ]
            );
            if (!$validation['valid']) {
                throw new RuntimeException($this->firstValidationError($validation['errors'], 'Input reset password tidak valid.'));
            }

            $tokenRow = $this->passwordResetTokenModel->findValidByToken($token);
            if ($tokenRow === null) {
                securityLog('auth.reset_password.failed', [
                    'reason' => 'invalid_or_expired_token',
                ]);
                throw new RuntimeException('Link reset password tidak valid atau sudah kedaluwarsa.');
            }

            $this->userModel->updatePasswordById((int) ($tokenRow['user_id'] ?? 0), password_hash($password, PASSWORD_DEFAULT));
            $this->passwordResetTokenModel->markAsUsed((int) ($tokenRow['id'] ?? 0));
            $this->passwordResetTokenModel->deleteExpired();
            securityLog('auth.reset_password.success', [
                'user_id' => (int) ($tokenRow['user_id'] ?? 0),
            ]);

            unset($_SESSION['reset_password_old']);
            $this->redirectToPath('login', ['info' => 'Password berhasil diperbarui. Silakan login dengan password baru Anda.']);
        } catch (Throwable $e) {
            $_SESSION['reset_password_error'] = $e->getMessage();
            $this->redirectToPath('reset-password', ['token' => $token]);
        }
    }

    private function buildResetPasswordUrl(string $token): string
    {
        $app = require __DIR__ . '/../../config.php';
        $baseUrl = rtrim((string) ($app['app']['url'] ?? 'http://localhost/sapa-lppm'), '/');

        return $baseUrl . '/reset-password/' . rawurlencode($token);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('login');
        }

        ensureSessionStarted();
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $validation = ciValidateData(
            ['login' => $login, 'password' => $password],
            ['login' => 'required|min_length[3]|max_length[80]', 'password' => 'required|min_length[1]|max_length[72]']
        );
        if (!$validation['valid']) {
            $this->redirectToPath('login', [
                'error' => $this->firstValidationError($validation['errors'], 'Username dan password wajib diisi.'),
            ]);
        }

        $throttleStatus = authLoginThrottleStatus($login);
        if ((bool) ($throttleStatus['blocked'] ?? false)) {
            $retryAfter = max(1, (int) ($throttleStatus['retry_after'] ?? 0));
            securityLog('auth.login.blocked', [
                'login' => $login,
                'retry_after' => $retryAfter,
            ]);
            $this->redirectToPath('login', [
                'error' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $retryAfter . ' detik.',
            ]);
        }

        $user = $this->userModel->findByUsername($login);
        if ($user === null) {
            authLoginThrottleRegisterFailure($login);
            securityLog('auth.login.failed', [
                'login' => $login,
                'reason' => 'invalid_credentials',
            ]);
            $this->redirectToPath('login', ['error' => 'Username atau password salah.']);
        }

        $isPasswordValid = password_verify($password, (string) $user['password']);
        if (!$isPasswordValid) {
            authLoginThrottleRegisterFailure($login);
            securityLog('auth.login.failed', [
                'login' => $login,
                'user_id' => (int) ($user['id'] ?? 0),
                'reason' => 'invalid_credentials',
            ]);
            $this->redirectToPath('login', ['error' => 'Username atau password salah.']);
        }

        authLoginThrottleReset($login);
        session_regenerate_id(true);
        unset($_SESSION['auth_impersonator'], $_SESSION['auth_impersonating_meta'], $_SESSION['auth_active_role']);

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'username' => (string) $user['username'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'nidn' => (string) ($user['nidn'] ?? ''),
            'gender' => (string) ($user['gender'] ?? ''),
            'avatar' => (string) ($user['avatar'] ?? ''),
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        authRememberSuccessfulLogin();
        securityLog('auth.login.success', [
            'user_id' => (int) ($user['id'] ?? 0),
            'role' => (string) ($user['role'] ?? ''),
        ]);

        $this->redirectAfterLogin();
    }

    public function impersonate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath($this->adminDashboardPath());
        }

        ensureSessionStarted();
        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $targetValidation = ciValidateData(
            ['target_user_id' => (string) $targetUserId],
            ['target_user_id' => 'required|integer|greater_than[0]']
        );
        if (!$targetValidation['valid']) {
            $this->redirectToPath('pengguna', [
                'error' => $this->firstValidationError($targetValidation['errors'], 'Target akun tidak valid.'),
            ]);
        }

        unset($_SESSION['auth_active_role']);

        $sourceUser = impersonatorUser() ?? authUser();
        if (!is_array($sourceUser)) {
            $this->redirectToPath('login');
        }

        $sourceRole = normalizeRoleName((string) ($sourceUser['role'] ?? ''));
        if (!in_array($sourceRole, ['admin', 'kepala_lppm'], true)) {
            $this->redirectToPath($this->adminDashboardPath(), [
                'error' => 'Role Anda tidak punya akses masuk sebagai user lain.',
            ]);
        }

        $targetUser = $this->userModel->findById($targetUserId);
        if ($targetUser === null) {
            $this->redirectToPath('pengguna', ['error' => 'Target akun tidak ditemukan.']);
        }

        $targetRole = normalizeRoleName((string) ($targetUser['role'] ?? ''));
        $allowedTargetRoles = $sourceRole === 'admin' ? ['kepala_lppm', 'dosen'] : ['dosen'];
        if (!in_array($targetRole, $allowedTargetRoles, true)) {
            $this->redirectToPath('pengguna', ['error' => 'Role target tidak diizinkan untuk akun Anda.']);
        }

        if ((int) ($sourceUser['id'] ?? 0) === (int) ($targetUser['id'] ?? 0)) {
            $this->redirectToPath('pengguna', ['info' => 'Anda sudah berada pada akun tersebut.']);
        }

        $_SESSION['auth_impersonator'] = [
            'id' => (int) ($sourceUser['id'] ?? 0),
            'name' => (string) ($sourceUser['name'] ?? ''),
            'username' => (string) ($sourceUser['username'] ?? ''),
            'email' => (string) ($sourceUser['email'] ?? ''),
            'role' => (string) ($sourceUser['role'] ?? ''),
            'nidn' => (string) ($sourceUser['nidn'] ?? ''),
            'gender' => (string) ($sourceUser['gender'] ?? ''),
            'avatar' => (string) ($sourceUser['avatar'] ?? ''),
        ];
        $_SESSION['auth_impersonating_meta'] = [
            'started_at' => date('c'),
            'target_id' => (int) $targetUser['id'],
            'target_role' => $targetRole,
        ];

        $_SESSION['auth_user'] = [
            'id' => (int) $targetUser['id'],
            'name' => (string) $targetUser['name'],
            'username' => (string) $targetUser['username'],
            'email' => (string) $targetUser['email'],
            'role' => (string) $targetUser['role'],
            'nidn' => (string) ($targetUser['nidn'] ?? ''),
            'gender' => (string) ($targetUser['gender'] ?? ''),
            'avatar' => (string) ($targetUser['avatar'] ?? ''),
        ];
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        authRememberSuccessfulLogin();
        securityLog('auth.impersonate.start', [
            'source_user_id' => (int) ($sourceUser['id'] ?? 0),
            'target_user_id' => (int) ($targetUser['id'] ?? 0),
            'target_role' => $targetRole,
        ]);

        $this->redirectAfterLogin();
    }

    public function stopImpersonate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath($this->adminDashboardPath());
        }

        ensureSessionStarted();
        $originUser = impersonatorUser();
        if ($originUser === null) {
            $this->redirectAfterLogin();
            return;
        }

        $_SESSION['auth_user'] = [
            'id' => (int) ($originUser['id'] ?? 0),
            'name' => (string) ($originUser['name'] ?? ''),
            'username' => (string) ($originUser['username'] ?? ''),
            'email' => (string) ($originUser['email'] ?? ''),
            'role' => (string) ($originUser['role'] ?? ''),
            'nidn' => (string) ($originUser['nidn'] ?? ''),
            'gender' => (string) ($originUser['gender'] ?? ''),
            'avatar' => (string) ($originUser['avatar'] ?? ''),
        ];
        unset($_SESSION['auth_impersonator'], $_SESSION['auth_impersonating_meta'], $_SESSION['auth_active_role']);
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        authRememberSuccessfulLogin();
        securityLog('auth.impersonate.stop', [
            'restored_user_id' => (int) ($originUser['id'] ?? 0),
        ]);

        $this->redirectAfterLogin();
    }

    public function switchRole(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectAfterLogin();
            return;
        }

        ensureSessionStarted();
        if (isImpersonating()) {
            $this->redirectToPath($this->adminDashboardPath(), [
                'error' => 'Role tidak dapat diganti saat mode Masuk Sebagai aktif.',
            ]);
        }

        $targetRoleRaw = trim((string) ($_POST['role'] ?? ''));
        $roleValidation = ciValidateData(
            ['role' => $targetRoleRaw],
            ['role' => 'required|in_list[admin,kepala_lppm,dosen,admin_lppm]']
        );
        if (!$roleValidation['valid']) {
            $this->redirectToPath($this->adminDashboardPath(), [
                'error' => $this->firstValidationError($roleValidation['errors'], 'Role tujuan tidak valid untuk akun Anda.'),
            ]);
        }
        $targetRole = normalizeRoleName($targetRoleRaw);
        $baseRole = authBaseRole();
        $availableRoles = authAvailableRoles();

        if ($baseRole === null || $targetRole === '' || !in_array($targetRole, $availableRoles, true)) {
            $this->redirectToPath($this->adminDashboardPath(), [
                'error' => 'Role tujuan tidak valid untuk akun Anda.',
            ]);
        }

        if ($targetRole === $baseRole) {
            unset($_SESSION['auth_active_role']);
        } else {
            $_SESSION['auth_active_role'] = $targetRole;
        }

        $this->redirectAfterLogin();
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('login');
        }

        ensureSessionStarted();
        securityLog('auth.logout', [
            'user_id' => (int) (authUserId() ?? 0),
            'role' => (string) (authRole() ?? ''),
        ]);
        $_SESSION = [];
        session_destroy();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => (string) ($params['path'] ?? '/'),
                    'domain' => (string) ($params['domain'] ?? ''),
                    'secure' => authIsHttpsRequest(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        $app = require __DIR__ . '/../../config.php';
        $basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
        header('Location: ' . $basePath . '/login');
        exit;
    }

    private function generateTemporaryPassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }

    private function redirectAfterLogin(): void
    {
        $role = authRole();
        if ($role === 'dosen') {
            $user = $this->userModel->findById((int) (authUserId() ?? 0));
            if ($user !== null && !$this->userModel->isDosenProfileComplete($user)) {
                $this->redirectToPath('profil', ['info' => 'Lengkapi profil Anda terlebih dahulu sebelum menggunakan sistem.']);
            }
            $this->redirectToPath('dashboard-dosen');
        }
        if (isAdminPanelRole($role)) {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $_SESSION = [];
        session_destroy();
        $this->redirectToPath('login', ['error' => 'Role akun tidak valid. Hubungi admin sistem.']);
    }

    private function appBasePath(): string
    {
        $app = require __DIR__ . '/../../config.php';
        return rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
    }

    private function redirectToPath(string $path, array $query = [], int $statusCode = 302): void
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

    private function adminDashboardPath(): string
    {
        return normalizeRoleName((string) authRole()) === 'kepala_lppm'
            ? 'dashboard-kepala-lppm'
            : 'dashboard-admin';
    }

    private function firstValidationError(array $errors, string $fallback): string
    {
        if ($errors === []) {
            return $fallback;
        }

        $first = array_values($errors)[0] ?? $fallback;
        return trim((string) $first) !== '' ? (string) $first : $fallback;
    }
}
