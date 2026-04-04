<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');

$user = $user ?? [];
$availableColumns = $availableColumns ?? [];
$columnMap = array_flip(array_map('strval', $availableColumns));
$displayName = (string) ($user['name'] ?? 'Dosen Pengusul');
$gender = strtolower(trim((string) ($user['gender'] ?? '')));
$defaultAvatar = ($gender === 'perempuan' || $gender === 'female') ? 'woman-avatar.png' : 'man-avatar.png';
$defaultAvatarUrl = appAssetUrl('assets/img/' . $defaultAvatar);
$avatarUrl = !empty($user['avatar'])
    ? buildUserAvatarUrl($basePath, (int) ($user['id'] ?? 0), (string) $user['avatar'])
    : $defaultAvatarUrl;
$hasColumn = static function (string $name) use ($columnMap): bool {
    return isset($columnMap[$name]);
};
$infoMessage = $infoMessage ?? null;
$isProfileComplete = (bool) ($isProfileComplete ?? false);
$startInEditMode = !empty($errorMessage) || !$isProfileComplete;
?>

<div class="page-content profile-page">
    <div class="profile-header mb-3">
        <div>
            <h1 class="page-title mb-1">Profil Dosen</h1>
            <p class="page-subtitle mb-0">
                <?= htmlspecialchars($isProfileComplete
                    ? 'Informasi identitas dosen yang terdaftar pada sistem SAPA LPPM.'
                    : 'Lengkapi profil Anda terlebih dahulu untuk mulai menggunakan seluruh fitur SAPA LPPM.', ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
        <?php if ($isProfileComplete): ?>
            <a href="<?= htmlspecialchars($basePath . '/dashboard-dosen', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main profile-back-btn">Kembali</a>
        <?php endif; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="profile-card profile-summary-card h-100">
                <div class="profile-summary-top">
                    <div class="profile-avatar-wrap">
                        <img id="profileAvatarPreview" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar Dosen" class="profile-avatar" onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>';">
                        <label for="avatarFileField" class="profile-avatar-edit-btn" title="Ganti avatar">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                    </div>

                    <div class="profile-user-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-user-role">Dosen</div>
                </div>

                <div class="profile-summary-divider"></div>

                <div class="profile-mini-list">
                    <div class="profile-mini-item">
                        <span class="mini-label">NUPTK</span>
                        <span class="mini-value"><?= htmlspecialchars((string) ($user['nuptk'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">NIDN</span>
                        <?php $nidnValue = trim((string) ($user['nidn'] ?? '')); ?>
                        <span class="mini-value"><?= htmlspecialchars($nidnValue !== '' ? $nidnValue : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">Email</span>
                        <span class="mini-value"><?= htmlspecialchars((string) ($user['email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">Fakultas</span>
                        <span class="mini-value"><?= htmlspecialchars((string) ($user['faculty'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">Program Studi</span>
                        <span class="mini-value"><?= htmlspecialchars((string) ($user['study_program'] ?? $user['unit'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">No HP</span>
                        <span class="mini-value"><?= htmlspecialchars((string) ($user['phone'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">ID Google Scholar</span>
                        <?php $googleScholarLink = trim((string) ($user['google_scholar_id'] ?? '')); ?>
                        <?php if ($googleScholarLink !== ''): ?>
                            <a href="<?= htmlspecialchars($googleScholarLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="mini-value profile-link-value">Google Scholar</a>
                        <?php else: ?>
                            <span class="mini-value">-</span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-mini-item">
                        <span class="mini-label">ID Sinta</span>
                        <?php $sintaLink = trim((string) ($user['sinta_id'] ?? '')); ?>
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
            <?php if (!$isProfileComplete): ?>
                <div class="profile-onboarding-banner mb-3">
                    <div class="profile-onboarding-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="profile-onboarding-content">
                        <div class="profile-onboarding-title">Lengkapi profil untuk mulai menggunakan SAPA LPPM</div>
                        <p class="profile-onboarding-text mb-0">
                            Isi seluruh data wajib seperti NUPTK, email aktif, fakultas, program studi, no HP, dan jenis kelamin.
                            Setelah profil lengkap, menu dashboard dan persuratan akan otomatis terbuka.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success profile-alert"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($infoMessage)): ?>
                <div class="alert alert-info profile-alert"><?= htmlspecialchars((string) $infoMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger profile-alert"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="profile-card profile-detail-card profile-form-card">
                <div class="section-head mb-3">
                    <h3 class="section-title mb-0"><?= htmlspecialchars($isProfileComplete ? 'Edit Biodata' : 'Lengkapi Biodata Wajib', ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>

                <form id="profileEditForm" action="<?= htmlspecialchars($basePath . '/profil/simpan', ENT_QUOTES, 'UTF-8'); ?>" method="post" enctype="multipart/form-data" class="row g-3" data-edit-mode="<?= $startInEditMode ? 'edit' : 'lock'; ?>">
                    <input id="avatarFileField" type="file" name="avatar_file" accept="image/png,image/jpeg,image/webp" class="d-none">
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="nameField">Nama</label>
                            <input id="nameField" type="text" name="name" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama lengkap beserta gelar" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="nidnField">NIDN</label>
                            <input id="nidnField" type="text" name="nidn" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['nidn'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NIDN (opsional)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="nuptkField">NUPTK</label>
                            <input id="nuptkField" type="text" name="nuptk" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['nuptk'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan NUPTK" <?= $hasColumn('nuptk') ? 'required' : 'disabled data-permanent-disabled=\"1\"'; ?>>
                            <?php if (!$hasColumn('nuptk')): ?><small class="field-helper">Kolom belum tersedia di database.</small><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="emailField">Email</label>
                            <input id="emailField" type="email" name="email" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan email aktif" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="usernameField">Username</label>
                            <input id="usernameField" type="text" name="username" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Masukkan nama pengguna" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="facultyField">Fakultas</label>
                            <?php
                            $facultyValue = (string) ($user['faculty'] ?? '');
                            $studyProgramValue = (string) ($user['study_program'] ?? $user['unit'] ?? '');
                            ?>
                            <select id="profileFacultySelect" class="form-select profile-input" <?= $hasColumn('faculty') ? 'required' : 'disabled data-permanent-disabled=\"1\"'; ?>>
                                <option value="">-- Pilih Fakultas --</option>
                                <option value="FTP">Fakultas Teknik dan Perencanaan (FTP)</option>
                                <option value="FMIPA">Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)</option>
                                <option value="FKIP">Fakultas Keguruan dan Ilmu Pendidikan (FKIP)</option>
                            </select>
                            <input type="hidden" id="profileFacultyNameInput" name="faculty" value="<?= htmlspecialchars($facultyValue, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!$hasColumn('faculty')): ?><small class="field-helper">Kolom belum tersedia di database.</small><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="studyProgramField">Program Studi</label>
                            <select id="profileProgramSelect" class="form-select profile-input" <?= $hasColumn('study_program') ? 'required' : 'disabled data-permanent-disabled=\"1\"'; ?>>
                                <option value="">-- Pilih Program Studi --</option>
                            </select>
                            <input type="hidden" id="profileUnitInput" name="unit" value="<?= htmlspecialchars($studyProgramValue, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" id="profileStudyProgramInput" name="study_program" value="<?= htmlspecialchars($studyProgramValue, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!$hasColumn('study_program')): ?><small class="field-helper">Kolom belum tersedia di database.</small><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="phoneField">No HP</label>
                            <input id="phoneField" type="text" name="phone" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 0812xxxxxxxx" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="genderField">Jenis Kelamin</label>
                            <select id="genderField" name="gender" class="form-select profile-input" <?= $hasColumn('gender') ? 'required' : 'disabled data-permanent-disabled=\"1\"'; ?>>
                                <?php $genderRaw = (string) ($user['gender'] ?? ''); ?>
                                <option value="" <?= $genderRaw === '' ? 'selected' : ''; ?>>Pilih</option>
                                <option value="Laki-laki" <?= $genderRaw === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="Perempuan" <?= $genderRaw === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                            <?php if (!$hasColumn('gender')): ?><small class="field-helper">Kolom belum tersedia di database.</small><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="currentPasswordField">Password Saat Ini</label>
                            <input id="currentPasswordField" type="password" name="current_password" class="form-control profile-input" placeholder="Wajib diisi jika ganti password">
                            <small class="field-helper">Isi password saat ini hanya jika Anda ingin mengganti password.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="newPasswordField">Password Baru</label>
                            <input id="newPasswordField" type="password" name="new_password" class="form-control profile-input" minlength="8" placeholder="Kosongkan jika tidak diubah">
                            <small class="field-helper">Minimal 8 karakter. Kosongkan jika tidak ingin mengganti password.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="googleScholarField">ID Google Scholar</label>
                            <input id="googleScholarField" type="url" name="google_scholar_id" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['google_scholar_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://scholar.google.com/citations?user=...&hl=id" <?= $hasColumn('google_scholar_id') ? '' : 'disabled data-permanent-disabled=\"1\"'; ?>>
                            <?php if (!$hasColumn('google_scholar_id')): ?><small class="field-helper">Kolom belum tersedia di database.</small><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-form-item">
                            <label class="profile-info-label" for="sintaField">ID Sinta</label>
                            <input id="sintaField" type="url" name="sinta_id" class="form-control profile-input" value="<?= htmlspecialchars((string) ($user['sinta_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://sinta.kemdiktisaintek.go.id/authors/profile/..." <?= $hasColumn('sinta_id') ? '' : 'disabled data-permanent-disabled=\"1\"'; ?>>
                            <?php if (!$hasColumn('sinta_id')): ?><small class="field-helper">Kolom belum tersedia di database.</small><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="button" id="profileEditBtn" class="btn btn-light-soft">Edit</button>
                        <button type="submit" id="profileSaveBtn" class="btn btn-primary-main">Simpan Perubahan</button>
                        <a href="<?= htmlspecialchars($basePath . '/dashboard-dosen', ENT_QUOTES, 'UTF-8'); ?>" id="profileCancelBtn" class="btn btn-light-soft">Batal</a>
                    </div>
                </form>
            </div>

            <div class="profile-card profile-note-card mt-3">
                <div class="section-head mb-2">
                    <h3 class="section-title mb-0">Keterangan</h3>
                </div>
                <p class="profile-note-text mb-0">
                    Data profil ini digunakan untuk identitas pemohon, proses administrasi surat, dan sinkronisasi data dosen pada sistem SAPA LPPM.
                </p>
                <?php if (!$isProfileComplete): ?>
                    <p class="profile-note-text mt-2 mb-0">
                        Sebelum profil lengkap, akses ke dashboard dan menu persuratan akan dibatasi oleh sistem.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const profileForm = document.getElementById('profileEditForm');
        const editBtn = document.getElementById('profileEditBtn');
        const saveBtn = document.getElementById('profileSaveBtn');
        const cancelBtn = document.getElementById('profileCancelBtn');
        const avatarInput = document.getElementById('avatarFileField');
        const avatarPreview = document.getElementById('profileAvatarPreview');
        const avatarEditBtn = document.querySelector('.profile-avatar-edit-btn');
        const facultySelect = document.getElementById('profileFacultySelect');
        const programSelect = document.getElementById('profileProgramSelect');
        const facultyNameInput = document.getElementById('profileFacultyNameInput');
        const unitInput = document.getElementById('profileUnitInput');
        const studyProgramInput = document.getElementById('profileStudyProgramInput');
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
            if (!profileForm) {
                return;
            }
            profileForm.querySelectorAll('.profile-input').forEach(function (el) {
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

        const setProgramOptions = function (facultyKey, selectedProgram) {
            if (!programSelect) {
                return;
            }
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
            if (!programSelect || !unitInput || !studyProgramInput) {
                return;
            }
            const value = programSelect.value || '';
            unitInput.value = value;
            studyProgramInput.value = value;
        };

        const syncFacultyName = function () {
            if (!facultySelect || !facultyNameInput) {
                return;
            }
            const key = facultySelect.value;
            facultyNameInput.value = key && facultyPrograms[key] ? facultyPrograms[key].label : '';
        };

        const initFacultyProgram = function () {
            if (!facultySelect || !programSelect) {
                return;
            }
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

        if (profileForm && editBtn) {
            const initialMode = profileForm.dataset.editMode === 'edit';
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
            setEditMode(initialMode);
            editBtn.addEventListener('click', function () {
                setEditMode(true);
            });
        }

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
