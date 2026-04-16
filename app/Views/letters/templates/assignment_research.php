<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Tugas Penelitian</title>
    <style>
        @page { size: A4 portrait; margin: 2cm 2.54cm; }
        body { font-family: "Bookman Old Style", serif; font-size: 12pt; color: #000; line-height: 1.35; }
        .header { width: 100%; margin: 0 0 10px 0; }
        .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .logo-cell { width: 16%; vertical-align: middle; text-align: left; padding: 0; }
        .logo { width: 74px; height: auto; display: block; margin: 0 auto; }
        .org-cell { width: 84%; text-align: center; vertical-align: top; padding: 0; }
        .h1 { font-size: 16.5pt; font-weight: 700; text-transform: uppercase; line-height: 1.03; margin: 0; white-space: nowrap; }
        .h2 { font-size: 11.5pt; font-weight: 700; text-transform: uppercase; line-height: 1.03; margin: 0; white-space: nowrap; }
        .h3 { font-size: 11.5pt; font-weight: 700; text-transform: uppercase; line-height: 1.04; margin: 2px 0 0 0; white-space: nowrap; }
        .h4 { font-size: 9.8pt; margin: 2px 0 0 0; white-space: nowrap; }
        .h5 { font-size: 8.8pt; margin: 0; white-space: nowrap; }
        .h6 { font-size: 8.6pt; margin: 0; white-space: nowrap; }
        .line { border-bottom: 2px solid #000; margin-top: 4px; }
        .center { text-align: center; }
        .title { font-size: 15pt; font-weight: 700; text-transform: uppercase; text-decoration: underline; margin: 8px 0 0; }
        .number { margin: 0 0 10px; font-weight: 700; }
        p { margin: 0 0 8px 0; text-align: justify; }
        .data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .data td { padding: 0; vertical-align: top; }
        .dlabel { width: 200px; }
        .colon { width: 12px; text-align: center; }
        .sub { font-weight: 700; text-align: center; margin: 10px 0 6px; text-transform: uppercase; }
        .task-table { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        .task-table th, .task-table td { border: 1px solid #000; padding: 4px 6px; font-size: 11.5pt; }
        .task-table th { text-align: center; font-weight: 700; }
        .task-table td:nth-child(1) { width: 8%; text-align: center; }
        .task-table td:nth-child(3) { width: 18%; }
        .sig { width: 300px; margin-left: auto; text-align: left; margin-top: 18px; }
        .sig img { max-width: 205px; max-height: 88px; margin: 4px 0; }
        .bold { font-weight: 700; }
        .sig-name { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <?php if (!empty($logoBase64)): ?>
                        <img src="<?= htmlspecialchars((string) $logoBase64, ENT_QUOTES, 'UTF-8'); ?>" class="logo" alt="Logo Universitas">
                    <?php endif; ?>
                </td>
                <td class="org-cell">
                    <div class="h1">UNIVERSITAS SAN PEDRO (UNISAP)</div>
                    <div class="h2">BILINGUAL MEDIUM UNIVERSITY</div>
                    <div class="h3">LEMBAGA PENELITIAN DAN PENGABDIAN KEPADA MASYARAKAT</div>
                    <div class="h4">Ijin Operasional: SK MENRISTEK-DIKTI No. 115/KPT/I/2016 - TERAKREDITASI</div>
                    <div class="h5">Jl. Ir. Soekarno No. 06, Kel. Fontein, Kec. Kota Raja, Kota Kupang - NTT</div>
                    <div class="h6">Telp./Fax. (0380) 822990, 822993; email: universitas.sanpedro@gmail.com; website: https://unisap.ac.id</div>
                </td>
            </tr>
        </table>
        <div class="line"></div>
    </div>

    <?php
    $activityType = strtolower(trim((string) ($letter['task_activity_type'] ?? '')));
    $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
    $isPengabdian = $activityType === 'pengabdian' || str_contains($subjectLower, 'pengabdian');

    $labelKegiatan = $isPengabdian ? 'Pengabdian' : 'Penelitian';
    $judulSuratTugas = $isPengabdian ? 'SURAT TUGAS PENGABDIAN' : 'SURAT TUGAS PENELITIAN';
    $labelKetua = $isPengabdian ? 'Ketua Pelaksana' : 'Ketua Peneliti';
    $labelSkema = $isPengabdian ? 'Skema Pengabdian' : 'Skema Penelitian';
    $labelLokasi = $isPengabdian ? 'Lokasi Pengabdian' : 'Lokasi Penelitian';

    $memberCandidates = preg_split('/[\r\n,;]+/', (string) ($taskMembersRaw ?? '')) ?: [];
    $memberCandidates = array_values(array_filter(array_map(static fn($value): string => trim((string) $value), $memberCandidates), static fn($value): bool => $value !== ''));
    $firstTableMembers = array_slice($memberCandidates, 0, 2);
    $remainingMembers = array_slice($memberCandidates, 2);
    $tableNo = 1;
    ?>

    <div class="center title"><?= htmlspecialchars($judulSuratTugas, ENT_QUOTES, 'UTF-8'); ?></div>
    <p class="center number">Nomor: <?= htmlspecialchars((string) ($letter['letter_number'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>

    <p>Kepala Lembaga Penelitian dan Pengabdian kepada Masyarakat (LPPM) Universitas San Pedro (UNISAP) memberikan tugas kepada dosen/peneliti yang namanya tercantum di bawah ini untuk melaksanakan kegiatan <?= htmlspecialchars(strtolower($labelKegiatan), ENT_QUOTES, 'UTF-8'); ?> sebagai bagian dari pelaksanaan Tri Dharma Perguruan Tinggi, dengan rincian sebagai berikut:</p>

    <table class="data">
        <tr><td class="dlabel">Judul <?= htmlspecialchars($labelKegiatan, ENT_QUOTES, 'UTF-8'); ?></td><td class="colon">:</td><td><?= htmlspecialchars((string) ($taskTitle ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel"><?= htmlspecialchars($labelSkema, ENT_QUOTES, 'UTF-8'); ?></td><td class="colon">:</td><td><?= htmlspecialchars((string) ($taskScheme ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel">Sumber Dana</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($taskFundingSource ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel">Tahun Pelaksanaan</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($taskYear ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel"><?= htmlspecialchars($labelLokasi, ENT_QUOTES, 'UTF-8'); ?></td><td class="colon">:</td><td><?= htmlspecialchars((string) ($taskLocation ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel">Waktu Pelaksanaan</td><td class="colon">:</td><td><?= htmlspecialchars((string) (($taskTanggalMulaiDisplay ?? '-') . ' s.d. ' . ($taskTanggalSelesaiDisplay ?? '-')), ENT_QUOTES, 'UTF-8'); ?></td></tr>
    </table>

    <div class="sub">DAFTAR PENUGASAN</div>
    <table class="task-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NUPTK</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $tableNo++; ?></td>
                <td><?= htmlspecialchars((string) ($taskLeaderName ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars((string) ($taskLeaderNuptk ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($labelKetua, ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
                <td><?= $tableNo++; ?></td>
                <td><?= htmlspecialchars((string) ($firstTableMembers[0] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>-</td>
                <td>Anggota</td>
            </tr>
            <tr>
                <td><?= $tableNo++; ?></td>
                <td><?= htmlspecialchars((string) ($firstTableMembers[1] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>-</td>
                <td>Anggota</td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($remainingMembers)): ?>
        <table class="task-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NUPTK</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($remainingMembers as $memberName): ?>
                    <tr>
                        <td><?= $tableNo++; ?></td>
                        <td><?= htmlspecialchars((string) $memberName, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>-</td>
                        <td>Anggota</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p>Dalam pelaksanaan tugas tersebut, yang bersangkutan diharapkan melaksanakan kegiatan <?= htmlspecialchars(strtolower($labelKegiatan), ENT_QUOTES, 'UTF-8'); ?> sesuai dengan proposal yang telah disetujui, menjunjung tinggi etika akademik dan integritas ilmiah, menjalin kerja sama yang baik dengan instansi/mitra terkait, dan menyusun laporan hasil <?= htmlspecialchars(strtolower($labelKegiatan), ENT_QUOTES, 'UTF-8'); ?> sesuai ketentuan yang berlaku.</p>
    <p>Surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab serta digunakan sebagaimana mestinya.</p>

    <div class="sig">
        <div><?= htmlspecialchars((string) $kotaSurat, ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars((string) $formattedCreatedDate, ENT_QUOTES, 'UTF-8'); ?></div>
        <div>Kepala LPPM,</div>
        <?php if (!empty($signatureDataUri)): ?>
            <img src="<?= htmlspecialchars((string) $signatureDataUri, ENT_QUOTES, 'UTF-8'); ?>" alt="Tanda Tangan">
        <?php else: ?>
            <br><br><br>
            <div>(tanda tangan)</div>
        <?php endif; ?>
        <div class="bold sig-name"><?= htmlspecialchars((string) $chairmanName, ENT_QUOTES, 'UTF-8'); ?></div>
        <div>NUPTK <?= htmlspecialchars((string) $chairmanIdentifier, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
</body>
</html>
