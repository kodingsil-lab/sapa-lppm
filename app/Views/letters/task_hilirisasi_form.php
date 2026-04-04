<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$formData = $formData ?? [];
$validationErrors = $validationErrors ?? [];
$activeActivityOptions = $activeActivityOptions ?? [];
$activityRow = $activityRow ?? null;
$selectedCategory = 'hilirisasi';
$activityType = 'hilirisasi';
$activityId = (int) ($activityId ?? ($formData['activity_id'] ?? $formData['penelitian_id'] ?? 0));
$letterId = (int) ($letterId ?? 0);

$label = [
    'activity' => 'Hilirisasi',
    'activity_list' => 'Hilirisasi Aktif',
    'title' => 'Judul Hilirisasi',
    'leader' => 'Ketua Pelaksana',
    'members' => 'Anggota Tim Pelaksana',
    'scheme' => 'Skema Hilirisasi',
    'year' => 'Tahun Hilirisasi',
    'task_title' => 'Surat Tugas Pelaksanaan Hilirisasi',
    'task_button' => 'Ajukan Surat Tugas Pelaksanaan Hilirisasi',
    'empty' => 'Pilih data hilirisasi aktif terlebih dahulu untuk melanjutkan pengajuan surat tugas pelaksanaan hilirisasi.',
    'location' => 'Lokasi Pelaksanaan Hilirisasi',
    'institution' => 'Instansi / Mitra Tujuan',
    'description' => 'Deskripsi Kegiatan Hilirisasi',
    'description_ph' => 'Contoh: Menjelaskan tahapan pelaksanaan hilirisasi mulai dari uji coba, validasi, implementasi, hingga evaluasi hasil.',
    'institution_ph' => 'Contoh: Laboratorium Mitra / Instansi Implementasi',
];

