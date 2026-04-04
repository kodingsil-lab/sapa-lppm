<?php
$currentUser = authUser();
$displayName = $currentUser['name'] ?? 'Dosen Pengusul';
$role = (string) (authRole() ?? ($currentUser['role'] ?? ''));
$normalizedRole = normalizeRoleName($role);
$isAdmin = isAdminPanelRole($role);
$dayNames = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
];
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$today = date('Y-m-d');
$dayName = $dayNames[date('l', strtotime($today))] ?? date('l');
$monthName = $monthNames[(int) date('n', strtotime($today))] ?? date('F');
$formattedDate = $dayName . ', ' . date('d', strtotime($today)) . ' ' . $monthName . ' ' . date('Y', strtotime($today));
$basePath = appBasePath();
$gender = strtolower(trim((string) ($currentUser['gender'] ?? '')));
$defaultAvatar = ($gender === 'perempuan' || $gender === 'female') ? 'woman-avatar.png' : 'man-avatar.png';
$defaultAvatarPath = appAssetUrl('assets/img/' . $defaultAvatar);
$avatarFile = trim((string) ($currentUser['avatar'] ?? ''));
$avatarPath = $avatarFile !== ''
    ? buildUserAvatarUrl($basePath, (int) ($currentUser['id'] ?? 0), $avatarFile)
    : $defaultAvatarPath;
$roleLabel = $normalizedRole === 'admin'
    ? 'Admin LPPM'
    : ($normalizedRole === 'kepala_lppm' ? 'Kepala LPPM' : 'Dosen');
$isInImpersonationMode = isImpersonating();
$impersonator = impersonatorUser();
$impersonatorName = (string) ($impersonator['name'] ?? '');
$impersonatorRole = impersonatorRole();
$impersonatorRoleLabel = $impersonatorRole === 'admin' ? 'Admin LPPM' : ($impersonatorRole === 'kepala_lppm' ? 'Kepala LPPM' : 'Pengguna');
$availableRoles = authAvailableRoles();
$canSwitchRole = !$isInImpersonationMode && count($availableRoles) > 1;
?>
<header class="app-topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>

        <span class="topbar-accent-badge" aria-hidden="true"></span>

        <div class="topbar-appmeta">
            <div class="topbar-appname">SAPA LPPM</div>
            <div class="topbar-appsub">Persuratan dan Arsip</div>
        </div>
    </div>

    <div class="topbar-right">
        <div class="dropdown">
            <button class="topbar-user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="topbar-avatar-shell">
                    <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar Pengguna" class="topbar-avatar-img" onerror="this.onerror=null;this.src='<?= htmlspecialchars($defaultAvatarPath, ENT_QUOTES, 'UTF-8'); ?>';">
                </span>
                <span class="topbar-user-meta">
                    <span class="topbar-user-name"><?= htmlspecialchars((string) $displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="topbar-user-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <span class="topbar-user-caret">
                    <i class="bi bi-chevron-down"></i>
                </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end topbar-account-menu">
                <li class="topbar-account-summary">
                    <div class="topbar-account-summary-name"><?= htmlspecialchars((string) $displayName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="topbar-account-summary-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                </li>
                <?php if ($isInImpersonationMode): ?>
                    <li>
                        <div class="dropdown-item-text small text-warning-emphasis">
                            Masuk sebagai akun lain<br>
                            <span class="text-muted">Akun asli: <?= htmlspecialchars($impersonatorName !== '' ? $impersonatorName : $impersonatorRoleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <?php if ($isAdmin): ?>
                    <li><a class="dropdown-item topbar-account-item" href="<?= htmlspecialchars($basePath . '/profil-admin', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-person-circle"></i><span>Profil</span></a></li>
                <?php else: ?>
                    <li><a class="dropdown-item topbar-account-item" href="<?= htmlspecialchars($basePath . '/profil', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-person-circle"></i><span>Profil</span></a></li>
                    <li><a class="dropdown-item topbar-account-item" href="<?= htmlspecialchars($basePath . '/surat-saya', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-folder2-open"></i><span>Surat Saya</span></a></li>
                <?php endif; ?>
                <?php if ($canSwitchRole): ?>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($availableRoles as $switchRole): ?>
                        <?php if ($switchRole === $normalizedRole): continue; endif; ?>
                        <?php
                            $switchLabel = $switchRole === 'kepala_lppm' ? 'Masuk sebagai Kepala LPPM' : ($switchRole === 'dosen' ? 'Masuk sebagai Dosen' : 'Masuk sebagai ' . ucfirst($switchRole));
                        ?>
                        <li>
                            <form method="post" action="<?= htmlspecialchars($basePath . '/auth/ganti-role', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                                <input type="hidden" name="role" value="<?= htmlspecialchars($switchRole, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="dropdown-item topbar-account-item">
                                    <i class="bi bi-arrow-left-right"></i><span><?= htmlspecialchars($switchLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($isInImpersonationMode): ?>
                    <li>
                        <form method="post" action="<?= htmlspecialchars($basePath . '/auth/impersonate-exit', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                            <button type="submit" class="dropdown-item topbar-account-item">
                                <i class="bi bi-arrow-counterclockwise"></i><span>Kembali ke akun asli</span>
                            </button>
                        </form>
                    </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= htmlspecialchars($basePath . '/logout', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                        <button type="submit" class="dropdown-item topbar-account-item topbar-account-item-logout">
                            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
