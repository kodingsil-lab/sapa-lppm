<?php
$app = require __DIR__ . '/../../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$formData = $formData ?? [];
$routes = $routes ?? [];
$validationErrors = $validationErrors ?? [];
$itemId = $itemId ?? null;
$activityType = strtolower((string) ($activityType ?? ''));
$isPenelitian = $activityType === 'penelitian';
$isPengabdian = $activityType === 'pengabdian';
$formMode = strtolower((string) ($formMode ?? (($itemId !== null && (int) $itemId > 0) ? 'edit' : 'create')));
$isEdit = (bool) ($isEdit ?? ($itemId !== null && (int) $itemId > 0));
$isDetail = (bool) ($isDetail ?? ($formMode === 'detail'));
$isMemberReadOnly = (bool) ($isMemberReadOnly ?? false);
$isCoreLocked = (bool) ($isCoreLocked ?? false);
$lockedReadonlyAttr = ($isDetail || $isCoreLocked) ? 'readonly' : '';
$lockedDisabledAttr = ($isDetail || $isCoreLocked) ? 'disabled' : '';
$revisionNoteFromLetter = trim((string) ($revisionNoteFromLetter ?? ''));
$fromLetterId = (int) ($fromLetterId ?? 0);
$resubmitContractMode = (bool) ($resubmitContractMode ?? false);
$routeToPath = static function (string $route, ?int $id = null) use ($basePath): string {
    $route = trim($route);
    if ($route === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    $map = [
        'data-penelitian' => 'data/penelitian',
        'data-penelitian-show' => $id !== null ? 'data/penelitian/' . $id : 'data/penelitian',
        'data-penelitian-edit' => $id !== null ? 'data/penelitian/' . $id . '/edit' : 'data/penelitian',
        'data-penelitian-store' => 'data/penelitian/simpan',
        'data-penelitian-update' => 'data/penelitian/perbarui',
        'data-pengabdian' => 'data/pengabdian',
        'data-pengabdian-show' => $id !== null ? 'data/pengabdian/' . $id : 'data/pengabdian',
        'data-pengabdian-edit' => $id !== null ? 'data/pengabdian/' . $id . '/edit' : 'data/pengabdian',
        'data-pengabdian-store' => 'data/pengabdian/simpan',
        'data-pengabdian-update' => 'data/pengabdian/perbarui',
        'data-hilirisasi' => 'data/hilirisasi',
        'data-hilirisasi-show' => $id !== null ? 'data/hilirisasi/' . $id : 'data/hilirisasi',
        'data-hilirisasi-edit' => $id !== null ? 'data/hilirisasi/' . $id . '/edit' : 'data/hilirisasi',
        'data-hilirisasi-store' => 'data/hilirisasi/simpan',
        'data-hilirisasi-update' => 'data/hilirisasi/perbarui',
    ];

    $path = $map[$route] ?? '';
    if ($path === '') {
        return $basePath . '/?route=' . rawurlencode($route) . ($id !== null ? '&id=' . $id : '');
    }

    return $basePath . '/' . ltrim($path, '/');
};
$masterOptions = is_array($masterOptions ?? null) ? $masterOptions : [];
$schemeOptions = is_array($masterOptions['scheme_names'] ?? null) ? array_values(array_filter(array_map('strval', (array) $masterOptions['scheme_names']), static fn (string $item): bool => trim($item) !== '')) : [];
$scopeMap = is_array($masterOptions['scopes_by_scheme'] ?? null) ? $masterOptions['scopes_by_scheme'] : [];
$fundingOptions = is_array($masterOptions['funding_names'] ?? null) ? array_values(array_filter(array_map('strval', (array) $masterOptions['funding_names']), static fn (string $item): bool => trim($item) !== '')) : [];
$fundingPresets = array_values(array_filter($fundingOptions, static fn (string $item): bool => strcasecmp($item, 'Lainnya') !== 0));
$targetLuaranRequiredOptions = is_array($masterOptions['target_luaran_required'] ?? null) ? $masterOptions['target_luaran_required'] : [];
$targetLuaranAdditionalOptions = is_array($masterOptions['target_luaran_additional'] ?? null) ? $masterOptions['target_luaran_additional'] : [];
$savedFunding = trim((string) ($formData['sumber_dana'] ?? ''));
$selectedFunding = '';
$customFunding = '';
if ($savedFunding !== '') {
    if (in_array($savedFunding, $fundingPresets, true)) {
        $selectedFunding = $savedFunding;
    } else {
        $selectedFunding = 'Lainnya';
        $customFunding = $savedFunding;
    }
}
$selectedTargetLuaranWajib = $formData['target_luaran_wajib'] ?? [];
if (!is_array($selectedTargetLuaranWajib)) {
    $selectedTargetLuaranWajib = trim((string) $selectedTargetLuaranWajib) === '' ? [] : [trim((string) $selectedTargetLuaranWajib)];
}
$selectedTargetLuaranTambahan = $formData['target_luaran_tambahan'] ?? [];
if (!is_array($selectedTargetLuaranTambahan)) {
    $selectedTargetLuaranTambahan = trim((string) $selectedTargetLuaranTambahan) === '' ? [] : [trim((string) $selectedTargetLuaranTambahan)];
}
$targetLuaranWajibRows = !empty($selectedTargetLuaranWajib) ? array_values($selectedTargetLuaranWajib) : [''];
$targetLuaranTambahanRows = !empty($selectedTargetLuaranTambahan) ? array_values($selectedTargetLuaranTambahan) : [''];
$hilirisasiLuaranWajib = [];
foreach ($targetLuaranRequiredOptions as $value => $label) {
    $hilirisasiLuaranWajib[] = [
        'code' => (string) $value,
        'label' => (string) $label,
    ];
}

$old = static function (string $key, string $default = '') use ($formData): string {
    return htmlspecialchars((string) ($formData[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$memberSuggestions = is_array($memberSuggestions ?? null) ? $memberSuggestions : [];
$memberSuggestionRows = [];
foreach ($memberSuggestions as $suggestion) {
    $name = trim((string) ($suggestion['name'] ?? ''));
    if ($name !== '') {
        $memberSuggestionRows[] = [
            'id' => (int) ($suggestion['id'] ?? 0),
            'name' => $name,
        ];
    }
}
$memberSuggestionRows = array_values(array_reduce($memberSuggestionRows, static function (array $carry, array $row): array {
    $key = strtolower(trim((string) ($row['name'] ?? '')));
    if ($key !== '' && !isset($carry[$key])) {
        $carry[$key] = $row;
    }
    return $carry;
}, []));
$memberEntries = [];
if (is_array($formData['_member_entries'] ?? null) && $formData['_member_entries'] !== []) {
    foreach ((array) $formData['_member_entries'] as $entry) {
        $memberEntries[] = [
            'name' => trim((string) ($entry['member_name'] ?? $entry['name'] ?? '')),
            'user_id' => (int) ($entry['member_user_id'] ?? $entry['user_id'] ?? 0),
        ];
    }
} else {
    $anggotaRows = preg_split('/\r\n|\r|\n/', trim((string) ($formData['anggota'] ?? ''))) ?: [];
    $anggotaRows = array_values(array_filter(array_map(
        static fn (string $item): string => trim((string) $item),
        $anggotaRows
    ), static fn (string $item): bool => $item !== ''));
    $anggotaMemberIds = is_array($formData['anggota_member_ids'] ?? null) ? $formData['anggota_member_ids'] : [];
    foreach ($anggotaRows as $index => $memberName) {
        $memberEntries[] = [
            'name' => $memberName,
            'user_id' => (int) ($anggotaMemberIds[$index] ?? 0),
        ];
    }
}
if ($memberEntries === []) {
    $memberEntries = [['name' => '', 'user_id' => 0]];
}
$renderMemberField = static function (string $groupId, array $memberRows, bool $isDetailMode): void {
    ?>
    <div class="member-picker-group" id="<?= htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="member-picker-list">
            <?php foreach ($memberRows as $index => $memberEntry): ?>
                <div class="member-picker-row">
                    <div class="member-picker-input-wrap">
                        <input
                            type="text"
                            name="anggota_items[]"
                            class="form-control modern-input member-picker-input"
                            value="<?= htmlspecialchars((string) ($memberEntry['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Ketik nama dosen anggota"
                            autocomplete="off"
                            <?= $isDetailMode ? 'readonly' : ''; ?>
                            <?= !$isDetailMode && $index === 0 ? 'required' : ''; ?>
                        >
                        <input type="hidden" name="anggota_member_ids[]" class="member-picker-user-id" value="<?= (int) ($memberEntry['user_id'] ?? 0); ?>">
                        <?php if (!$isDetailMode): ?>
                            <div class="member-picker-suggestions d-none"></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isDetailMode): ?>
                        <div class="member-picker-actions">
                            <button type="button" class="btn btn-outline-soft btn-sm member-picker-add js-add-member">+ Tambah</button>
                            <button type="button" class="btn btn-light-soft btn-sm member-picker-remove js-remove-member">Hapus</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <small class="helper-text">
            <?= $isDetailMode
                ? 'Daftar anggota kegiatan.'
                : 'Ketik nama anggota untuk melihat saran dosen dari sistem. Ketua tidak bisa dipilih sebagai anggota.'; ?>
        </small>
    </div>
    <?php
};

$computedLamaKegiatan = '1';
$mulaiDate = trim((string) ($formData['tanggal_mulai'] ?? ''));
$selesaiDate = trim((string) ($formData['tanggal_selesai'] ?? ''));
if ($mulaiDate !== '' && $selesaiDate !== '') {
    $mulaiTs = strtotime($mulaiDate);
    $selesaiTs = strtotime($selesaiDate);
    if ($mulaiTs !== false && $selesaiTs !== false && $selesaiTs >= $mulaiTs) {
        $tahunMulai = (int) date('Y', $mulaiTs);
        $tahunSelesai = (int) date('Y', $selesaiTs);
        $durasi = max(1, min(3, ($tahunSelesai - $tahunMulai) + 1));
        $computedLamaKegiatan = (string) $durasi;
    }
}

$actionRoute = $isEdit ? ($routes['update'] ?? $routes['store'] ?? '') : ($routes['store'] ?? '');
$saveLabel = $resubmitContractMode ? 'Ajukan Kontrak' : ($isEdit ? 'Simpan Perubahan' : 'Simpan Data');
?>

<style>
.lampiran-section {
    border: 1px solid #e6edf7;
    border-radius: 12px;
    padding: 12px 14px 8px;
    background: #fbfdff;
}
.lampiran-title {
    font-family: "Inter", "Segoe UI", Tahoma, sans-serif;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.1px;
}
.lampiran-helper {
    font-family: "Inter", "Segoe UI", Tahoma, sans-serif;
    font-size: 11px;
    color: #6b7a90;
}
.lampiran-field-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    min-height: 22px;
}
.lampiran-link {
    font-family: "Inter", "Segoe UI", Tahoma, sans-serif;
    font-size: 11px;
    font-weight: 500;
    color: var(--bs-primary);
    text-decoration: none;
}
.lampiran-preview-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 999px;
    background: rgba(13, 110, 253, 0.1);
    border: 1px solid rgba(13, 110, 253, 0.18);
}
.member-picker-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.member-picker-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.member-picker-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.member-picker-input-wrap {
    position: relative;
    flex: 1 1 auto;
}
.member-picker-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.member-picker-suggestions {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    max-height: 220px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #dbe6f4;
    border-radius: 12px;
    box-shadow: 0 12px 28px rgba(18, 60, 107, 0.12);
    z-index: 20;
    padding: 6px 0;
}
.member-picker-suggestion-item {
    display: block;
    width: 100%;
    padding: 10px 14px;
    border: 0;
    background: transparent;
    text-align: left;
    color: #123c6b;
    font-size: 14px;
    line-height: 1.35;
}
.member-picker-suggestion-item:hover,
.member-picker-suggestion-item.is-active {
    background: #eef5ff;
}
.member-picker-suggestion-empty {
    padding: 10px 14px;
    color: #7b8aa5;
    font-size: 13px;
}
@media (min-width: 768px) {
    .member-picker-row {
        flex-direction: row;
        align-items: center;
    }
    .member-picker-input {
        flex: 1 1 auto;
    }
    .member-picker-actions {
        flex: 0 0 auto;
    }
}
.lampiran-link:hover {
    color: var(--bs-primary);
    opacity: 0.9;
    text-decoration: none;
}
</style>

<div class="page-content activity-page activity-form-page">
    <div class="mb-3">
        <h1 class="page-title mb-1"><?= htmlspecialchars((string) ($pageTitle ?? 'Form Data'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-subtitle mb-0"><?= htmlspecialchars((string) ($pageSubtitle ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-warning form-alert"><?= htmlspecialchars((string) $errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success form-alert"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($isCoreLocked && !$isDetail): ?>
        <div class="alert alert-info form-alert">Data ini sudah dipakai pada pengajuan surat. Field inti (judul, skema, ruang lingkup, sumber dana, tahun) dikunci untuk menjaga konsistensi arsip.</div>
    <?php endif; ?>
    <?php if (!$isDetail && $revisionNoteFromLetter !== ''): ?>
        <div class="alert alert-warning form-alert">
            <strong>Catatan Perbaikan LPPM</strong><br>
            <span><?= htmlspecialchars($revisionNoteFromLetter, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($routeToPath((string) $actionRoute, $isEdit && (int) ($itemId ?? 0) > 0 ? (int) $itemId : null), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id" value="<?= (int) ($itemId ?? 0); ?>">
        <?php if ($fromLetterId > 0): ?>
            <input type="hidden" name="from_letter_id" value="<?= (int) $fromLetterId; ?>">
        <?php endif; ?>

        <div class="activity-card activity-form-card mb-3">
            <h3 class="section-title mb-3">Informasi <?= htmlspecialchars((string) ($activityLongLabel ?? 'Kegiatan'), ENT_QUOTES, 'UTF-8'); ?></h3>

            <?php if ($isPenelitian): ?>
                <div class="row g-3 activity-form-grid">
                    <div class="col-12">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control modern-input" value="<?= $old('judul'); ?>" placeholder="Contoh: Model Pembelajaran Adaptif Berbasis Diagnostik Numerasi" <?= $lockedReadonlyAttr; ?>>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Skema <span class="text-danger">*</span></label>
                            <select name="skema" id="penelitianSkema" class="form-select modern-input modern-select" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Skema --</option>
                                <?php foreach ($schemeOptions as $skema): ?>
                                    <option value="<?= htmlspecialchars($skema, ENT_QUOTES, 'UTF-8'); ?>" <?= $old('skema') === $skema ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($skema, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Ruang Lingkup <span class="text-danger">*</span></label>
                            <select name="ruang_lingkup" id="penelitianRuangLingkup" class="form-select modern-input modern-select" data-selected="<?= $old('ruang_lingkup'); ?>" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Ruang Lingkup --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Sumber Dana <span class="text-danger">*</span></label>
                            <select name="sumber_dana" id="penelitianSumberDana" class="form-select modern-input modern-select" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Sumber Dana --</option>
                                <?php foreach ($fundingOptions as $option): ?>
                                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedFunding === $option ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="funding-other-field mt-2" id="penelitianFundingOtherWrap">
                                <input type="text" id="penelitianSumberDanaLainnya" name="sumber_dana_lainnya" class="form-control modern-input" value="<?= htmlspecialchars($customFunding, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tuliskan sumber dana lainnya" <?= $lockedReadonlyAttr; ?>>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Total Dana Disetujui <span class="text-danger">*</span></label>
                            <input type="text" id="totalDanaDisetujui" name="total_dana_disetujui" class="form-control modern-input" value="<?= $old('total_dana_disetujui'); ?>" placeholder="Contoh: Rp 150.000.000" inputmode="numeric" autocomplete="off" <?= $isDetail ? 'readonly' : 'required'; ?>>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="text" name="tahun" class="form-control modern-input" value="<?= $old('tahun', date('Y')); ?>" placeholder="Contoh: 2026" <?= $lockedReadonlyAttr; ?>>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Mitra</label>
                            <input type="text" name="mitra" class="form-control modern-input" value="<?= $old('mitra'); ?>" placeholder="Contoh: SMP Negeri 5 Kupang" <?= $isDetail ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Ketua <span class="text-danger">*</span></label>
                            <input type="text" name="ketua" class="form-control modern-input" value="<?= $old('ketua'); ?>" readonly>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Anggota <span class="text-danger">*</span></label>
                            <?php $renderMemberField('penelitianAnggotaGroup', $memberEntries, $isDetail); ?>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Lama Kegiatan <span class="text-danger">*</span></label>
                            <select name="lama_kegiatan" class="form-select modern-input modern-select" required <?= $isDetail ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Lama Kegiatan --</option>
                                <option value="1" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '1' ? 'selected' : ''; ?>>1 Tahun</option>
                                <option value="2" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '2' ? 'selected' : ''; ?>>2 Tahun</option>
                                <option value="3" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '3' ? 'selected' : ''; ?>>3 Tahun</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="lokasi" class="form-control modern-input" value="<?= $old('lokasi'); ?>" placeholder="Contoh: Kota Kupang" <?= $isDetail ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Status Pelaksanaan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select modern-input modern-select" required <?= $isDetail ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Status Pelaksanaan --</option>
                                <option value="aktif" <?= strtolower((string) $old('status')) === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="selesai" <?= strtolower((string) $old('status')) === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                            <small class="helper-text">Pilih status pelaksanaan kegiatan penelitian saat ini.</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Target Luaran</label>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="target-luaran-group" id="penelitianTargetWajibGroup">
                                        <label class="form-label mb-1">Luaran Wajib <span class="text-danger">*</span></label>
                                        <div class="target-luaran-list">
                                            <?php foreach ($targetLuaranWajibRows as $index => $selectedValue): ?>
                                                <div class="target-luaran-row">
                                                    <select name="target_luaran_wajib[]" class="form-select modern-input modern-select" <?= !$isDetail && $index === 0 ? 'required' : ''; ?> <?= $isDetail ? 'disabled' : ''; ?>>
                                                        <option value="">-- Pilih Luaran Wajib --</option>
                                                        <?php foreach ($targetLuaranRequiredOptions as $value => $label): ?>
                                                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) $selectedValue === $value ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (!$isDetail): ?>
                                                        <div class="target-luaran-actions">
                                                            <button type="button" class="btn btn-outline-soft btn-sm target-luaran-add js-add-luaran">+ Tambah</button>
                                                            <button type="button" class="btn btn-light-soft btn-sm target-luaran-remove js-remove-luaran">Hapus</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="target-luaran-group" id="penelitianTargetTambahanGroup">
                                        <label class="form-label mb-1">Luaran Tambahan</label>
                                        <div class="target-luaran-list">
                                            <?php foreach ($targetLuaranTambahanRows as $selectedValue): ?>
                                                <div class="target-luaran-row">
                                                    <select name="target_luaran_tambahan[]" class="form-select modern-input modern-select" <?= $isDetail ? 'disabled' : ''; ?>>
                                                        <option value="">-- Pilih Luaran Tambahan (Opsional) --</option>
                                                        <?php foreach ($targetLuaranAdditionalOptions as $value => $label): ?>
                                                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) $selectedValue === $value ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (!$isDetail): ?>
                                                        <div class="target-luaran-actions">
                                                            <button type="button" class="btn btn-outline-soft btn-sm target-luaran-add js-add-luaran">+ Tambah</button>
                                                            <button type="button" class="btn btn-light-soft btn-sm target-luaran-remove js-remove-luaran">Hapus</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="helper-text">Tambahkan baris luaran sesuai kebutuhan persyaratan kegiatan.</small>
                        </div>
                    </div>
                </div>
            <?php elseif ($isPengabdian): ?>
                <div class="row g-3 activity-form-grid">
                    <div class="col-12">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control modern-input" value="<?= $old('judul'); ?>" placeholder="Contoh: Pemberdayaan Literasi Digital di Desa Binaan" <?= $lockedReadonlyAttr; ?>>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Skema <span class="text-danger">*</span></label>
                            <select name="skema" id="pengabdianSkema" class="form-select modern-input modern-select" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Skema --</option>
                                <?php foreach ($schemeOptions as $skema): ?>
                                    <option value="<?= htmlspecialchars($skema, ENT_QUOTES, 'UTF-8'); ?>" <?= $old('skema') === $skema ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($skema, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Ruang Lingkup <span class="text-danger">*</span></label>
                            <select name="ruang_lingkup" id="pengabdianRuangLingkup" class="form-select modern-input modern-select" data-selected="<?= $old('ruang_lingkup'); ?>" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Ruang Lingkup --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Sumber Dana <span class="text-danger">*</span></label>
                            <select name="sumber_dana" id="pengabdianSumberDana" class="form-select modern-input modern-select" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Sumber Dana --</option>
                                <?php foreach ($fundingOptions as $option): ?>
                                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedFunding === $option ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="funding-other-field mt-2" id="pengabdianFundingOtherWrap">
                                <input type="text" id="pengabdianSumberDanaLainnya" name="sumber_dana_lainnya" class="form-control modern-input" value="<?= htmlspecialchars($customFunding, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tuliskan sumber dana lainnya" <?= $lockedReadonlyAttr; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Total Dana Disetujui <span class="text-danger">*</span></label>
                            <input type="text" id="totalDanaDisetujui" name="total_dana_disetujui" class="form-control modern-input" value="<?= $old('total_dana_disetujui'); ?>" placeholder="Contoh: Rp 150.000.000" inputmode="numeric" autocomplete="off" <?= $isDetail ? 'readonly' : 'required'; ?>>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="text" name="tahun" class="form-control modern-input" value="<?= $old('tahun', date('Y')); ?>" placeholder="Contoh: 2026" <?= $lockedReadonlyAttr; ?>>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Mitra</label>
                            <input type="text" name="mitra" class="form-control modern-input" value="<?= $old('mitra'); ?>" placeholder="Contoh: Kelompok UMKM Binaan" <?= $isDetail ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Ketua <span class="text-danger">*</span></label>
                            <input type="text" name="ketua" class="form-control modern-input" value="<?= $old('ketua'); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Anggota <span class="text-danger">*</span></label>
                            <?php $renderMemberField('pengabdianAnggotaGroup', $memberEntries, $isDetail); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Lama Kegiatan <span class="text-danger">*</span></label>
                            <select name="lama_kegiatan" class="form-select modern-input modern-select" required <?= $isDetail ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Lama Kegiatan --</option>
                                <option value="1" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '1' ? 'selected' : ''; ?>>1 Tahun</option>
                                <option value="2" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '2' ? 'selected' : ''; ?>>2 Tahun</option>
                                <option value="3" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '3' ? 'selected' : ''; ?>>3 Tahun</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="lokasi" class="form-control modern-input" value="<?= $old('lokasi'); ?>" placeholder="Contoh: Kota Kupang" <?= $isDetail ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Status Pelaksanaan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select modern-input modern-select" required <?= $isDetail ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Status Pelaksanaan --</option>
                                <option value="aktif" <?= strtolower((string) $old('status')) === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="selesai" <?= strtolower((string) $old('status')) === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                            <small class="helper-text">Pilih status pelaksanaan kegiatan pengabdian saat ini.</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Target Luaran</label>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="target-luaran-group" id="pengabdianTargetWajibGroup">
                                        <label class="form-label mb-1">Luaran Wajib <span class="text-danger">*</span></label>
                                        <div class="target-luaran-list">
                                            <?php foreach ($targetLuaranWajibRows as $index => $selectedValue): ?>
                                                <div class="target-luaran-row">
                                                    <select name="target_luaran_wajib[]" class="form-select modern-input modern-select" <?= !$isDetail && $index === 0 ? 'required' : ''; ?> <?= $isDetail ? 'disabled' : ''; ?>>
                                                        <option value="">-- Pilih Luaran Wajib --</option>
                                                        <?php foreach ($targetLuaranRequiredOptions as $value => $label): ?>
                                                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) $selectedValue === $value ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (!$isDetail): ?>
                                                        <div class="target-luaran-actions">
                                                            <button type="button" class="btn btn-outline-soft btn-sm target-luaran-add js-add-luaran">+ Tambah</button>
                                                            <button type="button" class="btn btn-light-soft btn-sm target-luaran-remove js-remove-luaran">Hapus</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="target-luaran-group" id="pengabdianTargetTambahanGroup">
                                        <label class="form-label mb-1">Luaran Tambahan</label>
                                        <div class="target-luaran-list">
                                            <?php foreach ($targetLuaranTambahanRows as $selectedValue): ?>
                                                <div class="target-luaran-row">
                                                    <select name="target_luaran_tambahan[]" class="form-select modern-input modern-select" <?= $isDetail ? 'disabled' : ''; ?>>
                                                        <option value="">-- Pilih Luaran Tambahan (Opsional) --</option>
                                                        <?php foreach ($targetLuaranAdditionalOptions as $value => $label): ?>
                                                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) $selectedValue === $value ? 'selected' : ''; ?>>
                                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (!$isDetail): ?>
                                                        <div class="target-luaran-actions">
                                                            <button type="button" class="btn btn-outline-soft btn-sm target-luaran-add js-add-luaran">+ Tambah</button>
                                                            <button type="button" class="btn btn-light-soft btn-sm target-luaran-remove js-remove-luaran">Hapus</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="helper-text">Tambahkan baris luaran sesuai kebutuhan persyaratan kegiatan.</small>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3 activity-form-grid">
                    <div class="col-md-8">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control modern-input" value="<?= $old('judul'); ?>" placeholder="Contoh: Hilirisasi Produk Inovasi Pangan Lokal" <?= $lockedReadonlyAttr; ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <input type="text" name="tahun" class="form-control modern-input" value="<?= $old('tahun', date('Y')); ?>" placeholder="Contoh: 2026" <?= $lockedReadonlyAttr; ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Skema <span class="text-danger">*</span></label>
                            <select name="skema" id="hilirisasiSkema" class="form-select modern-input modern-select" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Skema --</option>
                                <?php foreach ($schemeOptions as $skema): ?>
                                    <option value="<?= htmlspecialchars($skema, ENT_QUOTES, 'UTF-8'); ?>" <?= $old('skema') === $skema ? 'selected' : ''; ?>><?= htmlspecialchars($skema, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Ruang Lingkup <span class="text-danger">*</span></label>
                            <select name="ruang_lingkup" id="hilirisasiRuangLingkup" class="form-select modern-input modern-select" data-selected="<?= $old('ruang_lingkup'); ?>" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Ruang Lingkup --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Sumber Dana <span class="text-danger">*</span></label>
                            <select name="sumber_dana" id="hilirisasiSumberDana" class="form-select modern-input modern-select" required <?= $lockedDisabledAttr; ?>>
                                <option value="">-- Pilih Sumber Dana --</option>
                                <?php foreach ($fundingOptions as $option): ?>
                                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $selectedFunding === $option ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="funding-other-field mt-2" id="hilirisasiFundingOtherWrap">
                                <input type="text" id="hilirisasiSumberDanaLainnya" name="sumber_dana_lainnya" class="form-control modern-input" value="<?= htmlspecialchars($customFunding, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tuliskan sumber dana lainnya" <?= $lockedReadonlyAttr; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Total Dana Disetujui <span class="text-danger">*</span></label>
                            <input type="text" id="totalDanaDisetujui" name="total_dana_disetujui" class="form-control modern-input" value="<?= $old('total_dana_disetujui'); ?>" placeholder="Contoh: Rp 150.000.000" inputmode="numeric" autocomplete="off" <?= $isDetail ? 'readonly' : 'required'; ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Ketua <span class="text-danger">*</span></label>
                            <input type="text" name="ketua" class="form-control modern-input" value="<?= $old('ketua'); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Anggota <span class="text-danger">*</span></label>
                            <?php $renderMemberField('hilirisasiAnggotaGroup', $memberEntries, $isDetail); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="lokasi" class="form-control modern-input" value="<?= $old('lokasi'); ?>" placeholder="Contoh: Kota Kupang atau Kabupaten Sleman" <?= $isDetail ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Mitra</label>
                            <input type="text" name="mitra" class="form-control modern-input" value="<?= $old('mitra'); ?>" placeholder="Contoh: PT Mitra Inovasi Nusantara" <?= $isDetail ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Lama Kegiatan <span class="text-danger">*</span></label>
                            <select name="lama_kegiatan" class="form-select modern-input modern-select" required <?= $isDetail ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Lama Kegiatan --</option>
                                <option value="1" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '1' ? 'selected' : ''; ?>>1 Tahun</option>
                                <option value="2" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '2' ? 'selected' : ''; ?>>2 Tahun</option>
                                <option value="3" <?= $old('lama_kegiatan', $computedLamaKegiatan) === '3' ? 'selected' : ''; ?>>3 Tahun</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Status Pelaksanaan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select modern-input modern-select" required <?= $isDetail ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Status Pelaksanaan --</option>
                                <option value="aktif" <?= strtolower((string) $old('status')) === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="selesai" <?= strtolower((string) $old('status')) === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                            <small class="helper-text">Pilih status pelaksanaan kegiatan hilirisasi saat ini.</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="profile-info-item form-field">
                            <label class="form-label">Luaran Wajib Hilirisasi <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <?php foreach ($hilirisasiLuaranWajib as $index => $label): ?>
                                    <div class="col-12">
                                        <input type="hidden" name="target_luaran_wajib[]" value="<?= htmlspecialchars((string) ($label['code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" class="form-control modern-input" value="<?= htmlspecialchars((string) ($label['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="helper-text">Luaran wajib Hilirisasi ditetapkan sistem dan tidak dapat diubah.</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="profile-info-item form-field mt-3 lampiran-section">
                <label class="form-label lampiran-title">Lampiran</label>
                <small class="helper-text d-block mb-2 lampiran-helper">Gunakan link Google Drive, OneDrive, Dropbox, atau penyimpanan cloud lainnya.</small>
                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label">Lampiran Proposal <span class="text-danger">*</span></label>
                        <input type="url" name="file_proposal" class="form-control modern-input" value="<?= $old('file_proposal'); ?>" placeholder="https://contoh.com/proposal.pdf" <?= $isDetail ? 'readonly' : 'required'; ?>>
                        <?php
                            $lampiranProposal = trim((string) ($formData['file_proposal'] ?? ''));
                        ?>
                        <div class="lampiran-field-actions">
                            <a
                                href="<?= htmlspecialchars($lampiranProposal !== '' ? $lampiranProposal : '#', ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="lampiran-link lampiran-preview-badge <?= $lampiranProposal !== '' ? '' : 'd-none'; ?>"
                                data-lampiran-preview-for="file_proposal"
                            >Lampiran Proposal</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lampiran Instrumen</label>
                        <input type="url" name="file_instrumen" class="form-control modern-input" value="<?= $old('file_instrumen'); ?>" placeholder="https://contoh.com/instrumen.pdf" <?= $isDetail ? 'readonly' : ''; ?>>
                        <?php
                            $lampiranInstrumen = trim((string) ($formData['file_instrumen'] ?? ''));
                        ?>
                        <div class="lampiran-field-actions">
                            <a
                                href="<?= htmlspecialchars($lampiranInstrumen !== '' ? $lampiranInstrumen : '#', ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="lampiran-link lampiran-preview-badge <?= $lampiranInstrumen !== '' ? '' : 'd-none'; ?>"
                                data-lampiran-preview-for="file_instrumen"
                            >Lampiran Instrumen</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lampiran Lainnya</label>
                        <input type="url" name="file_pendukung_lain" class="form-control modern-input" value="<?= $old('file_pendukung_lain'); ?>" placeholder="https://contoh.com/pendukung.pdf" <?= $isDetail ? 'readonly' : ''; ?>>
                        <?php
                            $lampiranPendukung = trim((string) ($formData['file_pendukung_lain'] ?? ''));
                        ?>
                        <div class="lampiran-field-actions">
                            <a
                                href="<?= htmlspecialchars($lampiranPendukung !== '' ? $lampiranPendukung : '#', ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="lampiran-link lampiran-preview-badge <?= $lampiranPendukung !== '' ? '' : 'd-none'; ?>"
                                data-lampiran-preview-for="file_pendukung_lain"
                            >Lampiran Pendukung Lainnya</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($validationErrors)): ?>
                <div class="mt-3">
                    <?php foreach ($validationErrors as $error): ?>
                        <div class="field-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-action-bar">
            <a href="<?= htmlspecialchars($routeToPath((string) ($routes['index'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" class="btn <?= $isDetail ? 'btn-primary-main' : 'btn-light-soft'; ?>">Kembali</a>
            <?php if ($isDetail && !$isMemberReadOnly): ?>
                <a href="<?= htmlspecialchars($routeToPath((string) ($routes['edit'] ?? ''), (int) ($itemId ?? 0)), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-soft">Edit Data</a>
            <?php elseif (!$isDetail): ?>
                <button type="submit" class="btn btn-primary-main"><?= htmlspecialchars($saveLabel, ENT_QUOTES, 'UTF-8'); ?></button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDetailMode = <?= $isDetail ? 'true' : 'false'; ?>;
    const isCoreLocked = <?= $isCoreLocked ? 'true' : 'false'; ?>;
    const memberSuggestions = <?= json_encode(array_values($memberSuggestionRows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const totalDanaInput = document.getElementById('totalDanaDisetujui');

    function setupFundingOther(selectId, wrapperId, inputId) {
        const selectEl = document.getElementById(selectId);
        const wrapperEl = document.getElementById(wrapperId);
        const inputEl = document.getElementById(inputId);
        if (!selectEl || !wrapperEl || !inputEl) {
            return;
        }

        function syncFundingOther() {
            const isOther = String(selectEl.value || '') === 'Lainnya';
            wrapperEl.style.display = isOther ? 'block' : 'none';
            if (!isDetailMode && !isCoreLocked) {
                inputEl.required = isOther;
            }
        }

        if (!isDetailMode && !isCoreLocked) {
            selectEl.addEventListener('change', syncFundingOther);
        }

        syncFundingOther();
    }

    function formatRupiah(value) {
        const digits = String(value || '').replace(/\D/g, '');
        if (digits === '') {
            return '';
        }
        const grouped = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return 'Rp ' + grouped;
    }

    if (totalDanaInput) {
        totalDanaInput.value = formatRupiah(totalDanaInput.value);
        if (!isDetailMode) {
            totalDanaInput.addEventListener('input', function () {
                totalDanaInput.value = formatRupiah(totalDanaInput.value);
            });
        }
    }

    setupFundingOther('penelitianSumberDana', 'penelitianFundingOtherWrap', 'penelitianSumberDanaLainnya');
    setupFundingOther('pengabdianSumberDana', 'pengabdianFundingOtherWrap', 'pengabdianSumberDanaLainnya');
    setupFundingOther('hilirisasiSumberDana', 'hilirisasiFundingOtherWrap', 'hilirisasiSumberDanaLainnya');

    function setupLampiranPreviewAndValidation() {
        const fields = ['file_proposal', 'file_instrumen', 'file_pendukung_lain'];

        fields.forEach(function (fieldName) {
            const input = document.querySelector('input[name="' + fieldName + '"]');
            const previewEl = document.querySelector('[data-lampiran-preview-for="' + fieldName + '"]');
            if (!input || !previewEl) {
                return;
            }

            function syncView() {
                const value = String(input.value || '').trim();

                if (value === '') {
                    previewEl.classList.add('d-none');
                    previewEl.setAttribute('href', '#');
                    return;
                }

                previewEl.setAttribute('href', value);
                previewEl.classList.remove('d-none');
            }

            syncView();
            if (!isDetailMode) {
                input.addEventListener('input', syncView);
                input.addEventListener('change', syncView);
            }
        });
    }

    setupLampiranPreviewAndValidation();

    function setupScopeDependency(skemaId, ruangId, scopeMap) {
        const skemaSelect = document.getElementById(skemaId);
        const ruangSelect = document.getElementById(ruangId);
        if (!skemaSelect || !ruangSelect) {
            return;
        }

        let selectedValue = ruangSelect.getAttribute('data-selected') || '';

        function renderScopes() {
            const skema = skemaSelect.value;
            const options = scopeMap[skema] || [];

            ruangSelect.innerHTML = '<option value="">-- Pilih Ruang Lingkup --</option>';
            options.forEach(function (item) {
                const opt = document.createElement('option');
                opt.value = item;
                opt.textContent = item;
                if (selectedValue !== '' && selectedValue === item) {
                    opt.selected = true;
                }
                ruangSelect.appendChild(opt);
            });
        }

        if (!isDetailMode && !isCoreLocked) {
            skemaSelect.addEventListener('change', function () {
                ruangSelect.setAttribute('data-selected', '');
                selectedValue = '';
                renderScopes();
            });
        }

        renderScopes();
    }

    const scopeOptionsMap = <?= json_encode($scopeMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    setupScopeDependency('penelitianSkema', 'penelitianRuangLingkup', scopeOptionsMap);
    setupScopeDependency('pengabdianSkema', 'pengabdianRuangLingkup', scopeOptionsMap);
    setupScopeDependency('hilirisasiSkema', 'hilirisasiRuangLingkup', scopeOptionsMap);

    function setupDynamicLuaran(groupId, isWajibGroup) {
        const group = document.getElementById(groupId);
        if (!group) {
            return;
        }

        const list = group.querySelector('.target-luaran-list');
        if (!list) {
            return;
        }

        function refreshState() {
            const rows = list.querySelectorAll('.target-luaran-row');
            rows.forEach(function (row, index) {
                const removeBtn = row.querySelector('.js-remove-luaran');
                const selectEl = row.querySelector('select');

                if (selectEl && isWajibGroup) {
                    selectEl.required = index === 0;
                }

                if (removeBtn) {
                    removeBtn.style.display = index === 0 ? 'none' : 'inline-flex';
                    removeBtn.disabled = rows.length <= 1;
                }

                const addBtn = row.querySelector('.js-add-luaran');
                if (addBtn) {
                    addBtn.style.display = index === 0 ? 'inline-flex' : 'none';
                }
            });
        }

        if (!isDetailMode) {
            list.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.classList.contains('js-add-luaran')) {
                    const template = list.querySelector('.target-luaran-row');
                    if (!template) {
                        return;
                    }
                    const clone = template.cloneNode(true);
                    const selectEl = clone.querySelector('select');
                    if (selectEl) {
                        selectEl.value = '';
                    }
                    list.appendChild(clone);
                    refreshState();
                    return;
                }

                if (!target.classList.contains('js-remove-luaran')) {
                    return;
                }
                const row = target.closest('.target-luaran-row');
                if (!row) {
                    return;
                }
                const rows = list.querySelectorAll('.target-luaran-row');
                if (rows.length <= 1) {
                    return;
                }
                row.remove();
                refreshState();
            });
        }

        refreshState();
    }

    function setupDynamicMembers(groupId) {
        const group = document.getElementById(groupId);
        if (!group) {
            return;
        }

        const list = group.querySelector('.member-picker-list');
        if (!list) {
            return;
        }

        const ketuaInput = document.querySelector('input[name="ketua"]');

        function closeSuggestions(exceptInput) {
            list.querySelectorAll('.member-picker-input').forEach(function (input) {
                if (exceptInput && input === exceptInput) {
                    return;
                }
                const wrap = input.closest('.member-picker-input-wrap');
                const box = wrap ? wrap.querySelector('.member-picker-suggestions') : null;
                if (box) {
                    box.classList.add('d-none');
                    box.innerHTML = '';
                }
            });
        }

        function renderSuggestions(input) {
            const wrap = input.closest('.member-picker-input-wrap');
            const box = wrap ? wrap.querySelector('.member-picker-suggestions') : null;
            if (!box) {
                return;
            }

            const query = normalizeValue(input.value);
            const ketuaValue = normalizeValue(ketuaInput ? ketuaInput.value : '');
            const usedNames = Array.from(list.querySelectorAll('.member-picker-input'))
                .filter(function (item) { return item !== input; })
                .map(function (item) { return normalizeValue(item.value); })
                .filter(function (value) { return value !== ''; });

            if (query === '') {
                box.classList.add('d-none');
                box.innerHTML = '';
                return;
            }

            const matches = memberSuggestions.filter(function (member) {
                const normalized = normalizeValue(member.name);
                if (normalized === '' || normalized === ketuaValue || usedNames.includes(normalized)) {
                    return false;
                }
                return normalized.indexOf(query) !== -1;
            }).slice(0, 8);

            if (matches.length === 0) {
                box.innerHTML = '<div class="member-picker-suggestion-empty">Tidak ada nama dosen yang cocok.</div>';
                box.classList.remove('d-none');
                return;
            }

            box.innerHTML = matches.map(function (member) {
                return '<button type="button" class="member-picker-suggestion-item" data-member-id="' + String(member.id) + '" data-member-name="' + String(member.name)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;') + '">' + String(member.name)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;') + '</button>';
            }).join('');
            box.classList.remove('d-none');
        }

        function refreshState() {
            const rows = list.querySelectorAll('.member-picker-row');
            rows.forEach(function (row, index) {
                const input = row.querySelector('.member-picker-input');
                const memberIdInput = row.querySelector('.member-picker-user-id');
                const addBtn = row.querySelector('.js-add-member');
                const removeBtn = row.querySelector('.js-remove-member');

                if (input) {
                    input.required = index === 0;
                }
                if (memberIdInput && !String(input ? input.value : '').trim()) {
                    memberIdInput.value = '0';
                }

                if (addBtn) {
                    addBtn.style.display = index === 0 ? 'inline-flex' : 'none';
                }

                if (removeBtn) {
                    removeBtn.style.display = index === 0 ? 'none' : 'inline-flex';
                    removeBtn.disabled = rows.length <= 1;
                }
            });
        }

        function normalizeValue(value) {
            return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
        }

        if (!isDetailMode) {
            list.addEventListener('click', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.classList.contains('js-add-member')) {
                    const template = list.querySelector('.member-picker-row');
                    if (!template) {
                        return;
                    }
                    const clone = template.cloneNode(true);
                    const input = clone.querySelector('.member-picker-input');
                    const memberIdInput = clone.querySelector('.member-picker-user-id');
                    const suggestionBox = clone.querySelector('.member-picker-suggestions');
                    if (input) {
                        input.value = '';
                    }
                    if (memberIdInput) {
                        memberIdInput.value = '0';
                    }
                    if (suggestionBox) {
                        suggestionBox.classList.add('d-none');
                        suggestionBox.innerHTML = '';
                    }
                    list.appendChild(clone);
                    refreshState();
                    if (input) {
                        input.focus();
                    }
                    return;
                }

                if (target.classList.contains('member-picker-suggestion-item')) {
                    const inputWrap = target.closest('.member-picker-input-wrap');
                    const input = inputWrap ? inputWrap.querySelector('.member-picker-input') : null;
                    const memberIdInput = inputWrap ? inputWrap.querySelector('.member-picker-user-id') : null;
                    if (input instanceof HTMLInputElement) {
                        input.value = target.getAttribute('data-member-name') || '';
                        if (memberIdInput instanceof HTMLInputElement) {
                            memberIdInput.value = target.getAttribute('data-member-id') || '0';
                        }
                        closeSuggestions();
                        input.focus();
                    }
                    return;
                }

                if (!target.classList.contains('js-remove-member')) {
                    return;
                }

                const row = target.closest('.member-picker-row');
                const rows = list.querySelectorAll('.member-picker-row');
                if (!row || rows.length <= 1) {
                    return;
                }
                row.remove();
                refreshState();
            });

            list.addEventListener('change', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLInputElement) || !target.classList.contains('member-picker-input')) {
                    return;
                }

                const currentValue = normalizeValue(target.value);
                const ketuaValue = normalizeValue(ketuaInput ? ketuaInput.value : '');
                const memberIdInput = target.parentElement ? target.parentElement.querySelector('.member-picker-user-id') : null;
                if (memberIdInput instanceof HTMLInputElement) {
                    const exactMatch = memberSuggestions.find(function (member) {
                        return normalizeValue(member.name) === currentValue;
                    });
                    memberIdInput.value = exactMatch ? String(exactMatch.id || 0) : '0';
                }

                if (currentValue !== '' && ketuaValue !== '' && currentValue === ketuaValue) {
                    alert('Ketua tidak boleh dimasukkan lagi sebagai anggota.');
                    target.value = '';
                    if (memberIdInput instanceof HTMLInputElement) {
                        memberIdInput.value = '0';
                    }
                    target.focus();
                    return;
                }

                const duplicate = Array.from(list.querySelectorAll('.member-picker-input')).find(function (input) {
                    return input !== target && normalizeValue(input.value) !== '' && normalizeValue(input.value) === currentValue;
                });
                if (currentValue !== '' && duplicate) {
                    alert('Nama anggota tidak boleh duplikat.');
                    target.value = '';
                    if (memberIdInput instanceof HTMLInputElement) {
                        memberIdInput.value = '0';
                    }
                    target.focus();
                }
                renderSuggestions(target);
            });

            list.addEventListener('input', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLInputElement) || !target.classList.contains('member-picker-input')) {
                    return;
                }
                const memberIdInput = target.parentElement ? target.parentElement.querySelector('.member-picker-user-id') : null;
                if (memberIdInput instanceof HTMLInputElement) {
                    memberIdInput.value = '0';
                }
                closeSuggestions(target);
                renderSuggestions(target);
            });

            list.addEventListener('focusin', function (event) {
                const target = event.target;
                if (!(target instanceof HTMLInputElement) || !target.classList.contains('member-picker-input')) {
                    return;
                }
                closeSuggestions(target);
                renderSuggestions(target);
            });

            document.addEventListener('click', function (event) {
                if (!group.contains(event.target)) {
                    closeSuggestions();
                }
            });
        }

        refreshState();
    }

    setupDynamicLuaran('penelitianTargetWajibGroup', true);
    setupDynamicLuaran('penelitianTargetTambahanGroup', false);
    setupDynamicLuaran('pengabdianTargetWajibGroup', true);
    setupDynamicLuaran('pengabdianTargetTambahanGroup', false);
    setupDynamicMembers('penelitianAnggotaGroup');
    setupDynamicMembers('pengabdianAnggotaGroup');
    setupDynamicMembers('hilirisasiAnggotaGroup');
});
</script>

