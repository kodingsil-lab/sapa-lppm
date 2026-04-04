<?php
require_once __DIR__ . '/../../Helpers/LetterUiHelper.php';
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$letter = $letter ?? [];
$detail = $detail ?? [];
$penelitian = $penelitian ?? [];
$applicantProfile = $applicantProfile ?? [];
$chairmanProfile = $chairmanProfile ?? [];
$activityType = 'pengabdian';
$statusRaw = strtolower((string) ($letter['status'] ?? 'draft'));
$statusKey = str_replace(' ', '_', $statusRaw);
$statusMap = [
    'draft' => ['label' => 'Draft', 'class' => 'myletters-status-ready', 'icon' => 'bi-send-fill'],
    'diajukan' => ['label' => 'Menunggu Diproses', 'class' => 'myletters-status-waiting', 'icon' => 'bi-clock'],
    'submitted' => ['label' => 'Menunggu Diproses', 'class' => 'myletters-status-waiting', 'icon' => 'bi-clock'],
    'diverifikasi' => ['label' => 'Menunggu Diproses', 'class' => 'myletters-status-waiting', 'icon' => 'bi-clock'],
    'perlu_diperbaiki' => ['label' => 'Perlu Diperbaiki', 'class' => 'myletters-status-revision', 'icon' => 'bi-pencil-square'],
    'perlu diperbaiki' => ['label' => 'Perlu Diperbaiki', 'class' => 'myletters-status-revision', 'icon' => 'bi-pencil-square'],
    'menunggu_finalisasi' => ['label' => 'Disetujui', 'class' => 'myletters-status-approved', 'icon' => 'bi-check2-circle'],
    'disetujui' => ['label' => 'Disetujui', 'class' => 'myletters-status-approved', 'icon' => 'bi-check2-circle'],
    'approved' => ['label' => 'Disetujui', 'class' => 'myletters-status-approved', 'icon' => 'bi-check2-circle'],
    'surat_terbit' => ['label' => 'Surat Terbit', 'class' => 'myletters-status-issued', 'icon' => 'bi-patch-check'],
    'surat terbit' => ['label' => 'Surat Terbit', 'class' => 'myletters-status-issued', 'icon' => 'bi-patch-check'],
    'terbit' => ['label' => 'Surat Terbit', 'class' => 'myletters-status-issued', 'icon' => 'bi-patch-check'],
    'ditolak' => ['label' => 'Perlu Diperbaiki', 'class' => 'myletters-status-revision', 'icon' => 'bi-pencil-square'],
    'rejected' => ['label' => 'Perlu Diperbaiki', 'class' => 'myletters-status-revision', 'icon' => 'bi-pencil-square'],
    'selesai' => ['label' => 'Selesai', 'class' => 'myletters-status-approved', 'icon' => 'bi-check2-circle'],
];
$currentStatus = $statusMap[$statusRaw] ?? ['label' => ucfirst($statusRaw), 'class' => 'myletters-status-ready', 'icon' => 'bi-send-fill'];
$canDownloadLetter = in_array($statusRaw, ['approved', 'disetujui', 'menunggu_finalisasi', 'surat_terbit', 'surat terbit', 'terbit', 'selesai'], true);
$canEditSubmission = in_array($statusKey, ['draft', 'perlu_diperbaiki', 'ditolak', 'rejected'], true);
$isMemberReadOnly = (bool) ($isMemberReadOnly ?? false);
if ($isMemberReadOnly) {
    $canEditSubmission = false;
}
$isEditMode = $canEditSubmission && ((string) ($_GET['edit'] ?? '') === '1');
$currentRole = normalizeRoleName((string) authRole());
$isHeadPanel = in_array($currentRole, ['kepala_lppm', 'admin'], true);
$backListUrl = $currentRole === 'dosen'
    ? ($basePath . '/surat-saya')
    : ($basePath . '/persuratan');
