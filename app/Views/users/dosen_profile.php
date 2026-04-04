<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');

$row = $dosen ?? [];
$availableColumns = $availableColumns ?? [];
$columnMap = array_flip(array_map('strval', $availableColumns));
$hasColumn = static function (string $name) use ($columnMap): bool {
    return isset($columnMap[$name]);
};
$viewMode = (string) ($viewMode ?? 'detail');
$startInEditMode = $viewMode === 'edit' || !empty($errorMessage);
$gender = strtolower(trim((string) ($row['gender'] ?? '')));
$defaultAvatar = ($gender === 'perempuan' || $gender === 'female') ? 'woman-avatar.png' : 'man-avatar.png';
$defaultAvatarUrl = appAssetUrl('assets/img/' . $defaultAvatar);
$avatarUrl = !empty($row['avatar'])
    ? buildUserAvatarUrl($basePath, (int) ($row['id'] ?? 0), (string) $row['avatar'])
    : $defaultAvatarUrl;
?>

<div class="page-content admin-profile-page">
    <div class="profile-header mb-3">
        <div>
            <h1 class="page-title mb-1"><?= htmlspecialchars($viewMode === 'edit' ? 'Edit Profil Dosen' : 'Detail Profil Dosen', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle mb-0">Informasi identitas dosen yang terdaftar pada sistem SAPA LPPM.</p>
        </div>
        <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main profile-back-btn">Kembali</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="profile-card profile-summary-card h-100">
                <div class="profile-summary-top">
                    <div class="profile-avatar-wrap">
                        <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar Dosen" class="profile-avatar" onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>';">
                    </div>
                    <div class="profile-user-name"><?= htmlspecialchars((string) ($row['name'] ?? 'Dosen'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-user-role">Dosen</div>
                </div>

                <div class="profile-summary-divider"></div>

                <div class="profile-mini-list">
                    <div class="profile-mini-item">
                        <span class="mini-label">NUPTK</span>
                        <span class="mini-value"><?= htmlspecialchars((string) (($row['nuptk'] ?? '') !== '' ? $row['nuptk'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">NIDN</span>
                        <span class="mini-value"><?= htmlspecialchars((string) (($row['nidn'] ?? '') !== '' ? $row['nidn'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">Email</span>
                        <span class="mini-value"><?= htmlspecialchars((string) (($row['email'] ?? '') !== '' ? $row['email'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">Fakultas</span>
                        <span class="mini-value"><?= htmlspecialchars((string) (($row['faculty'] ?? '') !== '' ? $row['faculty'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">Program Studi</span>
                        <span class="mini-value"><?= htmlspecialchars((string) (($row['study_program'] ?? '') !== '' ? $row['study_program'] : ($row['unit'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">No HP</span>
                        <span class="mini-value"><?= htmlspecialchars((string) (($row['phone'] ?? '') !== '' ? $row['phone'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">ID Google Scholar</span>
                        <?php $googleScholarLink = trim((string) ($row['google_scholar_id'] ?? '')); ?>
                        <?php if ($googleScholarLink !== ''): ?>
                            <a href="<?= htmlspecialchars($googleScholarLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="mini-value profile-link-value">Google Scholar</a>
                        <?php else: ?>
                            <span class="mini-value">-</span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">ID Sinta</span>
                        <?php $sintaLink = trim((string) ($row['sinta_id'] ?? '')); ?>
                        <?php if ($sintaLink !== ''): ?>
                            <a href="<?= htmlspecialchars($sintaLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="mini-value profile-link-value">Sinta</a>
                        <?php else: ?>
                            <span class="mini-value">-</span>
                        <?php endif; ?>
                    </div>
                </div>
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

                <form id="dosenManageForm" action="<?= htmlspecialchars($basePath . '/pengguna/dosen/simpan', ENT_QUOTES, 'UTF-8'); ?>" method="post" class="row g-3" data-edit-mode="<?= $startInEditMode ? 'edit' : 'lock'; ?>">
                    <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0); ?>">
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">Nama</label>
                            <input type="text" name="name" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama lengkap beserta gelar" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">NIDN</label>
                            <input type="text" name="nidn" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['nidn'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NIDN (opsional)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">NUPTK</label>
                            <input type="text" name="nuptk" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['nuptk'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NUPTK" <?= $hasColumn('nuptk') ? 'required' : 'disabled data-permanent-disabled="1"'; ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">Email</label>
                            <input type="email" name="email" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan email aktif" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">Username</label>
                            <input type="text" name="username" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama pengguna" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">Fakultas</label>
                            <?php
                            $facultyValue = (string) ($row['faculty'] ?? '');
                            $studyProgramValue = (string) ($row['study_program'] ?? $row['unit'] ?? '');
                            ?>
                            <select id="manageFacultySelect" class="form-select profile-input" <?= $hasColumn('faculty') ? 'required' : 'disabled data-permanent-disabled="1"'; ?>>
                                <option value="">-- Pilih Fakultas --</option>
                                <option value="FTP">Fakultas Teknik dan Perencanaan (FTP)</option>
                                <option value="FMIPA">Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)</option>
                                <option value="FKIP">Fakultas Keguruan dan Ilmu Pendidikan (FKIP)</option>
                            </select>
                            <input type="hidden" id="manageFacultyNameInput" name="faculty" value="<?= htmlspecialchars($facultyValue, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">Program Studi</label>
                            <select id="manageProgramSelect" class="form-select profile-input" <?= $hasColumn('study_program') ? 'required' : 'disabled data-permanent-disabled="1"'; ?>>
                                <option value="">-- Pilih Program Studi --</option>
                            </select>
                            <input type="hidden" id="manageUnitInput" name="unit" value="<?= htmlspecialchars($studyProgramValue, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" id="manageStudyProgramInput" name="study_program" value="<?= htmlspecialchars($studyProgramValue, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">No HP</label>
                            <input type="text" name="phone" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 0812xxxxxxxx" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">Jenis Kelamin</label>
                            <?php $genderRaw = (string) ($row['gender'] ?? ''); ?>
                            <select name="gender" class="form-select profile-input" <?= $hasColumn('gender') ? 'required' : 'disabled data-permanent-disabled="1"'; ?>>
                                <option value="" <?= $genderRaw === '' ? 'selected' : ''; ?>>Pilih</option>
                                <option value="Laki-laki" <?= $genderRaw === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="Perempuan" <?= $genderRaw === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control profile-input" minlength="8" placeholder="Kosongkan jika tidak diubah">
                            <small class="field-helper">Minimal 8 karakter. Kosongkan jika tidak ingin mengganti password.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">ID Google Scholar</label>
                            <input type="url" name="google_scholar_id" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['google_scholar_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://scholar.google.com/citations?user=...&hl=id" <?= $hasColumn('google_scholar_id') ? '' : 'disabled data-permanent-disabled="1"'; ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label">ID Sinta</label>
                            <input type="url" name="sinta_id" class="form-control profile-input" value="<?= htmlspecialchars((string) ($row['sinta_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://sinta.kemdiktisaintek.go.id/authors/profile/..." <?= $hasColumn('sinta_id') ? '' : 'disabled data-permanent-disabled="1"'; ?>>
                        </div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="button" id="dosenManageEditBtn" class="btn btn-light-soft">Edit</button>
                        <button type="submit" id="dosenManageSaveBtn" class="btn btn-primary-main">Simpan Perubahan</button>
                        <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" id="dosenManageCancelBtn" class="btn btn-light-soft">Batal</a>
                    </div>
                </form>
            </div>

            <div class="profile-card profile-note-card mt-3">
                <div class="section-head mb-2">
                    <h3 class="section-title mb-0">Keterangan</h3>
                </div>
                <p class="profile-note-text mb-0">
                    Perubahan profil dosen akan memengaruhi data identitas pada proses pengajuan surat dosen di sistem SAPA LPPM.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('dosenManageForm');
        const editBtn = document.getElementById('dosenManageEditBtn');
        const saveBtn = document.getElementById('dosenManageSaveBtn');
        const cancelBtn = document.getElementById('dosenManageCancelBtn');
        const facultySelect = document.getElementById('manageFacultySelect');
        const programSelect = document.getElementById('manageProgramSelect');
        const facultyNameInput = document.getElementById('manageFacultyNameInput');
        const unitInput = document.getElementById('manageUnitInput');
        const studyProgramInput = document.getElementById('manageStudyProgramInput');
        const savedFacultyLabel = facultyNameInput ? facultyNameInput.value : '';
        const savedProgram = unitInput ? unitInput.value : '';

        const facultyPrograms = {
            FTP: {
                label: 'Fakultas Teknik dan Perencanaan (FTP)',
                programs: ['Teknik Lingkungan (TL)', 'Teknik Informatika (TI)'],
            },
            FMIPA: {
                label: 'Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)',
                programs: ['Statistika (STAT)', 'Matematika (MAT)', 'Fisika (FIS)', 'Biologi (BO)'],
            },
            FKIP: {
                label: 'Fakultas Keguruan dan Ilmu Pendidikan (FKIP)',
                programs: ['Pendidikan Luar Biasa (PLB)', 'Pendidikan Jasmani, Kesehatan, dan Rekreasi (PJKR)', 'Pendidikan Bahasa Inggris (PBI)', 'Pendidikan Guru Sekolah Dasar (PGSD)'],
            },
        };

        const setEditMode = function (isEdit) {
            if (!form) return;
            form.querySelectorAll('.profile-input').forEach(function (el) {
                const permanentlyDisabled = el.dataset.permanentDisabled === '1';
                el.disabled = permanentlyDisabled ? true : !isEdit;
            });
            if (saveBtn) saveBtn.classList.toggle('d-none', !isEdit);
            if (cancelBtn) cancelBtn.classList.toggle('d-none', !isEdit);
        };

        const setProgramOptions = function (facultyKey, selectedProgram) {
            if (!programSelect) return;
            programSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';
            if (!facultyKey || !facultyPrograms[facultyKey]) {
                programSelect.disabled = true;
                return;
            }
            facultyPrograms[facultyKey].programs.forEach(function (program) {
                const option = document.createElement('option');
                option.value = program;
                option.textContent = program;
                programSelect.appendChild(option);
            });
            programSelect.disabled = false;
            if (selectedProgram) {
                programSelect.value = selectedProgram;
            }
        };

        const syncProgramToInputs = function () {
            if (!programSelect || !unitInput || !studyProgramInput) return;
            const value = programSelect.value || '';
            unitInput.value = value;
            studyProgramInput.value = value;
        };

        const syncFacultyName = function () {
            if (!facultySelect || !facultyNameInput) return;
            const key = facultySelect.value;
            facultyNameInput.value = key && facultyPrograms[key] ? facultyPrograms[key].label : '';
        };

        const initFacultyProgram = function () {
            if (!facultySelect || !programSelect) return;
            let matchedKey = '';
            Object.keys(facultyPrograms).forEach(function (key) {
                if (facultyPrograms[key].label === savedFacultyLabel) {
                    matchedKey = key;
                }
            });
            if (!matchedKey && savedProgram) {
                Object.keys(facultyPrograms).forEach(function (key) {
                    if (facultyPrograms[key].programs.indexOf(savedProgram) !== -1) {
                        matchedKey = key;
                    }
                });
            }
            if (matchedKey) {
                facultySelect.value = matchedKey;
                setProgramOptions(matchedKey, savedProgram);
                syncFacultyName();
                syncProgramToInputs();
            } else {
                setProgramOptions('');
            }
        };

        if (form) {
            initFacultyProgram();
            if (facultySelect) {
                facultySelect.addEventListener('change', function () {
                    setProgramOptions(facultySelect.value);
                    syncFacultyName();
                    syncProgramToInputs();
                });
            }
            if (programSelect) {
                programSelect.addEventListener('change', syncProgramToInputs);
            }
            setEditMode(form.dataset.editMode === 'edit');
            if (editBtn) {
                editBtn.addEventListener('click', function () {
                    setEditMode(true);
                });
            }
        }
    })();
</script>
