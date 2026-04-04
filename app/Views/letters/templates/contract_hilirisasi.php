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
        .huruf-row { margin: 0 0 2px 0; page-break-inside: auto; break-inside: auto; }
        .huruf-row .huruf-marker-inline { display: inline-block; width: 16px; vertical-align: top; }
        .huruf-row .huruf-content-inline { display: inline-block; width: calc(100% - 24px); vertical-align: top; text-align: justify; }
        .angka-list { margin: 0; padding: 0; }
        .angka-item { width: 100%; border-collapse: collapse; margin: 0; }
        .angka-item td { padding: 0; vertical-align: top; }
        .angka-marker { width: 24px; }
        .angka-content { text-align: justify; padding-left: 4px; }
        .angka-row { margin: 0; page-break-inside: auto; break-inside: auto; }
        .angka-row .angka-marker-inline { display: inline-block; width: 24px; vertical-align: top; }
        .angka-row .angka-content-inline { display: inline-block; width: calc(100% - 30px); vertical-align: top; text-align: justify; }
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
        .contract-opening .line-1 { font-size: 12pt; font-weight: 700; text-transform: uppercase; }
        .contract-opening .line-2 { font-size: 12pt; font-weight: 700; text-transform: uppercase; }
        .contract-opening .line-3 { font-size: 12pt; font-weight: 700; text-transform: uppercase; margin-top: 2px; }
        .contract-opening .line-gap { height: 22px; }
        .contract-opening .line-party { font-size: 12pt; text-transform: uppercase; line-height: 1.28; }
        .party-table { width: 100%; border-collapse: collapse; margin: 12px 0 14px; }
        .party-table td { vertical-align: top; padding: 0; }
        .party-name { width: 40%; padding-right: 10px; }
        .party-colon { width: 12px; text-align: center; }
        .party-desc { text-align: justify; padding-left: 6px; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../../Helpers/terbilang_helper.php'; ?>
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
    $judulSurat = 'SURAT KONTRAK HILIRISASI';
    $judulKontrak = 'KONTRAK PELAKSANAAN PROGRAM HILIRISASI RISET PRIORITAS PENGUJIAN MODEL DAN PROTOTIPE';
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
    $toTitleCase = static function (string $text): string {
        $normalized = trim((string) preg_replace('/\s+/', ' ', strtolower($text)));
        if ($normalized === '') {
            return '';
        }

        return ucwords($normalized);
    };
    $contractSigningDateText = $contractSigningDate !== false ? $toTitleCase(terbilang((int) date('j', $contractSigningDate))) : '-';
    $contractSigningMonthText = $contractSigningDate !== false ? ($monthNames[(int) date('n', $contractSigningDate)] ?? date('F', $contractSigningDate)) : '-';
    $contractSigningYearText = $contractSigningDate !== false ? $toTitleCase(terbilang((int) date('Y', $contractSigningDate))) : '-';

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
    $pihakKeduaText = 'Dosen Tetap Universitas San Pedro, dalam hal ini bertindak sebagai Ketua Peneliti sekaligus pelaksana kegiatan sebagaimana tercantum dalam Lampiran, selanjutnya disebut PIHAK KEDUA;';
    $formatPihakText = static function (string $text): string {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $escaped = str_replace('PIHAK KESATU', '<strong>PIHAK KESATU</strong>', $escaped);
        $escaped = str_replace('PIHAK KEDUA', '<strong>PIHAK KEDUA</strong>', $escaped);

        return $escaped;
    };
    ?>

    <div class="contract-opening">
        <p class="line-1">KONTRAK PELAKSANAAN PROGRAM HILIRISASI RISET PRIORITAS PENGUJIAN MODEL DAN PROTOTIPE</p>
        <p class="line-2">TAHUN ANGGARAN <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="line-3">NOMOR: <?= htmlspecialchars((string) ($letter['letter_number'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="line-gap"></div>
        <p class="line-party">ANTARA</p>
        <p class="line-party">KEPALA LEMBAGA PENELITIAN DAN PENGABDIAN KEPADA MASYARAKAT UNIVERSITAS SAN PEDRO</p>
        <p class="line-party">DENGAN</p>
        <p class="line-party">KETUA PENELITI</p>
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
    <p><strong>PARA PIHAK</strong> sepakat mengikatkan diri dalam Kontrak Pelaksanaan Program Hilirisasi Riset Prioritas Pengujian Model dan Prototipe Tahun Anggaran <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?> yang selanjutnya disebut Kontrak Program Hilirisasi Riset Prioritas, dengan ketentuan dan syarat sebagai berikut:</p>

    <div class="pasal">Pasal 1</div>
    <div class="pasal-title">RUANG LINGKUP</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Ruang lingkup Kontrak Program Hilirisasi Riset Prioritas ini meliputi pelaksanaan kegiatan pengujian model dan prototipe tahun anggaran <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?> dengan judul "<?= htmlspecialchars((string) ($letter['research_title'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>".
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Daftar judul kegiatan sebagaimana dimaksud pada ayat (1) beserta nama ketua peneliti, skema, target luaran, jangka waktu pelaksanaan, dan besarnya biaya sebagaimana tercantum dalam Lampiran yang merupakan bagian tidak terpisahkan dari Kontrak Program Hilirisasi Riset Prioritas ini.
        </div>
    </div>

    <div class="pasal">Pasal 2</div>
    <div class="pasal-title">SUMBER DANA</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            <strong>PIHAK KESATU</strong> memberikan pendanaan kepada <strong>PIHAK KEDUA</strong> dalam rangka pelaksanaan Program Hilirisasi Riset Prioritas Pengujian Model dan Prototipe Tahun Anggaran <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Pendanaan sebagaimana dimaksud pada ayat (1) bersumber dari DIPA Direktorat Riset, Teknologi, dan Pengabdian Kepada Masyarakat, Direktorat Jenderal Pendidikan Tinggi, Riset, dan Teknologi Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi berdasarkan nomor kontrak <?= htmlspecialchars($nomorKontrakDikti, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            Pendanaan sebagaimana dimaksud pada ayat (1) digunakan untuk mendukung kegiatan pengembangan dan pengujian model dan/atau prototipe dalam rangka peningkatan Tingkat Kesiapterapan Teknologi (TKT) sesuai dengan ketentuan Program Hilirisasi Riset Prioritas.
        </div>
    </div>

    <div class="pasal-block">
        <div class="pasal">Pasal 3</div>
        <div class="pasal-title">NILAI KONTRAK</div>

        <div class="ayat-block">
            <div class="ayat-number">(1)</div>
            <div class="ayat-content">
                <strong>PIHAK KESATU</strong> memberikan pendanaan kepada <strong>PIHAK KEDUA</strong> dengan nilai kontrak sebesar <?= htmlspecialchars((string) $nilaiKontrakRupiah, ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars((string) $nilaiKontrakTerbilangUcfirst, ENT_QUOTES, 'UTF-8'); ?>) yang sudah termasuk pajak sesuai dengan ketentuan peraturan perundang-undangan.
            </div>
        </div>

        <div class="ayat-block">
            <div class="ayat-number">(2)</div>
            <div class="ayat-content">
                Pendanaan sebagaimana dimaksud pada ayat (1) diberikan untuk pelaksanaan Program Hilirisasi Riset Prioritas Pengujian Model dan Prototipe dan digunakan sesuai dengan ketentuan penggunaan anggaran yang berlaku dalam program tersebut.
            </div>
        </div>

        <div class="ayat-block">
            <div class="ayat-number">(3)</div>
            <div class="ayat-content">
                Pembayaran dana sebagaimana dimaksud pada ayat (1) dilakukan kepada <strong>PIHAK KEDUA</strong> melalui secara tunai dengan rincian sebagai berikut:
                <table class="rincian-table">
                    <tr>
                        <td class="rincian-label">Nama Ketua Peneliti</td>
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
            <div class="ayat-number">(4)</div>
            <div class="ayat-content">
                <strong>PIHAK KESATU</strong> tidak bertanggung jawab atas keterlambatan dan/atau tidak terbayarnya dana yang disebabkan oleh kesalahan <strong>PIHAK KEDUA</strong> dalam menyampaikan data dan/atau informasi penerima dana.
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
            Dana pelaksanaan kegiatan sebagaimana dimaksud dalam Pasal 3 ayat (1) dibayarkan oleh <strong>PIHAK KESATU</strong> kepada <strong>PIHAK KEDUA</strong> secara bertahap dengan ketentuan sebagai berikut:
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
                    <td class="huruf-content">Pembayaran tahap kedua sebesar <?= htmlspecialchars((string) ($contractStageCalc['stage2_percent'] ?? 20), ENT_QUOTES, 'UTF-8'); ?>% dari nilai kontrak sebesar <?= htmlspecialchars($danaTahap2Rupiah, ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($danaTahap2TerbilangUcfirst, ENT_QUOTES, 'UTF-8'); ?>) dibayarkan setelah <strong>PIHAK KEDUA</strong> mengunggah: 1) Surat Pernyataan Tanggung Jawab Belanja (SPTB); dan 2) Laporan kemajuan pelaksanaan kegiatan melalui laman BIMA;</td>
                </tr>
            </table>
            <table class="huruf-item">
                <tr>
                    <td class="huruf-marker">c.</td>
                    <td class="huruf-content">Dalam hal pencairan tahap pertama dilakukan melewati batas waktu yang ditentukan, maka <strong>PIHAK KEDUA</strong> wajib mengunggah Laporan kemajuan pelaksanaan kegiatan dan SPTB paling lambat 2 (dua) minggu setelah dana diterima.</td>
                </tr>
            </table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Pembayaran tahap kedua sebagaimana dimaksud pada ayat (1) huruf b dilakukan setelah hasil monitoring dan evaluasi menunjukkan bahwa pelaksanaan kegiatan berjalan sesuai dengan target luaran dan peningkatan Tingkat Kesiapterapan Teknologi (TKT).
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> wajib menyampaikan laporan akhir kegiatan dan SPTB melalui laman BIMA sesuai dengan jadwal yang ditetapkan dalam program.
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(4)</div>
        <div class="ayat-content">
            Dalam hal keterlambatan pemenuhan kewajiban pelaporan sebagaimana dimaksud pada ayat (1) huruf b dan ayat (3), maka pembayaran tahap berikutnya dapat ditunda sesuai dengan ketentuan Program Hilirisasi Riset Prioritas.
        </div>
    </div>

    <div class="pasal">Pasal 5</div>
    <div class="pasal-title">JANGKA WAKTU PELAKSANAAN</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Jangka waktu pelaksanaan Program Hilirisasi Riset Prioritas dimulai sejak tanggal <?= htmlspecialchars($contractTanggalMulaiDisplay, ENT_QUOTES, 'UTF-8'); ?> sampai dengan <?= htmlspecialchars($contractTanggalSelesaiDisplay, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>

    <div class="pasal">Pasal 6</div>
    <div class="pasal-title">HAK DAN KEWAJIBAN</div>

    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            <strong>PIHAK KESATU</strong> mempunyai kewajiban:
            <div class="huruf-list">
                <table class="huruf-item"><tr><td class="huruf-marker">a.</td><td class="huruf-content">Memberikan pendanaan kepada <strong>PIHAK KEDUA</strong> sesuai dengan ketentuan Kontrak Program Hilirisasi Riset Prioritas;</td></tr></table>
                <table class="huruf-item"><tr><td class="huruf-marker">b.</td><td class="huruf-content">Melaksanakan pemantauan dan evaluasi (monev) terhadap pelaksanaan kegiatan;</td></tr></table>
                <table class="huruf-item"><tr><td class="huruf-marker">c.</td><td class="huruf-content">Melakukan penilaian terhadap capaian luaran dan peningkatan Tingkat Kesiapterapan Teknologi (TKT);</td></tr></table>
                <table class="huruf-item"><tr><td class="huruf-marker">d.</td><td class="huruf-content">Melakukan validasi terhadap luaran kegiatan sesuai ketentuan program.</td></tr></table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> mempunyai kewajiban:
            <div class="huruf-list">
                <table class="huruf-item"><tr><td class="huruf-marker">a.</td><td class="huruf-content">Melaksanakan kegiatan pengujian model dan/atau prototipe sesuai dengan proposal yang telah disetujui serta target peningkatan TKT;</td></tr></table>
                <div class="huruf-row">
                    <span class="huruf-marker-inline">b.</span>
                    <span class="huruf-content-inline">
                        Mengunggah dokumen pelaksanaan kegiatan melalui laman BIMA yang meliputi:
                        <div class="angka-list">
                            <div class="angka-row"><span class="angka-marker-inline">1)</span><span class="angka-content-inline">Revisi proposal;</span></div>
                            <div class="angka-row"><span class="angka-marker-inline">2)</span><span class="angka-content-inline">Surat pernyataan kesanggupan pelaksanaan kegiatan;</span></div>
                            <div class="angka-row"><span class="angka-marker-inline">3)</span><span class="angka-content-inline">Catatan harian (logbook) pelaksanaan kegiatan;</span></div>
                            <div class="angka-row"><span class="angka-marker-inline">4)</span><span class="angka-content-inline">Laporan kemajuan pelaksanaan kegiatan;</span></div>
                            <div class="angka-row"><span class="angka-marker-inline">5)</span><span class="angka-content-inline">Surat Pernyataan Tanggung Jawab Belanja (SPTB);</span></div>
                            <div class="angka-row"><span class="angka-marker-inline">6)</span><span class="angka-content-inline">Laporan akhir kegiatan;</span></div>
                            <div class="angka-row">
                                <span class="angka-marker-inline">7)</span>
                                <span class="angka-content-inline">
                                    Luaran wajib program berupa:
                                    <div class="huruf-list">
                                        <div class="huruf-row"><span class="huruf-marker-inline">a)</span><span class="huruf-content-inline">Bukti hasil pengujian prototipe dalam rangka peningkatan TKT;</span></div>
                                        <div class="huruf-row"><span class="huruf-marker-inline">b)</span><span class="huruf-content-inline">Dokumen desain (blueprint) hasil pengembangan;</span></div>
                                        <div class="huruf-row"><span class="huruf-marker-inline">c)</span><span class="huruf-content-inline">Poster prototipe;</span></div>
                                        <div class="huruf-row"><span class="huruf-marker-inline">d)</span><span class="huruf-content-inline">Video proses dan hasil pengembangan prototipe.</span></div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </span>
                </div>
                <table class="huruf-item"><tr><td class="huruf-marker">c.</td><td class="huruf-content">Mengikuti kegiatan monitoring dan evaluasi yang dilaksanakan oleh <strong>PIHAK KESATU</strong> dan/atau pemberi dana;</td></tr></table>
                <table class="huruf-item"><tr><td class="huruf-marker">d.</td><td class="huruf-content">Mengembalikan sisa dana ke kas negara sesuai dengan ketentuan apabila terdapat sisa dana.</td></tr></table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            <strong>PIHAK KESATU</strong> mempunyai hak:
            <div class="huruf-list">
                <table class="huruf-item"><tr><td class="huruf-marker">a.</td><td class="huruf-content">Mengakses seluruh dokumen yang diunggah oleh <strong>PIHAK KEDUA</strong> melalui laman BIMA;</td></tr></table>
                <table class="huruf-item"><tr><td class="huruf-marker">b.</td><td class="huruf-content">Melakukan evaluasi atas pelaksanaan kegiatan dan capaian luaran;</td></tr></table>
                <table class="huruf-item"><tr><td class="huruf-marker">c.</td><td class="huruf-content">Menunda atau menghentikan pendanaan apabila <strong>PIHAK KEDUA</strong> tidak memenuhi kewajiban sesuai ketentuan program.</td></tr></table>
            </div>
        </div>
    </div>

    <div class="ayat-block">
        <div class="ayat-number">(4)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> mempunyai hak:
            <div class="huruf-list">
                <table class="huruf-item"><tr><td class="huruf-marker">a.</td><td class="huruf-content">Menerima pendanaan sesuai dengan ketentuan Kontrak Program Hilirisasi Riset Prioritas;</td></tr></table>
                <table class="huruf-item"><tr><td class="huruf-marker">b.</td><td class="huruf-content">Mendapatkan pembinaan dan fasilitasi dalam pelaksanaan kegiatan sesuai ketentuan program.</td></tr></table>
            </div>
        </div>
    </div>

    <div class="pasal">Pasal 7</div>
    <div class="pasal-title">PENGGANTIAN KEANGGOTAAN</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Perubahan susunan tim pelaksana kegiatan dapat dilakukan setelah memperoleh persetujuan dari pemberi dana sesuai dengan ketentuan Program Hilirisasi Riset Prioritas.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Dalam hal Ketua Peneliti tidak dapat melaksanakan atau menyelesaikan kegiatan, maka <strong>PIHAK KEDUA</strong> wajib menunjuk pengganti dari anggota tim yang memenuhi persyaratan setelah mendapat persetujuan dari pemberi dana.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            Apabila tidak terdapat pengganti yang memenuhi ketentuan, maka kegiatan dinyatakan batal dan dana yang telah diterima wajib dikembalikan sesuai ketentuan yang berlaku.
        </div>
    </div>

    <div class="pasal">Pasal 8</div>
    <div class="pasal-title">PAJAK</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Ketentuan perpajakan dalam pelaksanaan kegiatan Program Hilirisasi Riset Prioritas ini dilaksanakan oleh <strong>PIHAK KEDUA</strong> sesuai dengan ketentuan peraturan perundang-undangan di bidang perpajakan.
        </div>
    </div>

    <div class="pasal">Pasal 9</div>
    <div class="pasal-title">KEKAYAAN INTELEKTUAL</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            Hak Kekayaan Intelektual yang dihasilkan dari pelaksanaan kegiatan diatur dan dikelola sesuai dengan ketentuan peraturan perundang-undangan.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Setiap publikasi, makalah, video, poster, dan/atau bentuk luaran lainnya wajib mencantumkan sumber pendanaan dari Direktorat Riset, Teknologi, dan Pengabdian Kepada Masyarakat, Direktorat Jenderal Pendidikan Tinggi, Riset, dan Teknologi Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi berdasarkan Tahun Anggaran <?= htmlspecialchars($tahunAnggaran, ENT_QUOTES, 'UTF-8'); ?>.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            Pencantuman sumber pendanaan sebagaimana dimaksud pada ayat (2) paling sedikit mencantumkan nama kementerian dan/atau lembaga pemberi dana.
        </div>
    </div>

    <div class="pasal">Pasal 10</div>
    <div class="pasal-title">INTEGRITAS AKADEMIK</div>
    <div class="ayat-block">
        <div class="ayat-number">(1)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> wajib menjunjung tinggi integritas akademik yang meliputi kejujuran, kredibilitas, dan tanggung jawab dalam pelaksanaan kegiatan.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Kegiatan dilaksanakan sesuai dengan kaidah etika, hukum, dan profesionalitas serta ketentuan peraturan perundang-undangan.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            <strong>PIHAK KEDUA</strong> wajib menjaga standar ketelitian dan integritas dalam seluruh proses pengembangan dan pengujian prototipe.
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
            Perselisihan yang timbul dalam pelaksanaan kontrak diselesaikan secara musyawarah dan mufakat.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Apabila tidak tercapai kesepakatan, maka penyelesaian dilakukan melalui Pengadilan Negeri Kupang.
        </div>
    </div>

    <div class="pasal">Pasal 13</div>
    <div class="pasal-title">AMANDEMEN KONTRAK</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Perubahan terhadap kontrak ini dapat dilakukan berdasarkan kesepakatan <strong>PARA PIHAK</strong> sesuai dengan ketentuan Program Hilirisasi Riset Prioritas.
        </div>
    </div>

    <div class="pasal-intro-keep">
        <div class="pasal">Pasal 14</div>
        <div class="pasal-title">SANKSI</div>
        <div class="ayat-block">
            <div class="ayat-number">(1)</div>
            <div class="ayat-content">
                <strong>PIHAK KEDUA</strong> yang tidak melaksanakan kewajiban sebagaimana diatur dalam kontrak ini dikenai sanksi administratif sesuai ketentuan program.
            </div>
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(2)</div>
        <div class="ayat-content">
            Dalam hal ditemukan pelanggaran berupa duplikasi usulan, ketidakjujuran, atau ketidaksesuaian dengan kaidah ilmiah, maka kegiatan dinyatakan batal.
        </div>
    </div>
    <div class="ayat-block">
        <div class="ayat-number">(3)</div>
        <div class="ayat-content">
            Sanksi sebagaimana dimaksud dapat berupa penghentian pendanaan, kewajiban pengembalian dana, dan/atau larangan mengajukan usulan pada program pendanaan dalam jangka waktu tertentu.
        </div>
    </div>

    <div class="pasal">Pasal 15</div>
    <div class="pasal-title">LAIN-LAIN</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Dalam hal <strong>PIHAK KEDUA</strong> tidak dapat melanjutkan kegiatan, maka wajib dilakukan serah terima tanggung jawab kepada pengganti yang disetujui sesuai ketentuan program.
        </div>
    </div>

    <div class="pasal">Pasal 16</div>
    <div class="pasal-title">PENUTUP</div>
    <div class="ayat-block-no-number">
        <div class="ayat-content">
            Kontrak Program Hilirisasi Riset Prioritas ini dibuat dan ditandatangani oleh <strong>PARA PIHAK</strong> dalam rangkap 2 (dua) asli bermeterai cukup, masing-masing mempunyai kekuatan hukum yang sama.
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
