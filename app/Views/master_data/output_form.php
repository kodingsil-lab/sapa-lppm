<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$categories = is_array($categories ?? null) ? $categories : [];
$formValues = is_array($formValues ?? null) ? $formValues : [];
$selectedCategories = is_array($selectedCategories ?? null) ? $selectedCategories : [];
$formMode = (string) ($formMode ?? 'create');
$isEdit = $formMode === 'edit';
?>
<?php require __DIR__ . '/_styles.php'; ?>
<div class="page-content admin-profile-page master-form-page">
    <div class="profile-header mb-3">
        <div>
            <h1 class="page-title mb-1"><?= htmlspecialchars((string) ($pageTitle ?? 'Form Jenis Luaran'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle mb-0"><?= htmlspecialchars((string) ($pageSubtitle ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="<?= htmlspecialchars($basePath . '/' . ltrim((string) ($backPath ?? 'master-data/luaran'), '/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main profile-back-btn">Kembali</a>
    </div>

    <?php if (!empty($successMessage)): ?><div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!empty($errorMessage)): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="profile-card profile-detail-card master-form-page-card mt-3">
        <div class="section-head mb-3">
            <h3 class="section-title mb-0"><?= $isEdit ? 'Edit Jenis Luaran' : 'Tambah Jenis Luaran'; ?></h3>
        </div>
        <div>
            <form method="post" action="<?= htmlspecialchars($basePath . '/' . ltrim((string) ($savePath ?? 'master-data/luaran/simpan'), '/'), ENT_QUOTES, 'UTF-8'); ?>" class="row g-3 master-form-grid">
                <input type="hidden" name="id" value="<?= (int) ($formValues['id'] ?? 0); ?>">
                <div class="col-md-4 master-field-group">
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" class="form-control profile-input" value="<?= htmlspecialchars((string) ($formValues['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="contoh: prosiding_nasional" required>
                    <small class="master-field-note">Gunakan kode unik tanpa spasi agar mudah dipakai sistem.</small>
                </div>
                <div class="col-md-8 master-field-group">
                    <label class="form-label">Nama Luaran</label>
                    <input type="text" name="name" class="form-control profile-input" value="<?= htmlspecialchars((string) ($formValues['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nama jenis luaran" required>
                    <small class="master-field-note">Nama ini yang akan tampil pada form dosen.</small>
                </div>
                <div class="col-12">
                    <div class="master-check-section">
                        <div class="master-check-title">Kategori Kegiatan Berlaku</div>
                        <div class="master-check-grid">
                            <?php foreach ($categories as $category): ?>
                                <?php $categoryCode = strtolower(trim((string) ($category['code'] ?? ''))); ?>
                                <label class="form-check-label d-flex align-items-center gap-2">
                                    <input type="checkbox" class="form-check-input" name="activity_categories[]" value="<?= htmlspecialchars($categoryCode, ENT_QUOTES, 'UTF-8'); ?>" <?= in_array($categoryCode, $selectedCategories, true) ? 'checked' : ''; ?>>
                                    <span><?= htmlspecialchars((string) ($category['name'] ?? $categoryCode), ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="master-field-note">Pilih modul kegiatan yang boleh memakai jenis luaran ini.</small>
                    </div>
                </div>
                <div class="col-md-4 master-field-group">
                    <label class="form-label">Urutan</label>
                    <input type="number" min="1" name="sort_order" class="form-control profile-input" value="<?= (int) ($formValues['sort_order'] ?? 1); ?>">
                    <small class="master-field-note">Angka kecil akan tampil lebih dulu pada daftar pilihan.</small>
                </div>
                <div class="col-md-8 master-field-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="description" class="form-control profile-input" value="<?= htmlspecialchars((string) ($formValues['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Opsional">
                    <small class="master-field-note">Opsional untuk penjelasan internal admin.</small>
                </div>
                <div class="col-12">
                    <div class="master-check-section">
                        <div class="master-check-title">Pengaturan Pemakaian</div>
                        <div class="master-check-grid">
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input" name="allow_required" value="1" <?= (int) ($formValues['allow_required'] ?? 1) === 1 ? 'checked' : ''; ?>>
                                <span>Berlaku untuk Luaran Wajib</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input" name="allow_additional" value="1" <?= (int) ($formValues['allow_additional'] ?? 1) === 1 ? 'checked' : ''; ?>>
                                <span>Berlaku untuk Luaran Tambahan</span>
                            </label>
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= (int) ($formValues['is_active'] ?? 1) === 1 ? 'checked' : ''; ?>>
                                <span>Aktif</span>
                            </label>
                        </div>
                        <small class="master-field-note">Atur apakah luaran ini muncul pada pilihan wajib, tambahan, atau keduanya.</small>
                    </div>
                </div>
                <div class="col-12 master-form-actions">
                    <a href="<?= htmlspecialchars($basePath . '/' . ltrim((string) ($backPath ?? 'master-data/luaran'), '/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Batal</a>
                    <button type="submit" class="btn btn-primary-main"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="profile-card profile-note-card mt-3">
        <div class="section-head mb-2">
            <h3 class="section-title mb-0">Keterangan</h3>
        </div>
        <p class="profile-note-text mb-0">Gunakan halaman ini untuk mengatur jenis luaran yang muncul pada form penelitian, pengabdian, dan hilirisasi sesuai kebutuhan admin.</p>
    </div>
</div>
