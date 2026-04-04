<?php
require_once __DIR__ . '/../../Helpers/LetterUiHelper.php';
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');

$letter = $letter ?? [];
$detail = $detail ?? [];
$applicantProfile = $applicantProfile ?? [];
$chairmanProfile = $chairmanProfile ?? [];
$activityType = 'penelitian';

$typeSource = strtolower((string) (($letter['type'] ?? '') . ' ' . ($letter['letter_type_name'] ?? '') . ' ' . ($letter['subject'] ?? '')));
$letterKind = str_contains($typeSource, 'kontrak') ? 'kontrak' : 'izin';

$labelMap = [
    'penelitian' => [
        'kind' => [
            'izin' => [
                'detail_title' => 'Detail Surat Izin Penelitian',
                'detail_subtitle' => 'Informasi lengkap pengajuan surat izin penelitian pada sistem SAPA LPPM.',
                'default_type' => 'Surat Izin Penelitian',
            ],
            'kontrak' => [
                'detail_title' => 'Detail Surat Kontrak Penelitian',
                'detail_subtitle' => 'Informasi lengkap pengajuan surat kontrak penelitian pada sistem SAPA LPPM.',
                'default_type' => 'Surat Kontrak Penelitian',
            ],
        ],
        'info_section' => 'Informasi Penelitian',
        'title' => 'Judul Penelitian',
        'scheme' => 'Skema Penelitian',
        'year' => 'Tahun Penelitian',
        'leader' => 'Ketua Peneliti',
        'members' => 'Anggota Peneliti',
        'purpose' => 'Target Luaran',
        'location_section' => 'Lokasi dan Jadwal Penelitian',
        'institution' => 'Instansi / Lokasi Tujuan Penelitian',
    ],
    'pengabdian' => [
        'kind' => [
            'izin' => [
                'detail_title' => 'Detail Surat Izin Pengabdian',
                'detail_subtitle' => 'Informasi lengkap pengajuan surat izin pengabdian pada sistem SAPA LPPM.',
                'default_type' => 'Surat Izin Pengabdian',
            ],
            'kontrak' => [
                'detail_title' => 'Detail Surat Kontrak Pengabdian',
                'detail_subtitle' => 'Informasi lengkap pengajuan surat kontrak pengabdian pada sistem SAPA LPPM.',
                'default_type' => 'Surat Kontrak Pengabdian',
            ],
        ],
        'info_section' => 'Informasi Pengabdian',
        'title' => 'Judul Pengabdian',
        'scheme' => 'Skema Pengabdian',
        'year' => 'Tahun Pengabdian',
        'leader' => 'Ketua Pelaksana',
        'members' => 'Anggota Pelaksana',
        'purpose' => 'Target Luaran',
        'location_section' => 'Lokasi dan Jadwal Pengabdian',
        'institution' => 'Instansi / Lokasi Tujuan Pengabdian',
    ],
    'hilirisasi' => [
        'kind' => [
            'izin' => [
                'detail_title' => 'Detail Surat Izin Hilirisasi',
                'detail_subtitle' => 'Informasi lengkap pengajuan surat izin hilirisasi pada sistem SAPA LPPM.',
                'default_type' => 'Surat Izin Hilirisasi',
            ],
            'kontrak' => [
                'detail_title' => 'Detail Surat Kontrak Hilirisasi',
                'detail_subtitle' => 'Informasi lengkap pengajuan surat kontrak hilirisasi pada sistem SAPA LPPM.',
                'default_type' => 'Surat Kontrak Hilirisasi',
            ],
        ],
        'info_section' => 'Informasi Hilirisasi',
        'title' => 'Judul Hilirisasi',
        'scheme' => 'Skema Hilirisasi',
        'year' => 'Tahun Hilirisasi',
        'leader' => 'Ketua Pelaksana',
        'members' => 'Anggota Tim Pelaksana',
        'purpose' => 'Luaran Wajib',
        'location_section' => 'Lokasi dan Jadwal Hilirisasi',
        'institution' => 'Instansi / Lokasi Tujuan Hilirisasi',
    ],
];
$labels = $labelMap[$activityType];
$kindLabels = $labels['kind'][$letterKind] ?? $labels['kind']['izin'];
$adminSectionTitle = $letterKind === 'kontrak' ? 'Administrasi Surat Kontrak' : 'Administrasi Surat Izin';
$subjectLabel = $letterKind === 'kontrak' ? 'Perihal Surat Kontrak' : 'Perihal / Subjek Surat';
$targetLuaranLabel = (string) ($labels['purpose'] ?? 'Target Luaran');
$keperluanLabel = $activityType === 'hilirisasi' ? 'Luaran Wajib' : 'Keperluan';

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
$isContractLetter = $letterKind === 'kontrak';
$canHeadDirectEdit = !$isContractLetter
    && $isHeadPanel
    && in_array($statusRaw, ['draft', 'diajukan', 'submitted', 'diverifikasi', 'menunggu diproses', 'perlu_diperbaiki', 'perlu diperbaiki', 'ditolak', 'rejected'], true);