$canHeadApprove = in_array($statusRaw, ['diajukan', 'submitted', 'diverifikasi', 'menunggu diproses'], true);
$canHeadRevise = in_array($statusRaw, ['diajukan', 'submitted', 'diverifikasi', 'menunggu diproses'], true);
$canHeadDirectEdit = $isHeadPanel && in_array($statusRaw, ['draft', 'diajukan', 'submitted', 'diverifikasi', 'menunggu diproses', 'perlu_diperbaiki', 'perlu diperbaiki', 'ditolak', 'rejected'], true);
$isHeadEditMode = $canHeadDirectEdit && ((string) ($_GET['head_edit'] ?? '') === '1');
$isAnyEditMode = $isEditMode || $isHeadEditMode;
$editFormAction = $isHeadEditMode
    ? ($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '/head-update')
    : ($basePath . '/letters/store');
$editSubmitLabel = $isHeadEditMode ? 'Simpan Perubahan' : 'Ajukan Surat';
$label = [
    'page' => 'Detail Surat Tugas Pengabdian',
    'subtitle' => 'Informasi lengkap pengajuan surat tugas pengabdian pada sistem SAPA LPPM.',
    'section' => 'Data Pengabdian',
    'title' => 'Judul Pengabdian',
    'scheme' => 'Skema Pengabdian',
    'year' => 'Tahun Pengabdian',
    'leader' => 'Ketua Pelaksana',
    'members' => 'Anggota Pelaksana',
    'location' => 'Lokasi Pelaksanaan',
    'institution' => 'Instansi / Mitra Tujuan',
    'description' => 'Deskripsi Kegiatan',
];
$taskSubject = 'Permohonan Surat Tugas Pengabdian Kepada Masyarakat';

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$letterDateTs = strtotime((string) ($letter['letter_date'] ?? ''));
$letterDateText = $letterDateTs
    ? ((int) date('j', $letterDateTs)) . ' ' . ($months[(int) date('n', $letterDateTs)] ?? date('F', $letterDateTs)) . ' ' . date('Y', $letterDateTs)
    : '-';
$startDateTs = strtotime((string) ($detail['tanggal_mulai'] ?? ''));
$startDateText = $startDateTs
    ? ((int) date('j', $startDateTs)) . ' ' . ($months[(int) date('n', $startDateTs)] ?? date('F', $startDateTs)) . ' ' . date('Y', $startDateTs)
    : '-';
$endDateTs = strtotime((string) ($detail['tanggal_selesai'] ?? ''));
$endDateText = $endDateTs
    ? ((int) date('j', $endDateTs)) . ' ' . ($months[(int) date('n', $endDateTs)] ?? date('F', $endDateTs)) . ' ' . date('Y', $endDateTs)
    : '-';
$lamaKegiatanText = '-';
if ($startDateTs && $endDateTs && $endDateTs >= $startDateTs) {
    $years = ((int) date('Y', $endDateTs) - (int) date('Y', $startDateTs)) + 1;
    $lamaKegiatanText = $years . ' Tahun';
}

$applicantName = (string) ($applicantProfile['name'] ?? $penelitian['ketua'] ?? '-');
$applicantNidn = (string) ($applicantProfile['nidn'] ?? '-');
$applicantEmail = (string) ($applicantProfile['email'] ?? '-');
$applicantPhone = (string) ($applicantProfile['phone'] ?? '-');
$applicantFaculty = (string) ($applicantProfile['faculty'] ?? $applicantProfile['fakultas'] ?? '-');
$applicantProgramStudy = (string) ($applicantProfile['study_program'] ?? $applicantProfile['unit'] ?? '-');
$destinationName = (string) ($chairmanProfile['name'] ?? $letter['destination'] ?? 'Kepala LPPM');
$statusKegiatanRaw = strtolower(trim((string) ($penelitian['status'] ?? '')));
$statusKegiatanMap = [
    'aktif' => 'Aktif',
    'active' => 'Aktif',
    'draft' => 'Draft',
    'selesai' => 'Selesai',
    'completed' => 'Selesai',
    'done' => 'Selesai',
];
$statusKegiatanText = $statusKegiatanMap[$statusKegiatanRaw] ?? ucwords((string) ($penelitian['status'] ?? '-'));
$revisionNote = trim((string) ($detail['revision_note'] ?? ''));
if ($revisionNote === '') {
    $revisionNote = trim((string) ($detail['keterangan'] ?? ''));
}
if ($revisionNote === '') {
    $revisionNote = 'Belum ada catatan perbaikan dari Kepala LPPM.';
}