$old = static function (string $key, string $default = '') use ($formData): string {
    return htmlspecialchars((string) ($formData[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};

$fieldError = static function (string $key) use ($validationErrors): string {
    return isset($validationErrors[$key])
        ? '<div class="field-error">' . htmlspecialchars((string) $validationErrors[$key], ENT_QUOTES, 'UTF-8') . '</div>'
        : '';
};
?>

<div class="submission-izin-layout">
    <div class="submission-stepper mb-3" aria-label="Alur pengajuan surat tugas">
        <div class="submission-stepper-item is-active" data-task-step-item="1">
            <span class="submission-stepper-index">1</span>
            <span class="submission-stepper-text">Pilih Kegiatan</span>
        </div>
        <div class="submission-stepper-item" data-task-step-item="2">
            <span class="submission-stepper-index">2</span>
            <span class="submission-stepper-text">Data Pelaksana</span>
        </div>
        <div class="submission-stepper-item" data-task-step-item="3">
            <span class="submission-stepper-index">3</span>
            <span class="submission-stepper-text">Deskripsi Kegiatan</span>
        </div>
        <div class="submission-stepper-item" data-task-step-item="4">
            <span class="submission-stepper-index">4</span>
            <span class="submission-stepper-text">Konfirmasi & Ajukan</span>
        </div>
    </div>

    <div class="submission-izin-card mb-3">
        <h6 class="submission-izin-card-title">Step 1 - Pilih <?= htmlspecialchars($label['activity_list'], ENT_QUOTES, 'UTF-8'); ?></h6>
        <form method="get" class="row g-2 align-items-end submission-step1-row">
            <input type="hidden" name="route" value="ajukan-surat">
            <input type="hidden" name="activity_type" value="<?= htmlspecialchars($activityType, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="surat_kind" value="tugas">
            <?php if ($letterId > 0): ?>
                <input type="hidden" name="letter_id" value="<?= $letterId; ?>">
            <?php endif; ?>
            <div class="col-md-10">
                <label class="form-label">Pilih <?= htmlspecialchars($label['activity'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                <select name="activity_id" id="taskStepActivitySelect" class="form-select modern-input" onchange="this.form.submit()">
                    <option value="">-- Pilih Data --</option>
                    <?php foreach ($activeActivityOptions as $opt): ?>
                        <?php $optId = (int) ($opt['id'] ?? 0); ?>
                        <?php
                        $taskOptTitle = (string) (($opt['judul'] ?? '-') . ' - ' . ($opt['tahun'] ?? '-'));
                        $taskOptLabel = $taskOptTitle;
                        $taskMaxLength = 120;
                        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                            if (mb_strlen($taskOptLabel, 'UTF-8') > $taskMaxLength) {
                                $taskOptLabel = rtrim((string) mb_substr($taskOptLabel, 0, $taskMaxLength - 3, 'UTF-8')) . '...';
                            }
                        } elseif (strlen($taskOptLabel) > $taskMaxLength) {
                            $taskOptLabel = rtrim(substr($taskOptLabel, 0, $taskMaxLength - 3)) . '...';
                        }
                        ?>
                        <option value="<?= $optId; ?>" title="<?= htmlspecialchars($taskOptTitle, ENT_QUOTES, 'UTF-8'); ?>" <?= $optId === $activityId ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($taskOptLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $fieldError('penelitian_id'); ?>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/' . urlencode($activityType) . '?surat_kind=tugas', ENT_QUOTES, 'UTF-8'); ?>" class="btn submission-reset-btn w-100">Reset Pilihan</a>
            </div>
        </form>
    </div>

    <?php if ($activityRow === null): ?>
        <div class="submission-izin-empty">
            <?= htmlspecialchars($label['empty'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php else: ?>
        <div class="submission-izin-preview mb-3">
            <h6 class="submission-izin-preview-title">Step 2 - Data Pelaksana (Auto)</h6>
            <div class="submission-izin-preview-grid">
                <div class="submission-izin-preview-item"><span><?= htmlspecialchars($label['title'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?= htmlspecialchars((string) ($activityRow['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="submission-izin-preview-item"><span><?= htmlspecialchars($label['leader'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?= htmlspecialchars((string) ($activityRow['ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="submission-izin-preview-item"><span><?= htmlspecialchars($label['scheme'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?= htmlspecialchars((string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? ($activityRow['ruang_lingkup'] ?? '') : ($activityRow['skema'] ?? '-')), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="submission-izin-preview-item"><span><?= htmlspecialchars($label['year'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?= htmlspecialchars((string) ($activityRow['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="submission-izin-preview-item"><span>Fakultas</span><strong><?= htmlspecialchars((string) ($formData['faculty'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="submission-izin-preview-item"><span>Program Studi</span><strong><?= htmlspecialchars((string) ($formData['unit'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="submission-izin-preview-item submission-izin-preview-item-wide"><span><?= htmlspecialchars($label['members'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?= nl2br(htmlspecialchars((string) ($activityRow['anggota'] ?? '-'), ENT_QUOTES, 'UTF-8')); ?></strong></div>
            </div>
        </div>

        <form method="post" action="<?= htmlspecialchars($basePath . '/letters/store', ENT_QUOTES, 'UTF-8'); ?>" class="submission-izin-form">
            <input type="hidden" name="form_variant" value="task_research">
            <input type="hidden" name="activity_type" value="<?= htmlspecialchars($activityType, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="letter_id" value="<?= $letterId; ?>">
            <input type="hidden" name="activity_id" value="<?= (int) ($activityRow['id'] ?? 0); ?>">
            <input type="hidden" name="penelitian_id" value="<?= (int) ($activityRow['id'] ?? 0); ?>">
            <input type="hidden" name="name" value="<?= $old('name'); ?>">
            <input type="hidden" name="nidn" value="<?= $old('nidn'); ?>">
            <input type="hidden" name="faculty" value="<?= $old('faculty'); ?>">
            <input type="hidden" name="unit" value="<?= $old('unit'); ?>">
            <input type="hidden" name="applicant_email" value="<?= $old('applicant_email'); ?>">
            <input type="hidden" name="phone" value="<?= $old('phone'); ?>">
            <input type="hidden" name="existing_file_proposal" value="<?= $old('file_proposal', (string) ($activityRow['file_proposal'] ?? '')); ?>">
            <input type="hidden" name="existing_file_instrumen" value="<?= $old('file_instrumen', (string) ($activityRow['file_instrumen'] ?? '')); ?>">
            <input type="hidden" name="existing_file_pendukung_lain" value="<?= $old('file_pendukung_lain', (string) ($activityRow['file_pendukung_lain'] ?? '')); ?>">
            <input type="hidden" name="file_proposal" value="<?= $old('file_proposal', (string) ($activityRow['file_proposal'] ?? '')); ?>">
            <input type="hidden" name="file_instrumen" value="<?= $old('file_instrumen', (string) ($activityRow['file_instrumen'] ?? '')); ?>">
            <input type="hidden" name="file_pendukung_lain" value="<?= $old('file_pendukung_lain', (string) ($activityRow['file_pendukung_lain'] ?? '')); ?>">

            <div class="submission-izin-card mb-3">
                <h6 class="submission-izin-card-title">Step 3 - Detail Penugasan</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars($label['location'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="lokasi_penugasan" id="taskStepLocation" class="form-control modern-input" value="<?= $old('lokasi_penugasan'); ?>" placeholder="Contoh: Kabupaten Kupang / Sekolah Mitra / Desa Binaan">
                        <?= $fieldError('lokasi_penugasan'); ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= htmlspecialchars($label['institution'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="instansi_tujuan" id="taskStepInstitution" class="form-control modern-input" value="<?= $old('instansi_tujuan'); ?>" placeholder="<?= htmlspecialchars($label['institution_ph'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?= $fieldError('instansi_tujuan'); ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_mulai" id="taskStepStartDate" class="form-control modern-input" value="<?= $old('tanggal_mulai'); ?>" placeholder="Pilih tanggal mulai">
                        <?= $fieldError('tanggal_mulai'); ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_selesai" id="taskStepEndDate" class="form-control modern-input" value="<?= $old('tanggal_selesai'); ?>" placeholder="Pilih tanggal selesai">
                        <?= $fieldError('tanggal_selesai'); ?>
                    </div>
                </div>
            </div>

            <div class="submission-izin-card mb-3">
                <h6 class="submission-izin-card-title">Step 3 - <?= htmlspecialchars($label['description'], ENT_QUOTES, 'UTF-8'); ?></h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label"><?= htmlspecialchars($label['description'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                        <textarea name="deskripsi_kegiatan" id="taskStepDescription" class="form-control modern-input modern-textarea" rows="5" placeholder="<?= htmlspecialchars($label['description_ph'], ENT_QUOTES, 'UTF-8'); ?>"><?= $old('deskripsi_kegiatan', (string) ($formData['uraian_tugas'] ?? '')); ?></textarea>
                        <?= $fieldError('uraian_tugas'); ?>
                    </div>
                </div>
            </div>

            <div class="form-action-bar">
                <a href="<?= htmlspecialchars($basePath . '/ajukan-surat/' . urlencode($activityType) . '?surat_kind=tugas', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light-soft">Kembali</a>
                <button type="submit" name="submit_action" value="submit" class="btn btn-primary-main"><?= htmlspecialchars($label['task_button'], ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>