$isHeadEditMode = $canHeadDirectEdit && ((string) ($_GET['head_edit'] ?? '') === '1');
$isAnyEditMode = $isEditMode || $isHeadEditMode;
$editFormAction = $isHeadEditMode
    ? ($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '/head-update')
    : ($basePath . '/letters/store');
$editSubmitLabel = $isHeadEditMode ? 'Simpan Perubahan' : 'Ajukan Surat';

function detail_value(array $data, string $key, string $default = '-'): string
{
    $value = $data[$key] ?? $default;
    if ($value === null || $value === '') {
        $value = $default;
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function detail_date(array $data, string $key): string
{
    $value = (string) ($data[$key] ?? '');
    if ($value === '') {
        return '-';
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $day = (int) date('j', $ts);
    $monthNum = (int) date('n', $ts);
    $year = (int) date('Y', $ts);
    $monthName = $months[$monthNum] ?? date('F', $ts);

    return htmlspecialchars($day . ' ' . $monthName . ' ' . $year, ENT_QUOTES, 'UTF-8');
}

function detail_duration_years(array $data, string $startKey, string $endKey): string
{
    $start = trim((string) ($data[$startKey] ?? ''));
    $end = trim((string) ($data[$endKey] ?? ''));
    if ($start === '' || $end === '') {
        return '-';
    }

    $startTs = strtotime($start);
    $endTs = strtotime($end);
    if ($startTs === false || $endTs === false || $endTs < $startTs) {
        return '-';
    }

    $years = ((int) date('Y', $endTs) - (int) date('Y', $startTs)) + 1;
    return $years . ' Tahun';
}

function detail_text_clean(string $value): string
{
    $text = trim($value);
    if ($text === '') {
        return '-';
    }

    $normalizeOutputToken = static function (string $token): string {
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

        $token = str_replace('_', ' ', $key);
        return ucwords($token);
    };

    $rows = preg_split('/\R+/', $text) ?: [];
    $formatted = [];

    foreach ($rows as $row) {
        $line = trim($row);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^(Luaran\s+Wajib|Luaran\s+Tambahan)\s*:\s*(.+)$/iu', $line, $m)) {
            $label = (string) $m[1];
            $rawItems = preg_split('/[|,]/', (string) $m[2]) ?: [];
            $items = [];
            foreach ($rawItems as $rawItem) {
                $item = $normalizeOutputToken((string) $rawItem);
                if ($item !== '') {
                    $items[] = $item;
                }
            }
            $formatted[] = $label . ': ' . implode(', ', $items);
            continue;
        }

        $segments = preg_split('/[|,]/', $line) ?: [];
        $items = [];
        foreach ($segments as $segment) {
            $item = $normalizeOutputToken((string) $segment);
            if ($item !== '') {
                $items[] = $item;
            }
        }
        $formatted[] = implode(', ', $items);
    }

    $final = implode("\n", $formatted);
    return $final !== '' ? htmlspecialchars($final, ENT_QUOTES, 'UTF-8') : '-';
}

function detail_text_clean_plain(string $value): string
{
    $text = trim($value);
    if ($text === '') {
        return '';
    }

    $normalizeOutputToken = static function (string $token): string {
        $key = strtolower(trim($token));
        $map = [
            'artikel_sinta' => 'Artikel SINTA',
            'artikel_internasional' => 'Artikel Internasional',
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

        $token = str_replace('_', ' ', $key);
        return ucwords($token);
    };

    $rows = preg_split('/\R+/', $text) ?: [];
    $formatted = [];

    foreach ($rows as $row) {
        $line = trim($row);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^(Luaran\s+Wajib|Luaran\s+Tambahan)\s*:\s*(.+)$/iu', $line, $m)) {
            $label = (string) $m[1];
            $rawItems = preg_split('/[|,]/', (string) $m[2]) ?: [];
            $items = [];
            foreach ($rawItems as $rawItem) {
                $item = $normalizeOutputToken((string) $rawItem);
                if ($item !== '') {
                    $items[] = $item;
                }
            }
            $formatted[] = $label . ': ' . implode(', ', $items);
            continue;
        }

        $segments = preg_split('/[|,]/', $line) ?: [];
        $items = [];
        foreach ($segments as $segment) {
            $item = $normalizeOutputToken((string) $segment);
            if ($item !== '') {
                $items[] = $item;
            }
        }
        $formatted[] = implode(', ', $items);
    }

    $final = trim(implode("\n", $formatted));
    return $final;
}

function detail_notes_clean(string $value): string
{
    $text = trim($value);
    if ($text === '') {
        return '-';
    }

    // Hilangkan token referensi internal yang tidak perlu ditampilkan ke pengguna.
    $text = preg_replace('/__ACTIVITY_REF__\[[^\]]+\]/', '', $text);
    $text = preg_replace('/__CONTRACT_SOURCE__\[[^\]]+\]/', '', (string) $text);
    $text = preg_replace('/\s{2,}/', ' ', (string) $text);
    $text = trim((string) $text, " \t\n\r\0\x0B.;,-");

    return $text === '' ? '-' : htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function detail_currency(string $value): string
{
    $digits = preg_replace('/\D+/', '', trim($value));
    if ($digits === '' || $digits === '0') {
        return '-';
    }

    return 'Rp ' . number_format((int) $digits, 0, ',', '.');
}

function is_system_contract_note(string $value): bool
{
    $text = preg_replace('/__ACTIVITY_REF__\[[^\]]+\]/', '', $value);
    $text = preg_replace('/__CONTRACT_SOURCE__\[[^\]]+\]/', '', (string) $text);
    $text = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $text)));
    $text = trim($text, " \t\n\r\0\x0B.;,-");

    return $text === 'ajuan kontrak dari menu ajukan surat';
}

