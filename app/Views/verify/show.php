<?php
$verifyBasePath = appBasePath();
$bootstrapCssPath = __DIR__ . '/../../../public/assets/vendor/bootstrap/css/bootstrap.min.css';
$bootstrapCssVersion = is_file($bootstrapCssPath) ? (string) filemtime($bootstrapCssPath) : '1';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Surat - SAPA LPPM</title>
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap/css/bootstrap.min.css?v=' . $bootstrapCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <style>
        body { background: #f7f9fc; }
        .verify-card { max-width: 760px; margin: 40px auto; border: 0; border-radius: 14px; box-shadow: 0 4px 20px rgba(17, 24, 39, 0.08); }
        .verify-head { background: #123c6b; color: #fff; border-radius: 14px 14px 0 0; padding: 18px 22px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card verify-card">
        <div class="verify-head">
            <h5 class="mb-0">Verifikasi Surat SAPA LPPM</h5>
            <small><?= htmlspecialchars((string) ($appConfig['app']['name'] ?? 'SAPA LPPM'), ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
        <div class="card-body p-4">
            <?php if ($isValid): ?>
                <div class="alert alert-success"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php else: ?>
                <div class="alert alert-danger"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <table class="table table-bordered">
                <tr>
                    <th width="220">Nomor Surat</th>
                    <td><?= htmlspecialchars((string) ($letter['letter_number'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Jenis Surat</th>
                    <td><?= htmlspecialchars((string) ($letter['letter_type_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td>
                        <?php if (!empty($letter['letter_date'])): ?>
                            <?= htmlspecialchars((string) date('d M Y', strtotime((string) $letter['letter_date'])), ENT_QUOTES, 'UTF-8'); ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Nama Pemohon</th>
                    <td><?= htmlspecialchars((string) ($letter['applicant_name'] ?? $letter['researcher_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ($isValid): ?>
                            <span class="badge text-bg-success">Valid</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger">Tidak Valid</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
