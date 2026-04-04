<?php
$rows = $dosenUsers ?? [];
$sum = $summary ?? ['total_dosen' => 0, 'total_prodi' => 0, 'total_with_nuptk' => 0];
$chairman = $chairman ?? null;
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$currentUser = authUser();
$currentUserId = (int) ($currentUser['id'] ?? 0);
$currentRole = authRole();
$sourceRole = impersonatorRole() ?? $currentRole;
$canImpersonateDosen = in_array($sourceRole, ['admin', 'kepala_lppm'], true);
$canImpersonateKepala = $sourceRole === 'admin';
$canChangeRole = $currentRole === 'admin';
$canBulkDelete = $canChangeRole;
$hasActiveKepala = (bool) ($hasActiveKepala ?? false);
$filters = $userFilters ?? ['keyword' => '', 'faculty' => '', 'study_program' => ''];
$filterOptions = $filterOptions ?? ['faculties' => [], 'study_programs' => []];
?>

<div class="page-content myletters-page compact-list">
    <div class="mb-4">
        <h2 class="admin-page-title mb-1">Data Pengguna Dosen</h2>
        <p class="admin-page-subtitle mb-0">Daftar pengguna dosen yang terdaftar pada sistem SAPA LPPM.</p>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!empty($chairman) && is_array($chairman)): ?>
        <?php
            $chairRoleRaw = strtolower(trim((string) ($chairman['role'] ?? '')));
            $isKepala = $chairRoleRaw === 'kepala_lppm';
            $panelTitle = $isKepala ? 'Profil Kepala LPPM' : 'Profil Admin LPPM';
            $gender = strtolower(trim((string) ($chairman['gender'] ?? '')));
            $defaultAvatar = ($gender === 'perempuan' || $gender === 'female') ? 'woman-avatar.png' : 'man-avatar.png';
            $defaultAvatarUrl = appAssetUrl('assets/img/' . $defaultAvatar);
            $avatarUrl = !empty($chairman['avatar'])
                ? buildUserAvatarUrl($basePath, (int) ($chairman['id'] ?? 0), (string) $chairman['avatar'])
                : $defaultAvatarUrl;
            $roleLabel = $isKepala ? 'Kepala LPPM' : 'Admin LPPM';
        ?>
        <div class="card dashboard-card mb-4">
            <div class="card-header bg-white border-0 pt-3 px-3 d-flex align-items-center justify-content-between gap-2">
                <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($panelTitle, ENT_QUOTES, 'UTF-8'); ?></h6>
                <?php if ($canChangeRole && $isKepala): ?>
                    <form id="roleActionForm-demote-<?= (int) ($chairman['id'] ?? 0); ?>" method="post" action="<?= htmlspecialchars($basePath . '/pengguna/ganti-role', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline m-0">
                        <input type="hidden" name="id" value="<?= (int) ($chairman['id'] ?? 0); ?>">
                        <input type="hidden" name="action" value="demote_dosen">
                        <button type="button" class="btn btn-sm user-role-btn user-role-demote js-role-action-btn" data-form-id="roleActionForm-demote-<?= (int) ($chairman['id'] ?? 0); ?>" data-confirm-title="Konfirmasi Perubahan Jabatan" data-confirm-message="Kembalikan role Kepala LPPM menjadi dosen?">Lepas Jabatan Kepala LPPM</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-body pt-2">
                <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                    <img
                        src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?= htmlspecialchars($panelTitle, ENT_QUOTES, 'UTF-8'); ?>"
                        style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1px solid #dbe6f4;"
                        onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>';"
                    >
                    <div class="flex-grow-1">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Nama:</strong> <?= htmlspecialchars((string) ($chairman['name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="col-md-6">
                                <strong>Role:</strong> <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($canImpersonateKepala && $isKepala && (int) ($chairman['id'] ?? 0) !== $currentUserId): ?>
                                    <form method="post" action="<?= htmlspecialchars($basePath . '/auth/impersonate', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline ms-2">
                                        <input type="hidden" name="target_user_id" value="<?= (int) ($chairman['id'] ?? 0); ?>">
                                        <button type="submit" class="btn btn-sm user-switch-btn">Masuk Sebagai</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6"><strong>Email:</strong> <?= htmlspecialchars((string) (($chairman['email'] ?? '') !== '' ? $chairman['email'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="col-md-6"><strong>Username:</strong> <?= htmlspecialchars((string) (($chairman['username'] ?? '') !== '' ? $chairman['username'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4 myletters-stats">
        <div class="col-md-6 col-xl-4"><div class="card dashboard-card stat-card myletters-stat-card"><div class="card-body"><div class="stat-label">Total Dosen</div><div class="stat-value"><?= (int) ($sum['total_dosen'] ?? 0); ?></div><div class="stat-icon"><i class="bi bi-people-fill"></i></div></div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card dashboard-card stat-card myletters-stat-card"><div class="card-body"><div class="stat-label">Program Studi</div><div class="stat-value"><?= (int) ($sum['total_prodi'] ?? 0); ?></div><div class="stat-icon"><i class="bi bi-journal-richtext"></i></div></div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card dashboard-card stat-card myletters-stat-card"><div class="card-body"><div class="stat-label">Dosen dengan NUPTK</div><div class="stat-value"><?= (int) ($sum['total_with_nuptk'] ?? 0); ?></div><div class="stat-icon"><i class="bi bi-patch-check"></i></div></div></div></div>
    </div>

    <div class="card dashboard-card mb-3 myletters-filter-card">
        <div class="card-body">
            <form method="get" action="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="myletters-filter-form" id="userFilterForm">
                <div class="myletters-filter-item">
                    <label class="form-label">Fakultas</label>
                    <select name="faculty" class="form-select">
                        <option value="">Semua Fakultas</option>
                        <?php foreach (($filterOptions['faculties'] ?? []) as $faculty): ?>
                            <option value="<?= htmlspecialchars((string) $faculty, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($filters['faculty'] ?? '') === (string) $faculty ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $faculty, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="myletters-filter-item">
                    <label class="form-label">Program Studi</label>
                    <select name="study_program" class="form-select">
                        <option value="">Semua Program Studi</option>
                        <?php foreach (($filterOptions['study_programs'] ?? []) as $studyProgram): ?>
                            <option value="<?= htmlspecialchars((string) $studyProgram, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($filters['study_program'] ?? '') === (string) $studyProgram ? 'selected' : ''; ?>>
                                <?= htmlspecialchars((string) $studyProgram, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="myletters-filter-item">
                    <label class="form-label">Cari</label>
                    <input
                        type="text"
                        id="userKeywordInput"
                        name="keyword"
                        class="form-control"
                        placeholder="Nama, email, username, atau NUPTK"
                        autocomplete="off"
                        value="<?= htmlspecialchars((string) ($filters['keyword'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>
                <div class="myletters-filter-btn-item">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <button type="submit" class="btn btn-primary-main myletters-btn">Terapkan</button>
                </div>
                <div class="myletters-filter-btn-item">
                    <label class="form-label form-label-ghost">Aksi</label>
                    <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft myletters-btn">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card dashboard-card mt-3 letters-table-card myletters-table-card">
        <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Pengguna Dosen</h6>
            <?php if ($canBulkDelete): ?>
                <button type="button" id="btnBulkDeleteUsers" class="btn btn-sm btn-danger" disabled data-bs-toggle="modal" data-bs-target="#bulkDeleteUserModal">
                    Hapus Terpilih
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body pt-2">
            <div class="activity-table-wrap myletters-table-wrap table-responsive">
                <table id="dosenUsersTable" data-custom-pagination="10" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <?php if ($canBulkDelete): ?>
                                <th style="width:42px;"><input type="checkbox" id="checkAllUsers"></th>
                            <?php endif; ?>
                            <th>No.</th>
                            <th>Nama Dosen</th>
                            <th>NUPTK</th>
                            <th>Fakultas</th>
                            <th>Program Studi</th>
                            <th>Google Scholar</th>
                            <th>Sinta</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="<?= $canBulkDelete ? '9' : '8'; ?>" class="text-center text-muted py-3">Belum ada pengguna dosen terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $index => $row): ?>
                                <tr>
                                    <?php if ($canBulkDelete): ?>
                                        <td><input type="checkbox" value="<?= (int) ($row['id'] ?? 0); ?>" class="user-checkbox"></td>
                                    <?php endif; ?>
                                    <td><?= (int) $index + 1; ?></td>
                                    <td><?= htmlspecialchars((string) ($row['name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) (($row['nuptk'] ?? '') !== '' ? $row['nuptk'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) (($row['faculty'] ?? '') !== '' ? $row['faculty'] : '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string) (($row['study_program'] ?? '') !== '' ? $row['study_program'] : ($row['unit'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php $googleScholar = trim((string) ($row['google_scholar_id'] ?? '')); ?>
                                        <?php if ($googleScholar !== ''): ?>
                                            <a href="<?= htmlspecialchars($googleScholar, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="profile-link-value">Google Scholar</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $sinta = trim((string) ($row['sinta_id'] ?? '')); ?>
                                        <?php if ($sinta !== ''): ?>
                                            <a href="<?= htmlspecialchars($sinta, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="profile-link-value">Sinta</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="activity-action-wrap myletters-actions">
                                            <a href="<?= htmlspecialchars($basePath . '/pengguna/dosen/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm activity-btn user-action-btn user-action-detail">Detail</a>
                                            <?php if ($canImpersonateDosen && (int) ($row['id'] ?? 0) !== $currentUserId): ?>
                                                <form method="post" action="<?= htmlspecialchars($basePath . '/auth/impersonate', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                                    <input type="hidden" name="target_user_id" value="<?= (int) ($row['id'] ?? 0); ?>">
                                                    <button type="submit" class="btn btn-sm activity-btn user-action-btn user-action-impersonate user-switch-btn">Masuk Sebagai</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($canChangeRole): ?>
                                                <form id="roleActionForm-promote-<?= (int) ($row['id'] ?? 0); ?>" method="post" action="<?= htmlspecialchars($basePath . '/pengguna/ganti-role', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0); ?>">
                                                    <input type="hidden" name="action" value="promote_kepala">
                                                    <button type="button" class="btn btn-sm activity-btn user-action-btn user-role-btn user-role-promote js-role-action-btn" data-form-id="roleActionForm-promote-<?= (int) ($row['id'] ?? 0); ?>" data-confirm-title="Konfirmasi Perubahan Jabatan" data-confirm-message="Ubah role dosen ini menjadi Kepala LPPM?" <?= $hasActiveKepala ? 'disabled title="Sudah ada Kepala LPPM aktif. Lepas jabatan dulu."' : ''; ?>>Tunjuk sebagai Kepala LPPM</button>
                                                </form>
                                            <?php endif; ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm activity-btn user-action-btn user-action-delete"
                                                data-bs-toggle="modal"
                                                data-bs-target="#hapusDosenModal"
                                                data-delete-id="<?= (int) ($row['id'] ?? 0); ?>"
                                                data-delete-name="<?= htmlspecialchars((string) ($row['name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($canBulkDelete): ?>
    <form id="bulkDeleteUsersForm" method="post" action="<?= htmlspecialchars($basePath . '/pengguna/dosen/hapus-terpilih', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    </form>
<?php endif; ?>

<div class="modal fade" id="hapusDosenModal" tabindex="-1" aria-labelledby="hapusDosenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;">
            <div class="modal-header" style="background:#f6f9ff;border-bottom:1px solid #e5edf8;">
                <h5 class="modal-title" id="hapusDosenModalLabel" style="color:#123c6b;">Konfirmasi Hapus Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Anda yakin ingin menghapus pengguna dosen berikut?</p>
                <div class="fw-semibold" id="deleteUserName" style="color:#123c6b;">-</div>
                <p class="text-muted mb-0 mt-2" style="font-size:13px;">Akun hanya bisa dihapus jika belum terhubung ke surat atau data lain di sistem.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light-soft" data-bs-dismiss="modal">Batal</button>
                <form method="post" action="<?= htmlspecialchars($basePath . '/pengguna/dosen/hapus', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="id" id="deleteUserId" value="">
                    <button type="submit" class="btn btn-danger">Hapus Pengguna</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="konfirmasiRoleModal" tabindex="-1" aria-labelledby="konfirmasiRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;">
            <div class="modal-header" style="background:#f6f9ff;border-bottom:1px solid #e5edf8;">
                <h5 class="modal-title" id="konfirmasiRoleModalLabel" style="color:#123c6b;">Konfirmasi Perubahan Jabatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="konfirmasiRoleModalMessage">Apakah Anda yakin melanjutkan perubahan jabatan ini?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light-soft" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="konfirmasiRoleSubmitBtn" class="btn btn-primary-main">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

<?php if ($canBulkDelete): ?>
    <div class="modal fade" id="bulkDeleteUserModal" tabindex="-1" aria-labelledby="bulkDeleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:14px;">
                <div class="modal-header" style="background:#f6f9ff;border-bottom:1px solid #e5edf8;">
                    <h5 class="modal-title" id="bulkDeleteUserModalLabel" style="color:#123c6b;">Konfirmasi Hapus Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Anda yakin ingin menghapus pengguna dosen terpilih?</p>
                    <div class="text-muted" style="font-size:13px;">Hanya pengguna yang belum terhubung ke data lain yang akan terhapus.</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light-soft" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDeleteUsersBtn">Hapus</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var keywordInput = document.getElementById('userKeywordInput');
    var filterForm = document.getElementById('userFilterForm');
    var keywordTimer = null;

    if (keywordInput && filterForm) {
        keywordInput.addEventListener('input', function () {
            var query = String(keywordInput.value || '').trim();
            if (keywordTimer) {
                clearTimeout(keywordTimer);
            }

            if (query.length === 0) {
                keywordTimer = window.setTimeout(function () {
                    filterForm.submit();
                }, 250);
                return;
            }

            if (query.length < 3) {
                return;
            }

            keywordTimer = window.setTimeout(function () {
                filterForm.submit();
            }, 350);
        });

        keywordInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (keywordTimer) {
                    clearTimeout(keywordTimer);
                }
                filterForm.submit();
            }
        });
    }

    var modal = document.getElementById('hapusDosenModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;
            var userId = button.getAttribute('data-delete-id') || '';
            var userName = button.getAttribute('data-delete-name') || '-';

            var idInput = document.getElementById('deleteUserId');
            var nameText = document.getElementById('deleteUserName');
            if (idInput) idInput.value = userId;
            if (nameText) nameText.textContent = userName;
        });
    }

    var roleModalEl = document.getElementById('konfirmasiRoleModal');
    var roleModalTitle = document.getElementById('konfirmasiRoleModalLabel');
    var roleModalMessage = document.getElementById('konfirmasiRoleModalMessage');
    var roleSubmitBtn = document.getElementById('konfirmasiRoleSubmitBtn');
    var activeRoleForm = null;

    if (roleModalEl && roleSubmitBtn) {
        var roleModal = new bootstrap.Modal(roleModalEl);
        document.querySelectorAll('.js-role-action-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var formId = btn.getAttribute('data-form-id') || '';
                activeRoleForm = formId !== '' ? document.getElementById(formId) : null;
                if (!activeRoleForm) return;

                if (roleModalTitle) {
                    roleModalTitle.textContent = btn.getAttribute('data-confirm-title') || 'Konfirmasi';
                }
                if (roleModalMessage) {
                    roleModalMessage.textContent = btn.getAttribute('data-confirm-message') || 'Apakah Anda yakin melanjutkan aksi ini?';
                }
                roleModal.show();
            });
        });

        roleSubmitBtn.addEventListener('click', function () {
            if (activeRoleForm) {
                activeRoleForm.submit();
            }
        });
    }

    var checkAllUsers = document.getElementById('checkAllUsers');
    var userCheckboxes = document.querySelectorAll('.user-checkbox');
    var bulkDeleteUsersBtn = document.getElementById('btnBulkDeleteUsers');
    var confirmBulkDeleteUsersBtn = document.getElementById('confirmBulkDeleteUsersBtn');
    var bulkDeleteUsersForm = document.getElementById('bulkDeleteUsersForm');

    var refreshBulkDeleteUsersState = function () {
        var selectedCount = 0;
        userCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                selectedCount += 1;
            }
        });
        if (bulkDeleteUsersBtn) {
            bulkDeleteUsersBtn.disabled = selectedCount === 0;
        }
    };

    if (checkAllUsers) {
        checkAllUsers.addEventListener('change', function () {
            userCheckboxes.forEach(function (cb) {
                cb.checked = checkAllUsers.checked;
            });
            refreshBulkDeleteUsersState();
        });
    }

    userCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (!cb.checked && checkAllUsers) {
                checkAllUsers.checked = false;
            }
            if (checkAllUsers) {
                var allChecked = true;
                userCheckboxes.forEach(function (item) {
                    if (!item.checked) {
                        allChecked = false;
                    }
                });
                checkAllUsers.checked = allChecked && userCheckboxes.length > 0;
            }
            refreshBulkDeleteUsersState();
        });
    });

    if (confirmBulkDeleteUsersBtn && bulkDeleteUsersForm) {
        confirmBulkDeleteUsersBtn.addEventListener('click', function () {
            bulkDeleteUsersForm.querySelectorAll('input[name="user_ids[]"]').forEach(function (input) {
                input.remove();
            });

            userCheckboxes.forEach(function (cb) {
                if (!cb.checked) {
                    return;
                }

                var hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'user_ids[]';
                hiddenInput.value = cb.value || '';
                bulkDeleteUsersForm.appendChild(hiddenInput);
            });

            bulkDeleteUsersForm.submit();
        });
    }

    refreshBulkDeleteUsersState();
});
</script>
