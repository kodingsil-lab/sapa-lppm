<?php
$currentRoute = $_GET['route'] ?? 'dashboard';
$role = authRole();
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
?>
<?php if ($role === 'dosen'): ?>
<?php
$isDashboard = in_array($currentRoute, ['dashboard', 'dashboard-dosen'], true);

$isDataPenelitian = $currentRoute === 'data-penelitian';
$isDataPengabdian = $currentRoute === 'data-pengabdian';
$isDataHilirisasi = $currentRoute === 'data-hilirisasi';

$suratPenelitianRoutes = [
    'surat-penelitian-kontrak',
    'surat-penelitian-izin',
    'surat-penelitian-tugas',
];
$suratPengabdianRoutes = [
    'surat-pengabdian-kontrak',
    'surat-pengabdian-izin',
    'surat-pengabdian-tugas',
];
$suratHilirisasiRoutes = [
    'surat-hilirisasi-kontrak',
    'surat-hilirisasi-izin',
    'surat-hilirisasi-tugas',
];
$isAjukanPenelitian = in_array($currentRoute, $suratPenelitianRoutes, true);
$isAjukanPengabdian = in_array($currentRoute, $suratPengabdianRoutes, true);
$isAjukanHilirisasi = in_array($currentRoute, $suratHilirisasiRoutes, true);

if ($currentRoute === 'ajukan-surat') {
    $activityTypeFromMenu = strtolower((string) ($_GET['activity_type'] ?? 'penelitian'));
    $isAjukanPenelitian = $activityTypeFromMenu === 'penelitian';
    $isAjukanPengabdian = $activityTypeFromMenu === 'pengabdian';
    $isAjukanHilirisasi = $activityTypeFromMenu === 'hilirisasi';
}

$isAjukan = $isAjukanPenelitian || $isAjukanPengabdian || $isAjukanHilirisasi || $currentRoute === 'ajukan-surat';

$isStatusLuaran = $currentRoute === 'status-luaran';
$isSuratSaya = in_array($currentRoute, ['my-letters', 'my-letters-detail', 'my-letters-show'], true);
$isProfil = in_array($currentRoute, ['profile', 'my-profile'], true);
?>

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand-wrap">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-building"></i>
            </div>
            <div class="brand-text">
                <h5>SAPA LPPM</h5>
                <p>Sistem Administrasi Persuratan dan Arsip LPPM</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <li>
                <a href="<?= htmlspecialchars($basePath . '/dashboard-dosen', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isDashboard ? 'active' : ''; ?>">
                    <span class="sidebar-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span>
                    <span class="sidebar-link-text">Dashboard</span>
                </a>
            </li>

            <li class="sidebar-section-label">Persuratan</li>

            <li class="has-submenu <?= $isAjukan ? 'open' : ''; ?>">
                <button type="button" class="sidebar-link sidebar-toggle <?= $isAjukan ? 'active' : ''; ?>" data-sidebar-toggle>
                    <span class="sidebar-link-left">
                        <span class="sidebar-icon-wrap"><i class="bi bi-send-fill"></i></span>
                        <span class="sidebar-link-text">Ajukan Surat</span>
                    </span>
                    <i class="bi bi-chevron-down sidebar-arrow"></i>
                </button>

                <ul class="sidebar-submenu" <?= $isAjukan ? 'style="display:block;"' : ''; ?>>
                    <li>
                        <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/penelitian', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isAjukanPenelitian ? 'active' : ''; ?>">
                            Penelitian
                        </a>
                    </li>
                    <li>
                        <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/pengabdian', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isAjukanPengabdian ? 'active' : ''; ?>">
                            Pengabdian
                        </a>
                    </li>
                    <li>
                        <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/hilirisasi', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isAjukanHilirisasi ? 'active' : ''; ?>">
                            Hilirisasi
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="<?= htmlspecialchars($basePath . '/surat-saya', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isSuratSaya ? 'active' : ''; ?>">
                    <span class="sidebar-icon-wrap"><i class="bi bi-folder2-open"></i></span>
                    <span class="sidebar-link-text">Surat Saya</span>
                </a>
            </li>

            <li class="sidebar-section-label">Data Kegiatan</li>

            <li>
                <a href="<?= htmlspecialchars($basePath . '/data/penelitian', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isDataPenelitian ? 'active' : ''; ?>">
                    <span class="sidebar-icon-wrap"><i class="bi bi-database"></i></span>
                    <span class="sidebar-link-text">Data Penelitian</span>
                </a>
            </li>

            <li>
                <a href="<?= htmlspecialchars($basePath . '/data/pengabdian', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isDataPengabdian ? 'active' : ''; ?>">
                    <span class="sidebar-icon-wrap"><i class="bi bi-database"></i></span>
                    <span class="sidebar-link-text">Data Pengabdian</span>
                </a>
            </li>

            <li>
                <a href="<?= htmlspecialchars($basePath . '/data/hilirisasi', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isDataHilirisasi ? 'active' : ''; ?>">
                    <span class="sidebar-icon-wrap"><i class="bi bi-diagram-3"></i></span>
                    <span class="sidebar-link-text">Data Hilirisasi</span>
                </a>
            </li>

            <li>
                <a href="<?= htmlspecialchars($basePath . '/status-luaran', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isStatusLuaran ? 'active' : ''; ?>">
                    <span class="sidebar-icon-wrap"><i class="bi bi-clipboard2-data"></i></span>
                    <span class="sidebar-link-text">Status Luaran</span>
                </a>
            </li>

            <li class="sidebar-section-label">Pengaturan</li>

            <li>
                <a href="<?= htmlspecialchars($basePath . '/profil', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isProfil ? 'active' : ''; ?>">
                    <span class="sidebar-icon-wrap"><i class="bi bi-person-badge"></i></span>
                    <span class="sidebar-link-text">Profil</span>
                </a>
            </li>

            <li>
                <form method="post" action="<?= htmlspecialchars($basePath . '/logout', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                    <button type="submit" class="sidebar-link sidebar-link-logout w-100 border-0 bg-transparent text-start">
                        <span class="sidebar-icon-wrap"><i class="bi bi-box-arrow-right"></i></span>
                        <span class="sidebar-link-text">Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </nav>
