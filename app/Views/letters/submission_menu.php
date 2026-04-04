<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');

$selectedCategory = $selectedCategory ?? 'penelitian';
$selectedType = $selectedType ?? 'kontrak';
$activityId = (int) ($activityId ?? 0);
$contractRows = $contractRows ?? [];
$activeActivityOptions = $activeActivityOptions ?? [];
$activityRow = $activityRow ?? null;
$formData = $formData ?? [];

$categoryLabel = [
    'penelitian' => 'Penelitian',
    'pengabdian' => 'Pengabdian',
    'hilirisasi' => 'Hilirisasi',
];

$typeLabel = [
    'kontrak' => 'Surat Kontrak',
    'izin' => 'Surat Izin',
    'tugas' => 'Surat Tugas',
];
$typeLabelByCategory = [
    'penelitian' => [
        'kontrak' => 'Surat Kontrak Penelitian',
        'izin' => 'Surat Izin Penelitian',
        'tugas' => 'Surat Tugas Penelitian',
    ],
    'pengabdian' => [
        'kontrak' => 'Surat Kontrak Pengabdian',
        'izin' => 'Surat Izin Pengabdian',
        'tugas' => 'Surat Tugas Pengabdian',
    ],
    'hilirisasi' => [
        'kontrak' => 'Surat Kontrak Hilirisasi',
        'izin' => 'Surat Izin Pelaksanaan Hilirisasi',
        'tugas' => 'Surat Tugas Pelaksanaan Hilirisasi',
    ],
];
$inactiveTypeIcon = 'mdi:file-document-outline';
$activeTypeIcon = 'mdi:file-document-edit-outline';

$routes = [
    'penelitian' => [
        'kontrak' => 'surat-penelitian-kontrak',
        'izin' => 'surat-penelitian-izin',
        'tugas' => 'surat-penelitian-tugas',
    ],
    'pengabdian' => [
        'kontrak' => 'surat-pengabdian-kontrak',
        'izin' => 'surat-pengabdian-izin',
        'tugas' => 'surat-pengabdian-tugas',
    ],
    'hilirisasi' => [
        'kontrak' => 'surat-hilirisasi-kontrak',
        'izin' => 'surat-hilirisasi-izin',
        'tugas' => 'surat-hilirisasi-tugas',
    ],
];

$contentSubtitleMap = [
    'penelitian' => [
        'kontrak' => 'Ajukan Surat Kontrak Penelitian Anda.',
        'izin' => 'Ajukan Surat Izin Penelitian Anda.',
        'tugas' => 'Ajukan Surat Tugas Penelitian Anda.',
    ],
    'pengabdian' => [
        'kontrak' => 'Ajukan Surat Kontrak Pengabdian Anda.',
        'izin' => 'Ajukan Surat Izin Pengabdian Anda.',
        'tugas' => 'Ajukan Surat Tugas Pengabdian Anda.',
    ],
    'hilirisasi' => [
        'kontrak' => 'Ajukan Surat Kontrak Hilirisasi Anda.',
        'izin' => 'Ajukan Surat Izin Hilirisasi Anda.',
        'tugas' => 'Ajukan Surat Tugas Hilirisasi Anda.',
    ],
];
$contentSubtitle = $contentSubtitleMap[$selectedCategory][$selectedType] ?? 'Lengkapi data pengajuan surat Anda.';
$activeTypeTitle = $typeLabelByCategory[$selectedCategory][$selectedType] ?? ($typeLabel[$selectedType] . ' ' . $categoryLabel[$selectedCategory]);
$isPengabdianCategory = $selectedCategory === 'pengabdian';
$isHilirisasiCategory = $selectedCategory === 'hilirisasi';
?>