$cleanLuaranToken = static function (string $token): string {
    $key = strtolower(trim($token));
    $map = [
        'artikel_sinta' => 'Artikel SINTA',
        'laporan_akhir' => 'Laporan Akhir',
        'hki' => 'HKI',
        'prosiding_nasional' => 'Prosiding Nasional',
        'prosiding_internasional' => 'Prosiding Internasional',
        'produk_inovasi' => 'Produk Inovasi',
        'buku_ajar' => 'Buku Ajar',
        'hilirisasi' => 'Hilirisasi',
    ];
    if (isset($map[$key])) {
        return $map[$key];
    }

    return ucwords(str_replace('_', ' ', $key));
};

$targetLuaranText = (string) ($penelitian['deskripsi'] ?? '');
$targetLuaranText = trim($targetLuaranText);
if ($targetLuaranText === '') {
    $targetLuaranText = '-';
} else {
    $rows = preg_split('/\R+/', $targetLuaranText) ?: [];
    $formattedRows = [];
    foreach ($rows as $row) {
        $line = trim($row);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^(Luaran\s+Wajib|Luaran\s+Tambahan)\s*:\s*(.+)$/iu', $line, $m)) {
            $rawLabel = strtolower(trim((string) $m[1]));
            $labelText = str_contains($rawLabel, 'tambahan') ? 'Luaran Tambahan' : 'Luaran Wajib';
            $parts = preg_split('/[|,]/', (string) $m[2]) ?: [];
            $items = [];
            foreach ($parts as $part) {
                $cleaned = $cleanLuaranToken((string) $part);
                if ($cleaned !== '') {
                    $items[] = $cleaned;
                }
            }
            $formattedRows[] = $labelText . ': ' . implode(', ', $items);
            continue;
        }

        $parts = preg_split('/[|,]/', $line) ?: [];
        $items = [];
        foreach ($parts as $part) {
            $cleaned = $cleanLuaranToken((string) $part);
            if ($cleaned !== '') {
                $items[] = $cleaned;
            }
        }
        $formattedRows[] = implode(', ', $items);
    }

    $targetLuaranText = !empty($formattedRows) ? implode("\n", $formattedRows) : '-';
}

$formatCurrency = static function (string $value): string {
    $digits = preg_replace('/\D+/', '', trim($value));
    if ($digits === '' || $digits === '0') {
        return '-';
    }

    return 'Rp ' . number_format((int) $digits, 0, ',', '.');
};
?>