</aside>

<?php elseif (isAdminPanelRole($role)): ?>
<?php
$normalizedRole = normalizeRoleName((string) $role);
$isDashboardAdmin = in_array($currentRoute, ['dashboard', 'dashboard-admin'], true);
$isPersuratanAdmin = $currentRoute === 'letters';
$isUserListAdmin = $currentRoute === 'users';
$isLogAdmin = $currentRoute === 'logs';
$isProfilAdmin = $currentRoute === 'users-profile';
$isSettingNomorSurat = $currentRoute === 'settings-letter-number';
$contractScope = strtolower((string) ($_GET['scope'] ?? 'penelitian'));
$isSettingKontrakPenelitian = in_array($currentRoute, ['settings-contract', 'settings-contract-detail'], true) && $contractScope === 'penelitian';
$isSettingKontrakPengabdian = in_array($currentRoute, ['settings-contract', 'settings-contract-detail'], true) && $contractScope === 'pengabdian';
$isSettingKontrakHilirisasi = in_array($currentRoute, ['settings-contract', 'settings-contract-detail'], true) && $contractScope === 'hilirisasi';
$isPengaturanKepala = $isSettingNomorSurat || $isSettingKontrakPenelitian || $isSettingKontrakPengabdian || $isSettingKontrakHilirisasi;
$isMasterLuaran = $currentRoute === 'master-data-outputs';
$isMasterSkema = $currentRoute === 'master-data-schemes';
$isMasterRuangLingkup = $currentRoute === 'master-data-scopes';
$isMasterSumberDana = $currentRoute === 'master-data-funding-sources';
$isMasterData = $isMasterLuaran || $isMasterSkema || $isMasterRuangLingkup || $isMasterSumberDana;
$isUserImport = $currentRoute === 'users-import';
$isUserExport = $currentRoute === 'users-export';
$isImportExport = $isUserImport || $isUserExport;
$adminDashboardPath = $basePath . '/' . ($role === 'kepala_lppm' ? 'dashboard-kepala-lppm' : 'dashboard-admin');
$isAdminOnly = $normalizedRole === 'admin';
$isKepalaLppm = $normalizedRole === 'kepala_lppm';
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand-wrap">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-building"></i>
            </div>
            <div class="brand-text">
                <h5>SAPA LPPM</h5>
                <p>Sistem Administrasi Persuratan dan Arsip LPPM</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <?php if ($isAdminOnly): ?>
                <li>
                    <a href="<?= htmlspecialchars($adminDashboardPath, ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isDashboardAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span>
                        <span class="sidebar-link-text">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-section-label">Manajemen</li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isUserListAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-people-fill"></i></span>
                        <span class="sidebar-link-text">Pengguna</span>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/log-aktivitas', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isLogAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-clock-history"></i></span>
                        <span class="sidebar-link-text">Log Aktivitas</span>
                    </a>
                </li>
                <li class="has-submenu <?= $isMasterData ? 'open' : ''; ?>">
                    <button type="button" class="sidebar-link sidebar-toggle <?= $isMasterData ? 'active' : ''; ?>" data-sidebar-toggle>
                        <span class="sidebar-link-left">
                            <span class="sidebar-icon-wrap"><i class="bi bi-diagram-3-fill"></i></span>
                            <span class="sidebar-link-text">Master Data</span>
                        </span>
                        <i class="bi bi-chevron-down sidebar-arrow"></i>
                    </button>
                    <ul class="sidebar-submenu" <?= $isMasterData ? 'style="display:block;"' : ''; ?>>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/master-data/luaran', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isMasterLuaran ? 'active' : ''; ?>">
                                Jenis Luaran
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/master-data/skema', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isMasterSkema ? 'active' : ''; ?>">
                                Skema
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/master-data/ruang-lingkup', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isMasterRuangLingkup ? 'active' : ''; ?>">
                                Ruang Lingkup
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/master-data/sumber-dana', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isMasterSumberDana ? 'active' : ''; ?>">
                                Sumber Dana
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="has-submenu <?= $isImportExport ? 'open' : ''; ?>">
                    <button type="button" class="sidebar-link sidebar-toggle <?= $isImportExport ? 'active' : ''; ?>" data-sidebar-toggle>
                        <span class="sidebar-link-left">
                            <span class="sidebar-icon-wrap"><i class="bi bi-arrow-left-right"></i></span>
                            <span class="sidebar-link-text">Import &amp; Ekspor</span>
                        </span>
                        <i class="bi bi-chevron-down sidebar-arrow"></i>
                    </button>
                    <ul class="sidebar-submenu" <?= $isImportExport ? 'style="display:block;"' : ''; ?>>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengguna/impor', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isUserImport ? 'active' : ''; ?>">
                                Impor Pengguna
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengguna/ekspor', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isUserExport ? 'active' : ''; ?>">
                                Ekspor Pengguna
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-section-label">Pengaturan</li>
                <li class="has-submenu <?= $isPengaturanKepala ? 'open' : ''; ?>">
                    <button type="button" class="sidebar-link sidebar-toggle <?= $isPengaturanKepala ? 'active' : ''; ?>" data-sidebar-toggle>
                        <span class="sidebar-link-left">
                            <span class="sidebar-icon-wrap"><i class="bi bi-gear-fill"></i></span>
                            <span class="sidebar-link-text">Pengaturan</span>
                        </span>
                        <i class="bi bi-chevron-down sidebar-arrow"></i>
                    </button>
                    <ul class="sidebar-submenu" <?= $isPengaturanKepala ? 'style="display:block;"' : ''; ?>>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingNomorSurat ? 'active' : ''; ?>">
                                Nomor Surat
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingKontrakPenelitian ? 'active' : ''; ?>">
                                Kontrak Penelitian
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak?scope=pengabdian', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingKontrakPengabdian ? 'active' : ''; ?>">
                                Kontrak Pengabdian
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak?scope=hilirisasi', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingKontrakHilirisasi ? 'active' : ''; ?>">
                                Kontrak Hilirisasi
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/profil-admin', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isProfilAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-person-badge"></i></span>
                        <span class="sidebar-link-text">Profil</span>
                    </a>
                </li>
                <li>
                    <form method="post" action="<?= htmlspecialchars($basePath . '/logout', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                        <button type="submit" class="sidebar-link sidebar-link-logout w-100 border-0 bg-transparent text-start">
                            <span class="sidebar-icon-wrap"><i class="bi bi-box-arrow-right"></i></span>
                            <span class="sidebar-link-text">Logout</span>
                        </button>
                    </form>
                </li>
            <?php elseif ($isKepalaLppm): ?>
                <li>
                    <a href="<?= htmlspecialchars($adminDashboardPath, ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isDashboardAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span>
                        <span class="sidebar-link-text">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-section-label">Persuratan</li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/persuratan', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isPersuratanAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-envelope-paper-fill"></i></span>
                        <span class="sidebar-link-text">Persuratan</span>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/arsip-surat', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $currentRoute === 'archives' ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-archive-fill"></i></span>
                        <span class="sidebar-link-text">Arsip Surat</span>
                    </a>
                </li>
                <li class="sidebar-section-label">Pengaturan</li>
                <li class="has-submenu <?= $isPengaturanKepala ? 'open' : ''; ?>">
                    <button type="button" class="sidebar-link sidebar-toggle <?= $isPengaturanKepala ? 'active' : ''; ?>" data-sidebar-toggle>
                        <span class="sidebar-link-left">
                            <span class="sidebar-icon-wrap"><i class="bi bi-gear-fill"></i></span>
                            <span class="sidebar-link-text">Pengaturan</span>
                        </span>
                        <i class="bi bi-chevron-down sidebar-arrow"></i>
                    </button>
                    <ul class="sidebar-submenu" <?= $isPengaturanKepala ? 'style="display:block;"' : ''; ?>>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/nomor-surat', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingNomorSurat ? 'active' : ''; ?>">
                                Nomor Surat
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingKontrakPenelitian ? 'active' : ''; ?>">
                                Kontrak Penelitian
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak?scope=pengabdian', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingKontrakPengabdian ? 'active' : ''; ?>">
                                Kontrak Pengabdian
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($basePath . '/pengaturan/kontrak?scope=hilirisasi', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-sublink <?= $isSettingKontrakHilirisasi ? 'active' : ''; ?>">
                                Kontrak Hilirisasi
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-section-label">Manajemen</li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/pengguna', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isUserListAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-people-fill"></i></span>
                        <span class="sidebar-link-text">Pengguna</span>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/log-aktivitas', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isLogAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-clock-history"></i></span>
                        <span class="sidebar-link-text">Log Aktivitas</span>
                    </a>
                </li>
                <li class="sidebar-section-label">Akun</li>
                <li>
                    <a href="<?= htmlspecialchars($basePath . '/profil-admin', ENT_QUOTES, 'UTF-8'); ?>" class="sidebar-link <?= $isProfilAdmin ? 'active' : ''; ?>">
                        <span class="sidebar-icon-wrap"><i class="bi bi-person-badge"></i></span>
                        <span class="sidebar-link-text">Profil</span>
                    </a>
                </li>
                <li>
                    <form method="post" action="<?= htmlspecialchars($basePath . '/logout', ENT_QUOTES, 'UTF-8'); ?>" class="m-0">
                        <button type="submit" class="sidebar-link sidebar-link-logout w-100 border-0 bg-transparent text-start">
                            <span class="sidebar-icon-wrap"><i class="bi bi-box-arrow-right"></i></span>
                            <span class="sidebar-link-text">Logout</span>
                        </button>
                    </form>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
<?php else: ?>
<aside class="app-sidebar" id="appSidebar"></aside>
<?php endif; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var parent = btn.closest('.has-submenu, .has-submenu-nested');
            if (!parent) return;

            var submenu = null;
            Array.prototype.forEach.call(parent.children, function (child) {
                if (!submenu && child.classList && child.classList.contains('sidebar-submenu')) {
                    submenu = child;
                }
            });
            if (!submenu) return;

            parent.classList.toggle('open');
            submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
        });
    });
});
</script>