$applicantName = (string) ($applicantProfile['name'] ?? $letter['applicant_name'] ?? $detail['researcher_name'] ?? '-');
$applicantNidn = (string) ($applicantProfile['nidn'] ?? $letter['applicant_nidn'] ?? '-');
$applicantEmail = (string) ($applicantProfile['email'] ?? $detail['applicant_email'] ?? $letter['applicant_email'] ?? '-');
$applicantPhone = (string) ($applicantProfile['phone'] ?? $detail['phone'] ?? $letter['applicant_phone'] ?? '-');
$applicantFaculty = (string) ($applicantProfile['faculty'] ?? $applicantProfile['fakultas'] ?? $detail['faculty'] ?? '-');
$applicantStudyProgram = (string) ($applicantProfile['study_program'] ?? $applicantProfile['unit'] ?? $detail['unit'] ?? $letter['applicant_unit'] ?? '-');
$cleanNotes = detail_notes_clean((string) ($detail['notes'] ?? ''));
$showAdditionalNotes = $letterKind === 'tugas'
    && $cleanNotes !== '-'
    && !is_system_contract_note((string) ($detail['notes'] ?? ''));
$destinationColClass = $letterKind === 'izin' ? 'col-md-4' : 'col-md-3';
$destinationPositionColClass = $letterKind === 'izin' ? 'col-md-4' : 'col-md-3';
$attachmentColClass = $letterKind === 'izin'
    ? 'col-md-4'
    : ($showAdditionalNotes ? 'col-md-6' : 'col-md-12');
$isInternalLppmTarget = $letterKind === 'kontrak';
$destinationDisplay = $isInternalLppmTarget
    ? (string) ($chairmanProfile['name'] ?? $letter['destination'] ?? '-')
    : (string) ($letter['destination'] ?? '-');
$destinationPositionDisplay = $isInternalLppmTarget
    ? 'Kepala LPPM'
    : (string) ($detail['destination_position'] ?? '-');
$revisionNoteDisplay = trim((string) ($detail['revision_note'] ?? ''));
if ($revisionNoteDisplay === '') {
    $revisionNoteDisplay = $cleanNotes !== '-' ? strip_tags($cleanNotes) : '';
}
if (strtolower(trim((string) preg_replace('/\s+/', ' ', $revisionNoteDisplay))) === 'ajuan kontrak dari menu ajukan surat') {
    $revisionNoteDisplay = '';
}
if ($revisionNoteDisplay === '') {
    $revisionNoteDisplay = 'Belum ada catatan perbaikan dari Kepala LPPM.';
}

