<?php
$basePath = appBasePath();
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$logoSrc = appAssetUrl('assets/img/logo-unisap.png');
$faviconUrl = appPublicUrl('unisap_favicon.ico');
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
    <title>Reset Password SAPA LPPM</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap/css/bootstrap.min.css?v=' . $bootstrapCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css?v=' . $bootstrapIconsCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/css/auth-bootstrap-fallback.css?v=' . $authFallbackCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
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
            padding: 30px 26px 22px;
        }

        .auth-top-line {
            display: none;
        }

        .logo-wrap {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-wrap img {
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        .auth-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 1.03rem;
            line-height: 1.45;
            margin-bottom: 14px;
        }

        .auth-app-title {
            color: #2b59b5;
            font-weight: 700;
            display: block;
            font-size: 2.05rem;
            line-height: 1.1;
            margin-bottom: 10px;
        }

        .auth-divider {
            border-top: 1px solid #e3e9f2;
            margin: 14px 0 16px;
        }

        .page-title {
            text-align: center;
            font-size: 1.5rem;
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-copy {
            text-align: center;
            color: #64748b;
            margin-bottom: 16px;
            line-height: 1.55;
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
            background: #fff;
        }

        .form-control:focus {
            border-color: #2b59b5;
            box-shadow: 0 0 0 0.2rem rgba(43, 89, 181, 0.15);
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .btn-auth {
            border-radius: 8px;
            padding: 11px 14px;
            font-weight: 500;
        }

        .btn-auth-secondary {
            border: 1px solid #d6dee8;
            background: #f8fbff;
            color: #2b59b5;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-auth-secondary:hover {
            background: #eef4ff;
            color: #244c9b;
            text-decoration: none;
        }

        .btn-auth-primary {
            border: 1px solid #2b59b5;
            background: #2b59b5;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-auth-primary:hover {
            background: #244c9b;
            border-color: #244c9b;
            color: #fff;
        }

        .page-note {
            margin-top: 14px;
            text-align: center;
            color: #64748b;
            line-height: 1.55;
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

        .auth-bottom-heart {
            color: #d22d2d;
        }

        @media (max-width: 767.98px) {
            body {
                padding: 12px;
                align-items: flex-start;
                overflow-x: hidden;
                overflow-y: auto;
            }

            .auth-card {
                width: min(100%, 420px);
                border-radius: 18px;
                margin: 8px auto;
            }

            .auth-card .card-body {
                padding: 26px 20px 18px;
            }

            .logo-wrap {
                margin-bottom: 8px;
            }

            .logo-wrap img {
                width: 68px;
                height: 68px;
            }

            .auth-subtitle {
                font-size: 0.88rem;
                line-height: 1.5;
                margin-bottom: 12px;
            }

            .auth-divider {
                margin: 12px 0 14px;
            }

            .page-title {
                font-size: 1.28rem;
            }

            .page-copy,
            .page-note {
                font-size: 0.9rem;
                line-height: 1.5;
                margin-bottom: 14px;
            }

            .form-label {
                margin-bottom: 6px;
                font-size: 0.92rem;
            }

            .form-control {
                padding: 10px 12px;
                font-size: 0.92rem;
            }

            .actions {
                grid-template-columns: 1fr;
                gap: 10px;
                margin-top: 14px;
            }

            .btn-auth {
                padding: 10px 12px;
            }

            .auth-bottom-credit {
                margin-top: 8px;
                font-size: 0.78rem;
            }
        }

        @media (max-width: 420px) {
            .auth-card .card-body {
                padding: 24px 16px 16px;
            }

            .auth-subtitle {
                font-size: 0.84rem;
            }

            .auth-app-title {
                font-size: 1.7rem;
                margin-bottom: 8px;
            }

            .page-title {
                font-size: 1.2rem;
            }

            .page-copy,
            .page-note,
            .auth-bottom-credit {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card card">
        <div class="card-body">
            <div class="auth-top-line"></div>

            <div class="logo-wrap">
                <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo UNISAP">
            </div>
            <p class="auth-subtitle">
                <span class="auth-app-title">SAPA LPPM</span>
                Sistem Administrasi Persuratan dan Arsip LPPM<br>
                Universitas San Pedro
            </p>

            <div class="auth-divider"></div>
            <div class="page-title">Reset Password</div>
            <p class="page-copy">Masukkan email aktif Anda. Jika email terdaftar, kami akan mengirim link reset password yang aman ke email tersebut.</p>

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

            <form method="post" action="<?= htmlspecialchars($basePath . '/forgot-password', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <div>
                    <label for="emailField" class="form-label">Email Aktif</label>
                    <input id="emailField" type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($oldInput['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan email aktif" required>
                </div>

                <div class="actions">
                    <a href="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8'); ?>" class="btn-auth btn-auth-secondary">
                        <i class="bi bi-arrow-left-circle"></i>Kembali ke Login
                    </a>
                    <button type="submit" class="btn-auth btn-auth-primary">
                        <i class="bi bi-key"></i>Reset Password
                    </button>
                </div>
            </form>

            <div class="page-note">
                Jika email Anda terdaftar, link reset password akan dikirim ke email Anda dan berlaku sementara.
            </div>
            <div class="auth-bottom-credit">
                Developed By <a href="https://wa.me/628113821126" target="_blank" rel="noopener noreferrer">KSJ</a> <span class="auth-bottom-heart">&hearts;</span>
            </div>
        </div>
    </div>
</body>
</html>