<div class="page-content submission-menu-page">
    <div class="mb-3">
        <h1 class="page-title mb-1">Ajukan Surat <?= htmlspecialchars($categoryLabel[$selectedCategory] ?? 'Penelitian', ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-subtitle mb-0">Pilih jenis surat dalam satu halaman terpusat.</p>
    </div>

    <div class="form-section-card submission-content-card">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success form-alert mb-3"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-warning form-alert mb-3"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="submission-subtab-row submission-subtab-top mb-3">
            <?php foreach (['kontrak', 'izin', 'tugas'] as $type): ?>
                <?php $isActive = $selectedType === $type; ?>
                <?php
                $tabUrl = $basePath . '/ajukan-surat/' . urlencode($selectedCategory) . '?surat_kind=' . urlencode($type);
                if ($activityId > 0) {
                    $tabUrl .= '&activity_id=' . $activityId;
                }
                ?>
                <a href="<?= htmlspecialchars($tabUrl, ENT_QUOTES, 'UTF-8'); ?>" class="submission-subtab <?= $isActive ? 'active' : ''; ?>">
                    <iconify-icon icon="<?= htmlspecialchars($isActive ? $activeTypeIcon : $inactiveTypeIcon, ENT_QUOTES, 'UTF-8'); ?>" class="submission-tab-icon" aria-hidden="true"></iconify-icon>
                    <?= htmlspecialchars($typeLabelByCategory[$selectedCategory][$type] ?? ($typeLabel[$type] . ' ' . $categoryLabel[$selectedCategory]), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="submission-section-head mb-3">
            <div class="submission-section-meta">
                <span class="submission-section-dot" aria-hidden="true"></span>
                <span>Pengajuan Surat</span>
            </div>
            <h3 class="section-title mb-1">
                <?= htmlspecialchars($activeTypeTitle, ENT_QUOTES, 'UTF-8'); ?>
            </h3>
            <p class="submission-section-subtitle mb-0"><?= htmlspecialchars($contentSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php if ($selectedType === 'kontrak'): ?>
            <div class="submission-contract-table-wrap table-responsive">
                <table data-custom-pagination="10" class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Judul</th>
                        <th>Skema</th>
                        <th>Ruang Lingkup</th>
                        <th>Sumber Dana</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($contractRows)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="submission-contract-empty text-center py-4">
                                    Belum ada data kegiatan aktif/terbaru yang siap diajukan kontrak.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contractRows as $index => $row): ?>
                            <tr>
                                <td><?= (int) $index + 1; ?></td>
                                <td><div class="activity-col-title"><?= htmlspecialchars((string) ($row['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div></td>
                                <td><?= htmlspecialchars((string) ($row['skema'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['ruang_lingkup'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['sumber_dana'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) ($row['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="contract-action-cell">
                                    <?php if (!empty($row['can_submit'])): ?>
                                        <form method="post" action="<?= htmlspecialchars($basePath . '/surat-kontrak/submit', ENT_QUOTES, 'UTF-8'); ?>" class="d-inline-block">
                                            <input type="hidden" name="activity_type" value="<?= htmlspecialchars((string) $selectedCategory, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="activity_id" value="<?= (int) ($row['id'] ?? 0); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary-main contract-submit-btn">
                                                <i class="bi bi-send-fill contract-submit-icon" aria-hidden="true"></i>
                                                Ajukan Kontrak
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-light-soft contract-submit-btn contract-submit-btn-disabled" disabled>
                                            <i class="bi bi-send-fill contract-submit-icon" aria-hidden="true"></i>
                                            Ajukan Kontrak
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($selectedType === 'izin' && in_array($selectedCategory, ['penelitian', 'pengabdian', 'hilirisasi'], true)): ?>
            <div class="submission-izin-layout">
                <div class="submission-stepper mb-3" aria-label="Alur pengajuan surat izin">
                    <div class="submission-stepper-item is-active" data-step-item="1">
                        <span class="submission-stepper-index">1</span>
                        <span class="submission-stepper-text">Pilih Kegiatan</span>
                    </div>
                    <div class="submission-stepper-item" data-step-item="2">
                        <span class="submission-stepper-index">2</span>
                        <span class="submission-stepper-text"><?= $isPengabdianCategory || $isHilirisasiCategory ? 'Data Pelaksana' : 'Data Peneliti'; ?></span>
                    </div>
                    <div class="submission-stepper-item" data-step-item="3">
                        <span class="submission-stepper-index">3</span>
                        <span class="submission-stepper-text">Instansi Tujuan</span>
                    </div>
                    <div class="submission-stepper-item" data-step-item="4">
                        <span class="submission-stepper-index">4</span>
                        <span class="submission-stepper-text">Pelaksanaan</span>
                    </div>
                    <div class="submission-stepper-item" data-step-item="5">
                        <span class="submission-stepper-index">5</span>
                        <span class="submission-stepper-text">Konfirmasi & Ajukan</span>
                    </div>
                </div>

                <div class="submission-izin-card mb-3">
                    <h6 class="submission-izin-card-title">Step 1 - Pilih Kegiatan Aktif</h6>
                    <form method="get" class="row g-2 align-items-end submission-step1-row">
                        <input type="hidden" name="route" value="ajukan-surat">
                        <input type="hidden" name="activity_type" value="<?= htmlspecialchars((string) $selectedCategory, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="surat_kind" value="izin">
                        <div class="col-md-10">
                            <label class="form-label">Pilih <?= $isPengabdianCategory ? 'Pengabdian Aktif' : ($isHilirisasiCategory ? 'Hilirisasi Aktif' : 'Penelitian Aktif'); ?> <span class="text-danger">*</span></label>
                            <select name="activity_id" id="stepActivitySelect" class="form-select modern-input" onchange="this.form.submit()">
                                <option value="">-- Pilih Data --</option>
                                <?php foreach ($activeActivityOptions as $opt): ?>
                                    <?php $optId = (int) ($opt['id'] ?? 0); ?>
                                    <?php
                                    $optTitle = (string) ($opt['judul'] ?? '-');
                                    $optLabel = $optTitle;
                                    $maxOptLength = 120;
                                    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                                        if (mb_strlen($optLabel, 'UTF-8') > $maxOptLength) {
                                            $optLabel = rtrim((string) mb_substr($optLabel, 0, $maxOptLength - 3, 'UTF-8')) . '...';
                                        }
                                    } elseif (strlen($optLabel) > $maxOptLength) {
                                        $optLabel = rtrim(substr($optLabel, 0, $maxOptLength - 3)) . '...';
                                    }
                                    ?>
                                    <option value="<?= $optId; ?>" title="<?= htmlspecialchars($optTitle, ENT_QUOTES, 'UTF-8'); ?>" <?= $optId === $activityId ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/' . urlencode($selectedCategory) . '?surat_kind=izin', ENT_QUOTES, 'UTF-8'); ?>" class="btn submission-reset-btn w-100">Reset Pilihan</a>
                        </div>
                    </form>
                </div>

                <?php if ($activityRow === null): ?>
                    <div class="submission-izin-empty">
                        Pilih data kegiatan aktif terlebih dahulu untuk melanjutkan pengajuan surat izin.
                    </div>
                <?php else: ?>
                    <div class="submission-izin-preview mb-3">
                        <h6 class="submission-izin-preview-title">Step 2 - <?= $isPengabdianCategory || $isHilirisasiCategory ? 'Data Pelaksana' : 'Data Peneliti'; ?> (Auto)</h6>
                        <div class="submission-izin-preview-grid">
                            <div class="submission-izin-preview-item"><span>Judul</span><strong><?= htmlspecialchars((string) ($activityRow['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="submission-izin-preview-item"><span><?= $isPengabdianCategory || $isHilirisasiCategory ? 'Ketua Pelaksana' : 'Ketua Peneliti'; ?></span><strong><?= htmlspecialchars((string) ($activityRow['ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="submission-izin-preview-item"><span>Skema</span><strong><?= htmlspecialchars((string) ($activityRow['skema'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="submission-izin-preview-item"><span>Tahun</span><strong><?= htmlspecialchars((string) ($activityRow['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="submission-izin-preview-item"><span>Fakultas</span><strong><?= htmlspecialchars((string) ($formData['faculty'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div class="submission-izin-preview-item"><span>Program Studi</span><strong><?= htmlspecialchars((string) ($formData['unit'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        </div>
                    </div>

                    <form method="post" action="<?= htmlspecialchars($basePath . '/letters/store', ENT_QUOTES, 'UTF-8'); ?>" class="submission-izin-form">
                        <input type="hidden" name="name" value="<?= htmlspecialchars((string) ($formData['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="nidn" value="<?= htmlspecialchars((string) ($formData['nidn'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="activity_type" value="<?= htmlspecialchars((string) $selectedCategory, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="activity_id" value="<?= (int) ($activityRow['id'] ?? 0); ?>">
                        <input type="hidden" name="faculty" value="<?= htmlspecialchars((string) ($formData['faculty'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="unit" value="<?= htmlspecialchars((string) ($formData['unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="applicant_email" value="<?= htmlspecialchars((string) ($formData['applicant_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="phone" value="<?= htmlspecialchars((string) ($formData['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="research_title" value="<?= htmlspecialchars((string) ($activityRow['judul'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="research_scheme" value="<?= htmlspecialchars((string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? ($activityRow['ruang_lingkup'] ?? '') : ($activityRow['skema'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="funding_source" value="<?= htmlspecialchars((string) ($activityRow['sumber_dana'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="research_year" value="<?= htmlspecialchars((string) ($activityRow['tahun'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="researcher_name" value="<?= htmlspecialchars((string) ($activityRow['ketua'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="members" value="<?= htmlspecialchars((string) ($activityRow['anggota'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="file_proposal" value="<?= htmlspecialchars((string) ($activityRow['file_proposal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="file_instrumen" value="<?= htmlspecialchars((string) ($activityRow['file_instrumen'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="file_pendukung_lain" value="<?= htmlspecialchars((string) ($activityRow['file_pendukung_lain'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="statement_true" value="1">
                        <input type="hidden" name="statement_rules" value="1">
                        <input type="hidden" name="subject" value="<?= htmlspecialchars((string) ($formData['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="submission-izin-card mb-3">
                            <h6 class="submission-izin-card-title">Step 3 - Instansi Tujuan</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Instansi <span class="text-danger">*</span></label>
                                    <input type="text" name="institution" id="stepInstitution" class="form-control modern-input" placeholder="Contoh: Dinas Pendidikan Kota Kupang" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Instansi</label>
                                    <input type="text" name="jenis_instansi" class="form-control modern-input" placeholder="Sekolah / Dinas / Desa / Perusahaan">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Pimpinan <span class="text-danger">*</span></label>
                                    <input type="text" name="destination" id="stepDestination" class="form-control modern-input" placeholder="Contoh: Dr. Nama Pimpinan" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jabatan Pimpinan <span class="text-danger">*</span></label>
                                    <input type="text" name="destination_position" id="stepDestinationPosition" class="form-control modern-input" placeholder="Contoh: Kepala Dinas" required>
                                </div>
                            </div>
                        </div>

                        <div class="submission-izin-card mb-3">
                            <h6 class="submission-izin-card-title">Step 4 - Alamat dan Pelaksanaan</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Alamat Lengkap Instansi <span class="text-danger">*</span></label>
                                    <textarea name="address" id="stepAddress" class="form-control modern-input modern-textarea" rows="3" placeholder="Contoh: Jl. El Tari No. 10, Kel. Oebobo, Kec. Oebobo, Kota Kupang" required></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><?= $isPengabdianCategory ? 'Lokasi Pengabdian' : ($isHilirisasiCategory ? 'Lokasi Pelaksanaan Hilirisasi' : 'Lokasi Penelitian'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" name="city" id="stepCity" class="form-control modern-input" value="<?= htmlspecialchars((string) ($formData['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: Kota Kupang" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="stepStartDate" class="form-control modern-input" value="<?= htmlspecialchars((string) ($activityRow['tanggal_mulai'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal mulai" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="stepEndDate" class="form-control modern-input" value="<?= htmlspecialchars((string) ($activityRow['tanggal_selesai'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih tanggal selesai" required>
                                </div>
                            </div>
                        </div>

                        <div class="submission-izin-card mb-3">
                            <h6 class="submission-izin-card-title">Step 5 - Keperluan</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label"><?= $isPengabdianCategory ? 'Keperluan Pengabdian' : ($isHilirisasiCategory ? 'Keperluan Pelaksanaan Hilirisasi' : 'Keperluan Penelitian'); ?> <span class="text-danger">*</span></label>
                                    <textarea name="purpose" id="stepPurpose" class="form-control modern-input modern-textarea" rows="4" placeholder="<?= htmlspecialchars($isPengabdianCategory ? 'Contoh: pelatihan masyarakat, pendampingan UMKM, sosialisasi program, pemberdayaan komunitas' : ($isHilirisasiCategory ? 'Contoh: pelaksanaan uji produk, validasi implementasi, dan pendampingan adopsi hasil hilirisasi' : 'Contoh: pengambilan data, observasi, wawancara, penyebaran angket'), ENT_QUOTES, 'UTF-8'); ?>" required></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-action-bar">
                            <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/' . urlencode($selectedCategory) . '?surat_kind=izin', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Kembali</a>
                            <button type="submit" name="submit_action" value="submit" class="btn btn-primary-main"><?= htmlspecialchars($isHilirisasiCategory ? 'Ajukan Surat Izin Pelaksanaan Hilirisasi' : 'Ajukan Surat Izin', ENT_QUOTES, 'UTF-8'); ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php elseif ($selectedType === 'tugas' && in_array($selectedCategory, ['penelitian', 'pengabdian', 'hilirisasi'], true)): ?>
            <?php
            $inlineMode = true;
            $taskFormView = match ($selectedCategory) {
                'pengabdian' => '/task_services_form.php',
                'hilirisasi' => '/task_hilirisasi_form.php',
                default => '/task_research_form.php',
            };
            require __DIR__ . $taskFormView;
            ?>
        <?php else: ?>
            <div class="submission-izin-panel">
                <div class="submission-izin-empty">
                    <h5 class="mb-2">Jenis surat belum dipilih</h5>
                    <p class="text-muted mb-3">Silakan pilih tab surat Kontrak, Izin, atau Tugas untuk melanjutkan pengajuan.</p>
                    <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/' . urlencode($selectedCategory) . '?surat_kind=izin', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary-main">Pilih Surat Izin</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
if (!window.customElements || !window.customElements.get('iconify-icon')) {
    const iconifyScript = document.createElement('script');
    iconifyScript.src = 'https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js';
    iconifyScript.async = true;
    document.head.appendChild(iconifyScript);
}

document.addEventListener('DOMContentLoaded', function () {
    function hasValue(selector) {
        const el = document.querySelector(selector);
        if (!el) return false;
        if (el.type === 'file') {
            return (el.files && el.files.length > 0);
        }
        return String(el.value || '').trim() !== '';
    }

    function initIzinStepper() {
    const stepItems = {
        1: document.querySelector('[data-step-item="1"]'),
        2: document.querySelector('[data-step-item="2"]'),
        3: document.querySelector('[data-step-item="3"]'),
        4: document.querySelector('[data-step-item="4"]'),
        5: document.querySelector('[data-step-item="5"]'),
    };

    if (!stepItems[1]) {
        return;
    }

    function setStepState(stepNumber, isDone, isActive) {
        const step = stepItems[stepNumber];
        if (!step) return;
        step.classList.toggle('is-done', !!isDone);
        step.classList.toggle('is-active', !!isActive);
    }

    function refreshStepper() {
        const step1Done = hasValue('#stepActivitySelect');
        const step2Done = step1Done;
        const step3Done = hasValue('#stepInstitution') && hasValue('#stepDestination') && hasValue('#stepDestinationPosition');
        const step4Done = hasValue('#stepAddress') && hasValue('#stepCity') && hasValue('#stepStartDate') && hasValue('#stepEndDate') && hasValue('#stepPurpose');
        const step5Done = hasValue('#stepFileProposal') || hasValue('#stepFileInstrumen') || hasValue('#stepFilePendukung');

        const firstNotDone = !step1Done ? 1 : (!step2Done ? 2 : (!step3Done ? 3 : (!step4Done ? 4 : (!step5Done ? 5 : 5))));

        setStepState(1, step1Done, firstNotDone === 1);
        setStepState(2, step2Done, firstNotDone === 2);
        setStepState(3, step3Done, firstNotDone === 3);
        setStepState(4, step4Done, firstNotDone === 4);
        setStepState(5, step5Done, firstNotDone === 5);
    }

    ['#stepActivitySelect', '#stepInstitution', '#stepDestination', '#stepDestinationPosition', '#stepAddress', '#stepCity', '#stepStartDate', '#stepEndDate', '#stepPurpose', '#stepFileProposal', '#stepFileInstrumen', '#stepFilePendukung']
        .forEach(function (selector) {
            const el = document.querySelector(selector);
            if (!el) return;
            const eventName = el.type === 'file' ? 'change' : 'input';
            el.addEventListener(eventName, refreshStepper);
            if (eventName !== 'change') {
                el.addEventListener('change', refreshStepper);
            }
        });

    refreshStepper();
    }

    function initTaskStepper() {
        const stepItems = {
            1: document.querySelector('[data-task-step-item="1"]'),
            2: document.querySelector('[data-task-step-item="2"]'),
            3: document.querySelector('[data-task-step-item="3"]'),
            4: document.querySelector('[data-task-step-item="4"]'),
        };

        if (!stepItems[1]) {
            return;
        }

        function setStepState(stepNumber, isDone, isActive) {
            const step = stepItems[stepNumber];
            if (!step) return;
            step.classList.toggle('is-done', !!isDone);
            step.classList.toggle('is-active', !!isActive);
        }

        function refreshStepper() {
            const step1Done = hasValue('#taskStepActivitySelect');
            const step2Done = step1Done;
            const step3Done = hasValue('#taskStepLocation')
                && hasValue('#taskStepStartDate')
                && hasValue('#taskStepEndDate')
                && hasValue('#taskStepDescription');

            const firstNotDone = !step1Done ? 1 : (!step2Done ? 2 : (!step3Done ? 3 : 4));

            setStepState(1, step1Done, firstNotDone === 1);
            setStepState(2, step2Done, firstNotDone === 2);
            setStepState(3, step3Done, firstNotDone === 3);
            // Lampiran tetap opsional: step 4 aktif tetapi tidak menjadi done (hijau).
            setStepState(4, false, firstNotDone === 4);
        }

        ['#taskStepActivitySelect', '#taskStepLocation', '#taskStepInstitution', '#taskStepStartDate', '#taskStepEndDate', '#taskStepDescription', '#taskStepFileProposal', '#taskStepFileInstrumen', '#taskStepFilePendukung']
            .forEach(function (selector) {
                const el = document.querySelector(selector);
                if (!el) return;
                const eventName = el.type === 'file' ? 'change' : 'input';
                el.addEventListener(eventName, refreshStepper);
                if (eventName !== 'change') {
                    el.addEventListener('change', refreshStepper);
                }
            });

        refreshStepper();
    }

    initIzinStepper();
    initTaskStepper();
});
</script>
