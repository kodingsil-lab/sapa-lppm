<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Kontrak</title>
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
        .title { font-size: 15pt; font-weight: 700; text-transform: uppercase; margin: 8px 0 0; }
        .number { margin: 0 0 10px; font-weight: 700; }
        .meta { margin-top: 10px; margin-bottom: 10px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 0; vertical-align: top; }
        .label { width: 84px; }
        .colon { width: 12px; text-align: center; }
        p { margin: 0 0 8px 0; text-align: justify; }
        .left { text-align: left; }
        .section-title { font-weight: 700; margin: 12px 0 6px; text-transform: uppercase; }
        .pasal {
            text-align: center;
            font-weight: 700;
            margin: 8px 0 1px;
            page-break-inside: avoid;
            break-inside: avoid-page;
            page-break-after: avoid;
            break-after: avoid-page;
        }
        .pasal-title {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 6px;
            page-break-inside: avoid;
            break-inside: avoid-page;
            page-break-before: avoid;
            break-before: avoid-page;
            page-break-after: avoid;
            break-after: avoid-page;
        }
        .pasal-block { page-break-inside: auto; break-inside: auto; }
        .pasal-intro-keep {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .pasal-head {
            page-break-inside: avoid;
            break-inside: avoid-page;
            page-break-after: avoid;
            break-after: avoid-page;
        }
        .pasal + .pasal-title + .ayat-block,
        .pasal + .pasal-title + .ayat-block-no-number,
        .pasal-head + .ayat-block,
        .pasal-head + .ayat-block-no-number {
            page-break-before: avoid;
            break-before: avoid-page;
        }
        .huruf-list { margin: 0; padding: 0; }
        .huruf-item { width: 100%; border-collapse: collapse; margin: 0 0 2px 0; }
        .huruf-item td { padding: 0; vertical-align: top; }
        .huruf-marker { width: 16px; }
        .huruf-content { text-align: justify; padding-left: 4px; }
        .angka-list { margin: 0; padding: 0; }
        .angka-item { width: 100%; border-collapse: collapse; margin: 0; }
        .angka-item td { padding: 0; vertical-align: top; }
        .angka-marker { width: 24px; }
        .angka-content { text-align: justify; padding-left: 4px; }
        .ayat { margin: 0 0 8px 0; text-align: justify; }
        .ayat table { width: 100%; border-collapse: collapse; }
        .ayat td { vertical-align: top; padding: 0; }
        .ayat-no { width: 22px; }
        .ayat-body { text-align: justify; padding-left: 8px; }
        .ayat tr { page-break-inside: auto; break-inside: auto; }
        .ayat-block { margin: 0 0 8px 0; orphans: 3; widows: 3; }
        .ayat-block:after { content: ""; display: block; clear: both; }
        .ayat-block .ayat-number {
            float: left;
            width: 22px;
            page-break-after: avoid;
            break-after: avoid-page;
        }
        .ayat-block .ayat-content {
            margin-left: 30px;
            text-align: justify;
            text-justify: inter-word;
            page-break-before: avoid;
            break-before: avoid-page;
        }
        .ayat-block-no-number { margin: 0 0 8px 0; orphans: 3; widows: 3; }
        .ayat-block-no-number .ayat-content {
            text-align: justify;
            text-justify: inter-word;
        }
        .ayat-inline-block {
            margin: 0 0 8px 0;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .ayat-inline-block table { width: 100%; border-collapse: collapse; }
        .ayat-inline-block td { padding: 0; vertical-align: top; }
        .ayat-inline-block .ayat-inline-number { width: 22px; }
        .ayat-inline-block .ayat-inline-content {
            padding-left: 8px;
            text-align: justify;
            text-justify: inter-word;
        }
        .rincian-table { margin-top: 4px; border-collapse: collapse; }
        .rincian-table td { padding: 0; vertical-align: top; text-align: left; }
        .rincian-label { width: 165px; }
        .rincian-colon { width: 14px; text-align: center; }
        .data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .data td { padding: 0; vertical-align: top; }
        .dlabel { width: 200px; }
        .sig-contract { margin-top: 22px; }
        .sig-contract-top { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sig-contract-top td { width: 50%; text-align: center; vertical-align: top; padding: 0; }
        .sig-contract-bottom { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .sig-contract-bottom td { text-align: center; vertical-align: top; padding: 0; }
        .sig-role { font-weight: 700; text-transform: uppercase; }
        .sig-gap { height: 78px; }
        .sig-name { font-weight: 700; text-decoration: underline; }
        .sig-meta { margin-top: 2px; }
        .sig-img { max-width: 150px; max-height: 60px; display: block; margin: 6px auto; }
        .bold { font-weight: 700; }
        .placeholder-box { border: 1px solid #000; padding: 10px 12px; margin-top: 8px; }
        .contract-opening { margin-top: 8px; margin-bottom: 18px; }
        .contract-opening p { text-align: center; margin: 0; }
        .contract-opening .line-1 { font-size: 14pt; font-weight: 700; text-transform: uppercase; }
        .contract-opening .line-2 { font-size: 14pt; font-weight: 700; text-transform: uppercase; }
        .contract-opening .line-3 { font-size: 14pt; font-weight: 700; text-transform: uppercase; margin-top: 2px; }
        .contract-opening .line-gap { height: 22px; }
        .contract-opening .line-party { font-size: 14pt; text-transform: uppercase; line-height: 1.28; }
        .party-table { width: 100%; border-collapse: collapse; margin: 12px 0 14px; }
        .party-table td { vertical-align: top; padding: 0; }
        .party-name { width: 40%; padding-right: 10px; }
        .party-colon { width: 12px; text-align: center; }
        .party-desc { text-align: justify; padding-left: 6px; }
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
    $contractTanggalMulaiDisplay = $contractTanggalMulaiDisplay ?? '-';
    $contractTanggalSelesaiDisplay = $contractTanggalSelesaiDisplay ?? '-';
    $nilaiKontrakRupiah = $nilaiKontrakRupiah ?? '-';
    $nilaiKontrakTerbilangUcfirst = $nilaiKontrakTerbilangUcfirst ?? '-';
    $danaTahap1Rupiah = $danaTahap1Rupiah ?? '-';
    $danaTahap2Rupiah = $danaTahap2Rupiah ?? '-';
    $danaTahap1TerbilangUcfirst = $danaTahap1TerbilangUcfirst ?? '-';
    $danaTahap2TerbilangUcfirst = $danaTahap2TerbilangUcfirst ?? '-';
    $nomorKontrakDikti = $nomorKontrakDikti ?? '-';
    $subjectLower = strtolower((string) ($letter['subject'] ?? ''));
    $isPengabdian = false;
    $jenisKegiatan = 'Penelitian';
    $judulSurat = 'SURAT KONTRAK PENELITIAN';
    $judulKontrak = 'KONTRAK PELAKSANAAN PROGRAM PENELITIAN';
    $judulKontrakInline = 'Kontrak Pelaksanaan Program Penelitian';
    $tahunAnggaran = (string) ($letter['research_year'] ?? date('Y'));
    $pihakPertama = 'KEPALA LEMBAGA PENELITIAN DAN PENGABDIAN KEPADA MASYARAKAT UNIVERSITAS SAN PEDRO';
    $pihakKedua = $isPengabdian ? 'KETUA PELAKSANA' : 'KETUA PENELITI';
    $labelKegiatan = $isPengabdian ? 'Pengabdian' : 'Penelitian';
    $labelKegiatanLower = $isPengabdian ? 'pengabdian' : 'penelitian';
    $kontrakNama = $isPengabdian ? 'Kontrak Pengabdian' : 'Kontrak Penelitian';
    $frasaProgram = $isPengabdian ? 'program pengabdian' : 'program penelitian';
    $frasaPelaksanaan = $isPengabdian ? 'pelaksanaan pengabdian' : 'pelaksanaan penelitian';
    $frasaSkema = $isPengabdian ? 'skema pengabdian' : 'skema penelitian';
    $frasaLuaran = $isPengabdian ? 'luaran pengabdian' : 'luaran penelitian';
    $frasaProposal = $isPengabdian ? 'proposal pengabdian' : 'proposal penelitian';
    $frasaKesanggupan = $isPengabdian ? 'pelaksanaan pengabdian' : 'pelaksanaan penelitian';
    $frasaCatatanHarian = $isPengabdian ? 'catatan harian pelaksanaan pengabdian' : 'catatan harian pelaksanaan penelitian';
    $frasaLaporanKemajuan = $isPengabdian ? 'laporan kemajuan pelaksanaan pengabdian' : 'laporan kemajuan pelaksanaan penelitian';
    $frasaLaporanAkhir = $isPengabdian ? 'laporan akhir pengabdian' : 'laporan akhir penelitian';
    $frasaLaporanAkhirPelaksanaan = $isPengabdian ? 'Laporan Akhir Pelaksanaan Pengabdian.' : 'Laporan Akhir Pelaksanaan Penelitian.';
    $frasaDana = $isPengabdian ? 'dana pengabdian' : 'dana penelitian';
    $frasaTimPelaksana = $isPengabdian ? 'tim pelaksana pengabdian' : 'tim pelaksana penelitian';
    $frasaPanduan = $isPengabdian ? 'panduan pengabdian' : 'panduan penelitian';
    $frasaJudul = $isPengabdian ? 'judul pengabdian' : 'judul penelitian';
    $frasaKegiatanIni = $isPengabdian ? 'kegiatan pengabdian ini' : 'kegiatan penelitian ini';
    $frasaHasil = $isPengabdian ? 'hasil pengabdian' : 'hasil penelitian';
    $frasaIntegritas = $isPengabdian ? 'kegiatan pengabdian yang dilaksanakan' : 'kegiatan penelitian yang dilaksanakan';
    $frasaEtika = $isPengabdian ? 'Pengabdian dilaksanakan sesuai dengan kerangka etika, hukum, dan profesionalitas serta kewajiban sesuai dengan ketentuan peraturan perundang-undangan.' : 'Penelitian dilakukan sesuai dengan kerangka etika, hukum, dan profesionalitas serta kewajiban sesuai dengan ketentuan peraturan perundang-undangan.';
    $frasaStandar = $isPengabdian ? 'Pengabdian dilaksanakan dengan menjunjung tinggi standar kualitas, ketelitian, dan integritas tertinggi dalam semua aspek pengabdian.' : 'Penelitian dilakukan dengan menjunjung tinggi standar ketelitian dan integritas tertinggi dalam semua aspek penelitian.';
    $frasaKontrakIni = $isPengabdian ? 'Kontrak Pengabdian ini' : 'Kontrak Penelitian ini';
    $frasaAmandemen = $isPengabdian ? 'maka akan dilakukan amandemen Kontrak Pengabdian.' : 'maka akan dilakukan amandemen Kontrak Penelitian.';
    $frasaProgramDiajukan = $isPengabdian ? 'program pengabdian' : 'program penelitian';

    $numberWords = [
        0 => 'Nol',
        1 => 'Satu',
        2 => 'Dua',
        3 => 'Tiga',
        4 => 'Empat',
        5 => 'Lima',
        6 => 'Enam',
        7 => 'Tujuh',
        8 => 'Delapan',
        9 => 'Sembilan',
        10 => 'Sepuluh',
        11 => 'Sebelas',
    ];

    $spellNumber = static function (int $value) use (&$spellNumber, $numberWords): string {
        if ($value < 12) {
            return $numberWords[$value];
        }
        if ($value < 20) {
            return $spellNumber($value - 10) . ' Belas';
        }
        if ($value < 100) {
            $tens = intdiv($value, 10);
            $rest = $value % 10;
            return trim($spellNumber($tens) . ' Puluh' . ($rest > 0 ? ' ' . $spellNumber($rest) : ''));
        }
        if ($value < 200) {
            return trim('Seratus' . ($value > 100 ? ' ' . $spellNumber($value - 100) : ''));
        }
        if ($value < 1000) {
            $hundreds = intdiv($value, 100);
            $rest = $value % 100;
            return trim($spellNumber($hundreds) . ' Ratus' . ($rest > 0 ? ' ' . $spellNumber($rest) : ''));
        }
        if ($value < 2000) {
            return trim('Seribu' . ($value > 1000 ? ' ' . $spellNumber($value - 1000) : ''));
        }
        if ($value < 1000000) {
            $thousands = intdiv($value, 1000);
            $rest = $value % 1000;
            return trim($spellNumber($thousands) . ' Ribu' . ($rest > 0 ? ' ' . $spellNumber($rest) : ''));
        }

        return (string) $value;
    };

    $monthNames = [
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

    $contractSigningDay = trim((string) ($letter['contract_sign_day'] ?? $letter['hari_penandatanganan'] ?? ''));
    $contractSigningDateRaw = trim((string) ($letter['contract_sign_date'] ?? $letter['tanggal_penandatanganan'] ?? ''));
    $contractSigningDate = $contractSigningDateRaw !== '' ? strtotime($contractSigningDateRaw) : false;
    $contractSigningDateText = $contractSigningDate !== false ? $spellNumber((int) date('j', $contractSigningDate)) : '-';
    $contractSigningMonthText = $contractSigningDate !== false ? ($monthNames[(int) date('n', $contractSigningDate)] ?? date('F', $contractSigningDate)) : '-';
    $contractSigningYearText = $contractSigningDate !== false ? $spellNumber((int) date('Y', $contractSigningDate)) : '-';

    $kepalaLppmNama = trim((string) $chairmanName) !== '' ? (string) $chairmanName : 'Kepala LPPM';
    $ketuaPenelitiNama = trim((string) ($letter['researcher_name'] ?? '')) !== '' ? (string) $letter['researcher_name'] : '-';
    $ketuaPenelitiIdentifier = trim((string) ($letter['applicant_nuptk'] ?? $letter['applicant_nidn'] ?? '-'));
    if ($ketuaPenelitiIdentifier === '') {
        $ketuaPenelitiIdentifier = '-';
    }
    $rektorNama = 'Dr. Bertolomeus Bolong, M.Si.';
    $rektorNuptk = '6863743644130052';
    $nomorKontrakDikti = trim((string) ($letter['contract_number_dikti'] ?? $letter['nomor_kontrak_dikti'] ?? $letter['letter_number'] ?? '-'));
    if ($nomorKontrakDikti === '') {
        $nomorKontrakDikti = '-';
    }
    $pihakKesatuText = 'Kepala Lembaga Penelitian dan Pengabdian Kepada Masyarakat (LPPM) yang berkedudukan di Jalan Ir. Soekarno, Kelurahan Fontein, Kecamatan Kota Raja, Kota Kupang - NTT, dalam hal ini bertindak untuk dan atas nama Universitas San Pedro untuk selanjutnya disebut PIHAK KESATU;';
    $pihakKeduaText = $isPengabdian
        ? 'Dosen Tetap Universitas San Pedro, dalam hal ini bertindak sebagai Ketua pengusul dan Ketua Pelaksana program pengabdian sebagaimana tersebut dalam Lampiran untuk selanjutnya disebut PIHAK KEDUA;'
        : 'Dosen Tetap Universitas San Pedro, dalam hal ini bertindak sebagai Ketua pengusul dan Ketua Peneliti sebagaimana tersebut dalam Lampiran untuk selanjutnya disebut PIHAK KEDUA;';
    $formatPihakText = static function (string $text): string {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $escaped = str_replace('PIHAK KESATU', '<strong>PIHAK KESATU</strong>', $escaped);
        $escaped = str_replace('PIHAK KEDUA', '<strong>PIHAK KEDUA</strong>', $escaped);

        return $escaped;
    };
    ?>

    <div class="contract-opening">
        <p class="line-1"><?= htmlspecialchars($judulKontrak, ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="line-2">TAHUN ANGGARAN <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="line-3">NOMOR: <?= htmlspecialchars((string) ($letter['letter_number'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="line-gap"></div>
        <p class="line-party">ANTARA</p>
        <p class="line-party"><?= htmlspecialchars($pihakPertama, ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="line-party">DENGAN</p>
        <p class="line-party"><?= htmlspecialchars($pihakKedua, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <p>Pada hari ini <?= htmlspecialchars($contractSigningDay !== '' ? $contractSigningDay : '-', ENT_QUOTES, 'UTF-8'); ?> tanggal <?= htmlspecialchars($contractSigningDateText, ENT_QUOTES, 'UTF-8'); ?> bulan <?= htmlspecialchars($contractSigningMonthText, ENT_QUOTES, 'UTF-8'); ?> tahun <?= htmlspecialchars($contractSigningYearText, ENT_QUOTES, 'UTF-8'); ?>, kami yang bertandatangan di bawah ini:</p>

    <table class="party-table">
        <tr>
            <td class="party-name"><?= htmlspecialchars($kepalaLppmNama, ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="party-colon">:</td>
            <td class="party-desc"><?= $formatPihakText($pihakKesatuText); ?></td>
        </tr>
        <tr><td colspan="3" style="height:14px;"></td></tr>
        <tr>
            <td class="party-name"><?= htmlspecialchars($ketuaPenelitiNama, ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="party-colon">:</td>
            <td class="party-desc"><?= $formatPihakText($pihakKeduaText); ?></td>
        </tr>
    </table>

    <p><strong>PIHAK KESATU</strong> dan <strong>PIHAK KEDUA</strong> secara bersama-sama selanjutnya disebut <strong>PARA PIHAK</strong>.</p>
    <p><strong>PARA PIHAK</strong> sepakat mengikatkan diri dalam <?= htmlspecialchars($judulKontrakInline, ENT_QUOTES, 'UTF-8'); ?> Tahun Anggaran <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?> yang selanjutnya disebut <?= htmlspecialchars($kontrakNama, ENT_QUOTES, 'UTF-8'); ?>, dengan ketentuan dan syarat sebagai berikut:</p>

    <div class="pasal">Pasal 1</div>
    <div class="pasal-title">RUANG LINGKUP</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Ruang lingkup <?= htmlspecialchars($kontrakNama, ENT_QUOTES, 'UTF-8'); ?> ini meliputi <?= htmlspecialchars($frasaPelaksanaan, ENT_QUOTES, 'UTF-8'); ?> tahun anggaran <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?> dengan <?= htmlspecialchars($frasaJudul, ENT_QUOTES, 'UTF-8'); ?> "<?= htmlspecialchars((string) ($letter['research_title'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>".
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Daftar <?= htmlspecialchars($frasaJudul, ENT_QUOTES, 'UTF-8'); ?> sebagaimana dimaksud pada ayat (1) beserta nama pelaksana <?= htmlspecialchars($labelKegiatanLower, ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($frasaSkema, ENT_QUOTES, 'UTF-8'); ?>, luaran tambahan, jangka waktu <?= htmlspecialchars($labelKegiatanLower, ENT_QUOTES, 'UTF-8'); ?>, dan besarnya biaya masing-masing <?= htmlspecialchars($frasaJudul, ENT_QUOTES, 'UTF-8'); ?> sebagaimana tercantum dalam Lampiran yang merupakan bagian tidak terpisahkan dari <?= htmlspecialchars($kontrakNama, ENT_QUOTES, 'UTF-8'); ?> ini.
        </div>
    </div>

    <div class="pasal">Pasal 2</div>
    <div class="pasal-title">SUMBER DANA</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            <strong>PIHAK KESATU</strong> memberikan pendanaan Kontrak <?= htmlspecialchars($labelKegiatan, ENT_QUOTES, 'UTF-8'); ?> kepada <strong>PIHAK KEDUA</strong> yang bersumber pada DIPA Direktorat Riset, Teknologi, dan Pengabdian Kepada Masyarakat, Direktorat Jenderal Pendidikan Tinggi, Riset, dan Teknologi Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi Tahun Anggaran <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?>, Nomor <?= htmlspecialchars($nomorKontrakDikti, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>

    <div class="pasal-block">
        <div class="pasal">Pasal 3</div>
        <div class="pasal-title">NILAI KONTRAK</div>

        <div class="ayat-block">
            <div class="ayat-number">(1)</div>
            <div class="ayat-content">
                <strong>PIHAK KESATU</strong> memberikan pendanaan Kontrak <?= htmlspecialchars($labelKegiatan, ENT_QUOTES, 'UTF-8'); ?> kepada <strong>PIHAK KEDUA</strong> dengan nilai kontrak sebesar <?= htmlspecialchars((string) $nilaiKontrakRupiah, ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars((string) $nilaiKontrakTerbilangUcfirst, ENT_QUOTES, 'UTF-8'); ?>) yang di dalam nilai kontrak tersebut sudah termasuk seluruh biaya pajak sesuai peraturan perundang-undangan.
            </div>
        </div>

        <div class="ayat-block">
            <div class="ayat-number">(2)</div>
            <div class="ayat-content">
                Pendanaan pelaksanaan program <?= htmlspecialchars(strtolower($labelKegiatan), ENT_QUOTES, 'UTF-8'); ?> dengan nilai kontrak sebagaimana dimaksud pada ayat (1) dibayarkan secara tunai kepada <strong>PIHAK KEDUA</strong> oleh Bendahara Universitas dengan rincian sebagai berikut:
                <table class="rincian-table">
                    <tr>
                        <td class="rincian-label">Nama <?= htmlspecialchars($isPengabdian ? 'Ketua Pelaksana' : 'Ketua Peneliti', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="rincian-colon">:</td>
                        <td><?= htmlspecialchars($ketuaPenelitiNama, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td class="rincian-label">Jenis Pencairan Dana</td>
                        <td class="rincian-colon">:</td>
                        <td>Tunai</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="ayat-block">
            <div class="ayat-number">(3)</div>
            <div class="ayat-content">
                <strong>PIHAK KESATU</strong> tidak bertanggungjawab atas keterlambatan dan/atau tidak terbayarnya sejumlah dana, yang disebabkan oleh kesalahan <strong>PIHAK KEDUA</strong> dalam menyampaikan informasi identitas <?= htmlspecialchars($isPengabdian ? 'Ketua Pelaksana' : 'Ketua Peneliti', ENT_QUOTES, 'UTF-8'); ?> atau penerima sebagaimana dimaksud pada ayat (1).
            </div>
        </div>
    </div>

    <div class="pasal-head">
        <div class="pasal">Pasal 4</div>
        <div class="pasal-title">NILAI DAN TAHAPAN PEMBAYARAN</div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Dana pelaksanaan <?= htmlspecialchars(strtolower($labelKegiatan), ENT_QUOTES, 'UTF-8'); ?> sebagaimana nilai kontrak yang dimaksud dalam Pasal 3 ayat (1) dibayarkan oleh <strong>PIHAK KESATU</strong> kepada <strong>PIHAK KEDUA</strong> secara bertahap dengan ketentuan sebagai berikut:
            <div class="huruf-list">
            <table class="huruf-item">
                <tr>
                    <td class="huruf-marker">a.</td>
                    <td class="huruf-content">Pembayaran tahap pertama sebesar <?= htmlspecialchars((string) ($contractStageCalc['stage1_percent'] ?? 80), ENT_QUOTES, 'UTF-8'); ?>% dari nilai kontrak sebesar <?= htmlspecialchars($danaTahap1Rupiah, ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($danaTahap1TerbilangUcfirst, ENT_QUOTES, 'UTF-8'); ?>) setelah <strong>PIHAK KEDUA</strong> menandatangani kontrak serta mengunggah revisi proposal dan surat pernyataan kesanggupan pelaksanaan kegiatan pada laman BIMA;</td>
                </tr>
            </table>
            <table class="huruf-item">
                <tr>
                    <td class="huruf-marker">b.</td>
                    <td class="huruf-content">Pembayaran tahap kedua sebesar <?= htmlspecialchars((string) ($contractStageCalc['stage2_percent'] ?? 20), ENT_QUOTES, 'UTF-8'); ?>% dari nilai kontrak sebesar <?= htmlspecialchars($danaTahap2Rupiah, ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($danaTahap2TerbilangUcfirst, ENT_QUOTES, 'UTF-8'); ?>) dibayarkan setelah <strong>PIHAK KEDUA</strong> mengunggah: 1) Surat Pernyataan Tanggung Jawab Belanja (SPTB); dan 2) Laporan kemajuan pelaksanaan penelitian melalui laman BIMA;</td>
                </tr>
            </table>
            <table class="huruf-item">
                <tr>
                    <td class="huruf-marker">c.</td>
                    <td class="huruf-content">Dalam hal pencairan dana tahap pertama dilakukan melewati batas waktu yang ditentukan, maka <strong>PIHAK KEDUA</strong> wajib mengunggah laporan kemajuan pelaksanaan penelitian dan SPTB paling lambat 2 (dua) minggu setelah dana diterima.</td>
                </tr>
            </table>
            <table class="huruf-item">
                <tr>
                    <td class="huruf-marker">d.</td>
                    <td class="huruf-content">Dalam hal pencairan dana tahap kedua dilakukan melewati batas waktu yang ditentukan, maka <strong>PIHAK KEDUA</strong> wajib mengunggah Laporan akhir pelaksanaan penelitian dan SPTB paling lambat 2 (dua) minggu setelah dana diterima.</td>
                </tr>
            </table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Keberlanjutan pendanaan <?= htmlspecialchars($labelKegiatanLower, ENT_QUOTES, 'UTF-8'); ?> lanjutan untuk tahun anggaran berikutnya diberikan berdasarkan hasil penilaian atas capaian <?= htmlspecialchars($labelKegiatanLower, ENT_QUOTES, 'UTF-8'); ?> tahun sebelumnya yang dilakukan oleh Komite Penilaian Keluaran <?= htmlspecialchars($labelKegiatan, ENT_QUOTES, 'UTF-8'); ?> dan/atau Reviewer Keluaran <?= htmlspecialchars($labelKegiatan, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> wajib menyampaikan surat pernyataan telah menyelesaikan seluruh kegiatan penelitian, yang dibuktikan dengan pengunggahan pada laman yang ditentukan oleh <strong>PIHAK KESATU</strong> dalam hal ini pihak pemberi dana sesuai batas waktu yang telah ditentukan, dengan melampirkan dokumen sebagai berikut:
            <div class="huruf-list">
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">a.</td>
                        <td class="huruf-content">SPTB; dan</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">b.</td>
                        <td class="huruf-content"><?= htmlspecialchars($frasaLaporanAkhirPelaksanaan, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="pasal">Pasal 5</div>
    <div class="pasal-title">JANGKA WAKTU PENYELESAIAN</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Jangka waktu <?= htmlspecialchars($frasaPelaksanaan, ENT_QUOTES, 'UTF-8'); ?> dimulai sejak tanggal <?= htmlspecialchars($contractTanggalMulaiDisplay, ENT_QUOTES, 'UTF-8'); ?> sampai dengan <?= htmlspecialchars($contractTanggalSelesaiDisplay, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>

    <div class="pasal">Pasal 6</div>
    <div class="pasal-title">HAK DAN KEWAJIBAN</div>

    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            <strong>PIHAK KESATU</strong> mempunyai kewajiban:
            <div class="huruf-list">
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">a.</td>
                        <td class="huruf-content">Memberikan pendanaan <?= htmlspecialchars($labelKegiatanLower, ENT_QUOTES, 'UTF-8'); ?> kepada PIHAK KEDUA;</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">b.</td>
                        <td class="huruf-content">Melakukan pemantauan dan evaluasi;</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">c.</td>
                        <td class="huruf-content">Melakukan penilaian <?= htmlspecialchars($frasaLuaran, ENT_QUOTES, 'UTF-8'); ?>; dan</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">d.</td>
                        <td class="huruf-content">Melakukan validasi luaran tambahan.</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> mempunyai kewajiban:
            <div class="huruf-list">
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">a.</td>
                        <td class="huruf-content">Menindaklanjuti dan mengupayakan <?= htmlspecialchars($frasaPelaksanaan, ENT_QUOTES, 'UTF-8'); ?> yang dilakukan sesuai dengan ajuan dan pemenuhan target <?= htmlspecialchars($frasaLuaran, ENT_QUOTES, 'UTF-8'); ?>.</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">b.</td>
                        <td class="huruf-content">
                            Mengunggah ke laman yang ditentukan oleh pihak Pemberi Dana dalam hal ini melalui BIMA atas dokumen sebagai berikut:
                            <div class="angka-list">
                                <table class="angka-item">
                                    <tr>
                                        <td class="angka-marker">1)</td>
                                        <td class="angka-content">Revisi <?= htmlspecialchars($frasaProposal, ENT_QUOTES, 'UTF-8'); ?>;</td>
                                    </tr>
                                </table>
                                <table class="angka-item">
                                    <tr>
                                        <td class="angka-marker">2)</td>
                                        <td class="angka-content">Surat pernyataan kesanggupan <?= htmlspecialchars($frasaKesanggupan, ENT_QUOTES, 'UTF-8'); ?>;</td>
                                    </tr>
                                </table>
                                <table class="angka-item">
                                    <tr>
                                        <td class="angka-marker">3)</td>
                                        <td class="angka-content"><?= htmlspecialchars(ucfirst($frasaCatatanHarian), ENT_QUOTES, 'UTF-8'); ?>;</td>
                                    </tr>
                                </table>
                                <table class="angka-item">
                                    <tr>
                                        <td class="angka-marker">4)</td>
                                        <td class="angka-content"><?= htmlspecialchars(ucfirst($frasaLaporanKemajuan), ENT_QUOTES, 'UTF-8'); ?>;</td>
                                    </tr>
                                </table>
                                <table class="angka-item">
                                    <tr>
                                        <td class="angka-marker">5)</td>
                                        <td class="angka-content">SPTB atas <?= htmlspecialchars($frasaDana, ENT_QUOTES, 'UTF-8'); ?> yang telah ditetapkan;</td>
                                    </tr>
                                </table>
                                <table class="angka-item">
                                    <tr>
                                        <td class="angka-marker">6)</td>
                                        <td class="angka-content"><?= htmlspecialchars(ucfirst($frasaLaporanAkhir), ENT_QUOTES, 'UTF-8'); ?> (dilaporkan pada tahun terakhir <?= htmlspecialchars($frasaPelaksanaan, ENT_QUOTES, 'UTF-8'); ?>); dan</td>
                                    </tr>
                                </table>
                                <table class="angka-item">
                                    <tr>
                                        <td class="angka-marker">7)</td>
                                        <td class="angka-content"><?= htmlspecialchars(ucfirst($frasaLuaran), ENT_QUOTES, 'UTF-8'); ?>.</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">c.</td>
                        <td class="huruf-content">Mengembalikan sisa dana ke kas negara setelah berkoordinasi dengan <strong>PIHAK KESATU</strong>, apabila dalam <?= htmlspecialchars($frasaPelaksanaan, ENT_QUOTES, 'UTF-8'); ?> terdapat sisa dana.</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            <strong>PIHAK KESATU</strong> mempunyai hak mengakses dokumen hasil unggahan di laman yang ditentukan oleh pihak pemberi dana dalam hal ini pada aplikasi BIMA sebagai berikut:
            <div class="huruf-list">
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">a.</td>
                        <td class="huruf-content">Revisi <?= htmlspecialchars($frasaProposal, ENT_QUOTES, 'UTF-8'); ?>;</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">b.</td>
                        <td class="huruf-content">Surat pernyataan kesanggupan <?= htmlspecialchars($frasaKesanggupan, ENT_QUOTES, 'UTF-8'); ?>;</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">c.</td>
                        <td class="huruf-content"><?= htmlspecialchars(ucfirst($frasaCatatanHarian), ENT_QUOTES, 'UTF-8'); ?>;</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">d.</td>
                        <td class="huruf-content"><?= htmlspecialchars(ucfirst($frasaLaporanKemajuan), ENT_QUOTES, 'UTF-8'); ?>;</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">e.</td>
                        <td class="huruf-content">SPTB atas <?= htmlspecialchars($frasaDana, ENT_QUOTES, 'UTF-8'); ?> yang telah ditetapkan;</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">f.</td>
                        <td class="huruf-content"><?= htmlspecialchars(ucfirst($frasaLaporanAkhir), ENT_QUOTES, 'UTF-8'); ?>; dan</td>
                    </tr>
                </table>
                <table class="huruf-item">
                    <tr>
                        <td class="huruf-marker">g.</td>
                        <td class="huruf-content"><?= htmlspecialchars(ucfirst($frasaLuaran), ENT_QUOTES, 'UTF-8'); ?>.</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(4)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> mempunyai hak mendapatkan <?= htmlspecialchars($frasaDana, ENT_QUOTES, 'UTF-8'); ?> dari pihak pemberi dana melalui <strong>PIHAK KESATU</strong>.
        </div>
    </div>

    <div class="pasal">Pasal 7</div>
    <div class="pasal-title">PENGGANTIAN KEANGGOTAAN</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Perubahan terhadap susunan <?= htmlspecialchars($frasaTimPelaksana, ENT_QUOTES, 'UTF-8'); ?> dapat dibenarkan apabila telah mendapat persetujuan dari Direktorat Riset, Teknologi, dan Pengabdian Kepada Masyarakat, Direktorat Jenderal Pendidikan Tinggi, Riset, dan Teknologi.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Apabila ketua <?= htmlspecialchars($frasaTimPelaksana, ENT_QUOTES, 'UTF-8'); ?> tidak dapat menyelesaikan <?= htmlspecialchars($labelKegiatanLower, ENT_QUOTES, 'UTF-8'); ?> atau mengundurkan diri, maka <strong>PIHAK KEDUA</strong> wajib menunjuk pengganti ketua <?= htmlspecialchars($frasaTimPelaksana, ENT_QUOTES, 'UTF-8'); ?> yang merupakan salah satu anggota tim setelah mendapat persetujuan dari Direktorat Riset, Teknologi, dan Pengabdian Kepada Masyarakat, Direktorat Jenderal Pendidikan Tinggi, Riset, dan Teknologi.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            Dalam hal tidak terdapat pengganti ketua <?= htmlspecialchars($frasaTimPelaksana, ENT_QUOTES, 'UTF-8'); ?> sesuai dengan syarat dan ketentuan dalam <?= htmlspecialchars($frasaPanduan, ENT_QUOTES, 'UTF-8'); ?>, maka <?= htmlspecialchars($labelKegiatanLower, ENT_QUOTES, 'UTF-8'); ?> dibatalkan dan dana dikembalikan ke Kas Negara.
        </div>
    </div>

    <div class="pasal">Pasal 8</div>
    <div class="pasal-title">PAJAK</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Ketentuan pengenaan pajak pertambahan nilai dan/atau pajak penghasilan dalam rangka pelaksanaan <?= htmlspecialchars($frasaKegiatanIni, ENT_QUOTES, 'UTF-8'); ?> wajib dilaksanakan oleh <strong>PIHAK KEDUA</strong> sesuai dengan ketentuan peraturan perundang-undangan di bidang perpajakan.
        </div>
    </div>

    <div class="pasal">Pasal 9</div>
    <div class="pasal-title">KEKAYAAN INTELEKTUAL</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Hak Kekayaan Intelektual yang dihasilkan dari <?= htmlspecialchars($frasaPelaksanaan, ENT_QUOTES, 'UTF-8'); ?> diatur dan dikelola sesuai dengan ketentuan peraturan dan perundang-undangan.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Setiap publikasi, makalah, dan/atau ekspos dalam bentuk apapun yang berkaitan dengan <?= htmlspecialchars($frasaHasil, ENT_QUOTES, 'UTF-8'); ?> wajib mencantumkan Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi sebagai pemberi dana.
        </div>
    </div>
    <div class="ayat-inline-block">
        <table>
            <tr>
                <td class="ayat-inline-number">(3)</td>
                <td class="ayat-inline-content">Pencantuman nama pemberi dana sebagaimana dimaksud pada ayat (2), paling sedikit mencantumkan nama Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi.</td>
            </tr>
        </table>
    </div>

    <div class="pasal">Pasal 10</div>
    <div class="pasal-title">INTEGRITAS AKADEMIK</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> wajib menjunjung tinggi integritas akademik yaitu komitmen dalam bentuk perbuatan yang berdasarkan pada nilai kejujuran, kredibilitas, kewajaran, kehormatan, dan tanggung jawab dalam <?= htmlspecialchars($frasaIntegritas, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            <?= htmlspecialchars($frasaEtika, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            <?= htmlspecialchars($frasaStandar, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>

    <div class="pasal">Pasal 11</div>
    <div class="pasal-title">KEADAAN KAHAR</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Dalam hal terjadi keadaan kahar (<em>force majeure</em>) yang berada di luar kemampuan <strong>PARA PIHAK</strong> sehingga kewajiban dalam kontrak tidak dapat dipenuhi, maka <strong>PARA PIHAK</strong> sepakat untuk tidak saling menuntut.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Keadaan kahar meliputi bencana alam, wabah penyakit, kebakaran, perang, kerusuhan, serta kebijakan pemerintah yang secara langsung mempengaruhi pelaksanaan kegiatan.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            Pihak yang mengalami keadaan kahar wajib memberitahukan secara tertulis kepada pihak lainnya paling lambat 7 (tujuh) hari kerja sejak terjadinya keadaan tersebut.
        </div>
    </div>

    <div class="pasal">Pasal 12</div>
    <div class="pasal-title">PENYELESAIAN PERSELISIHAN</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Dalam hal terjadi perselisihan atau perbedaan penafsiran terkait <?= htmlspecialchars($frasaKontrakIni, ENT_QUOTES, 'UTF-8'); ?>, <strong>PARA PIHAK</strong> sepakat untuk menyelesaikannya secara musyawarah dan mufakat.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Dalam hal musyawarah dan mufakat sebagaimana dimaksud pada ayat (2) tidak tercapai, <strong>PARA PIHAK</strong> sepakat untuk menyelesaikannya melalui Pengadilan Negeri Kupang.
        </div>
    </div>

    <div class="pasal">Pasal 13</div>
    <div class="pasal-title">AMANDEMEN KONTRAK</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Apabila terdapat hal lain yang belum diatur atau terjadi perubahan dalam <?= htmlspecialchars($frasaKontrakIni, ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($frasaAmandemen, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>

    <div class="pasal-intro-keep">
        <div class="pasal">Pasal 14</div>
        <div class="pasal-title">SANKSI</div>
        <div class="ayat-block">
            <div class="ayat-number">(1)</div>
            <div class="ayat-content">
                Apabila sampai dengan batas waktu yang telah ditetapkan untuk melaksanakan <?= htmlspecialchars($frasaKontrakIni, ENT_QUOTES, 'UTF-8'); ?> telah berakhir, <strong>PIHAK KEDUA</strong> tidak melaksanakan kewajiban sebagaimana dimaksud dalam Pasal 6 ayat (2), maka <strong>PIHAK KEDUA</strong> dikenai sanksi administratif.
            </div>
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Apabila kemudian hari terbukti bahwa judul-judul proposal yang diajukan pada <?= htmlspecialchars($frasaProgramDiajukan, ENT_QUOTES, 'UTF-8'); ?> sebagaimana dimaksud dalam Pasal 1 ditemukan adanya duplikasi dan/atau ditemukan adanya ketidakjujuran/itikad buruk yang tidak sesuai dengan kaidah ilmiah, maka <?= htmlspecialchars($frasaKegiatanIni, ENT_QUOTES, 'UTF-8'); ?> dinyatakan batal dan <strong>PIHAK KEDUA</strong> dikenai sanksi administratif.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            Sanksi administratif sebagaimana dimaksud pada ayat (1) dan (2) dapat berupa penghentian pembayaran dan/atau <strong>PIHAK KEDUA</strong> tidak dapat mengajukan <?= htmlspecialchars($frasaProposal, ENT_QUOTES, 'UTF-8'); ?> dalam kurun waktu 2 (dua) tahun berturut-turut.
        </div>
    </div>

    <div class="pasal">Pasal 15</div>
    <div class="pasal-title">LAIN-LAIN</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Dalam hal <strong>PIHAK KEDUA</strong> berhenti dari jabatannya sebelum <?= htmlspecialchars($frasaKontrakIni, ENT_QUOTES, 'UTF-8'); ?> selesai, maka <strong>PIHAK KEDUA</strong> wajib melakukan serah terima tanggung jawabnya kepada pejabat baru yang menggantikannya.
        </div>
    </div>

    <div class="pasal">Pasal 16</div>
    <div class="pasal-title">PENUTUP</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            <?= htmlspecialchars($frasaKontrakIni, ENT_QUOTES, 'UTF-8'); ?> dibuat dan ditandatangani oleh <strong>PARA PIHAK</strong> dalam rangkap 2 (dua) asli bermeterai cukup yang biayanya dibebankan kepada <strong>PIHAK KEDUA</strong>, untuk tiap-tiap <strong>PIHAK</strong> dan memiliki kekuatan hukum yang sama.
        </div>
    </div>

    <div class="sig-contract">
        <table class="sig-contract-top">
            <tr>
                <td>
                    <div class="sig-role">PIHAK KESATU,</div>
                    <div class="sig-gap"></div>
                    <div class="sig-name"><?= htmlspecialchars($kepalaLppmNama, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="sig-meta">NUPTK. <?= htmlspecialchars((string) $chairmanIdentifier, ENT_QUOTES, 'UTF-8'); ?></div>
                </td>
                <td>
                    <div class="sig-role">PIHAK KEDUA,</div>
                    <div class="sig-gap"></div>
                    <div class="sig-name"><?= htmlspecialchars($ketuaPenelitiNama, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="sig-meta">NUPTK. <?= htmlspecialchars($ketuaPenelitiIdentifier, ENT_QUOTES, 'UTF-8'); ?></div>
                </td>
            </tr>
        </table>

        <table class="sig-contract-bottom">
            <tr>
                <td>
                    <div class="sig-role">MENGETAHUI,</div>
                    <div class="sig-role">REKTOR,</div>
                    <div class="sig-gap"></div>
                    <div class="sig-name"><?= htmlspecialchars($rektorNama, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="sig-meta">NUPTK. <?= htmlspecialchars($rektorNuptk, ENT_QUOTES, 'UTF-8'); ?></div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