$notesForEdit = trim((string) ($detail['notes'] ?? ''));
$notesForEdit = (string) preg_replace('/__ACTIVITY_REF__\[[^\]]+\]/', '', $notesForEdit);
$notesForEdit = (string) preg_replace('/__CONTRACT_SOURCE__\[[^\]]+\]/', '', $notesForEdit);
$notesForEdit = (string) preg_replace('/\s{2,}/', ' ', $notesForEdit);
$notesForEdit = trim($notesForEdit, " \t\n\r\0\x0B.;,-");
$notesKey = strtolower(trim((string) preg_replace('/\s+/', ' ', $notesForEdit)));
if ($notesKey === 'ajuan kontrak dari menu ajukan surat') {
    $notesForEdit = '';
}
$purposeForEdit = detail_text_clean_plain((string) ($detail['purpose'] ?? ''));
if ($purposeForEdit === '') {
    $purposeForEdit = (string) ($detail['purpose'] ?? '');
}
$targetLuaranRaw = trim((string) ($detail['target_luaran'] ?? ''));
if ($targetLuaranRaw === '') {
    $targetLuaranRaw = (string) ($detail['purpose'] ?? '');
}
$targetLuaranDisplay = detail_text_clean($targetLuaranRaw);
?>
<style>
.detail-link-soft {
    color: #1f4f82;
    text-decoration: none;
}
.detail-link-soft:hover {
    color: #173d67;
    text-decoration: none;
}
</style>

