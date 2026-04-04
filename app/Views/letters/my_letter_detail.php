<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$status = strtolower((string) ($letter['status'] ?? ''));
$statusBadge = [
    'draft' => 'secondary',
    'diajukan' => 'primary',
    'diverifikasi' => 'info',
    'perlu_diperbaiki' => 'warning',
    'perlu diperbaiki' => 'warning',
    'menunggu_finalisasi' => 'success',
    'disetujui' => 'success',
    'approved' => 'success',
    'surat_terbit' => 'success',
    'surat terbit' => 'success',
    'terbit' => 'success',
    'ditolak' => 'warning',
    'rejected' => 'warning',
    'selesai' => 'dark',
];
$statusLabel = [
    'draft' => 'Draft',
    'diajukan' => 'Menunggu Diproses',
    'submitted' => 'Menunggu Diproses',
    'diverifikasi' => 'Menunggu Diproses',
    'perlu_diperbaiki' => 'Perlu Diperbaiki',
    'perlu diperbaiki' => 'Perlu Diperbaiki',
    'menunggu_finalisasi' => 'Disetujui',
    'disetujui' => 'Disetujui',
    'approved' => 'Disetujui',
    'surat_terbit' => 'Surat Terbit',
    'surat terbit' => 'Surat Terbit',
    'terbit' => 'Surat Terbit',
    'ditolak' => 'Perlu Diperbaiki',
    'rejected' => 'Perlu Diperbaiki',
    'selesai' => 'Selesai',
];
$isIssuedForDownload = in_array($status, ['surat_terbit', 'surat terbit', 'terbit', 'selesai'], true);
$isMemberReadOnly = (bool) ($isMemberReadOnly ?? false);

$typeSource = strtolower((string) (($letter['letter_type_name'] ?? '') . ' ' . ($letter['subject'] ?? '')));
$activityType = str_contains($typeSource, 'pengabdian')
    ? 'pengabdian'
    : (str_contains($typeSource, 'hilirisasi') ? 'hilirisasi' : 'penelitian');
$isContractLetter = str_contains($typeSource, 'kontrak');

$labelMap = [
    'penelitian' => [
        'info_section' => 'Informasi Penelitian',
        'title' => 'Judul Penelitian',
        'scheme' => 'Skema Penelitian',
        'year' => 'Tahun Penelitian',
        'leader' => 'Ketua Peneliti',
        'members' => 'Anggota Peneliti',
        'purpose' => 'Tujuan / Ringkasan',
        'location_section' => 'Lokasi dan Waktu',
        'institution' => 'Instansi Tujuan',
    ],
    'pengabdian' => [
        'info_section' => 'Informasi Pengabdian',
        'title' => 'Judul Pengabdian',
        'scheme' => 'Skema Pengabdian',
        'year' => 'Tahun Pengabdian',
        'leader' => 'Ketua Pelaksana',
        'members' => 'Anggota Pelaksana',
        'purpose' => 'Tujuan / Ringkasan',
        'location_section' => 'Lokasi dan Waktu',
        'institution' => 'Instansi Tujuan',
    ],
    'hilirisasi' => [
        'info_section' => 'Informasi Hilirisasi',
        'title' => 'Judul Hilirisasi',
        'scheme' => 'Skema Hilirisasi',
        'year' => 'Tahun Hilirisasi',
        'leader' => 'Ketua Pelaksana',
        'members' => 'Anggota Tim Pelaksana',
        'purpose' => 'Tujuan / Ringkasan',
        'location_section' => 'Lokasi dan Waktu',
        'institution' => 'Instansi Tujuan',
    ],
];
$labels = $labelMap[$activityType];

$startDateRaw = trim((string) ($letter['start_date'] ?? ''));
$endDateRaw = trim((string) ($letter['end_date'] ?? ''));
$lamaKegiatanText = '-';
if ($startDateRaw !== '' && $endDateRaw !== '') {
    $startTs = strtotime($startDateRaw);
    $endTs = strtotime($endDateRaw);
    if ($startTs !== false && $endTs !== false && $endTs >= $startTs) {
        $years = ((int) date('Y', $endTs) - (int) date('Y', $startTs)) + 1;
        $lamaKegiatanText = $years . ' Tahun';
    }
}
?>

<div class="permit-page letter-detail-page compact-detail">

<div class="mb-3">
    <h4 class="mb-1">Detail Surat Saya</h4>
    <p class="text-muted mb-0">Informasi lengkap pengajuan surat Anda pada sistem SAPA LPPM.</p>
</div>

