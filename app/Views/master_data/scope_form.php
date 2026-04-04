<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$schemes = is_array($schemes ?? null) ? $schemes : [];
$formValues = is_array($formValues ?? null) ? $formValues : [];
$formMode = (string) ($formMode ?? 'create');
$isEdit = $formMode === 'edit';
?>
<?php require __DIR__ . '/_styles.php'; ?>
<div class="page-content admin-profile-page master-form-page">
    <div class="profile-header mb-3">
        <div>
            <h1 class="page-title mb-1"><?= htmlspecialchars((string) ($pageTitle ?? 'Form Ruang Lingkup'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle mb-0"><?= htmlspecialchars((string) ($pageSubtitle ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="<?= htmlspecialchars($basePath . '/' . ltrim((string) ($backPath ?? 'master-data/ruang-lingkup'), '/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main profile-back-btn">Kembali</a>
    </div>

    <?php if (!empty($successMessage)): ?><div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!empty($errorMessage)): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="profile-card profile-detail-card master-form-page-card mt-3">
        <div class="section-head mb-3">
            <h3 class="section-title mb-0"><?= $isEdit ? 'Edit Ruang Lingkup' : 'Tambah Ruang Lingkup'; ?></h3>
        </div>
        <div>
            <form method="post" action="<?= htmlspecialchars($basePath . '/' . ltrim((string) ($savePath ?? 'master-data/ruang-lingkup/simpan'), '/'), ENT_QUOTES, 'UTF-8'); ?>" class="row g-3 master-form-grid">
                <input type="hidden" name="id" value="<?= (int) ($formValues['id'] ?? 0); ?>">
                <div class="col-md-6 master-field-group">
                    <label class="form-label">Skema</label>
                    <select name="scheme_id" class="form-select profile-input" required>
                        <option value="">-- Pilih Skema --</option>
                        <?php foreach ($schemes as $scheme): ?>
                            <option value="<?= (int) ($scheme['id'] ?? 0); ?>" <?= (int) ($formValues['scheme_id'] ?? 0) === (int) ($scheme['id'] ?? 0) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars(ucfirst((string) ($scheme['activity_category_code'] ?? '')) . ' - ' . (string) ($scheme['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="master-field-note">Ruang lingkup akan mengikuti skema yang dipilih ini.</small>
                </div>
                <div class="col-md-3 master-field-group">
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" class="form-control profile-input" value="<?= htmlspecialchars((string) ($formValues['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    <small class="master-field-note">Gunakan kode unik untuk identifikasi internal.</small>
                </div>
                <div class="col-md-3 master-field-group">
                    <label class="form-label">Urutan</label>
                    <input type="number" min="1" name="sort_order" class="form-control profile-input" value="<?= (int) ($formValues['sort_order'] ?? 1); ?>">
                    <small class="master-field-note">Menentukan urutan daftar saat user memilih ruang lingkup.</small>
                </div>
                <div class="col-md-8 master-field-group">
                    <label class="form-label">Nama Ruang Lingkup</label>
                    <input type="text" name="name" class="form-control profile-input" value="<?= htmlspecialchars((string) ($formValues['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    <small class="master-field-note">Nama detail yang tampil setelah skema dipilih.</small>
                </div>
                <div class="col-md-4">
                    <div class="master-check-section h-100 justify-content-center">
                        <label class="form-check-label d-flex align-items-center gap-2">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" <?= (int) ($formValues['is_active'] ?? 1) === 1 ? 'checked' : ''; ?>>
                            <span>Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="col-12 master-field-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="description" class="form-control profile-input" value="<?= htmlspecialchars((string) ($formValues['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <small class="master-field-note">Opsional untuk menjelaskan ruang lingkup secara singkat.</small>
                </div>
                <div class="col-12 master-form-actions">
                    <a href="<?= htmlspecialchars($basePath . '/' . ltrim((string) ($backPath ?? 'master-data/ruang-lingkup'), '/'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Batal</a>
                    <button type="submit" class="btn btn-primary-main"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="profile-card profile-note-card mt-3">
        <div class="section-head mb-2">
            <h3 class="section-title mb-0">Keterangan</h3>
        </div>
        <p class="profile-note-text mb-0">Ruang lingkup akan mengikuti skema yang dipilih, sehingga pilihan pada form dosen tetap terstruktur dan sesuai konteks kegiatan.</p>
    </div>
</div>
