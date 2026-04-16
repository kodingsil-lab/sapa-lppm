<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Izin Penelitian</title>
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
        .meta { margin-top: 12px; margin-bottom: 10px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 0; vertical-align: top; }
        .label { width: 84px; }
        .colon { width: 12px; text-align: center; }
        p { margin: 0 0 8px 0; text-align: justify; }
        .left { text-align: left; }
        .recipient-end { margin-bottom: 16px; }
        .sub { font-weight: bold; margin-top: 8px; margin-bottom: 4px; }
        .data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .data td { padding: 0; vertical-align: top; }
        .dlabel { width: 190px; }
        .sig { width: 280px; margin-left: auto; text-align: left; margin-top: 18px; }
        .sig img { max-width: 205px; max-height: 88px; margin: 4px 0; }
        .bold { font-weight: bold; }
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
        $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
        $isPengabdian = str_contains($subjectLower, 'pengabdian');
        $labelKegiatan = $isPengabdian ? 'Pengabdian' : 'Penelitian';
        $perihalText = $isPengabdian
            ? 'Permohonan Izin Pelaksanaan Kegiatan Pengabdian Kepada Masyarakat'
            : 'Permohonan Izin Pelaksanaan Kegiatan Penelitian';
    ?>

    <div class="meta">
        <table>
            <tr><td class="label">Nomor</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($letter['letter_number'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td class="label">Lampiran</td><td class="colon">:</td><td><?= htmlspecialchars((string) $jumlahLampiran, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td class="label">Perihal</td><td class="colon">:</td><td><span class="bold"><?= htmlspecialchars($perihalText, ENT_QUOTES, 'UTF-8'); ?></span></td></tr>
        </table>
    </div>

    <?php
        $formatTanggalIndo = static function (string $rawDate): string {
            $value = trim($rawDate);
            if ($value === '' || $value === '-') {
                return '-';
            }

            $months = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            $dt = \DateTime::createFromFormat('Y-m-d', $value)
                ?: \DateTime::createFromFormat('d/m/Y', $value)
                ?: \DateTime::createFromFormat('d-m-Y', $value);

            if ($dt === false) {
                $ts = strtotime($value);
                if ($ts === false) {
                    return $value;
                }
                $dt = new \DateTime();
                $dt->setTimestamp($ts);
            }

            $day = (int) $dt->format('d');
            $month = (int) $dt->format('n');
            $year = $dt->format('Y');

            return $day . ' ' . ($months[$month] ?? $dt->format('F')) . ' ' . $year;
        };

        $instansiLokasiTujuan = trim((string) ($letter['institution'] ?? $namaInstansi ?? '-'));
        $jabatanTujuan = trim((string) ($letter['destination_position'] ?? '-'));
        $tujuanLengkap = trim($jabatanTujuan . ' ' . $instansiLokasiTujuan);
        if ($tujuanLengkap === '') {
            $tujuanLengkap = '-';
        }
        $tanggalMulaiDisplay = $formatTanggalIndo((string) $tanggalMulai);
        $tanggalSelesaiDisplay = $formatTanggalIndo((string) $tanggalSelesai);
    ?>
    <p class="left">Kepada Yth.</p>
    <p class="left bold"><?= htmlspecialchars($tujuanLengkap, ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="left recipient-end">di tempat</p>

    <p class="left">Dengan hormat,</p>
    <?php if ($isPengabdian): ?>
        <p>Sehubungan dengan pelaksanaan kegiatan pengabdian kepada masyarakat yang dilakukan oleh dosen Universitas San Pedro (UNISAP), bersama ini kami memohon izin kepada Bapak/Ibu agar kiranya dapat memberikan kesempatan kepada dosen kami yang bersangkutan untuk melaksanakan kegiatan pengabdian di instansi yang Bapak/Ibu pimpin. Adapun data kegiatan pengabdian dimaksud adalah sebagai berikut:</p>
    <?php else: ?>
        <p>Sehubungan dengan pelaksanaan kegiatan penelitian dosen, bersama ini kami memohon izin kepada Bapak/Ibu <?= htmlspecialchars($tujuanLengkap, ENT_QUOTES, 'UTF-8'); ?> agar dapat memberikan kesempatan kepada dosen kami untuk melaksanakan penelitian di instansi yang Bapak/Ibu pimpin. Adapun data peneliti dan kegiatan penelitian dimaksud adalah sebagai berikut:</p>
    <?php endif; ?>

    <table class="data">
        <tr><td class="dlabel"><?= htmlspecialchars($isPengabdian ? 'Nama Ketua Pelaksana' : 'Nama Ketua Peneliti', ENT_QUOTES, 'UTF-8'); ?></td><td class="colon">:</td><td><?= htmlspecialchars((string) ($letter['researcher_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel">Program Studi</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($letter['research_unit'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel"><?= htmlspecialchars($isPengabdian ? 'Judul Kegiatan Pengabdian' : 'Judul Penelitian', ENT_QUOTES, 'UTF-8'); ?></td><td class="colon">:</td><td><?= htmlspecialchars((string) ($letter['research_title'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <?php if ($isPengabdian): ?>
            <tr><td class="dlabel">Skema Pengabdian</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($letter['research_scheme'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><td class="dlabel">Tahun</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($letter['research_year'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <?php endif; ?>
        <tr><td class="dlabel">Waktu Pelaksanaan</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($tanggalMulaiDisplay . ' s.d. ' . $tanggalSelesaiDisplay), ENT_QUOTES, 'UTF-8'); ?></td></tr>
        <tr><td class="dlabel">Keperluan</td><td class="colon">:</td><td><?= htmlspecialchars((string) ($letter['research_purpose'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
    </table>

    <p>Sehubungan dengan hal tersebut, besar harapan kami kiranya Bapak/Ibu dapat memberikan izin serta dukungan demi kelancaran pelaksanaan kegiatan <?= htmlspecialchars(strtolower($labelKegiatan), ENT_QUOTES, 'UTF-8'); ?> tersebut.</p>
    <p>Demikian permohonan ini kami sampaikan. Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.</p>

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