<div class="page-content detail-page">
    <div class="detail-header mb-3">
        <div class="detail-header-row">
            <div>
                <h1 class="page-title mb-1"><?= htmlspecialchars($label['page'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="page-subtitle mb-0"><?= htmlspecialchars($label['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="detail-status-box">
                <span class="status-pill status-table-pill myletters-status-pill <?= htmlspecialchars($currentStatus['class'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi <?= htmlspecialchars((string) ($currentStatus['icon'] ?? 'bi-send-fill'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                    <?= htmlspecialchars($currentStatus['label'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3">
    <div class="col-lg-4">
    <div class="profile-card detail-summary-card mb-3 h-100">
        <div class="section-head mb-3">
            <h3 class="section-title mb-0">Ringkasan Surat</h3>
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Nomor Surat</span>
                <span class="summary-value"><?= htmlspecialchars((string) (($letter['letter_number'] ?? '') !== '' ? $letter['letter_number'] : '-'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Jenis Surat</span>
                <span class="summary-value"><?= htmlspecialchars((string) ($letter['type'] ?? $label['page']), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Tanggal Surat</span>
                <span class="summary-value"><?= htmlspecialchars($letterDateText, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Perihal</span>
                <span class="summary-value"><?= htmlspecialchars((string) $taskSubject, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Tujuan</span>
                <span class="summary-value"><?= htmlspecialchars($destinationName, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="summary-item">
                <span class="summary-label">Instansi</span>
                <span class="summary-value"><?= htmlspecialchars((string) ($letter['institution'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>

        <div class="profile-summary-divider"></div>

        <?php if (!$isAnyEditMode): ?>
            <?php if ($isHeadPanel): ?>
                <div class="detail-actions detail-head-actions">
                    <a href="<?= htmlspecialchars($backListUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Kembali</a>
                    <?php if ($canHeadApprove): ?>
                        <form method="post" action="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '/approve', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline m-0">
                            <input type="hidden" name="return" value="show">
                            <button type="submit" class="btn btn-primary-main">Setujui</button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>Setujui</button>
                    <?php endif; ?>

                    <button type="button" class="btn btn-outline-warning" id="toggleRevisionNoteBox" <?= $canHeadRevise ? '' : 'disabled'; ?>>
                        Perbaiki
                    </button>
                    <?php if ($canHeadDirectEdit): ?>
                        <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '?head_edit=1', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-head-edit-direct">Edit</a>
                    <?php endif; ?>
                </div>

                <div class="form-section-card mt-3 d-none" id="revisionNoteBox">
                    <div class="section-head mb-2">
                        <h3 class="section-title mb-0">Catatan Perbaikan</h3>
                    </div>
                    <form method="post" action="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '/reject', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="return" value="list">
                        <textarea name="revision_note" class="form-control modern-input modern-textarea" rows="4" placeholder="Tulis catatan perbaikan untuk dosen..." required></textarea>
                        <div class="detail-actions mt-2">
                            <button type="submit" class="btn btn-outline-warning">Kirim</button>
                            <button type="button" class="btn btn-light-soft" id="cancelRevisionNoteBox">Batal</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!$isHeadPanel): ?>
                <div class="detail-actions">
                    <a href="<?= htmlspecialchars($backListUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Kembali</a>
                    <?php if ($canDownloadLetter): ?>
                        <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '/download', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main form-btn">Unduh Surat</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary form-btn" disabled>Unduh Surat</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if ($isEditMode): ?>
    <div class="form-section-card mt-3">
        <div class="section-head">
            <h3 class="section-title mb-0">Catatan Perbaikan</h3>
        </div>
        <div class="profile-info-item mt-2">
            <div class="profile-info-value detail-multiline"><?= nl2br(htmlspecialchars($revisionNote, ENT_QUOTES, 'UTF-8')); ?></div>
        </div>
    </div>
    <?php endif; ?>
    </div>
    <div class="col-lg-8">
    <?php if ($isAnyEditMode): ?>
    <form method="post" action="<?= htmlspecialchars($editFormAction, ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" class="form-section-card mb-3">
        <input type="hidden" name="form_variant" value="task_research">
        <input type="hidden" name="letter_id" value="<?= (int) ($letter['id'] ?? 0); ?>">
        <input type="hidden" name="activity_type" value="<?= htmlspecialchars($activityType, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="activity_id" value="<?= (int) ($detail['activity_id'] ?? $detail['penelitian_id'] ?? $penelitian['id'] ?? 0); ?>">
        <input type="hidden" name="penelitian_id" value="<?= (int) ($detail['penelitian_id'] ?? $penelitian['id'] ?? 0); ?>">
        <input type="hidden" name="name" value="<?= htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="nidn" value="<?= htmlspecialchars($applicantNidn, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="faculty" value="<?= htmlspecialchars($applicantFaculty, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="unit" value="<?= htmlspecialchars($applicantProgramStudy, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="applicant_email" value="<?= htmlspecialchars($applicantEmail, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="phone" value="<?= htmlspecialchars($applicantPhone, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="existing_file_proposal" value="<?= htmlspecialchars((string) ($detail['file_proposal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="existing_file_instrumen" value="<?= htmlspecialchars((string) ($detail['file_instrumen'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="existing_file_pendukung_lain" value="<?= htmlspecialchars((string) ($detail['file_pendukung_lain'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="section-head">
            <h3 class="section-title mb-0">Edit Pengajuan Surat Tugas</h3>
            <span class="section-badge">Mode Edit</span>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6"><label class="form-label"><?= htmlspecialchars($label['location'], ENT_QUOTES, 'UTF-8'); ?></label><input type="text" name="lokasi_penugasan" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['lokasi_penugasan'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Kabupaten Kupang / Sekolah Mitra / Desa Binaan"></div>
            <div class="col-md-6"><label class="form-label"><?= htmlspecialchars($label['institution'], ENT_QUOTES, 'UTF-8'); ?></label><input type="text" name="instansi_tujuan" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['instansi_tujuan'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Dinas Pendidikan Kota Kupang"></div>
            <div class="col-md-6"><label class="form-label">Tanggal Mulai</label><input type="date" name="tanggal_mulai" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['tanggal_mulai'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal mulai"></div>
            <div class="col-md-6"><label class="form-label">Tanggal Selesai</label><input type="date" name="tanggal_selesai" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['tanggal_selesai'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal selesai"></div>
            <div class="col-12"><label class="form-label"><?= htmlspecialchars($label['description'], ENT_QUOTES, 'UTF-8'); ?></label><textarea name="deskripsi_kegiatan" class="form-control modern-input modern-textarea" rows="4" placeholder="Jelaskan deskripsi kegiatan secara ringkas"><?= htmlspecialchars((string) ($detail['uraian_tugas'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            <div class="col-md-4"><label class="form-label">Link Proposal (opsional)</label><input type="url" name="file_proposal" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['file_proposal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://contoh.com/proposal.pdf"></div>
            <div class="col-md-4"><label class="form-label">Link Instrumen (opsional)</label><input type="url" name="file_instrumen" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['file_instrumen'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://contoh.com/instrumen.pdf"></div>
            <div class="col-md-4"><label class="form-label">Link Pendukung Lain (opsional)</label><input type="url" name="file_pendukung_lain" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['file_pendukung_lain'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://contoh.com/pendukung.pdf"></div>
        </div>
        <div class="mt-3">
            <button type="submit" name="submit_action" value="submit" class="btn btn-primary-main"><?= htmlspecialchars($editSubmitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
            <?php if ($isHeadEditMode): ?>
                <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Batal</a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
    <?php if (!$isAnyEditMode): ?>

    <div class="form-section-card mb-3">
        <div class="section-head">
            <h3 class="section-title mb-0">Informasi Pemohon</h3>
            <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('pemohon'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label">Nama Dosen / Pemohon</div><div class="profile-info-value"><?= htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label">NIDN</div><div class="profile-info-value"><?= htmlspecialchars($applicantNidn, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label">Email</div><div class="profile-info-value"><?= htmlspecialchars($applicantEmail, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label">Nomor HP</div><div class="profile-info-value"><?= htmlspecialchars($applicantPhone, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label">Fakultas</div><div class="profile-info-value"><?= htmlspecialchars($applicantFaculty, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label">Program Studi</div><div class="profile-info-value"><?= htmlspecialchars($applicantProgramStudy, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
        </div>
    </div>

    <div class="form-section-card mb-3">
        <div class="section-head">
            <h3 class="section-title mb-0"><?= htmlspecialchars($label['section'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('data_kegiatan'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-12"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['title'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= htmlspecialchars((string) ($penelitian['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-4"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['scheme'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= htmlspecialchars((string) (($penelitian['ruang_lingkup'] ?? '') !== '' ? ($penelitian['ruang_lingkup'] ?? '') : ($penelitian['skema'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-4"><div class="profile-info-item"><div class="profile-info-label">Sumber Dana</div><div class="profile-info-value"><?= htmlspecialchars((string) ($penelitian['sumber_dana'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['year'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= htmlspecialchars((string) ($penelitian['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label">Total Dana Disetujui</div><div class="profile-info-value"><?= htmlspecialchars($formatCurrency((string) ($penelitian['total_dana_disetujui'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['leader'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= htmlspecialchars((string) ($penelitian['ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label">Status Kegiatan</div><div class="profile-info-value"><?= htmlspecialchars($statusKegiatanText, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-12"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['members'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value detail-multiline"><?= nl2br(htmlspecialchars((string) ($penelitian['anggota'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></div></div></div>
            <div class="col-12"><div class="profile-info-item"><div class="profile-info-label">Target Luaran</div><div class="profile-info-value detail-multiline"><?= nl2br(htmlspecialchars($targetLuaranText, ENT_QUOTES, 'UTF-8')); ?></div></div></div>
        </div>
    </div>

    <div class="form-section-card mb-3">
        <div class="section-head">
            <h3 class="section-title mb-0">Detail Penugasan</h3>
            <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('pelaksanaan'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['location'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= htmlspecialchars((string) ($detail['lokasi_penugasan'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['institution'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= htmlspecialchars((string) ($detail['instansi_tujuan'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-4"><div class="profile-info-item"><div class="profile-info-label">Lama Kegiatan</div><div class="profile-info-value"><?= htmlspecialchars($lamaKegiatanText, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-4"><div class="profile-info-item"><div class="profile-info-label">Tanggal Mulai</div><div class="profile-info-value"><?= htmlspecialchars($startDateText, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
            <div class="col-md-4"><div class="profile-info-item"><div class="profile-info-label">Tanggal Selesai</div><div class="profile-info-value"><?= htmlspecialchars($endDateText, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
        </div>
    </div>

    <div class="form-section-card mb-3">
        <div class="section-head">
            <h3 class="section-title mb-0">Deskripsi Kegiatan Penelitian</h3>
            <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('penugasan'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-12"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($label['description'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value detail-multiline"><?= nl2br(htmlspecialchars((string) ($detail['uraian_tugas'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></div></div></div>
        </div>
    </div>

    <div class="form-section-card mb-4">
        <div class="section-head">
            <h3 class="section-title mb-0">Administrasi Surat Tugas</h3>
            <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('administrasi'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <div class="profile-info-item">
                    <div class="profile-info-label">Perihal Surat Tugas</div>
                    <div class="profile-info-value"><?= htmlspecialchars((string) $taskSubject, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="profile-info-item">
                    <div class="profile-info-label">Ditujukan Kepada</div>
                    <div class="profile-info-value"><?= htmlspecialchars($destinationName, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="profile-info-item">
                    <div class="profile-info-label">Jabatan Tujuan</div>
                    <div class="profile-info-value">Kepala LPPM</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profile-info-item">
                    <div class="profile-info-label">Lampiran Proposal</div>
                    <div class="profile-info-value">
                        <?php if (!empty($detail['file_proposal'])): ?>
                            <a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_proposal', (string) $detail['file_proposal']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-primary text-decoration-none">Lihat Proposal</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profile-info-item">
                    <div class="profile-info-label">Lampiran Instrumen</div>
                    <div class="profile-info-value">
                        <?php if (!empty($detail['file_instrumen'])): ?>
                            <a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_instrumen', (string) $detail['file_instrumen']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-primary text-decoration-none">Lihat Instrumen</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profile-info-item">
                    <div class="profile-info-label">Lampiran Pendukung Lain</div>
                    <div class="profile-info-value">
                        <?php if (!empty($detail['file_pendukung_lain'])): ?>
                            <a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_pendukung_lain', (string) $detail['file_pendukung_lain']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-primary text-decoration-none">Lihat Pendukung</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
    <?php endif; ?>
    </div>

</div>

<?php if ($isHeadPanel && !$isAnyEditMode): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('toggleRevisionNoteBox');
    var cancelBtn = document.getElementById('cancelRevisionNoteBox');
    var box = document.getElementById('revisionNoteBox');
    if (!toggleBtn || !box) return;

    toggleBtn.addEventListener('click', function () {
        box.classList.toggle('d-none');
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            box.classList.add('d-none');
        });
    }
});
</script>
<?php endif; ?>
