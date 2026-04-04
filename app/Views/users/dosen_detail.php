<?php
$row = $dosen ?? [];
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
?>

<div class="page-content myletters-page compact-list">
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="admin-page-title mb-1">Detail Profil Dosen</h2>
            <p class="admin-page-subtitle mb-0">Informasi profil dosen yang terdaftar pada sistem SAPA LPPM.</p>
        </div>
        <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Kembali</a>
    </div>

    <div class="card dashboard-card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">Nama Dosen</div><div class="fw-semibold"><?= htmlspecialchars((string) ($row['name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">NIDN</div><div class="fw-semibold"><?= htmlspecialchars((string) (($row['nidn'] ?? '') !== '' ? $row['nidn'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">NUPTK</div><div class="fw-semibold"><?= htmlspecialchars((string) (($row['nuptk'] ?? '') !== '' ? $row['nuptk'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">Email</div><div class="fw-semibold"><?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">Program Studi</div><div class="fw-semibold"><?= htmlspecialchars((string) (($row['study_program'] ?? '') !== '' ? $row['study_program'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">Fakultas</div><div class="fw-semibold"><?= htmlspecialchars((string) (($row['faculty'] ?? '') !== '' ? $row['faculty'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">Nomor HP</div><div class="fw-semibold"><?= htmlspecialchars((string) (($row['phone'] ?? '') !== '' ? $row['phone'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                <div class="col-md-6"><div class="profile-form-item"><div class="profile-info-label">Username</div><div class="fw-semibold"><?= htmlspecialchars((string) (($row['username'] ?? '') !== '' ? $row['username'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            </div>
        </div>
    </div>
</div>
