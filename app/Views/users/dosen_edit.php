<?php
$row = $dosen ?? [];
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
?>

<div class="page-content myletters-page compact-list">
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="admin-page-title mb-1">Edit Profil Dosen</h2>
            <p class="admin-page-subtitle mb-0">Perbarui data pengguna dosen pada sistem SAPA LPPM.</p>
        </div>
        <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Kembali</a>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card dashboard-card">
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($basePath . '/pengguna/dosen/simpan', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0); ?>">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama Dosen</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama lengkap beserta gelar"></div>
                    <div class="col-md-6"><label class="form-label">NIDN</label><input type="text" name="nidn" class="form-control" value="<?= htmlspecialchars((string) ($row['nidn'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NIDN (opsional)"></div>
                    <div class="col-md-6"><label class="form-label">NUPTK</label><input type="text" name="nuptk" class="form-control" required value="<?= htmlspecialchars((string) ($row['nuptk'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NUPTK"></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan email aktif"></div>
                    <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required value="<?= htmlspecialchars((string) ($row['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama pengguna"></div>
                    <div class="col-md-6"><label class="form-label">Nomor HP</label><input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars((string) ($row['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 0812xxxxxxxx"></div>
                    <div class="col-md-6"><label class="form-label">Fakultas</label><input type="text" name="faculty" class="form-control" required value="<?= htmlspecialchars((string) ($row['faculty'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan fakultas"></div>
                    <div class="col-md-6"><label class="form-label">Program Studi</label><input type="text" name="study_program" class="form-control" required value="<?= htmlspecialchars((string) ($row['study_program'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan program studi"></div>
                    <div class="col-md-6"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" value="<?= htmlspecialchars((string) ($row['unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan unit (opsional)"></div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-select" required>
                            <option value="Laki-laki" <?= (string) ($row['gender'] ?? '') === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?= (string) ($row['gender'] ?? '') === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password Baru (Opsional)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-main">Simpan Perubahan</button>
                    <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
