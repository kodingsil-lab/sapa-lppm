<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$pageTitle = $pageTitle ?? 'Detail Status Luaran';
$pageSubtitle = $pageSubtitle ?? '';
$activity = $activity ?? [];
$outputs = $outputs ?? [];
$activityType = (string) ($activityType ?? '');
$activityId = (int) ($activityId ?? 0);
$completedCount = (int) ($completedCount ?? 0);
$totalCount = (int) ($totalCount ?? 0);
$progressPercent = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;
$progressPercent = max(0, min(100, $progressPercent));

$formatDate = static function (?string $value): string {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '' || $raw === '0000-00-00') {
        return '-';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return $raw;
    }

    return date('d/m/Y', $ts);
};

$formatLamaKegiatan = static function ($value): string {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '' || !preg_match('/^[123]$/', $raw)) {
        return '-';
    }

    return $raw . ' Tahun';
};

$requiredOutputs = [];
$additionalOutputs = [];
foreach ($outputs as $item) {
    if ((int) ($item['is_required'] ?? 0) === 1) {
        $requiredOutputs[] = $item;
    } else {
        $additionalOutputs[] = $item;
    }
}
?>

<div class="page-content activity-page status-luaran-page">
    <div class="mb-3 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title mb-1"><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle mb-0"><?= htmlspecialchars((string) $pageSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="<?= htmlspecialchars($basePath . '/status-luaran', ENT_QUOTES, 'UTF-8'); ?>" class="btn status-detail-back-btn">Kembali</a>
    </div>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success form-alert"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-warning form-alert"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="activity-card status-card status-summary-card mb-3">
        <h3 class="section-title mb-3">Ringkasan Kegiatan</h3>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="profile-info-item h-100">
                    <div class="profile-info-label">Jenis Kegiatan</div>
                    <div class="profile-info-value"><?= htmlspecialchars((string) ($activityTypeLabel ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="profile-info-item h-100">
                    <div class="profile-info-label">Skema</div>
                    <div class="profile-info-value"><?= htmlspecialchars((string) ($activity['skema'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="profile-info-item h-100">
                    <div class="profile-info-label">Tahun</div>
                    <div class="profile-info-value"><?= htmlspecialchars((string) ($activity['tahun'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-12">
                <div class="profile-info-item h-100">
                    <div class="profile-info-label">Judul Kegiatan</div>
                    <div class="profile-info-value"><?= htmlspecialchars((string) ($activity['judul'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="profile-info-item h-100">
                    <div class="profile-info-label">Ketua</div>
                    <div class="profile-info-value"><?= htmlspecialchars((string) ($activity['ketua'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="profile-info-item h-100">
                    <div class="profile-info-label">Lokasi</div>
                    <div class="profile-info-value"><?= htmlspecialchars((string) ($activity['lokasi'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="profile-info-item h-100">
                    <div class="profile-info-label">Lama Kegiatan</div>
                    <div class="profile-info-value"><?= htmlspecialchars($formatLamaKegiatan($activity['lama_kegiatan'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="activity-card status-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h3 class="section-title mb-0">Progress Luaran</h3>
            <div class="status-progress-count"><?= (int) $completedCount; ?> / <?= (int) $totalCount; ?> selesai</div>
        </div>
        <div class="status-progress-bar status-progress-bar-lg">
            <span style="width: <?= (int) $progressPercent; ?>%;"></span>
        </div>
    </div>

    <div class="activity-card status-card">
        <h3 class="section-title mb-1">Daftar Luaran</h3>
        <p class="helper-text mt-0 mb-3">Dosen wajib mengisi tautan/link bukti untuk luaran yang telah dipenuhi.</p>

        <?php if (empty($outputs)): ?>
            <div class="empty-state-card text-center">
                <h5 class="mb-2">Target luaran belum dipilih.</h5>
                <p class="text-muted mb-0">Silakan pilih Target Luaran Wajib/Tambahan di data kegiatan agar muncul di halaman ini.</p>
            </div>
        <?php else: ?>
            <div class="output-section-box mb-3">
                <h4 class="output-section-title">Luaran Wajib</h4>
                <?php if (empty($requiredOutputs)): ?>
                    <div class="empty-state-card text-center py-3">
                        <p class="text-muted mb-0">Belum ada luaran wajib untuk kegiatan ini.</p>
                    </div>
                <?php else: ?>
                    <div class="output-list">
                        <?php foreach ($requiredOutputs as $output): ?>
                            <?php
                                $evidenceLink = trim((string) ($output['evidence_link'] ?? ''));
                                $evidenceFile = trim((string) ($output['evidence_file'] ?? ''));
                                $evidencePath = $evidenceLink !== '' ? $evidenceLink : $evidenceFile;
                                $hasEvidence = $evidencePath !== '';
                                $statusLabel = $hasEvidence ? 'Sudah Ada' : 'Belum Ada';
                                $statusClass = $hasEvidence ? 'status-pill-selesai' : 'status-pill-belum';
                            ?>
                            <div class="output-item">
                                <div class="output-item-head">
                                    <div>
                                        <div class="output-item-title"><?= htmlspecialchars((string) ($output['output_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="output-note"><?= htmlspecialchars((string) ($output['output_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap"><span class="status-pill status-table-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>

                                <form method="post" action="<?= htmlspecialchars($basePath . '/status-luaran/simpan', ENT_QUOTES, 'UTF-8'); ?>" class="row g-3 output-form">
                                    <input type="hidden" name="activity_type" value="<?= htmlspecialchars($activityType, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="activity_id" value="<?= (int) $activityId; ?>">
                                    <input type="hidden" name="output_type_id" value="<?= (int) ($output['output_type_id'] ?? 0); ?>">

                                    <div class="col-md-8">
                                        <label class="form-label">Link Bukti Luaran</label>
                                        <input type="url" name="evidence_link" value="<?= htmlspecialchars($evidenceLink, ENT_QUOTES, 'UTF-8'); ?>" class="form-control modern-input output-link-input" placeholder="https://...">
                                        <span class="helper-text">Isi link publik bukti luaran.</span>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Keterangan</label>
                                        <textarea name="evidence_notes" class="form-control modern-input modern-textarea" rows="2" placeholder="Tambahkan keterangan tambahan....."><?= htmlspecialchars((string) ($output['evidence_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>

                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <?php if ($evidencePath !== ''): ?>
                                            <a class="btn output-proof-btn" href="<?= htmlspecialchars(buildStatusEvidenceUrl($basePath, $activityType, $activityId, (int) ($output['output_type_id'] ?? 0), $evidencePath), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Buka Bukti Luaran</a>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary-main output-save-btn">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="output-section-box">
                <h4 class="output-section-title">Luaran Tambahan</h4>
                <?php if (empty($additionalOutputs)): ?>
                    <div class="empty-state-card text-center py-3">
                        <p class="text-muted mb-0">Tidak ada luaran tambahan untuk kegiatan ini.</p>
                    </div>
                <?php else: ?>
                    <div class="output-list">
                        <?php foreach ($additionalOutputs as $output): ?>
                            <?php
                                $evidenceLink = trim((string) ($output['evidence_link'] ?? ''));
                                $evidenceFile = trim((string) ($output['evidence_file'] ?? ''));
                                $evidencePath = $evidenceLink !== '' ? $evidenceLink : $evidenceFile;
                                $hasEvidence = $evidencePath !== '';
                                $statusLabel = $hasEvidence ? 'Sudah Ada' : 'Belum Ada';
                                $statusClass = $hasEvidence ? 'status-pill-selesai' : 'status-pill-belum';
                            ?>
                            <div class="output-item">
                                <div class="output-item-head">
                                    <div>
                                        <div class="output-item-title"><?= htmlspecialchars((string) ($output['output_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="output-note"><?= htmlspecialchars((string) ($output['output_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap"><span class="status-pill status-table-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>

                                <form method="post" action="<?= htmlspecialchars($basePath . '/status-luaran/simpan', ENT_QUOTES, 'UTF-8'); ?>" class="row g-3 output-form">
                                    <input type="hidden" name="activity_type" value="<?= htmlspecialchars($activityType, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="activity_id" value="<?= (int) $activityId; ?>">
                                    <input type="hidden" name="output_type_id" value="<?= (int) ($output['output_type_id'] ?? 0); ?>">

                                    <div class="col-md-8">
                                        <label class="form-label">Link Bukti Luaran</label>
                                        <input type="url" name="evidence_link" value="<?= htmlspecialchars($evidenceLink, ENT_QUOTES, 'UTF-8'); ?>" class="form-control modern-input output-link-input" placeholder="https://...">
                                        <span class="helper-text">Isi link publik bukti luaran.</span>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Keterangan</label>
                                        <textarea name="evidence_notes" class="form-control modern-input modern-textarea" rows="2" placeholder="Tambahkan keterangan tambahan....."><?= htmlspecialchars((string) ($output['evidence_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>

                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <?php if ($evidencePath !== ''): ?>
                                            <a class="btn output-proof-btn" href="<?= htmlspecialchars(buildStatusEvidenceUrl($basePath, $activityType, $activityId, (int) ($output['output_type_id'] ?? 0), $evidencePath), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Buka Bukti Luaran</a>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary-main output-save-btn">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>