<div class="card dashboard-card detail-section mb-3">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Informasi Surat</h6></div>
    <div class="card-body row g-2">
        <div class="col-md-4"><strong>Nomor Surat</strong><div><?= htmlspecialchars((string) (($letter['letter_number'] ?? '') !== '' ? $letter['letter_number'] : '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Jenis Surat</strong><div><?= htmlspecialchars((string) ($letter['letter_type_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Status</strong><div><span class="badge text-bg-<?= $statusBadge[$status] ?? 'secondary'; ?>"><?= htmlspecialchars((string) ($statusLabel[$status] ?? ($letter['status'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></span></div></div>
        <div class="col-md-8"><strong>Perihal</strong><div><?= htmlspecialchars((string) ($letter['subject'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Tanggal Surat</strong><div><?= htmlspecialchars((string) date('d M Y', strtotime((string) ($letter['letter_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></div></div>
    </div>
</div>

<div class="card dashboard-card detail-section mb-3">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Informasi Pemohon</h6></div>
    <div class="card-body row g-2">
        <div class="col-md-4"><strong>Nama</strong><div><?= htmlspecialchars((string) ($letter['applicant_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-2"><strong>NIDN</strong><div><?= htmlspecialchars((string) ($letter['applicant_nidn'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-6"><strong>Fakultas</strong><div><?= htmlspecialchars((string) ($letter['faculty'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Program Studi</strong><div><?= htmlspecialchars((string) ($letter['research_unit'] ?? $letter['applicant_unit'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Email</strong><div><?= htmlspecialchars((string) ($letter['applicant_email'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Nomor HP</strong><div><?= htmlspecialchars((string) ($letter['applicant_phone'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
    </div>
</div>

<div class="card dashboard-card detail-section mb-3">
    <div class="card-header bg-white border-0"><h6 class="mb-0"><?= htmlspecialchars($labels['info_section'], ENT_QUOTES, 'UTF-8'); ?></h6></div>
    <div class="card-body row g-2">
        <div class="col-md-12"><strong><?= htmlspecialchars($labels['title'], ENT_QUOTES, 'UTF-8'); ?></strong><div><?= htmlspecialchars((string) ($letter['research_title'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong><?= htmlspecialchars($labels['scheme'], ENT_QUOTES, 'UTF-8'); ?></strong><div><?= htmlspecialchars((string) ($letter['research_scheme'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Sumber Dana</strong><div><?= htmlspecialchars((string) ($letter['funding_source'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong><?= htmlspecialchars($labels['year'], ENT_QUOTES, 'UTF-8'); ?></strong><div><?= htmlspecialchars((string) ($letter['research_year'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-6"><strong><?= htmlspecialchars($labels['leader'], ENT_QUOTES, 'UTF-8'); ?></strong><div><?= htmlspecialchars((string) ($letter['researcher_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-6"><strong><?= htmlspecialchars($labels['members'], ENT_QUOTES, 'UTF-8'); ?></strong><div><?= nl2br(htmlspecialchars((string) ($letter['members'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></div></div>
        <div class="col-md-12"><strong><?= htmlspecialchars($labels['purpose'], ENT_QUOTES, 'UTF-8'); ?></strong><div><?= nl2br(htmlspecialchars((string) ($letter['purpose'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></div></div>
    </div>
</div>

<div class="card dashboard-card detail-section mb-3">
    <div class="card-header bg-white border-0"><h6 class="mb-0"><?= htmlspecialchars($labels['location_section'], ENT_QUOTES, 'UTF-8'); ?></h6></div>
    <div class="card-body row g-2">
        <div class="col-md-4"><strong><?= htmlspecialchars($labels['institution'], ENT_QUOTES, 'UTF-8'); ?></strong><div><?= htmlspecialchars((string) ($letter['institution'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Alamat</strong><div><?= htmlspecialchars((string) ($letter['address'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Kota / Kabupaten</strong><div><?= htmlspecialchars((string) ($letter['city'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Lama Kegiatan</strong><div><?= htmlspecialchars($lamaKegiatanText, ENT_QUOTES, 'UTF-8'); ?></div></div>
        <?php if (!$isContractLetter): ?>
            <div class="col-md-4"><strong>Tanggal Mulai</strong><div><?= htmlspecialchars((string) ($letter['start_date'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
            <div class="col-md-4"><strong>Tanggal Selesai</strong><div><?= htmlspecialchars((string) ($letter['end_date'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <?php endif; ?>
    </div>
</div>

<div class="card dashboard-card detail-section mb-4">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Administrasi</h6></div>
    <div class="card-body row g-2">
        <div class="col-md-4"><strong>Ditujukan Kepada</strong><div><?= htmlspecialchars((string) ($letter['destination'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Jabatan Tujuan</strong><div><?= htmlspecialchars((string) ($letter['destination_position'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></div>
        <div class="col-md-4"><strong>Lampiran</strong>
            <div>
                <?php
                    $attachmentRaw = trim((string) ($letter['attachment_file'] ?? ''));
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
                <?php if (trim($attachmentPaths['file_proposal']) !== ''): ?>
                    <div><a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_proposal', (string) $attachmentPaths['file_proposal']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-primary text-decoration-none">Lampiran Proposal</a></div>
                <?php endif; ?>
                <?php if (trim($attachmentPaths['file_instrumen']) !== ''): ?>
                    <div><a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_instrumen', (string) $attachmentPaths['file_instrumen']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-primary text-decoration-none">Lampiran Instrumen</a></div>
                <?php endif; ?>
                <?php if (trim($attachmentPaths['file_pendukung_lain']) !== ''): ?>
                    <div><a href="<?= htmlspecialchars(buildLetterAttachmentUrl($basePath, (int) ($letter['id'] ?? 0), 'file_pendukung_lain', (string) $attachmentPaths['file_pendukung_lain']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="text-primary text-decoration-none">Lampiran Pendukung Lainnya</a></div>
                <?php endif; ?>
                <?php if (
                    trim($attachmentPaths['file_proposal']) === ''
                    && trim($attachmentPaths['file_instrumen']) === ''
                    && trim($attachmentPaths['file_pendukung_lain']) === ''
                ): ?>
                    -
                <?php else: ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-12"><strong>Catatan</strong><div><?= nl2br(htmlspecialchars((string) ($letter['notes'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></div></div>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap mb-4 detail-actions">
    <a href="<?= htmlspecialchars($basePath . '/surat-saya', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Kembali</a>
    <?php if ($status === 'draft' && !$isMemberReadOnly): ?>
        <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letter['id'] . '?edit=1', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-dark">Edit</a>
    <?php endif; ?>
    <a href="<?= htmlspecialchars($basePath . '/letters/' . (int) $letter['id'] . '/preview', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary" target="_blank">Preview</a>
    <?php if (!empty($letter['file_pdf']) && $isIssuedForDownload): ?>
        <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letter['id'] . '/download', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Download PDF</a>
    <?php endif; ?>
</div>

</div>
