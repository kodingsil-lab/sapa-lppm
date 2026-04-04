<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$chairman = $chairman ?? null;
$availableColumns = $availableColumns ?? [];
$columnMap = array_flip(array_map('strval', $availableColumns));
$hasColumn = static function (string $name) use ($columnMap): bool {
    return isset($columnMap[$name]);
};
$startInEditMode = !empty($errorMessage);
$roleRaw = strtolower(trim((string) ($chairman['role'] ?? 'kepala_lppm')));
$roleNormalized = $roleRaw === 'admin_lppm' ? 'admin' : $roleRaw;
$isKepalaProfile = $roleNormalized === 'kepala_lppm';
$isAdminProfile = $roleNormalized === 'admin';
$profileTitle = $isKepalaProfile ? 'Profil Kepala LPPM' : 'Profil Admin LPPM';
$profileSubtitle = $isKepalaProfile
    ? 'Informasi identitas Kepala LPPM yang terdaftar pada sistem SAPA LPPM.'
    : 'Kelola informasi akun Admin LPPM pada halaman ini.';
$gender = strtolower(trim((string) ($chairman['gender'] ?? '')));
$defaultAvatar = ($gender === 'perempuan' || $gender === 'female') ? 'woman-avatar.png' : 'man-avatar.png';
$defaultAvatarUrl = appAssetUrl('assets/img/' . $defaultAvatar);
$avatarUrl = !empty($chairman['avatar'])
    ? buildUserAvatarUrl($basePath, (int) ($chairman['id'] ?? 0), (string) $chairman['avatar'])
    : $defaultAvatarUrl;
$dashboardBackPath = $basePath . '/' . ($isKepalaProfile ? 'dashboard-kepala-lppm' : 'dashboard-admin');

$signatureUrl = '';
if (!empty($chairman['signature_path'])) {
    $rawPath = (string) $chairman['signature_path'];
    if (isSafeProjectRelativePathUnder($rawPath, 'storage/uploads/signatures')) {
        $signatureFullPath = __DIR__ . '/../../../' . normalizeProjectRelativePath($rawPath);
        if (is_file($signatureFullPath)) {
            $signatureBinary = file_get_contents($signatureFullPath);
            if ($signatureBinary !== false) {
                $signatureUrl = 'data:image/png;base64,' . base64_encode($signatureBinary);
            }
        }
    }
}
?>

