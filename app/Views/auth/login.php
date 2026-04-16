<?php
$basePath = appBasePath();
$publicBasePath = appPublicBasePath();
$faviconUrl = appPublicUrl('unisap_favicon.ico');
$logoSrc = appAssetUrl('assets/img/logo-unisap.png');
$bootstrapCssPath = __DIR__ . '/../../../public/assets/vendor/bootstrap/css/bootstrap.min.css';
$bootstrapCssVersion = is_file($bootstrapCssPath) ? (string) filemtime($bootstrapCssPath) : '1';
$bootstrapIconsCssPath = __DIR__ . '/../../../public/assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css';
$bootstrapIconsCssVersion = is_file($bootstrapIconsCssPath) ? (string) filemtime($bootstrapIconsCssPath) : '1';
$authFallbackCssPath = __DIR__ . '/../../../public/assets/css/auth-bootstrap-fallback.css';
$authFallbackCssVersion = is_file($authFallbackCssPath) ? (string) filemtime($authFallbackCssPath) : '1';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SAPA LPPM</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap/css/bootstrap.min.css?v=' . $bootstrapCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css?v=' . $bootstrapIconsCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/css/auth-bootstrap-fallback.css?v=' . $authFallbackCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 8% 12%, rgba(43, 89, 181, 0.18), transparent 34%),
                radial-gradient(circle at 90% 82%, rgba(59, 130, 246, 0.14), transparent 32%),
                radial-gradient(circle at 84% 18%, rgba(14, 165, 233, 0.12), transparent 24%),
                linear-gradient(135deg, #ecf2fb 0%, #e8eef8 48%, #f4f8ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: "Inter", "Segoe UI", sans-serif;
            font-weight: 400;
            position: relative;
            overflow: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        body::before {
            width: 460px;
            height: 460px;
            top: -220px;
            left: -150px;
            background: radial-gradient(circle, rgba(43, 89, 181, 0.24), rgba(43, 89, 181, 0));
        }

        body::after {
            width: 500px;
            height: 500px;
            right: -220px;
            bottom: -210px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.2), rgba(14, 165, 233, 0));
        }

        .auth-card {
            width: min(100%, 560px);
            border: 1px solid #dfe5ef;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(19, 46, 92, 0.05);
            position: relative;
            z-index: 1;
        }

        .auth-card .card-body {
            padding: 44px 34px 30px;
        }

        .logo-wrap {
            text-align: center;
            margin-bottom: 14px;
        }

        .logo-wrap img {
            width: 88px;
            height: 88px;
            object-fit: contain;
        }

        .auth-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 1.03rem;
            margin-bottom: 24px;
            line-height: 1.45;
        }

        .auth-app-title {
            color: #2b59b5;
            font-weight: 700;
            display: block;
            font-size: 2.05rem;
            line-height: 1.1;
            margin-bottom: 10px;
        }

        .form-label {
            color: #0f172a;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-control {
            border-radius: 8px;
            border-color: #d6dee8;
            padding: 12px 14px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #2b59b5;
            box-shadow: 0 0 0 0.2rem rgba(43, 89, 181, 0.15);
        }

        .auth-password-group {
            position: relative;
        }

        .auth-password-group .form-control {
            padding-right: 58px;
        }

        .auth-password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: 1px solid #d6dee8;
            background: #f8fbff;
            color: #64748b;
            padding: 0;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color 0.2s ease, border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-password-toggle:hover {
            color: #2b59b5;
            border-color: #b8cae3;
            background: #eef4ff;
        }

        .auth-password-toggle:focus-visible {
            outline: none;
            color: #2b59b5;
            border-color: #2b59b5;
            box-shadow: 0 0 0 0.2rem rgba(43, 89, 181, 0.12);
        }

        .form-check-input:checked {
            background-color: #2b59b5;
            border-color: #2b59b5;
        }

        .link-primary {
            color: #2b59b5 !important;
            text-decoration: none;
        }

        .link-primary:hover {
            text-decoration: underline;
        }

        .btn-signin {
            background: #2b59b5;
            border-color: #2b59b5;
            border-radius: 8px;
            padding-top: 11px;
            padding-bottom: 11px;
            font-weight: 500;
        }

        .btn-signin:hover {
            background: #244c9b;
            border-color: #244c9b;
        }

        .auth-footer {
            font-size: 1rem;
            color: #64748b;
        }

        .auth-bottom-credit {
            margin-top: 10px;
            text-align: center;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.35;
        }

        .auth-bottom-credit a {
            color: #0f172a;
            font-weight: 700;
            text-decoration: none;
        }

        .auth-bottom-credit a:hover {
            text-decoration: underline;
        }

        .auth-bottom-heart {
            color: #d22d2d;
        }

        .fw-semibold {
            font-weight: 500 !important;
        }

        @media (max-width: 575.98px) {
            body {
                padding: 12px;
                align-items: flex-start;
                overflow-x: hidden;
                overflow-y: auto;
            }

            .auth-card {
                border-radius: 18px;
                width: min(100%, 420px);
                margin: 8px auto;
            }

            .auth-card .card-body {
                padding: 28px 20px 22px;
            }

            .logo-wrap {
                margin-bottom: 10px;
            }

            .logo-wrap img {
                width: 68px;
                height: 68px;
            }

            .auth-subtitle {
                font-size: 0.88rem;
                line-height: 1.5;
                margin-bottom: 18px;
            }

            .form-label {
                margin-bottom: 6px;
                font-size: 0.92rem;
            }

            .form-control {
                padding: 10px 12px;
                font-size: 0.92rem;
            }

            .auth-password-group .form-control {
                padding-right: 60px;
            }

            .auth-password-toggle {
                width: 38px;
                height: 38px;
                right: 10px;
                border-radius: 12px;
            }

            .btn-signin {
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .auth-footer {
                font-size: 0.93rem;
            }

            .auth-bottom-credit {
                margin-top: 8px;
                font-size: 0.78rem;
            }
        }

        @media (max-width: 420px) {
            .auth-card .card-body {
                padding: 24px 16px 18px;
            }

            .auth-subtitle {
                font-size: 0.84rem;
                margin-bottom: 16px;
            }

            .auth-app-title {
                font-size: 1.7rem;
                margin-bottom: 8px;
            }

            .form-control {
                border-radius: 10px;
            }

            .d-flex.align-items-center.justify-content-between.mb-4 {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 10px;
                margin-bottom: 1rem !important;
            }

            .d-flex.align-items-center.justify-content-between.mb-4 > * {
                width: 100%;
            }

            .d-flex.align-items-center.justify-content-between.mb-4 .form-check {
                margin-bottom: 0;
            }

            .d-flex.align-items-center.justify-content-between.mb-4 .link-primary {
                display: inline-block;
                text-align: left;
            }

            .btn-signin.w-100.mb-4 {
                margin-bottom: 1rem !important;
            }

            .auth-footer {
                font-size: 0.89rem;
            }

            .auth-bottom-credit {
                margin-top: 6px;
                font-size: 0.74rem;
                line-height: 1.3;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card card">
        <div class="card-body">
            <div class="logo-wrap">
                <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo UNISAP">
            </div>
            <p class="auth-subtitle">
                <span class="auth-app-title">SAPA LPPM</span>
                Sistem Administrasi Persuratan dan Arsip LPPM<br>
                Universitas San Pedro
            </p>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($infoMessage)): ?>
                <div class="alert alert-info" role="alert">
                    <?= htmlspecialchars((string) $infoMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label for="loginField" class="form-label">Nama Pengguna</label>
                    <input type="text" name="login" id="loginField" class="form-control" placeholder="Masukkan nama pengguna" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="passwordField" class="form-label">Kata Sandi</label>
                    <div class="auth-password-group">
                        <input type="password" name="password" id="passwordField" class="form-control" placeholder="Masukkan kata sandi" required>
                        <button type="button" class="auth-password-toggle" data-password-toggle="passwordField" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="rememberDevice" checked>
                        <label class="form-check-label text-dark" for="rememberDevice">Ingat saya</label>
                    </div>
                    <a href="<?= htmlspecialchars($basePath . '/forgot-password', ENT_QUOTES, 'UTF-8'); ?>" class="link-primary fw-semibold">Lupa Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-signin w-100 mb-4">Masuk</button>

                <div class="text-center auth-footer">
                    Belum Punya Akun? <a href="<?= htmlspecialchars($basePath . '/register', ENT_QUOTES, 'UTF-8'); ?>" class="link-primary fw-semibold">Registrasi</a>
                </div>
            </form>
            <div class="auth-bottom-credit">
                Developed By <a href="https://wa.me/628113821126" target="_blank" rel="noopener noreferrer">KSJ</a> <span class="auth-bottom-heart">&hearts;</span>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                var input = document.getElementById(button.getAttribute('data-password-toggle'));
                if (!input) {
                    return;
                }

                var icon = button.querySelector('i');
                button.addEventListener('click', function () {
                    var isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                    button.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                    if (icon) {
                        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
                    }
                });
            });
        });
    </script>
</body>
</html>