<div class="page-content detail-page">
    <div class="detail-header mb-3">
        <div class="detail-header-row">
            <div>
                <h1 class="page-title mb-1"><?= htmlspecialchars($kindLabels['detail_title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="page-subtitle mb-0">
                    <?= htmlspecialchars($kindLabels['detail_subtitle'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </div>

            <div class="detail-status-box">
                <span class="status-pill status-table-pill myletters-status-pill <?= htmlspecialchars($currentStatus['class'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi <?= htmlspecialchars((string) ($currentStatus['icon'] ?? 'bi-send-fill'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                    <?= htmlspecialchars($currentStatus['label'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success form-alert"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="profile-card detail-summary-card h-100">
                <div class="section-head mb-3">
                    <h3 class="section-title mb-0">Ringkasan Surat</h3>
                </div>

                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Nomor Surat</span>
                        <span class="summary-value"><?= detail_value($letter, 'letter_number'); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Jenis Surat</span>
                        <span class="summary-value"><?= detail_value($letter, 'type', $kindLabels['default_type']); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Tanggal Surat</span>
                        <span class="summary-value"><?= detail_date($letter, 'letter_date'); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Perihal</span>
                        <span class="summary-value"><?= detail_value($letter, 'subject'); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Tujuan</span>
                        <span class="summary-value"><?= detail_value($letter, 'destination'); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Instansi</span>
                        <span class="summary-value"><?= detail_value($letter, 'institution'); ?></span>
                    </div>
                </div>

                <?php if ($isEditMode): ?>
                    <div class="form-section-card mt-3">
                        <div class="section-head">
                            <h3 class="section-title mb-0">Catatan Perbaikan</h3>
                        </div>
                        <div class="profile-info-item mt-2">
                            <div class="profile-info-value detail-multiline"><?= nl2br(htmlspecialchars($revisionNoteDisplay, ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="profile-summary-divider"></div>

                <?php if (!$isAnyEditMode): ?>
                    <?php if ($isHeadPanel): ?>
                        <div class="detail-actions detail-head-actions">
                            <a href="<?= htmlspecialchars($backListUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">
                                Kembali
                            </a>
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
                            <a href="<?= htmlspecialchars($backListUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">
                                Kembali
                            </a>
                            <?php if ($canDownloadLetter): ?>
                                <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0) . '/download', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main form-btn">
                                    Unduh Surat
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary form-btn" disabled>
                                    Unduh Surat
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if ($isAnyEditMode): ?>
                <?php if ($isHeadEditMode): ?>
                    <div class="form-section-card mb-3">
                        <div class="section-head">
                            <h3 class="section-title mb-0">Informasi Pemohon</h3>
                            <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('pemohon'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="row g-3 mt-1 applicant-info-grid">
                            <div class="col-md-6"><div class="profile-info-item applicant-info-item"><div class="applicant-info-head"><span class="applicant-info-icon"><i class="bi bi-person-badge"></i></span><div class="profile-info-label">Nama Dosen / Pemohon</div></div><div class="profile-info-value"><?= htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                            <div class="col-md-6"><div class="profile-info-item applicant-info-item"><div class="applicant-info-head"><span class="applicant-info-icon"><i class="bi bi-upc-scan"></i></span><div class="profile-info-label">NIDN</div></div><div class="profile-info-value"><?= htmlspecialchars($applicantNidn, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                            <div class="col-md-6"><div class="profile-info-item applicant-info-item"><div class="applicant-info-head"><span class="applicant-info-icon"><i class="bi bi-envelope"></i></span><div class="profile-info-label">Email</div></div><div class="profile-info-value"><?= htmlspecialchars($applicantEmail, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                            <div class="col-md-6"><div class="profile-info-item applicant-info-item"><div class="applicant-info-head"><span class="applicant-info-icon"><i class="bi bi-telephone"></i></span><div class="profile-info-label">Nomor HP</div></div><div class="profile-info-value"><?= htmlspecialchars($applicantPhone, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                            <div class="col-md-6"><div class="profile-info-item applicant-info-item"><div class="applicant-info-head"><span class="applicant-info-icon"><i class="bi bi-building"></i></span><div class="profile-info-label">Fakultas</div></div><div class="profile-info-value"><?= htmlspecialchars($applicantFaculty, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                            <div class="col-md-6"><div class="profile-info-item applicant-info-item"><div class="applicant-info-head"><span class="applicant-info-icon"><i class="bi bi-mortarboard"></i></span><div class="profile-info-label">Program Studi</div></div><div class="profile-info-value"><?= htmlspecialchars($applicantStudyProgram, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                        </div>
                    </div>

                    <div class="form-section-card mb-3">
                        <div class="section-head">
                            <h3 class="section-title mb-0"><?= htmlspecialchars($labels['info_section'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('data_kegiatan'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($labels['title'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= detail_value($detail, 'research_title'); ?></div></div></div>
                            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($labels['scheme'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= detail_value($detail, 'research_scheme'); ?></div></div></div>
                            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label">Sumber Dana</div><div class="profile-info-value"><?= detail_value($detail, 'funding_source'); ?></div></div></div>
                            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($labels['year'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= detail_value($detail, 'research_year'); ?></div></div></div>
                            <div class="col-md-3"><div class="profile-info-item"><div class="profile-info-label">Total Dana Disetujui</div><div class="profile-info-value"><?= htmlspecialchars(detail_currency((string) ($detail['total_dana_disetujui'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div></div></div>
                            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($labels['leader'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value"><?= detail_value($detail, 'researcher_name'); ?></div></div></div>
                            <div class="col-md-6"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($labels['members'], ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value detail-multiline"><?= nl2br(detail_value($detail, 'members')); ?></div></div></div>
                            <div class="col-12"><div class="profile-info-item"><div class="profile-info-label"><?= htmlspecialchars($targetLuaranLabel, ENT_QUOTES, 'UTF-8'); ?></div><div class="profile-info-value detail-multiline"><?= nl2br($targetLuaranDisplay); ?></div></div></div>
                        </div>
                    </div>

                    <form method="post" action="<?= htmlspecialchars($editFormAction, ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" class="form-section-card mb-3">
                        <input type="hidden" name="letter_id" value="<?= (int) ($letter['id'] ?? 0); ?>">
                        <input type="hidden" name="statement_true" value="1">
                        <input type="hidden" name="statement_rules" value="1">
                        <input type="hidden" name="purpose" value="<?= htmlspecialchars($purposeForEdit, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="subject" value="<?= htmlspecialchars((string) ($letter['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="destination" value="<?= htmlspecialchars((string) ($letter['destination'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="destination_position" value="<?= htmlspecialchars((string) ($detail['destination_position'] ?? 'Kepala LPPM'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="notes" value="<?= htmlspecialchars($notesForEdit, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="section-head">
                            <h3 class="section-title mb-0">Edit Administrasi Pengajuan</h3>
                            <span class="section-badge">Mode Edit</span>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6"><label class="form-label">Instansi</label><input type="text" name="institution" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['institution'] ?? $letter['institution'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: SMP Negeri 5 Kupang"></div>
                            <div class="col-md-6"><label class="form-label">Alamat</label><textarea name="address" class="form-control modern-input modern-textarea" rows="2" placeholder="Contoh: Jl. El Tari No. 10, Kota Kupang"><?= htmlspecialchars((string) ($detail['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                            <div class="col-md-4"><label class="form-label">Kota</label><input type="text" name="city" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Kota Kupang"></div>
                            <div class="col-md-4"><label class="form-label">Tanggal Mulai</label><input type="date" name="start_date" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['start_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal mulai"></div>
                            <div class="col-md-4"><label class="form-label">Tanggal Selesai</label><input type="date" name="end_date" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['end_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal selesai"></div>
                            <div class="col-md-6"><label class="form-label">Link Lampiran (opsional)</label><input type="url" name="attachment_file" class="form-control modern-input" placeholder="https://contoh.com/lampiran.pdf"></div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" name="submit_action" value="submit" class="btn btn-primary-main"><?= htmlspecialchars($editSubmitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                            <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) ($letter['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Batal</a>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= htmlspecialchars($editFormAction, ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data" class="form-section-card mb-3">
                        <input type="hidden" name="letter_id" value="<?= (int) ($letter['id'] ?? 0); ?>">
                        <input type="hidden" name="statement_true" value="1">
                        <input type="hidden" name="statement_rules" value="1">
                        <div class="section-head">
                            <h3 class="section-title mb-0">Edit Pengajuan</h3>
                            <span class="section-badge">Mode Edit</span>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="name" class="form-control modern-input" value="<?= htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label">NIDN</label><input type="text" name="nidn" class="form-control modern-input" value="<?= htmlspecialchars($applicantNidn, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="applicant_email" class="form-control modern-input" value="<?= htmlspecialchars($applicantEmail, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label">Nomor HP</label><input type="text" name="phone" class="form-control modern-input" value="<?= htmlspecialchars($applicantPhone, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label">Fakultas</label><input type="text" name="faculty" class="form-control modern-input" value="<?= htmlspecialchars($applicantFaculty, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label">Program Studi</label><input type="text" name="unit" class="form-control modern-input" value="<?= htmlspecialchars($applicantStudyProgram, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>

                            <div class="col-12"><label class="form-label">Judul</label><input type="text" name="research_title" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['research_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-3"><label class="form-label">Skema</label><input type="text" name="research_scheme" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['research_scheme'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-3"><label class="form-label">Sumber Dana</label><input type="text" name="funding_source" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['funding_source'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-3"><label class="form-label">Tahun</label><input type="text" name="research_year" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['research_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-3"><label class="form-label">Total Dana Disetujui</label><input type="text" class="form-control modern-input" value="<?= htmlspecialchars(detail_currency((string) ($detail['total_dana_disetujui'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label">Ketua</label><input type="text" name="researcher_name" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['researcher_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label">Anggota</label><textarea name="members" class="form-control modern-input modern-textarea" rows="2" readonly><?= htmlspecialchars((string) ($detail['members'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                            <div class="col-12"><label class="form-label"><?= htmlspecialchars($keperluanLabel, ENT_QUOTES, 'UTF-8'); ?></label><textarea name="purpose" class="form-control modern-input modern-textarea" rows="3" placeholder="Jelaskan keperluan surat secara ringkas"><?= htmlspecialchars($purposeForEdit, ENT_QUOTES, 'UTF-8'); ?></textarea></div>

                            <div class="col-md-6"><label class="form-label">Instansi</label><input type="text" name="institution" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['institution'] ?? $letter['institution'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: SMP Negeri 5 Kupang"></div>
                            <div class="col-md-6"><label class="form-label">Alamat</label><textarea name="address" class="form-control modern-input modern-textarea" rows="2" placeholder="Contoh: Jl. El Tari No. 10, Kota Kupang"><?= htmlspecialchars((string) ($detail['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                            <div class="col-md-4"><label class="form-label">Kota</label><input type="text" name="city" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Kota Kupang"></div>
                            <div class="col-md-4"><label class="form-label">Tanggal Mulai</label><input type="date" name="start_date" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['start_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal mulai"></div>
                            <div class="col-md-4"><label class="form-label">Tanggal Selesai</label><input type="date" name="end_date" class="form-control modern-input" value="<?= htmlspecialchars((string) ($detail['end_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal selesai"></div>

                            <div class="col-md-6"><label class="form-label">Link Lampiran (opsional)</label><input type="url" name="attachment_file" class="form-control modern-input" placeholder="https://contoh.com/lampiran.pdf"></div>
                        </div>
                        <input type="hidden" name="subject" value="<?= htmlspecialchars((string) ($letter['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="destination" value="<?= htmlspecialchars((string) ($letter['destination'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="destination_position" value="<?= htmlspecialchars((string) ($detail['destination_position'] ?? 'Kepala LPPM'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="notes" value="<?= htmlspecialchars($notesForEdit, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mt-3">
                            <button type="submit" name="submit_action" value="submit" class="btn btn-primary-main"><?= htmlspecialchars($editSubmitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!$isAnyEditMode): ?>
            <div class="form-section-card mb-3">
                <div class="section-head">
                    <h3 class="section-title mb-0">Informasi Pemohon</h3>
                    <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('pemohon'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <div class="row g-3 mt-1 applicant-info-grid">
                    <div class="col-md-6">
                        <div class="profile-info-item applicant-info-item">
                            <div class="applicant-info-head">
                                <span class="applicant-info-icon"><i class="bi bi-person-badge"></i></span>
                                <div class="profile-info-label">Nama Dosen / Pemohon</div>
                            </div>
                            <div class="profile-info-value"><?= htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item applicant-info-item">
                            <div class="applicant-info-head">
                                <span class="applicant-info-icon"><i class="bi bi-upc-scan"></i></span>
                                <div class="profile-info-label">NIDN</div>
                            </div>
                            <div class="profile-info-value"><?= htmlspecialchars($applicantNidn, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item applicant-info-item">
                            <div class="applicant-info-head">
                                <span class="applicant-info-icon"><i class="bi bi-envelope"></i></span>
                                <div class="profile-info-label">Email</div>
                            </div>
                            <div class="profile-info-value"><?= htmlspecialchars($applicantEmail, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item applicant-info-item">
                            <div class="applicant-info-head">
                                <span class="applicant-info-icon"><i class="bi bi-telephone"></i></span>
                                <div class="profile-info-label">Nomor HP</div>
                            </div>
                            <div class="profile-info-value"><?= htmlspecialchars($applicantPhone, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item applicant-info-item">
                            <div class="applicant-info-head">
                                <span class="applicant-info-icon"><i class="bi bi-building"></i></span>
                                <div class="profile-info-label">Fakultas</div>
                            </div>
                            <div class="profile-info-value"><?= htmlspecialchars($applicantFaculty, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item applicant-info-item">
                            <div class="applicant-info-head">
                                <span class="applicant-info-icon"><i class="bi bi-mortarboard"></i></span>
                                <div class="profile-info-label">Program Studi</div>
                            </div>
                            <div class="profile-info-value"><?= htmlspecialchars($applicantStudyProgram, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section-card mb-3">
                <div class="section-head">
                    <h3 class="section-title mb-0"><?= htmlspecialchars($labels['info_section'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('data_kegiatan'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <div class="profile-info-item">
                            <div class="profile-info-label"><?= htmlspecialchars($labels['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="profile-info-value"><?= detail_value($detail, 'research_title'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="profile-info-item">
                            <div class="profile-info-label"><?= htmlspecialchars($labels['scheme'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="profile-info-value"><?= detail_value($detail, 'research_scheme'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Sumber Dana</div>
                            <div class="profile-info-value"><?= detail_value($detail, 'funding_source'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="profile-info-item">
                            <div class="profile-info-label"><?= htmlspecialchars($labels['year'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="profile-info-value"><?= detail_value($detail, 'research_year'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Total Dana Disetujui</div>
                            <div class="profile-info-value"><?= htmlspecialchars(detail_currency((string) ($detail['total_dana_disetujui'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item">
                            <div class="profile-info-label"><?= htmlspecialchars($labels['leader'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="profile-info-value"><?= detail_value($detail, 'researcher_name'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item">
                            <div class="profile-info-label"><?= htmlspecialchars($labels['members'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="profile-info-value detail-multiline"><?= nl2br(detail_value($detail, 'members')); ?></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="profile-info-item">
                            <div class="profile-info-label"><?= htmlspecialchars($targetLuaranLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="profile-info-value detail-multiline"><?= nl2br($targetLuaranDisplay); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section-card mb-3">
                <div class="section-head">
                    <h3 class="section-title mb-0"><?= htmlspecialchars($labels['location_section'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('pelaksanaan'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <div class="profile-info-item">
                            <div class="profile-info-label"><?= htmlspecialchars($labels['institution'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="profile-info-value"><?= detail_value($detail, 'institution'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Alamat Lokasi</div>
                            <div class="profile-info-value detail-multiline"><?= nl2br(detail_value($detail, 'address')); ?></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Kota / Kabupaten</div>
                            <div class="profile-info-value"><?= detail_value($detail, 'city'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Lama Kegiatan</div>
                            <div class="profile-info-value"><?= htmlspecialchars(detail_duration_years($detail, 'start_date', 'end_date'), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                    <?php if ($letterKind !== 'kontrak'): ?>
                        <div class="col-md-4">
                            <div class="profile-info-item">
                                <div class="profile-info-label">Tanggal Mulai</div>
                                <div class="profile-info-value"><?= detail_date($detail, 'start_date'); ?></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="profile-info-item">
                                <div class="profile-info-label">Tanggal Selesai</div>
                                <div class="profile-info-value"><?= detail_date($detail, 'end_date'); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-section-card">
                <div class="section-head">
                    <h3 class="section-title mb-0"><?= htmlspecialchars($adminSectionTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <span class="section-badge"><?= htmlspecialchars(letter_detail_badge('administrasi'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <div class="row g-3 mt-1">
                    <?php if ($letterKind !== 'izin'): ?>
                        <div class="col-md-6">
                            <div class="profile-info-item">
                                <div class="profile-info-label"><?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="profile-info-value"><?= detail_value($letter, 'subject'); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="<?= htmlspecialchars($destinationColClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Ditujukan Kepada</div>
                            <div class="profile-info-value"><?= htmlspecialchars($destinationDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="<?= htmlspecialchars($destinationPositionColClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Jabatan Tujuan</div>
                            <div class="profile-info-value"><?= htmlspecialchars($destinationPositionDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="<?= htmlspecialchars($attachmentColClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="profile-info-item">
                            <div class="profile-info-label">Lampiran</div>
                            <div class="profile-info-value detail-multiline">
                                <?php
                                    $attachmentRaw = trim((string) ($detail['attachment_file'] ?? ''));
                                    $attachmentPaths = [
                                        'file_proposal' => '',
                                        'file_instrumen' => '',
                                        'file_pendukung_lain' => '',
                                    ];
                                    if ($attachmentRaw !== '') {
                                        $decodedAttachment = json_decode($attachmentRaw, true);
                                        if (is_array($decodedAttachment)) {
                                            foreach (array_keys($attachmentPaths) as $attachmentKey) {
                                                $attachmentPaths[$attachmentKey] = trim((string) ($decodedAttachment[$attachmentKey] ?? ''));
                                            }
                                        } else {
                                            $attachmentPaths['file_proposal'] = $attachmentRaw;
                                        }
                                    }
                                ?>
                                <?php
                                    $hasAnyAttachment = trim($attachmentPaths['file_proposal']) !== ''
                                        || trim($attachmentPaths['file_instrumen']) !== ''
                                        || trim($attachmentPaths['file_pendukung_lain']) !== '';
                                ?>
                                <?php if ($hasAnyAttachment): ?>
                                    <div class="d-flex flex-wrap gap-3 align-items-center">
                                        <?php if (trim($attachmentPaths['file_proposal']) !== ''): ?>
                                            <a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_proposal', (string) $attachmentPaths['file_proposal']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="detail-link-soft fw-semibold">Lampiran Proposal</a>
                                        <?php endif; ?>
                                        <?php if (trim($attachmentPaths['file_instrumen']) !== ''): ?>
                                            <a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_instrumen', (string) $attachmentPaths['file_instrumen']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="detail-link-soft fw-semibold">Lampiran Instrumen</a>
                                        <?php endif; ?>
                                        <?php if (trim($attachmentPaths['file_pendukung_lain']) !== ''): ?>
                                            <a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_pendukung_lain', (string) $attachmentPaths['file_pendukung_lain']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="detail-link-soft fw-semibold">Lampiran Pendukung Lainnya</a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($showAdditionalNotes): ?>
                        <div class="col-md-6">
                            <div class="profile-info-item">
                                <div class="profile-info-label">Catatan Tambahan</div>
                                <div class="profile-info-value detail-multiline"><?= nl2br($cleanNotes); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($letterKind === 'izin'): ?>
                        <div class="col-12">
                            <div class="profile-info-item">
                                <div class="profile-info-label"><?= htmlspecialchars($keperluanLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="profile-info-value detail-multiline"><?= nl2br(htmlspecialchars($purposeForEdit, ENT_QUOTES, 'UTF-8')); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isHeadPanel && !$isEditMode): ?>
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