<div class="page-content admin-profile-page">
    <div class="profile-header mb-3">
        <div>
            <h1 class="page-title mb-1"><?= htmlspecialchars($profileTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle mb-0"><?= htmlspecialchars($profileSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="<?= htmlspecialchars($dashboardBackPath, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main profile-back-btn">Kembali</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="profile-card profile-summary-card h-100">
                <div class="profile-summary-top">
                    <div class="profile-avatar-wrap">
                        <img id="adminAvatarPreview" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar Admin LPPM" class="profile-avatar" onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>';">
                        <label for="adminAvatarFileField" class="profile-avatar-edit-btn" title="Ganti avatar">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                    </div>
                    <div class="profile-user-name"><?= htmlspecialchars((string) ($chairman['name'] ?? ($isKepalaProfile ? 'Kepala LPPM' : 'Admin LPPM')), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-user-role"><?= htmlspecialchars($isKepalaProfile ? 'Kepala LPPM' : 'Admin', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="profile-summary-divider"></div>

                <div class="profile-mini-list">
                    <div class="profile-mini-item">
                        <span class="mini-label">Email</span>
                        <span class="mini-value"><?= htmlspecialchars((string) ($chairman['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">Username</span>
                        <span class="mini-value"><?= htmlspecialchars((string) (($chairman['username'] ?? '') !== '' ? $chairman['username'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php if (!$isAdminProfile): ?>
                        <div class="profile-mini-item">
                            <span class="mini-label">NUPTK</span>
                            <span class="mini-value"><?= htmlspecialchars((string) (($chairman['nuptk'] ?? '') !== '' ? $chairman['nuptk'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="profile-mini-item">
                            <span class="mini-label">NIDN</span>
                            <span class="mini-value"><?= htmlspecialchars((string) (($chairman['nidn'] ?? '') !== '' ? $chairman['nidn'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="profile-mini-item">
                            <span class="mini-label">Jenis Kelamin</span>
                            <span class="mini-value"><?= htmlspecialchars((string) (($chairman['gender'] ?? '') !== '' ? $chairman['gender'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="profile-mini-item">
                            <span class="mini-label">Jabatan</span>
                            <span class="mini-value">
                                <?= htmlspecialchars((string) (($chairman['jabatan'] ?? $chairman['position'] ?? '') !== '' ? ($chairman['jabatan'] ?? $chairman['position']) : 'Kepala LPPM'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$isAdminProfile): ?>
                    <div class="profile-summary-divider"></div>

                    <div class="admin-signature-preview">
                        <div class="mini-label mb-2">Signature Saat Ini</div>
                        <?php if ($signatureUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($signatureUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Signature Ketua LPPM" class="admin-signature-image">
                        <?php else: ?>
                            <div class="admin-signature-empty">Belum ada signature yang diupload.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success profile-alert"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger profile-alert"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="profile-card profile-detail-card profile-form-card">
                <div class="section-head mb-3">
                    <h3 class="section-title mb-0">Edit Biodata</h3>
                </div>

                <?php if (empty($chairman)): ?>
                    <div class="alert alert-warning mb-0">Belum ada user dengan role <code>kepala_lppm</code> atau <code>admin</code>.</div>
                <?php else: ?>
                    <form id="adminProfileForm" action="<?= htmlspecialchars($basePath . '/profil-admin/tanda-tangan', ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data" class="row g-3" data-edit-mode="<?= $startInEditMode ? 'edit' : 'lock'; ?>">
                        <input id="adminAvatarFileField" type="file" name="avatar_file" accept="image/png,image/jpeg,image/webp" class="d-none">
                        <input type="hidden" name="chairman_id" value="<?= (int) ($chairman['id'] ?? 0); ?>">

                        <div class="col-md-6">
                            <div class="profile-form-item">
                                <label class="profile-info-label">Nama Lengkap</label>
                                <input type="text" class="form-control profile-input" name="nama_lengkap" value="<?= htmlspecialchars((string) ($chairman['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama lengkap beserta gelar" required>
                            </div>
                        </div>

                        <?php if (!$isAdminProfile): ?>
                            <div class="col-md-6">
                                <div class="profile-form-item">
                                    <label class="profile-info-label">Jabatan</label>
                                    <input type="text" class="form-control profile-input" name="jabatan" value="<?= htmlspecialchars((string) (($chairman['jabatan'] ?? $chairman['position'] ?? '') !== '' ? ($chairman['jabatan'] ?? $chairman['position']) : 'Kepala LPPM'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Kepala LPPM" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="profile-form-item">
                                    <label class="profile-info-label">NUPTK</label>
                                    <input type="text" class="form-control profile-input" name="nuptk" value="<?= htmlspecialchars((string) ($chairman['nuptk'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NUPTK" <?= $hasColumn('nuptk') ? 'required' : 'disabled data-permanent-disabled="1"'; ?>>
                                    <?php if (!$hasColumn('nuptk')): ?><small class="field-helper">Kolom NUPTK belum tersedia di database.</small><?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="profile-form-item">
                                    <label class="profile-info-label">NIDN</label>
                                    <input type="text" class="form-control profile-input" name="nidn" value="<?= htmlspecialchars((string) ($chairman['nidn'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NIDN (opsional)" <?= $hasColumn('nidn') ? '' : 'disabled data-permanent-disabled="1"'; ?>>
                                    <?php if (!$hasColumn('nidn')): ?><small class="field-helper">Kolom NIDN belum tersedia di database.</small><?php endif; ?>
                                    <small class="field-helper">NIDN tidak wajib diisi.</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <div class="profile-form-item">
                                <label class="profile-info-label">Email</label>
                                <input type="email" class="form-control profile-input" name="email" value="<?= htmlspecialchars((string) ($chairman['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan email aktif" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="profile-form-item">
                                <label class="profile-info-label">Username</label>
                                <input type="text" class="form-control profile-input" name="username" value="<?= htmlspecialchars((string) ($chairman['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama pengguna" required>
                            </div>
                        </div>

                        <?php if (!$isAdminProfile): ?>
                            <div class="col-md-6">
                                <div class="profile-form-item">
                                    <label class="profile-info-label">Nomor HP</label>
                                    <input type="text" class="form-control profile-input" name="no_hp" value="<?= htmlspecialchars((string) ($chairman['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 0812xxxxxxxx" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="profile-form-item">
                                    <label class="profile-info-label">Jenis Kelamin</label>
                                    <select class="form-select profile-input" name="jenis_kelamin" required>
                                        <?php $genderValue = (string) ($chairman['gender'] ?? ''); ?>
                                        <option value="">Pilih</option>
                                        <option value="Laki-laki" <?= $genderValue === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="Perempuan" <?= $genderValue === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <div class="profile-form-item">
                                <label class="profile-info-label">Password Saat Ini</label>
                                <input type="password" class="form-control profile-input" name="current_password" placeholder="Wajib diisi jika ganti password">
                                <small class="field-helper">Isi password saat ini hanya jika Anda ingin mengganti password.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="profile-form-item">
                                <label class="profile-info-label">Password</label>
                                <input type="password" class="form-control profile-input" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah">
                                <small class="field-helper">Minimal 8 karakter. Kosongkan jika tidak ingin ganti password.</small>
                            </div>
                        </div>

                        <?php if (!$isAdminProfile): ?>
                            <div class="col-12">
                                <div class="profile-form-item">
                                    <label class="profile-info-label">Upload Tanda Tangan Digital</label>
                                    <input type="file" name="ttd_digital" class="form-control profile-input" accept=".png,image/png">
                                    <small class="field-helper">Gunakan file PNG transparan agar hasil surat terlihat rapi.</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="button" id="adminProfileEditBtn" class="btn btn-light-soft">Edit</button>
                            <button type="submit" id="adminProfileSaveBtn" class="btn btn-primary-main">Simpan Perubahan</button>
                            <a href="<?= htmlspecialchars($dashboardBackPath, ENT_QUOTES, 'UTF-8'); ?>" id="adminProfileCancelBtn" class="btn btn-light-soft">Batal</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="profile-card profile-note-card mt-3">
                <div class="section-head mb-2">
                    <h3 class="section-title mb-0">Keterangan</h3>
                </div>
                <p class="profile-note-text mb-0">
                    <?= $isAdminProfile
                        ? 'Silakan perbarui data akun jika ada perubahan nama, email, username, atau password.'
                        : 'Sesuaikan data profil ' . htmlspecialchars($isKepalaProfile ? 'Kepala LPPM' : 'Admin LPPM', ENT_QUOTES, 'UTF-8') . ' dengan identitas terbaru agar proses verifikasi, persetujuan, dan administrasi surat berjalan konsisten di seluruh sistem.'; ?>
                </p>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const form = document.getElementById('adminProfileForm');
        if (!form) {
            return;
        }

        const editBtn = document.getElementById('adminProfileEditBtn');
        const saveBtn = document.getElementById('adminProfileSaveBtn');
        const cancelBtn = document.getElementById('adminProfileCancelBtn');
        const avatarInput = document.getElementById('adminAvatarFileField');
        const avatarPreview = document.getElementById('adminAvatarPreview');
        const avatarEditBtn = document.querySelector('.profile-avatar-edit-btn');

        const setEditMode = function (isEdit) {
            form.querySelectorAll('.profile-input').forEach(function (el) {
                const permanentlyDisabled = el.dataset.permanentDisabled === '1';
                el.disabled = permanentlyDisabled ? true : !isEdit;
            });
            if (avatarInput) {
                avatarInput.disabled = !isEdit;
            }
            if (avatarEditBtn) {
                avatarEditBtn.classList.toggle('is-disabled', !isEdit);
            }

            if (saveBtn) {
                saveBtn.classList.toggle('d-none', !isEdit);
            }
            if (cancelBtn) {
                cancelBtn.classList.toggle('d-none', !isEdit);
            }
        };

        if (editBtn) {
            editBtn.addEventListener('click', function () {
                setEditMode(true);
            });
        }

        setEditMode(form.dataset.editMode === 'edit');

        if (avatarInput && avatarPreview) {
            avatarInput.addEventListener('change', function () {
                const file = avatarInput.files && avatarInput.files[0] ? avatarInput.files[0] : null;
                if (!file) {
                    return;
                }
                const objectUrl = URL.createObjectURL(file);
                avatarPreview.src = objectUrl;
            });
        }
    })();
</script>
